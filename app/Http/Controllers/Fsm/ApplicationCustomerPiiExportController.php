<?php

namespace App\Http\Controllers\Fsm;

use App\Http\Controllers\Controller;
use App\Services\ApplicationCustomerPiiExportService;
use App\Services\ApplicationPiiAccessService;
use App\Services\OwnerPiiAuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Throwable;

class ApplicationCustomerPiiExportController extends Controller
{
    private ApplicationCustomerPiiExportService $exporter;
    private OwnerPiiAuditService $audit;
    private ApplicationPiiAccessService $piiAccess;

    public function __construct(
        ApplicationCustomerPiiExportService $exporter,
        OwnerPiiAuditService $audit,
        ApplicationPiiAccessService $piiAccess
    ) {
        $this->middleware('auth');
        $this->exporter = $exporter;
        $this->audit = $audit;
        $this->piiAccess = $piiAccess;
    }

    public function export(Request $request)
    {
        $this->authorizeExport($request);
        $maximumFileSize = max(1, (int) config('pii.export.max_file_kb', 2048));
        $request->validate([
            'application_export_mode' => ['required', 'string', 'in:all,bin_list'],
            'application_bin_csv' => [
                'nullable',
                'required_if:application_export_mode,bin_list',
                'string',
                'max:' . ($maximumFileSize * 1024),
            ],
            'confirm_application_export_all' => ['sometimes', 'accepted'],
            'application_export_password' => ['required', 'string', 'max:255'],
        ], [
            'application_export_mode.required' => __('Select an Application customer PII export mode.'),
            'application_export_mode.in' => __('The selected Application customer PII export mode is invalid.'),
            'application_bin_csv.required_if' => __('Select a valid BIN list CSV.'),
            'application_bin_csv.max' => __('The BIN list CSV is too large.'),
        ]);

        $user = $request->user();
        $exportMode = (string) $request->input('application_export_mode');

        // Full-dataset export has a stricter role gate than a bounded BIN
        // export. The check is server-side because hiding the radio option in
        // the browser is not an authorization boundary.
        if ($exportMode === 'all' && !$this->canExportAllApplications($user)) {
            $this->audit->record(
                'application_pii_export_all_permission_denied',
                false,
                $user,
                null,
                [
                    'surface' => 'application_customer_pii_export',
                    'export_mode' => 'all',
                ],
                'authenticated_session'
            );

            abort(403, __('You are not authorized to export all Application customer PII.'));
        }

        // Require a separate acknowledgement so a missing or invalid BIN CSV
        // can never become an implicit full export request.
        if ($exportMode === 'all'
            && !$request->boolean('confirm_application_export_all')) {
            throw ValidationException::withMessages([
                'confirm_application_export_all' => __(
                    'Confirm that you intend to export all authorized Application customer PII.'
                ),
            ]);
        }

        $password = (string) $request->input('application_export_password', '');

        if ($password === ''
            || empty($user->password)
            || !Hash::check($password, (string) $user->password)) {
            $this->piiAccess->lock();
            $this->audit->record(
                'application_pii_export_password_failed',
                false,
                $user,
                null,
                [
                    'surface' => 'application_customer_pii_export',
                    'export_mode' => $exportMode,
                ],
                'password_reentry'
            );

            return response()->json([
                'message' => __('Password confirmation failed.'),
                'errors' => [
                    'application_export_password' => [__('Password confirmation failed.')],
                ],
            ], 422);
        }

        try {
            if ($exportMode === 'all') {
                $result = $this->exporter->rowsForAllApplications($user);
            } else {
                // Selected mode is always driven by a validated BIN file; it
                // never falls back to the full export branch.
                $bins = $this->exporter->parseBinCsvText(
                    (string) $request->input('application_bin_csv')
                );
                $result = $this->exporter->rowsForBins($bins, $user);
            }
        } catch (ValidationException $exception) {
            $this->audit->record(
                'application_pii_export_validation_failed',
                false,
                $user,
                null,
                [
                    'surface' => 'application_customer_pii_export',
                    'export_mode' => $exportMode,
                ],
                'password_reentry'
            );

            throw $exception;
        } catch (Throwable $exception) {
            $this->audit->record(
                'application_pii_export_failed',
                false,
                $user,
                null,
                [
                    'surface' => 'application_customer_pii_export',
                    'export_mode' => $exportMode,
                ],
                'password_reentry'
            );
            report($exception);

            return response()->json([
                'message' => __('The Application customer PII export could not be generated.'),
            ], 500);
        }

        $this->audit->record(
            'application_customer_pii_exported',
            true,
            $user,
            null,
            [
                'surface' => 'application_customer_pii_export',
                'export_mode' => $exportMode,
                'requested_count' => $result['requested_count'],
                'exported_count' => $result['exported_count'],
                'missing_count' => $result['missing_count'],
            ],
            'password_reentry'
        );

        $headers = $this->exporter->headers();
        $rows = $result['rows'];

        return response()->streamDownload(
            function () use ($headers, $rows) {
                $output = fopen('php://output', 'wb');
                fwrite($output, "\xEF\xBB\xBF");
                fputcsv($output, $headers);

                foreach ($rows as $row) {
                    fputcsv($output, $row);
                }

                fclose($output);
            },
            'application-customer-pii-export-' . $exportMode . '-'
                . now()->format('Ymd-His') . '.csv',
            [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Cache-Control' => 'no-store, private, max-age=0',
                'Pragma' => 'no-cache',
                'Expires' => '0',
                'X-Content-Type-Options' => 'nosniff',
            ]
        );
    }

    private function authorizeExport(Request $request): void
    {
        $user = $request->user();
        $allowed = $user
            && $user->can('List Applications')
            && $user->can('View Application Customer PII')
            && $user->can('Unlock Application Customer PII')
            && $user->can('Export Application Customer PII');

        if (!$allowed) {
            $this->audit->record(
                'application_pii_export_permission_denied',
                false,
                $user,
                null,
                ['surface' => 'application_customer_pii_export'],
                'authenticated_session'
            );
        }

        abort_unless($allowed, 403);
    }

    private function canExportAllApplications($user): bool
    {
        if (!$user) {
            return false;
        }

        return $user->hasAnyRole([
            'Super Admin',
            'Municipality - Super Admin',
            'Municipality - IT Admin',
        ]);
    }
}
