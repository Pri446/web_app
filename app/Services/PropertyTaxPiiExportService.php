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
                'tax_code_file' => __('The Tax Code list CSV is empty or too large.'),
            ]);
        }

        $handle = fopen('php://temp', 'w+b');
        if ($handle === false || fwrite($handle, $csv) === false) {
            throw ValidationException::withMessages([
                'tax_code_file' => __('The Tax Code list CSV could not be read.'),
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
                'tax_code_file' => __('The Tax Code list is empty.'),
            ]);
        }

        $header = array_map(function ($value) {
            $value = preg_replace('/^\xEF\xBB\xBF/', '', (string) $value);
            return strtolower(trim($value));
        }, $header);
        $codeIndex = array_search('tax_code', $header, true);

        if ($codeIndex === false) {
            throw ValidationException::withMessages([
                'tax_code_file' => __('The CSV must contain a tax_code header.'),
            ]);
        }

        $codes = [];
        $rowNumber = 1;
        $maximumCodes = max(1, (int) config('pii.export.max_tax_codes', 5000));

        while (($row = fgetcsv($handle)) !== false) {
            $rowNumber++;
            $code = strtoupper(trim((string) ($row[$codeIndex] ?? '')));

            if ($code === '' && $this->rowIsEmpty($row)) {
                continue;
            }

            if ($code === ''
                || strlen($code) > 100
                || preg_match('/^[A-Z0-9_-]+$/', $code) !== 1) {
                throw ValidationException::withMessages([
                    'tax_code_file' => __(sprintf(
                        'The Tax Code on CSV row %d is invalid.',
                        $rowNumber
                    )),
                ]);
            }

            $codes[$code] = true;
            if (count($codes) > $maximumCodes) {
                throw ValidationException::withMessages([
                    'tax_code_file' => __(sprintf(
                        'The CSV may contain no more than %d unique Tax Codes.',
                        $maximumCodes
                    )),
                ]);
            }
        }

        if ($codes === []) {
            throw ValidationException::withMessages([
                'tax_code_file' => __('The CSV does not contain any Tax Codes.'),
            ]);
        }

        return array_keys($codes);
    }

    public function rowsForTaxCodes(array $codes): array
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
            ->whereIn(DB::raw('UPPER(tax.tax_code)'), $codes)
            ->get()
            ->keyBy(function ($record) {
                return strtoupper((string) $record->tax_code);
            });

        $rows = [];
        $exportedCount = 0;

        foreach ($codes as $code) {
            $record = $records->get($code);
            if (!$record) {
                $rows[] = [$code, '', '', '', '', 'not_found'];
                continue;
            }

            $rows[] = [
                $this->escapeSpreadsheetValue($code),
                $this->escapeSpreadsheetValue($record->bin),
                $this->escapeSpreadsheetValue(
                    $this->encryption->decrypt($record->owner_name)
                ),
                $this->escapeSpreadsheetValue(
                    $this->encryption->decrypt($record->owner_contact)
                ),
                $this->escapeSpreadsheetValue((string) $record->last_payment_date),
                $record->bin ? 'matched' : 'unmatched',
            ];
            $exportedCount++;
        }

        return [
            'rows' => $rows,
            'requested_count' => count($codes),
            'exported_count' => $exportedCount,
            'missing_count' => count($codes) - $exportedCount,
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
}
