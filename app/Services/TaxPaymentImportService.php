<?php

namespace App\Services;

use App\Imports\TaxImport;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\HeadingRowImport;

class TaxPaymentImportService
{
    public function import(string $filePath): array
    {
        $headings = (new HeadingRowImport)->toArray($filePath);
        $headers = $headings[0][0] ?? [];
        $missing = array_diff(['tax_code', 'owner_name', 'owner_contact', 'last_payment_date'], $headers);
        if ($missing) {
            throw ValidationException::withMessages([
                'csvfile' => __('Missing CSV headings: :headings', ['headings' => implode(', ', $missing)]),
            ]);
        }

        return DB::transaction(function () use ($filePath) {
            DB::statement('TRUNCATE TABLE taxpayment_info.tax_payments RESTART IDENTITY');
            (new TaxImport)->import($filePath);
            $total = DB::table('taxpayment_info.tax_payments')->count();
            if (!$total) {
                throw ValidationException::withMessages(['csvfile' => __('The CSV contains no tax records. Previous data was preserved.')]);
            }
            if (DB::table('taxpayment_info.tax_payments')->select('tax_code')
                ->groupBy('tax_code')->havingRaw('COUNT(*) > 1')->exists()) {
                throw ValidationException::withMessages(['csvfile' => __('Duplicate Tax Codes are not allowed. Previous data was preserved.')]);
            }

            DB::statement('select taxpayment_info.fnc_taxpaymentstatus()');
            DB::statement('select taxpayment_info.fnc_updonimprt_gridnward_tax()');

            $matched = DB::table('taxpayment_info.tax_payments as t')
                ->whereExists(function ($query) {
                    $query->selectRaw('1')->from('building_info.buildings as b')
                        ->whereColumn('b.tax_code', 't.tax_code')->whereNull('b.deleted_at');
                })->count();

            return ['total' => $total, 'matched' => $matched, 'unmatched' => $total - $matched];
        });
    }
}
