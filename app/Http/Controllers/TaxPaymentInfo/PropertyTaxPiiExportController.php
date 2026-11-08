<?php

namespace App\Http\Controllers\TaxPaymentInfo;

use App\Http\Controllers\Controller;
use App\Services\OwnerPiiAuditService;
use App\Services\PropertyTaxPiiAccessService;
use App\Services\PropertyTaxPiiExportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Throwable;

class PropertyTaxPiiExportController extends Controller
{
    private PropertyTaxPiiExportService $exporter;
    private PropertyTaxPiiAccessService $access;
    private OwnerPiiAuditService $audit;

    public function __construct(
        PropertyTaxPiiExportService $exporter,
        PropertyTaxPiiAccessService $access,
        OwnerPiiAuditService $audit
    ) {
        $this->middleware('auth');
        $this->exporter = $exporter;
        $this->access = $access;
        $this->audit = $audit;
    }

    public function export(Request $request)
    {
        $user = $request->user();
        $allowed = $user
            && $user->can('List Property Tax Collection')
            && $user->can('View Property Tax Owner PII')
            && $user->can('Unlock Property Tax Owner PII')
            && $user->can('Export Property Tax Owner PII');

        abort_unless($allowed, 403);
        $maximumFileSize = max(1, (int) config('pii.export.max_file_kb', 2048));
        $request->validate([
            'tax_code_csv' => ['required', 'string', 'max:' . ($maximumFileSize * 1024)],
            'property_tax_export_password' => ['required', 'string', 'max:255'],
        ]);

        if (empty($user->password)
            || !Hash::check(
                (string) $request->input('property_tax_export_password'),
                (string) $user->password
            )) {
            $this->access->lock();
            $this->audit->record(
                'property_tax_pii_export_password_failed',
                false,
                $user,
                null,
                ['surface' => 'property_tax_pii_export'],
                'password_reentry'
            );

            return response()->json([
                'message' => __('Password confirmation failed.'),
                'errors' => [
                    'property_tax_export_password' => [__('Password confirmation failed.')],
                ],
            ], 422);
        }

        try {
            $codes = $this->exporter->parseCsvText(
                (string) $request->input('tax_code_csv')
            );
            $result = $this->exporter->rowsForTaxCodes($codes);
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            $this->audit->record(
                'property_tax_pii_export_failed',
                false,
                $user,
                null,
                ['surface' => 'property_tax_pii_export'],
                'password_reentry'
            );
            report($exception);

            return response()->json([
                'message' => __('The Property Tax owner PII export could not be generated.'),
            ], 500);
        }

        $this->audit->record(
            'property_tax_owner_pii_exported',
            true,
            $user,
            null,
            [
                'surface' => 'property_tax_pii_export',
                'requested_count' => $result['requested_count'],
                'exported_count' => $result['exported_count'],
                'missing_count' => $result['missing_count'],
            ],
            'password_reentry'
        );

        $headers = $this->exporter->headers();
        $rows = $result['rows'];

        return response()->streamDownload(function () use ($headers, $rows) {
            $output = fopen('php://output', 'wb');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, $headers);
            foreach ($rows as $row) {
                fputcsv($output, $row);
            }
            fclose($output);
        }, 'property-tax-owner-pii-' . now()->format('Ymd-His') . '.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-store, private, max-age=0',
            'Pragma' => 'no-cache',
            'Expires' => '0',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
