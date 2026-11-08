<?php

namespace App\Http\Controllers\BuildingInfo;

use App\Http\Controllers\Controller;
use App\Services\OwnerPiiAuditService;
use App\Services\OwnerPiiExportService;
use App\Services\PiiAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Throwable;

class OwnerPiiExportController extends Controller
{
    private OwnerPiiExportService $exporter;
    private OwnerPiiAuditService $audit;
    private PiiAccessService $piiAccess;

    public function __construct(
        OwnerPiiExportService $exporter,
        OwnerPiiAuditService $audit,
        PiiAccessService $piiAccess
    ) {
        $this->middleware('auth');
        $this->exporter = $exporter;
        $this->audit = $audit;
        $this->piiAccess = $piiAccess;
    }

    public function export(Request $request)
    {
        $this->authorizeExport($request);
        $maximumFileSize = max(
            1,
            (int) config('pii.export.max_file_kb', 2048)
        );
        $request->validate([
            'export_mode' => ['required', 'string', 'in:all,bin_list'],
            'bin_csv' => [
                'nullable',
                'required_if:export_mode,bin_list',
                'string',
                'max:' . ($maximumFileSize * 1024),
            ],
            // The checkbox exists only for the explicit full-export mode. Using
            // "sometimes" keeps the existing BIN-list request valid when the
            // field is not present at all.
            'confirm_export_all' => ['sometimes', 'accepted'],
            'export_password' => ['required', 'string', 'max:255'],
        ], [
            'export_mode.required' => __('Select an owner PII export mode.'),
            'export_mode.in' => __('The selected owner PII export mode is invalid.'),
            'bin_csv.required_if' => __('Select a valid BIN list CSV.'),
            'bin_csv.max' => __('The BIN list CSV is too large.'),
        ]);

        $user = $request->user();
        $exportMode = (string) $request->input('export_mode');

        // Full export is intentionally stricter than a selected-BIN export.
        // This project has no municipality_id on Owner/Building rows, so an
        // application-level row scope cannot be applied. Restricting the full
        // dataset to trusted administrative roles prevents ordinary export
        // users from expanding a bounded BIN export into a bulk disclosure.
        if ($exportMode === 'all' && !$this->canExportAllOwners($user)) {
            $this->audit->record(
                'pii_export_all_permission_denied',
                false,
                $user,
                null,
                [
                    'surface' => 'building_owner_pii_export',
                    'export_mode' => 'all',
                ],
                'authenticated_session'
            );

            abort(403, __('You are not authorized to export all owner PII.'));
        }

        // A separate acknowledgement prevents a missing/invalid CSV from ever
        // becoming an implicit request for the entire owner dataset.
        if ($exportMode === 'all' && !$request->boolean('confirm_export_all')) {
            throw ValidationException::withMessages([
                'confirm_export_all' => __(
                    'Confirm that you intend to export all authorized owner PII.'
                ),
            ]);
        }

        $password = (string) $request->input('export_password', '');
        $passwordHash = (string) ($user->password ?? '');

        if ($password === ''
            || $passwordHash === ''
            || !Hash::check($password, $passwordHash)) {
            $this->piiAccess->lock();
            $this->audit->record(
                'pii_export_password_failed',
                false,
                $user,
                null,
                ['surface' => 'building_owner_pii_export'],
                'password_reentry'
            );

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => __('Password confirmation failed.'),
                    'errors' => [
                        'export_password' => [
                            __('Password confirmation failed.'),
                        ],
                    ],
                ], 422);
            }

            return back()->withErrors([
                'export_password' => __('Password confirmation failed.'),
            ]);
        }

        try {
            if ($exportMode === 'all') {
                // This branch is explicit and role-gated. It is never used as
                // a fallback when the uploaded CSV is empty or invalid.
                $result = $this->exporter->rowsForAllOwners();
            } else {
                // Preserve the existing CSV parsing, validation, deduplication
                // and not-found reporting for selected BIN exports.
                $bins = $this->exporter->parseCsvText(
                    (string) $request->input('bin_csv')
                );
                $result = $this->exporter->rowsForBins($bins);
            }
        } catch (ValidationException $exception) {
            $this->audit->record(
                'pii_export_validation_failed',
                false,
                $user,
                null,
                [
                    'surface' => 'building_owner_pii_export',
                    'export_mode' => $exportMode,
                ],
                'password_reentry'
            );

            throw $exception;
        } catch (Throwable $exception) {
            $this->audit->record(
                'pii_export_failed',
                false,
                $user,
                null,
                [
                    'surface' => 'building_owner_pii_export',
                    'export_mode' => $exportMode,
                ],
                'password_reentry'
            );
            report($exception);

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => __('The owner PII export could not be generated.'),
                    'errors' => [
                        'bin_file' => [
                            __('The owner PII export could not be generated.'),
                        ],
                    ],
                ], 500);
            }

            return back()->withErrors([
                'bin_file' => __('The owner PII export could not be generated.'),
            ]);
        }

        $this->audit->record(
            'pii_exported',
            true,
            $user,
            null,
            [
                'surface' => 'building_owner_pii_export',
                'export_mode' => $exportMode,
                'requested_count' => $result['requested_count'],
                'exported_count' => $result['exported_count'],
                'missing_count' => $result['missing_count'],
            ],
            'password_reentry'
        );

        $headers = $this->exporter->headers();
        $rows = $result['rows'];
        $filename = 'owner-pii-export-' . $exportMode . '-'
            . now()->format('Ymd-His') . '.csv';

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
            $filename,
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
            && $user->can('List Building Structures')
            && $user->can('View Owner PII')
            && $user->can('Unlock Owner PII')
            && $user->can('Export Owner PII');

        if (!$allowed) {
            $this->audit->record(
                'pii_export_permission_denied',
                false,
                $user,
                null,
                ['surface' => 'building_owner_pii_export'],
                'authenticated_session'
            );
        }

        abort_unless($allowed, 403);
    }

    private function canExportAllOwners($user): bool
    {
        if (!$user) {
            return false;
        }

        // Keep this second gate in the controller even though the UI hides the
        // option. A caller can forge form fields, so browser visibility is not
        // an authorization boundary.
        return $user->hasAnyRole([
            'Super Admin',
            'Municipality - Super Admin',
            'Municipality - IT Admin',
        ]);
    }
}
