<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PropertyTaxPiiExportService
{
    private const HEADERS = [
        'tax_code',
        'bin',
        'owner_name',
        'owner_contact',
        'last_payment_date',
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

    public function parseCsvText(string $csv): array
    {
        $maximumBytes = max(1, (int) config('pii.export.max_file_kb', 2048)) * 1024;

        if ($csv === '' || strlen($csv) > $maximumBytes) {
            throw ValidationException::withMessages([
                'bin_file' => __('The BIN list CSV is empty or too large.'),
            ]);
        }

        $handle = fopen('php://temp', 'w+b');
        if ($handle === false || fwrite($handle, $csv) === false) {
            throw ValidationException::withMessages([
                'bin_file' => __('The BIN list CSV could not be read.'),
            ]);
        }
        rewind($handle);

        try {
            return $this->parseCsvHandle($handle);
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
                'bin_file' => __('The BIN list is empty.'),
            ]);
        }

        $header = array_map(function ($value) {
            $value = preg_replace('/^\xEF\xBB\xBF/', '', (string) $value);
            return strtolower(trim($value));
        }, $header);
        $binIndex = array_search('bin', $header, true);

        if ($binIndex === false) {
            throw ValidationException::withMessages([
                'bin_file' => __('The CSV must contain a bin header.'),
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
                    'bin_file' => __(sprintf(
                        'The BIN value on CSV row %d is invalid.',
                        $rowNumber
                    )),
                ]);
            }

            $bins[$bin] = true;
            if (count($bins) > $maximumBins) {
                throw ValidationException::withMessages([
                    'bin_file' => __(sprintf(
                        'The CSV may contain no more than %d unique BIN values.',
                        $maximumBins
                    )),
                ]);
            }
        }

        if ($bins === []) {
            throw ValidationException::withMessages([
                'bin_file' => __('The CSV does not contain any BIN values.'),
            ]);
        }

        return array_keys($bins);
    }

    public function rowsForBins(array $bins): array
    {
        $records = DB::table('taxpayment_info.tax_payments AS tax')
            ->leftJoin('building_info.buildings AS building', 'building.tax_code', '=', 'tax.tax_code')
            ->select([
                'tax.tax_code',
                'tax.owner_name',
                'tax.owner_contact',
                'tax.last_payment_date',
                'building.bin',
            ])
            ->whereIn(DB::raw('UPPER(building.bin)'), $bins)
            ->get()
            ->keyBy(function ($record) {
                return strtoupper((string) $record->bin);
            });

        $rows = [];
        $exportedCount = 0;

        foreach ($bins as $bin) {
            $record = $records->get($bin);
            if (!$record) {
                $rows[] = ['', $bin, '', '', '', 'not_found'];
                continue;
            }

            $rows[] = $this->rowForRecord($record);
            $exportedCount++;
        }

        return [
            'rows' => $rows,
            'requested_count' => count($bins),
            'exported_count' => $exportedCount,
            'missing_count' => count($bins) - $exportedCount,
        ];
    }

    public function rowsForAllRecords(): array
    {
        $query = DB::table('taxpayment_info.tax_payments AS tax')
            ->leftJoin('building_info.buildings AS building', 'building.tax_code', '=', 'tax.tax_code')
            ->select([
                'tax.tax_code',
                'tax.owner_name',
                'tax.owner_contact',
                'tax.last_payment_date',
                'building.bin',
            ]);
        $recordCount = (clone $query)->count();

        // Stream one decrypted record at a time so a full authorized export
        // does not keep the complete plaintext dataset in PHP memory.
        $rows = $query
            ->orderBy('tax.tax_code')
            ->cursor()
            ->map(function ($record): array {
                return $this->rowForRecord($record);
            });

        return [
            'rows' => $rows,
            'requested_count' => $recordCount,
            'exported_count' => $recordCount,
            'missing_count' => 0,
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
        return $value !== '' && preg_match('/^[=+\-@\t\r]/', $value) === 1
            ? "'" . $value
            : $value;
    }

    private function rowForRecord($record): array
    {
        return [
            $this->escapeSpreadsheetValue((string) $record->tax_code),
            $this->escapeSpreadsheetValue((string) $record->bin),
            $this->escapeSpreadsheetValue(
                $this->encryption->decrypt($record->owner_name)
            ),
            $this->escapeSpreadsheetValue(
                $this->encryption->decrypt($record->owner_contact)
            ),
            $this->escapeSpreadsheetValue((string) $record->last_payment_date),
            $record->bin ? 'matched' : 'unmatched',
        ];
    }
}
