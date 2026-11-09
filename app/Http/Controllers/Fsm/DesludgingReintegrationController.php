<?php

namespace App\Http\Controllers\Fsm;

use App\Http\Controllers\Controller;
use App\Models\Fsm\ServiceProvider;
use App\Services\Fsm\DesludgingScheduleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class DesludgingReintegrationController extends Controller
{
    protected $desludgingScheduleService;

    public function __construct(
        DesludgingScheduleService $desludgingScheduleService
    ) {
        $this->middleware('auth');
        $this->middleware(
            'permission:List Schedule Reintegration',
            ['only' => ['index', 'getData']]
        );
        $this->middleware(
            'permission:Confirm Schedule Reintegration',
            ['only' => ['confirm']]
        );

        $this->desludgingScheduleService = $desludgingScheduleService;
    }

    public function index()
    {
        $page_title = __('Scheduled Desludging Reintegration');
        $showServiceProvider = $this->canViewAllProviders(auth()->user());

        return view(
            'fsm.desludging-reintegration.index',
            compact('page_title', 'showServiceProvider')
        );
    }

    public function getData(Request $request)
    {
        $user = $request->user();
        $showServiceProvider = $this->canViewAllProviders($user);

        // Reintegration needs routing and scheduling identifiers only. Owner
        // PII is deliberately excluded: the Application create workflow will
        // perform its own permission- and session-scoped owner lookup.
        $query = DB::table('fsm.containments as containment')
            ->join('building_info.build_contains as building_containment', function ($join) {
                $join->on(
                    'building_containment.containment_id',
                    '=',
                    'containment.id'
                )->whereNull('building_containment.deleted_at');
            })
            ->join('building_info.buildings as building', function ($join) {
                $join->on('building.bin', '=', 'building_containment.bin')
                    ->whereNull('building.deleted_at');
            })
            ->whereNull('containment.deleted_at')
            ->where('containment.status', 4)
            ->select([
                DB::raw(
                    'ROW_NUMBER() OVER (' .
                    'ORDER BY containment.priority ASC NULLS LAST, ' .
                    'containment.fstp_distance ASC NULLS LAST, ' .
                    'containment.id ASC' .
                    ') AS sequence'
                ),
                'building.bin',
                'building.ward',
                'building.house_number',
                'building.house_locality',
                'building.road_code',
                'containment.id as containment_id',
                'containment.status',
                DB::raw($this->providerIdExpression() . ' AS service_provider_id'),
                DB::raw($this->providerNameExpression() . ' AS service_provider_name'),
            ])
            ->distinct();

        if (!$showServiceProvider) {
            $provider = !empty($user->service_provider_id)
                ? ServiceProvider::find($user->service_provider_id)
                : null;

            $wards = $provider
                ? $this->serviceAreaWards($provider->service_area)
                : [];

            if ($provider) {
                $wards = array_values(array_unique(array_merge(
                    $wards,
                    DB::table('fsm.service_provider_wards')
                        ->where('service_provider_id', $provider->id)
                        ->pluck('ward')
                        ->map(function ($ward) {
                            return (int) $ward;
                        })
                        ->all()
                )));
            }

            if (empty($wards)) {
                $query->whereRaw('1 = 0');
            } else {
                $query->whereIn('building.ward', $wards);
            }
        }

        if ($request->filled('containment_id')) {
            $query->where(
                'containment.id',
                'ILIKE',
                '%' . trim((string) $request->containment_id) . '%'
            );
        }

        if ($request->filled('holding_num')) {
            $query->where(
                'building.house_number',
                'ILIKE',
                '%' . trim((string) $request->holding_num) . '%'
            );
        }

        if ($request->filled('bin')) {
            $query->where(
                'building.bin',
                'ILIKE',
                '%' . trim((string) $request->bin) . '%'
            );
        }

        return DataTables::of($query)
            ->addColumn('action', function ($row) {
                if (!auth()->user()->can('Confirm Schedule Reintegration')) {
                    return '';
                }

                return '<button type="button"' .
                    ' title="' . e(__('Confirm Desludging')) . '"' .
                    ' class="btn btn-info btn-sm mb-1 reintegrate-schedule"' .
                    ' data-bin="' . e($row->bin) . '"' .
                    ' data-containment-id="' . e($row->containment_id) . '"' .
                    ' data-road-code="' . e($row->road_code) . '">' .
                    '<i class="fa fa-check"></i></button>';
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    public function confirm(Request $request)
    {
        try {
            return $this->desludgingScheduleService
                ->redirectReintegrationToApplication($request);
        } catch (
            \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface |
            \RuntimeException $exception
        ) {
            return back()->with('error', $exception->getMessage());
        } catch (\Throwable $exception) {
            report($exception);

            return back()->with(
                'error',
                __('The selected BIN could not be reintegrated.')
            );
        }
    }

    private function canViewAllProviders($user): bool
    {
        return $user->hasRole('Super Admin')
            || $user->hasRole('Municipality - Super Admin')
            || $user->hasRole('Municipality - Help Desk');
    }

    private function serviceAreaWards(?string $serviceArea): array
    {
        return array_values(array_filter(array_map(
            'intval',
            explode(',', (string) $serviceArea)
        ), function ($ward) {
            return $ward > 0;
        }));
    }

    private function providerIdExpression(): string
    {
        return "(SELECT provider.id
            FROM fsm.service_providers AS provider
            WHERE provider.deleted_at IS NULL
              AND provider.status = TRUE
              AND (
                  building.ward = ANY (
                      string_to_array(
                          replace(COALESCE(provider.service_area, ''), ' ', ''),
                          ','
                      )::integer[]
                  )
                  OR EXISTS (
                      SELECT 1
                      FROM fsm.service_provider_wards AS coverage
                      WHERE coverage.service_provider_id = provider.id
                        AND coverage.ward = building.ward
                  )
              )
            ORDER BY provider.id
            LIMIT 1)";
    }

    private function providerNameExpression(): string
    {
        return "(SELECT provider.company_name
            FROM fsm.service_providers AS provider
            WHERE provider.deleted_at IS NULL
              AND provider.status = TRUE
              AND (
                  building.ward = ANY (
                      string_to_array(
                          replace(COALESCE(provider.service_area, ''), ' ', ''),
                          ','
                      )::integer[]
                  )
                  OR EXISTS (
                      SELECT 1
                      FROM fsm.service_provider_wards AS coverage
                      WHERE coverage.service_provider_id = provider.id
                        AND coverage.ward = building.ward
                  )
              )
            ORDER BY provider.id
            LIMIT 1)";
    }
}
