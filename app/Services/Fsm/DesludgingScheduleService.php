<?php

namespace App\Services\Fsm;

use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Yajra\DataTables\DataTables;

class DesludgingScheduleService
{
    /**
     * Recalculate priority for eligible containments.
     *
     * Priority rules:
     *
     * P1:
     * - No valid last-emptying or construction date
     * - Effective date is more than three years old
     *
     * P2:
     * - Effective date is between one and three years old
     *
     * P3:
     * - Effective date is within the last year
     *
     * @return int Number of changed containment records
     */
    public function setPriority(): int
    {
        $result = DB::selectOne(<<<'SQL'
WITH calculated AS (
    SELECT id,
           CASE
               WHEN effective_date IS NULL THEN 1
               WHEN effective_date < CURRENT_DATE - INTERVAL '3 years' THEN 1
               WHEN effective_date < CURRENT_DATE - INTERVAL '1 year' THEN 2
               ELSE 3
           END AS new_priority
    FROM (
        SELECT id,
               CASE
                   WHEN last_emptied_date IS NOT NULL
                        AND last_emptied_date::date <= CURRENT_DATE
                       THEN last_emptied_date::date
                   WHEN construction_date IS NOT NULL
                        AND construction_date::date <= CURRENT_DATE
                       THEN construction_date::date
                   ELSE NULL
               END AS effective_date
        FROM fsm.containments
        WHERE deleted_at IS NULL
          AND (status IS NULL OR status IN (0, 4))
    ) eligible
),
updated AS (
    UPDATE fsm.containments AS containment
       SET priority = calculated.new_priority
      FROM calculated
     WHERE containment.id = calculated.id
       AND containment.priority IS DISTINCT FROM calculated.new_priority
    RETURNING containment.id
)
SELECT COUNT(*)::integer AS updated_count FROM updated
SQL
        );

        return (int) ($result->updated_count ?? 0);
    }

    /**
     * Recalculate priorities and allocate next-emptying dates without using
     * service-provider or ward mappings.
     */
    public function regenerate(?int $generatedBy = null): array
    {
        return DB::transaction(function () use ($generatedBy) {
            $lock = DB::selectOne(
                'SELECT pg_try_advisory_xact_lock(hashtext(?)) AS acquired',
                ['imis:desludging-date-generation']
            );

            if (!$this->databaseBoolean($lock->acquired ?? false)) {
                throw new ConflictHttpException(
                    __('Desludging date generation is already running. Please wait for it to finish.')
                );
            }

            $priorityUpdatedCount = $this->setPriority();
            $scheduledCount = $this->setEmptyingDate();
            $temporaryScheduleCount = $this->refreshTemporarySchedule($generatedBy);
            $serviceAreaAssignedCount =
                $this->assignUncoveredWardsToEmptyProviders();
            $providerAssignment =
                $this->assignGeneratedScheduleProvidersByBinWard();

            return [
                'priority_updated_count' => $priorityUpdatedCount,
                'scheduled_count' => $scheduledCount,
                'temporary_schedule_count' => $temporaryScheduleCount,
                'service_area_assigned_count' =>
                    $serviceAreaAssignedCount,
                'provider_assigned_count' =>
                    $providerAssignment['assigned_count'],
                'provider_unassigned_count' =>
                    $providerAssignment['unassigned_count'],
            ];
        }, 1);
    }

    /**
     * Allocate eligible containments to working days by priority, distance,
     * and remaining daily trip capacity.
     */
    public function setEmptyingDate(): int
    {
        $settings = $this->fetchSiteSettings()->keyBy('name');
        $dailyCapacity = (int) $this->settingValue(
            $settings,
            'Trip Capacity Per Day'
        );

        if ($dailyCapacity < 1) {
            throw new RuntimeException(
                __('Trip Capacity Per Day must be greater than zero.')
            );
        }

        $today = Carbon::today();
        $startDate = $this->scheduleStartDate($settings, $today);
        $weekends = $this->csvValues(
            $this->settingValue($settings, 'Weekend')
        )->map(function ($day) {
            return strtolower($day);
        })->all();
        $holidays = $this->csvValues(
            $this->settingValue($settings, 'Holiday Dates', false)
        )->all();

        $containmentIds = $this->getContainmentData();
        $total = $containmentIds->count();

        if ($total === 0) {
            return 0;
        }

        [$confirmedByDate, $automaticByDate] = $this->bookingCountsByDate();
        $updates = [];
        $offset = 0;
        $date = $startDate->copy();
        $daysInspected = 0;
        $maximumDays = max(
            3660,
            ((int) ceil($total / $dailyCapacity) * 14) + 366
        );

        while ($offset < $total) {
            if (++$daysInspected > $maximumDays) {
                throw new RuntimeException(
                    __('Unable to allocate the schedule within the safety horizon. Check capacity, weekend and holiday settings.')
                );
            }

            $dateValue = $date->format('Y-m-d');
            $isWeekend = in_array(
                strtolower($date->format('l')),
                $weekends,
                true
            );
            $isHoliday = in_array($dateValue, $holidays, true);

            if (!$isWeekend && !$isHoliday) {
                $booked = (int) ($confirmedByDate[$dateValue] ?? 0) +
                    (int) ($automaticByDate[$dateValue] ?? 0);
                $remainingTrips = max(0, $dailyCapacity - $booked);

                if ($remainingTrips > 0) {
                    $selected = $this->fetchContainmentsInRange(
                        $offset,
                        $offset + $remainingTrips,
                        $containmentIds
                    );

                    foreach ($selected as $containmentId) {
                        $updates[] = [
                            'id' => $containmentId,
                            'next_emptying_date' => $dateValue,
                        ];
                    }

                    $offset += $selected->count();
                }
            }

            $date->addDay();
        }

        foreach (array_chunk($updates, 1000) as $chunk) {
            $placeholders = implode(
                ', ',
                array_fill(0, count($chunk), '(?, ?)')
            );
            $bindings = [];

            foreach ($chunk as $update) {
                $bindings[] = $update['id'];
                $bindings[] = $update['next_emptying_date'];
            }

            DB::update(
                'UPDATE fsm.containments AS containment ' .
                'SET next_emptying_date = scheduled.next_emptying_date::date, updated_at = CURRENT_TIMESTAMP ' .
                'FROM (VALUES ' . $placeholders . ') AS scheduled(id, next_emptying_date) ' .
                'WHERE containment.id = scheduled.id::varchar',
                $bindings
            );
        }

        return count($updates);
    }

    /** Eligible containments ordered exactly as they are scheduled. */
    public function getContainmentData(): Collection
    {
        return DB::table('fsm.containments')
            ->whereNull('deleted_at')
            ->where('emptied_status', false)
            ->where(function ($query) {
                $query->whereNull('status')
                    ->orWhere('status', 0);
            })
            ->orderByRaw('priority ASC NULLS LAST')
            ->orderByRaw('fstp_distance ASC NULLS LAST')
            ->orderBy('id')
            ->pluck('id');
    }

    public function fetchSiteSettings(): Collection
    {
        return DB::table('public.site_settings')
            ->whereNull('deleted_at')
            ->where('category', 'desludging_schedule')
            ->get();
    }

    public function tripsAllocated(string $date): int
    {
        $settings = $this->fetchSiteSettings()->keyBy('name');
        $capacity = (int) $this->settingValue(
            $settings,
            'Trip Capacity Per Day'
        );
        $carbonDate = Carbon::createFromFormat('Y-m-d', $date);
        $weekends = $this->csvValues(
            $this->settingValue($settings, 'Weekend')
        )->map(function ($day) {
            return strtolower($day);
        })->all();
        $holidays = $this->csvValues(
            $this->settingValue($settings, 'Holiday Dates', false)
        )->all();

        if (
            in_array(strtolower($carbonDate->format('l')), $weekends, true) ||
            in_array($date, $holidays, true)
        ) {
            return 0;
        }

        [$confirmedByDate, $automaticByDate] = $this->bookingCountsByDate();

        return max(
            0,
            $capacity -
                (int) ($confirmedByDate[$date] ?? 0) -
                (int) ($automaticByDate[$date] ?? 0)
        );
    }

    public function fetchContainmentsInRange(
        int $start,
        int $end,
        Collection $containments
    ): Collection {
        return $containments->slice($start, $end - $start)->values();
    }

    /**
     * Rebuild the reference-style temporary queue without assigning providers.
     *
     * Date allocation and temporary-table membership are intentionally separate:
     * previously emptied containments may remain visible in the temporary table,
     * while only never-emptied containments receive regenerated dates.
     */
    public function refreshTemporarySchedule(?int $generatedBy = null): int
    {
        DB::table('fsm.desludging_schedule_temp')->delete();

        return DB::affectingStatement(<<<'SQL'
WITH candidates AS (
    SELECT DISTINCT ON (containment.id)
        containment.id AS containment_id,
        containment.next_emptying_date,
        containment.fstp_distance,
        containment.priority,
        containment.status,
        building.bin,
        building.ward,
        building.house_number,
        building.house_locality,
        building.road_code
    FROM fsm.containments AS containment
    JOIN building_info.build_contains AS building_containment
      ON building_containment.containment_id = containment.id
     AND building_containment.deleted_at IS NULL
    JOIN building_info.buildings AS building
      ON building.bin = building_containment.bin
     AND building.deleted_at IS NULL
    WHERE containment.deleted_at IS NULL
      AND (containment.status IS NULL OR containment.status IN (0, 3))
      AND NOT EXISTS (
          SELECT 1
          FROM fsm.applications AS application
          WHERE application.containment_id = containment.id
            AND application.emptying_status IN (0, 1)
            AND application.deleted_at IS NULL
      )
    ORDER BY containment.id, building.bin
),
ranked AS (
    SELECT candidates.*,
           ROW_NUMBER() OVER (
               ORDER BY fstp_distance ASC NULLS LAST,
                        priority ASC NULLS LAST,
                        containment_id ASC
           ) AS schedule_sequence
    FROM candidates
)
INSERT INTO fsm.desludging_schedule_temp (
    service_provider_id, service_provider_name, bin, ward, house_number,
    house_locality, road_code, containment_id, next_emptying_date,
    fstp_distance, priority, sequence, status, generated_by,
    generated_at, created_at, updated_at
)
SELECT
    NULL::bigint,
    NULL::varchar,
    ranked.bin,
    ranked.ward,
    ranked.house_number,
    ranked.house_locality,
    ranked.road_code,
    ranked.containment_id,
    ranked.next_emptying_date,
    ranked.fstp_distance,
    ranked.priority,
    ranked.schedule_sequence,
    ranked.status,
    ?,
    CURRENT_TIMESTAMP,
    CURRENT_TIMESTAMP,
    CURRENT_TIMESTAMP
FROM ranked
ORDER BY ranked.schedule_sequence
SQL
        , [$generatedBy]);
    }

    /**
     * Resolve a reintegration BIN from service areas prepared by regeneration.
     */
    public function redirectReintegrationToApplication(Request $request)
    {
        $data = $request->validate([
            'bin' => ['required', 'string'],
            'containment_id' => ['required', 'string'],
            'road_code' => ['nullable', 'string'],
        ]);

        $providerId = $this->providerIdForReintegrationBin($data['bin']);

        $request->merge([
            'service_provider_id' => $providerId,
        ]);

        return $this->redirectToApplication($request);
    }

    /** Return the provider covering the selected BIN's current ward. */
    public function providerIdForReintegrationBin(string $bin): int
    {
        return DB::transaction(function () use ($bin) {
            $lock = DB::selectOne(
                'SELECT pg_try_advisory_xact_lock(hashtext(?)) AS acquired',
                ['imis:reintegration-provider-assignment']
            );

            if (!$this->databaseBoolean($lock->acquired ?? false)) {
                throw new ConflictHttpException(
                    __('Service provider assignment is already running. Please wait for it to finish.')
                );
            }

            $building = DB::table('building_info.buildings')
                ->where('bin', $bin)
                ->whereNull('deleted_at')
                ->first(['ward']);

            if (!$building || empty($building->ward)) {
                throw new RuntimeException(
                    __('The selected BIN does not have a valid ward.')
                );
            }

            $ward = (int) $building->ward;
            $providerId = DB::table(
                'fsm.service_providers as provider'
            )
                ->whereNull('provider.deleted_at')
                ->where('provider.status', true)
                ->where(function ($query) use ($ward) {
                    $query->whereRaw(
                        '? = ANY (string_to_array(' .
                        "replace(COALESCE(provider.service_area, ''), ' ', ''), " .
                        "','::text)::integer[])",
                        [$ward]
                    )->orWhereExists(function ($coverage) use ($ward) {
                        $coverage->selectRaw('1')
                            ->from('fsm.service_provider_wards as coverage')
                            ->whereColumn(
                                'coverage.service_provider_id',
                                'provider.id'
                            )
                            ->where('coverage.ward', $ward);
                    });
                })
                ->orderBy('provider.id')
                ->value('provider.id');

            if (!$providerId) {
                throw new RuntimeException(
                    __(
                        'No operational service provider covers ward :ward for the selected BIN.',
                        ['ward' => $ward]
                    )
                );
            }

            return (int) $providerId;
        }, 1);
    }

    /**
     * Randomly and evenly allocate only currently uncovered wards to
     * operational providers that have no service area at all.
     */
    private function assignUncoveredWardsToEmptyProviders(): int
    {
        $municipalityWards = DB::table('layer_info.wards')
            ->whereNotNull('ward')
            ->pluck('ward')
            ->map(function ($ward) {
                return (int) $ward;
            })
            ->filter(function ($ward) {
                return $ward > 0;
            })
            ->unique()
            ->sort()
            ->values();

        if ($municipalityWards->isEmpty()) {
            return 0;
        }

        $providers = DB::table('fsm.service_providers')
            ->whereNull('deleted_at')
            ->where('status', true)
            ->orderBy('id')
            ->lockForUpdate()
            ->get(['id', 'service_area']);

        if ($providers->isEmpty()) {
            throw new RuntimeException(
                __('At least one operational service provider is required.')
            );
        }

        $mappedWards = DB::table('fsm.service_provider_wards')
            ->whereIn('service_provider_id', $providers->pluck('id'))
            ->get(['service_provider_id', 'ward'])
            ->groupBy('service_provider_id');

        $coveredWards = collect();
        $emptyProviderIds = collect();

        foreach ($providers as $provider) {
            $csvWards = $this->serviceAreaWardValues(
                $provider->service_area
            );

            $normalizedWards = collect($csvWards)
                ->merge(
                    ($mappedWards->get($provider->id) ?: collect())
                        ->pluck('ward')
                )
                ->map(function ($ward) {
                    return (int) $ward;
                })
                ->filter(function ($ward) {
                    return $ward > 0;
                })
                ->unique()
                ->values();

            if ($normalizedWards->isEmpty()) {
                $emptyProviderIds->push((int) $provider->id);
            } else {
                $coveredWards = $coveredWards->merge($normalizedWards);
            }
        }

        $uncoveredWards = $municipalityWards
            ->diff($coveredWards->unique())
            ->values();

        if ($emptyProviderIds->isEmpty() || $uncoveredWards->isEmpty()) {
            return 0;
        }

        $plan = $this->buildBalancedRandomPlan(
            $emptyProviderIds,
            $uncoveredWards
        );
        $assignedWardCount = 0;
        $timestamp = now();

        foreach ($plan as $providerId => $providerWards) {
            if (empty($providerWards)) {
                continue;
            }

            sort($providerWards);

            DB::table('fsm.service_providers')
                ->where('id', $providerId)
                ->where(function ($query) {
                    $query->whereNull('service_area')
                        ->orWhereRaw("BTRIM(service_area) = ''");
                })
                ->update([
                    'service_area' => implode(',', $providerWards),
                    'updated_at' => $timestamp,
                ]);

            DB::table('fsm.service_provider_wards')->insertOrIgnore(
                array_map(function ($ward) use (
                    $providerId,
                    $timestamp
                ) {
                    return [
                        'service_provider_id' => $providerId,
                        'ward' => $ward,
                        'created_at' => $timestamp,
                        'updated_at' => $timestamp,
                    ];
                }, $providerWards)
            );

            $assignedWardCount += count($providerWards);
        }

        return $assignedWardCount;
    }

    /**
     * Populate the regenerated queue by matching every BIN's current building
     * ward to an operational provider's preserved or newly assigned coverage.
     */
    private function assignGeneratedScheduleProvidersByBinWard(): array
    {
        DB::table('fsm.desludging_schedule_temp')->update([
            'service_provider_id' => null,
            'service_provider_name' => null,
            'updated_at' => now(),
        ]);

        $assignedCount = DB::affectingStatement(<<<'SQL'
WITH matches AS (
    SELECT
        schedule.id AS schedule_id,
        building.ward,
        provider.id AS service_provider_id,
        provider.company_name AS service_provider_name
    FROM fsm.desludging_schedule_temp AS schedule
    JOIN building_info.buildings AS building
      ON building.bin = schedule.bin
     AND building.deleted_at IS NULL
    JOIN LATERAL (
        SELECT candidate.id, candidate.company_name
        FROM fsm.service_providers AS candidate
        WHERE candidate.deleted_at IS NULL
          AND candidate.status = TRUE
          AND (
              building.ward = ANY (
                  string_to_array(
                      replace(COALESCE(candidate.service_area, ''), ' ', ''),
                      ','
                  )::integer[]
              )
              OR EXISTS (
                  SELECT 1
                  FROM fsm.service_provider_wards AS coverage
                  WHERE coverage.service_provider_id = candidate.id
                    AND coverage.ward = building.ward
              )
          )
        ORDER BY candidate.id
        LIMIT 1
    ) AS provider ON TRUE
)
UPDATE fsm.desludging_schedule_temp AS schedule
SET service_provider_id = matches.service_provider_id,
    service_provider_name = matches.service_provider_name,
    ward = matches.ward,
    updated_at = CURRENT_TIMESTAMP
FROM matches
WHERE schedule.id = matches.schedule_id
SQL
        );

        $unassignedCount = DB::table('fsm.desludging_schedule_temp')
            ->whereNull('service_provider_id')
            ->count();

        return [
            'assigned_count' => $assignedCount,
            'unassigned_count' => $unassignedCount,
        ];
    }

    /**
     * Shuffle both sets, then distribute uncovered wards round-robin.
     */
    private function buildBalancedRandomPlan(
        Collection $providerIds,
        Collection $wards
    ): array {
        $shuffledProviders = $providerIds->shuffle()->values();
        $shuffledWards = $wards->shuffle()->values();
        $plan = [];

        foreach ($shuffledProviders as $providerId) {
            $plan[(int) $providerId] = [];
        }

        foreach ($shuffledWards as $index => $ward) {
            $providerId = (int) $shuffledProviders[
                $index % $shuffledProviders->count()
            ];

            $plan[$providerId][] = (int) $ward;
        }

        return $plan;
    }

    private function serviceAreaWardValues(?string $serviceArea): array
    {
        return collect(explode(',', (string) $serviceArea))
            ->map(function ($ward) {
                return (int) trim($ward);
            })
            ->filter(function ($ward) {
                return $ward > 0;
            })
            ->unique()
            ->values()
            ->all();
    }

    /** Return generated rows through Yajra server-side DataTables. */
    public function getData(Request $request)
    {
        // Scheduling is an operational workflow, not a PII viewing surface.
        // Use an explicit allowlist so legacy owner snapshot columns can never
        // be serialized to the browser, even if they still exist in the table.
        $query = DB::table('fsm.desludging_schedule_temp as schedule')
            ->select([
                'schedule.service_provider_id',
                'schedule.service_provider_name',
                'schedule.bin',
                'schedule.ward',
                'schedule.house_number',
                'schedule.house_locality',
                'schedule.road_code',
                'schedule.containment_id',
                'schedule.next_emptying_date',
                'schedule.fstp_distance',
                'schedule.priority',
                'schedule.sequence',
                'schedule.status',
            ])
            ->where(function ($query) {
                $query->whereNull('schedule.status')
                    ->orWhereIn('schedule.status', [0, 3]);
            });

        $providerId = $this->providerIdFor($request->user());
        if ($providerId !== null) {
            $query->where('schedule.service_provider_id', $providerId);
        }

        $this->applyFilter($query, 'schedule.bin', $request->input('bin'));
        $this->applyFilter(
            $query,
            'schedule.containment_id',
            $request->input('containment_id')
        );
        $this->applyFilter(
            $query,
            'schedule.house_number',
            $request->input('holding_num')
        );

        return DataTables::of($query)
            ->addColumn('action', function ($row) {
                $buttons = '';

                if (Auth::user()->can('Confirm Schedule Desludging')) {
                    $buttons .= '<button type="button" title="' .
                        e(__('Accept Desludging')) .
                        '" class="btn btn-info btn-sm mb-1 accept-schedule"' .
                        ' data-bin="' . e($row->bin) . '"' .
                        ' data-containment-id="' . e($row->containment_id) . '"' .
                        ' data-road-code="' . e($row->road_code) . '"' .
                        ' data-service-provider-id="' .
                        e($row->service_provider_id) . '"' .
                        ' data-next-emptying-date="' .
                        e($row->next_emptying_date) . '">' .
                        '<i class="fa fa-check"></i></button> ';
                }

                if (Auth::user()->can('Delete Schedule Desludging')) {
                    $buttons .= '<button type="button" title="' .
                        e(__('Disagree for Schedule Desludging')) .
                        '" class="btn btn-danger btn-sm mb-1 disagree-schedule"' .
                        ' data-bin="' . e($row->bin) . '">' .
                        '<i class="fa fa-times"></i></button>';
                }

                return $buttons;
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    public function redirectToApplication(Request $request)
    {
        $data = $request->validate([
            'bin' => ['required', 'string'],
            'containment_id' => ['required', 'string'],
            'road_code' => ['nullable', 'string'],
            'service_provider_id' => ['nullable', 'integer'],
            'next_emptying_date' => ['nullable', 'date'],
        ]);

        $building = DB::table('building_info.buildings')
            ->where('bin', $data['bin'])
            ->whereNull('deleted_at')
            ->select(['ward', 'road_code'])
            ->first();

        if ($building) {
            $data['ward'] = $building->ward;

            if (empty($data['road_code'])) {
                $data['road_code'] = $building->road_code;
            }
        }

        if (empty($data['service_provider_id']) && auth()->user()) {
            $data['service_provider_id'] = auth()->user()->service_provider_id;
        }

        session()->put('schedule_accept', $data);

        foreach ($data as $key => $value) {
            session()->put($key, $value);
        }

        session()->put('action_type', 'confirm');

        return redirect()->route(
            'application.create',
            ['action_type' => 'confirm']
        );
    }

    public function disagree(string $bin)
    {
        $buildingContainment = DB::table('building_info.build_contains')
            ->where('bin', $bin)
            ->whereNull('deleted_at')
            ->whereNotNull('containment_id')
            ->first();

        if (!$buildingContainment) {
            return response()->json([
                'status' => 'error',
                'message' => __(
                    'Containment not found for the selected BIN.'
                ),
            ], 404);
        }

        $updated = DB::transaction(
            function () use ($buildingContainment) {
                $updated = DB::table('fsm.containments')
                    ->where('id', $buildingContainment->containment_id)
                    ->whereNull('deleted_at')
                    ->where(function ($query) {
                        $query->whereNull('status')
                            ->orWhere('status', 0);
                    })
                    ->update([
                        'status' => 4,
                        'updated_at' => now(),
                    ]);

                DB::table('fsm.desludging_schedule_temp')
                    ->where(
                        'containment_id',
                        (string) $buildingContainment->containment_id
                    )
                    ->delete();

                return $updated;
            }
        );

        return response()->json([
            'status' => 'success',
            'message' => $updated
                ? __(
                    'The property was removed from the desludging schedule.'
                )
                : __(
                    'The property was already removed from the desludging schedule.'
                ),
        ]);
    }

    /** Return confirmed and automatic bookings keyed by YYYY-MM-DD. */
    private function bookingCountsByDate(): array
    {
        $confirmed = DB::table('fsm.applications')
            ->whereNull('deleted_at')
            ->whereNotNull('proposed_emptying_date')
            ->selectRaw(
                'proposed_emptying_date::date::text AS schedule_date, ' .
                'COUNT(*)::integer AS total'
            )
            ->groupByRaw('proposed_emptying_date::date')
            ->pluck('total', 'schedule_date')
            ->map(function ($count) {
                return (int) $count;
            })
            ->all();

        $automatic = DB::table('fsm.containments as containment')
            ->whereNull('containment.deleted_at')
            ->where('containment.emptied_status', true)
            ->whereNotNull('containment.next_emptying_date')
            ->whereNotExists(function ($query) {
                $query->selectRaw('1')
                    ->from('fsm.applications as application')
                    ->whereColumn(
                        'application.containment_id',
                        'containment.id'
                    )
                    ->whereIn('application.emptying_status', [0, 1])
                    ->whereNull('application.deleted_at');
            })
            ->selectRaw(
                'containment.next_emptying_date::date::text AS schedule_date, ' .
                'COUNT(*)::integer AS total'
            )
            ->groupByRaw('containment.next_emptying_date::date')
            ->pluck('total', 'schedule_date')
            ->map(function ($count) {
                return (int) $count;
            })
            ->all();

        return [$confirmed, $automatic];
    }

    private function scheduleStartDate(
        Collection $settings,
        Carbon $today
    ): Carbon {
        $value = $this->settingValue(
            $settings,
            'Schedule Desludging Start Date',
            false
        );

        if ($value === '') {
            return $today->copy();
        }

        try {
            $startDate = Carbon::createFromFormat('Y-m-d', $value)
                ->startOfDay();
        } catch (\Throwable $exception) {
            throw new RuntimeException(
                __('Schedule Desludging Start Date must use YYYY-MM-DD format.')
            );
        }

        if ($startDate->lt($today)) {
            $regenerationPeriod = (int) $this->settingValue(
                $settings,
                'Schedule Regeneration Period'
            );

            return $today->copy()->addDays(max(0, $regenerationPeriod));
        }

        return $startDate;
    }

    private function settingValue(
        Collection $settings,
        string $name,
        bool $required = true
    ): string {
        $setting = $settings->get($name);

        if (!$setting) {
            if (!$required) {
                return '';
            }

            throw new RuntimeException(
                __('Missing desludging setting: :name', ['name' => $name])
            );
        }

        $value = trim((string) $setting->value);

        if ($required && $value === '') {
            throw new RuntimeException(
                __('Desludging setting is empty: :name', ['name' => $name])
            );
        }

        return $value;
    }

    private function csvValues(string $value): Collection
    {
        return collect(explode(',', $value))
            ->map(function ($item) {
                return trim($item);
            })
            ->filter()
            ->values();
    }

    private function providerIdFor($user): ?int
    {
        if ($user && !empty($user->service_provider_id)) {
            return (int) $user->service_provider_id;
        }

        return null;
    }

    private function applyFilter(
        Builder $query,
        string $column,
        $value
    ): void {
        if ($value !== null && trim((string) $value) !== '') {
            $query->where(
                $column,
                'ILIKE',
                '%' . trim((string) $value) . '%'
            );
        }
    }

    private function databaseBoolean($value): bool
    {
        return $value === true ||
            $value === 1 ||
            $value === '1' ||
            $value === 't';
    }

    private function normalizeDate(
        $value,
        Carbon $today
    ): ?Carbon {
        if (empty($value)) {
            return null;
        }

        try {
            if ($value instanceof \DateTimeInterface) {
                $date = Carbon::instance($value);
            } else {
                $date = Carbon::parse(trim((string) $value));
            }

            $date = $date->copy()->startOfDay();

            if ($date->greaterThan($today)) {
                return null;
            }

            return $date;
        } catch (\Throwable $exception) {
            return null;
        }
    }

    /**
     * Determine priority using exact date boundaries.
     */
    private function calculatePriority(
        ?Carbon $lastEmptiedDate,
        ?Carbon $constructionDate,
        Carbon $today
    ): int {
        /*
         * Last-emptying date has precedence.
         * Construction date is the fallback.
         */
        $effectiveDate = $lastEmptiedDate ?: $constructionDate;

        /*
         * No usable date receives the highest priority.
         */
        if (!$effectiveDate) {
            return 1;
        }

        $threeYearCutoff = $today
            ->copy()
            ->subYearsNoOverflow(3);

        $oneYearCutoff = $today
            ->copy()
            ->subYearsNoOverflow(1);

        if ($effectiveDate->lt($threeYearCutoff)) {
            return 1;
        }

        if ($effectiveDate->lt($oneYearCutoff)) {
            return 2;
        }

        return 3;
    }
}
