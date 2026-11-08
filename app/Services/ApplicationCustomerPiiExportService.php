<?php

namespace App\Services;

use App\Models\Fsm\Application;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class ApplicationCustomerPiiExportService
{
    private const HEADERS = [
        'application_id',
        'bin',
        'customer_name',
        'customer_gender',
        'customer_contact',
        'status',
    ];

    private PiiEncryptionService $encryption;

    public function __construct(PiiEncryptionService $encryption)
    {
        $this->encryption = $encryption;
    }

    public function headers(): array
    {
        return self::HEADERS;
    }

    /**
     * Parse a browser-validated CSV again on the server. Client validation is
     * usability only; this is the security boundary for the export request.
     */
    public function parseCsvText(string $csv): array
    {
        $maximumBytes = max(1, (int) config('pii.export.max_file_kb', 2048)) * 1024;

        if ($csv === '' || strlen($csv) > $maximumBytes) {
            throw ValidationException::withMessages([
                'application_id_file' => __('The Application ID list CSV is empty or too large.'),
            ]);
        }

        $handle = fopen('php://temp', 'w+b');

        if ($handle === false || fwrite($handle, $csv) === false) {
            throw ValidationException::withMessages([
                'application_id_file' => __('The Application ID list CSV could not be read.'),
            ]);
        }

        rewind($handle);

        try {
            return $this->parseCsvHandle($handle);
        } finally {
            fclose($handle);
        }
    }

    /**
     * Parse the selected-BIN export file. This is separate from the legacy
     * Application-ID parser so a BIN file can never be interpreted as IDs.
     */
    public function parseBinCsvText(string $csv): array
    {
        $maximumBytes = max(1, (int) config('pii.export.max_file_kb', 2048)) * 1024;

        if ($csv === '' || strlen($csv) > $maximumBytes) {
            throw ValidationException::withMessages([
                'application_bin_file' => __('The BIN list CSV is empty or too large.'),
            ]);
        }

        $handle = fopen('php://temp', 'w+b');

        if ($handle === false || fwrite($handle, $csv) === false) {
            throw ValidationException::withMessages([
                'application_bin_file' => __('The BIN list CSV could not be read.'),
            ]);
        }

        rewind($handle);

        try {
            $header = fgetcsv($handle);

            if (!is_array($header)) {
                throw ValidationException::withMessages([
                    'application_bin_file' => __('The BIN list is empty.'),
                ]);
            }

            $header = array_map(function ($value) {
                $value = preg_replace('/^\xEF\xBB\xBF/', '', (string) $value);

                return strtolower(trim($value));
            }, $header);
            $binIndex = array_search('bin', $header, true);

            if ($binIndex === false) {
                throw ValidationException::withMessages([
                    'application_bin_file' => __('The CSV must contain a bin header.'),
                ]);
            }

            $bins = [];
            $rowNumber = 1;
            $maximumBins = max(1, (int) config('pii.export.max_bins', 5000));

            while (($row = fgetcsv($handle)) !== false) {
                $rowNumber++;
                $bin = strtoupper(trim((string) ($row[$binIndex] ?? '')));

                if ($bin === '' && $this->rowIsEmpty($row)) {
                    continue;
                }

                if ($bin === ''
                    || strlen($bin) > 100
                    || preg_match('/^[A-Z0-9_-]+$/', $bin) !== 1) {
                    throw ValidationException::withMessages([
                        'application_bin_file' => __(sprintf(
                            'The BIN value on CSV row %d is invalid.',
                            $rowNumber
                        )),
                    ]);
                }

                $bins[$bin] = true;

                if (count($bins) > $maximumBins) {
                    throw ValidationException::withMessages([
                        'application_bin_file' => __(sprintf(
                            'The CSV may contain no more than %d unique BIN values.',
                            $maximumBins
                        )),
                    ]);
                }
            }

            if ($bins === []) {
                throw ValidationException::withMessages([
                    'application_bin_file' => __('The CSV does not contain any BIN values.'),
                ]);
            }

            return array_keys($bins);
        } finally {
            fclose($handle);
        }
    }

    /** @param resource $handle */
    private function parseCsvHandle($handle): array
    {
        $header = fgetcsv($handle);

        if (!is_array($header)) {
            throw ValidationException::withMessages([
                'application_id_file' => __('The Application ID list is empty.'),
            ]);
        }

        $header = array_map(function ($value) {
            $value = preg_replace('/^\xEF\xBB\xBF/', '', (string) $value);

            return strtolower(trim($value));
        }, $header);
        $idIndex = array_search('application_id', $header, true);

        if ($idIndex === false) {
            throw ValidationException::withMessages([
                'application_id_file' => __('The CSV must contain an application_id header.'),
            ]);
        }

        $ids = [];
        $rowNumber = 1;
        $maximumIds = max(1, (int) config('pii.export.max_application_ids', 5000));

        while (($row = fgetcsv($handle)) !== false) {
            $rowNumber++;
            $id = trim((string) ($row[$idIndex] ?? ''));

            if ($id === '' && $this->rowIsEmpty($row)) {
                continue;
            }

            if ($id === '' || preg_match('/^[1-9][0-9]*$/', $id) !== 1) {
                throw ValidationException::withMessages([
                    'application_id_file' => __(sprintf(
                        'The Application ID on CSV row %d is invalid.',
                        $rowNumber
                    )),
                ]);
            }

            $ids[$id] = true;

            if (count($ids) > $maximumIds) {
                throw ValidationException::withMessages([
                    'application_id_file' => __(sprintf(
                        'The CSV may contain no more than %d unique Application IDs.',
                        $maximumIds
                    )),
                ]);
            }
        }

        if ($ids === []) {
            throw ValidationException::withMessages([
                'application_id_file' => __('The CSV does not contain any Application IDs.'),
            ]);
        }

        // PHP converts numeric string array keys to integers; normalize them
        // back to strings so CSV values and map lookups remain predictable.
        return array_map('strval', array_keys($ids));
    }

    public function rowsForApplicationIds(array $ids, User $user): array
    {
        $query = Application::query()
            ->select([
                'id',
                'bin',
                'customer_name',
                'customer_gender',
                'customer_contact',
                'service_provider_id',
            ])
            ->setEagerLoads([])
            ->whereNull('deleted_at')
            ->whereIn('id', $ids);

        // Preserve the same row-level visibility rules used by the list page.
        if ($user->hasRole('Service Provider - Admin')
            || $user->hasRole('Service Provider - Help Desk')) {
            $query->where('service_provider_id', $user->service_provider_id);
        } elseif ($user->hasRole('Treatment Plant - Admin')) {
            $query->whereHas('emptying', function ($emptying) use ($user) {
                $emptying->where('treatment_plant_id', $user->treatment_plant_id)
                    ->where('emptying_status', true)
                    ->whereNull('deleted_at');
            });
        }

        $applications = $query->get()->keyBy(function (Application $application) {
            return (string) $application->id;
        });

        $rows = [];
        $exportedCount = 0;

        foreach ($ids as $id) {
            /** @var Application|null $application */
            $application = $applications->get((string) $id);

            if (!$application) {
                // Do not reveal whether a missing row exists outside the user's scope.
                $rows[] = [$id, '', '', '', '', 'not_found_or_unauthorized'];
                continue;
            }

            $attributes = $application->getAttributes();
            $rows[] = [
                $id,
                $this->escapeSpreadsheetValue((string) $application->bin),
                $this->escapeSpreadsheetValue(
                    $this->encryption->decrypt($attributes['customer_name'] ?? null)
                ),
                $this->escapeSpreadsheetValue(
                    $this->encryption->decrypt($attributes['customer_gender'] ?? null)
                ),
                $this->escapeSpreadsheetValue(
                    $this->encryption->decrypt($attributes['customer_contact'] ?? null)
                ),
                'found',
            ];
            $exportedCount++;
        }

        return [
            'rows' => $rows,
            'requested_count' => count($ids),
            'exported_count' => $exportedCount,
            'missing_count' => count($ids) - $exportedCount,
        ];
    }

    public function rowsForBins(array $bins, User $user): array
    {
        $applications = $this->authorizedApplicationsQuery($user)
            ->whereIn('bin', $bins)
            ->orderBy('bin')
            ->orderBy('id')
            ->get()
            ->groupBy(function (Application $application) {
                return strtoupper((string) $application->bin);
            });

        $rows = [];
        $exportedCount = 0;
        $missingCount = 0;

        foreach ($bins as $bin) {
            $matchingApplications = $applications->get($bin);

            if (!$matchingApplications || $matchingApplications->isEmpty()) {
                // Do not reveal whether the BIN exists outside this user's
                // normal Application row-level visibility.
                $rows[] = ['', $bin, '', '', '', 'not_found_or_unauthorized'];
                $missingCount++;
                continue;
            }

            foreach ($matchingApplications as $application) {
                $rows[] = $this->rowForApplication($application);
                $exportedCount++;
            }
        }

        return [
            'rows' => $rows,
            'requested_count' => count($bins),
            'exported_count' => $exportedCount,
            'missing_count' => $missingCount,
        ];
    }

    public function rowsForAllApplications(User $user): array
    {
        $query = $this->authorizedApplicationsQuery($user);
        $applicationCount = (clone $query)->count();

        // The controller applies an additional administrative-role gate before
        // this method runs. cursor() avoids collecting the complete decrypted
        // Application dataset in PHP memory before the CSV is streamed.
        $rows = $query
            ->orderBy('id')
            ->cursor()
            ->map(function (Application $application): array {
                return $this->rowForApplication($application);
            });

        return [
            'rows' => $rows,
            'requested_count' => $applicationCount,
            'exported_count' => $applicationCount,
            'missing_count' => 0,
        ];
    }

    private function authorizedApplicationsQuery(User $user)
    {
        $query = Application::query()
            ->select([
                'id',
                'bin',
                'customer_name',
                'customer_gender',
                'customer_contact',
                'service_provider_id',
            ])
            ->setEagerLoads([])
            ->whereNull('deleted_at');

        // Keep the export inside the same row-level boundary as the list page.
        if ($user->hasRole('Service Provider - Admin')
            || $user->hasRole('Service Provider - Help Desk')) {
            $query->where('service_provider_id', $user->service_provider_id);
        } elseif ($user->hasRole('Treatment Plant - Admin')) {
            $query->whereHas('emptying', function ($emptying) use ($user) {
                $emptying->where('treatment_plant_id', $user->treatment_plant_id)
                    ->where('emptying_status', true)
                    ->whereNull('deleted_at');
            });
        }

        return $query;
    }

    private function rowForApplication(Application $application): array
    {
        // Use raw attributes so accessors/serialization cannot implicitly add
        // fields outside the dedicated PII export allowlist.
        $attributes = $application->getAttributes();

        return [
            (string) $application->id,
            $this->escapeSpreadsheetValue((string) $application->bin),
            $this->escapeSpreadsheetValue(
                $this->encryption->decrypt($attributes['customer_name'] ?? null)
            ),
            $this->escapeSpreadsheetValue(
                $this->encryption->decrypt($attributes['customer_gender'] ?? null)
            ),
            $this->escapeSpreadsheetValue(
                $this->encryption->decrypt($attributes['customer_contact'] ?? null)
            ),
            'found',
        ];
    }

    private function rowIsEmpty(array $row): bool
    {
        foreach ($row as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    private function escapeSpreadsheetValue(?string $value): string
    {
        $value = $value ?? '';

        if ($value !== '' && preg_match('/^[=+\-@\t\r]/', $value) === 1) {
            return "'" . $value;
        }

        return $value;
    }
}
