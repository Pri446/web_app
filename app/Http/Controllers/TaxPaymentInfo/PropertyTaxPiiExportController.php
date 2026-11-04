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
            'export_mode' => ['required', 'string', 'in:all,bin_list'],
            'bin_csv' => [
                'nullable',
                'required_if:export_mode,bin_list',
                'string',
                'max:' . ($maximumFileSize * 1024),
            ],
            'confirm_export_all' => ['sometimes', 'accepted'],
            'property_tax_export_password' => ['required', 'string', 'max:255'],
        ], [
            'export_mode.required' => __('Select an owner PII export mode.'),
            'export_mode.in' => __('The selected owner PII export mode is invalid.'),
            'bin_csv.required_if' => __('Select a valid BIN list CSV.'),
            'bin_csv.max' => __('The BIN list CSV is too large.'),
        ]);

        $exportMode = (string) $request->input('export_mode');

        // A full Property Tax PII export has the same higher-trust boundary as
        // the Building full export. The server checks this even when a caller
        // bypasses or modifies the browser form.
        if ($exportMode === 'all' && !$this->canExportAllRecords($user)) {
            $this->audit->record(
                'property_tax_pii_export_all_permission_denied',
                false,
                $user,
                null,
                ['surface' => 'property_tax_pii_export', 'export_mode' => 'all'],
                'authenticated_session'
            );

            abort(403, __('You are not authorized to export all Property Tax owner PII.'));
        }

        // Never treat an absent or invalid BIN file as an implicit full export.
        // The administrator must explicitly choose and acknowledge bulk export.
        if ($exportMode === 'all' && !$request->boolean('confirm_export_all')) {
            throw ValidationException::withMessages([
                'confirm_export_all' => __(
                    'Confirm that you intend to export all authorized owner PII.'
                ),
            ]);
        }

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
            if ($exportMode === 'all') {
                $result = $this->exporter->rowsForAllRecords();
            } else {
                $bins = $this->exporter->parseCsvText(
                    (string) $request->input('bin_csv')
                );
                $result = $this->exporter->rowsForBins($bins);
            }
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            $this->audit->record(
                'property_tax_pii_export_failed',
                false,
                $user,
                null,
                [
                    'surface' => 'property_tax_pii_export',
                    'export_mode' => $exportMode,
                ],
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
                'export_mode' => $exportMode,
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
        }, 'property-tax-owner-pii-' . $exportMode . '-' . now()->format('Ymd-His') . '.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-store, private, max-age=0',
            'Pragma' => 'no-cache',
            'Expires' => '0',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function canExportAllRecords($user): bool
    {
        return $user && $user->hasAnyRole([
            'Super Admin',
            'Municipality - Super Admin',
            'Municipality - IT Admin',
        ]);
    }
}
