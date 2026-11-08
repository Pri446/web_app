<?php

namespace App\Http\Controllers\Security;

use App\Http\Controllers\Controller;
use App\Services\OwnerPiiAuditService;
use App\Services\PiiKeyBackupService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Throwable;

class PiiKeyBackupController extends Controller
{
    private PiiKeyBackupService $backups;
    private OwnerPiiAuditService $audit;

    public function __construct(
        PiiKeyBackupService $backups,
        OwnerPiiAuditService $audit
    ) {
        $this->middleware('auth');
        $this->backups = $backups;
        $this->audit = $audit;
    }

    public function download(Request $request)
    {
        $user = $request->user();

        if (!$this->backups->canBackup($user)) {
            $this->audit->record(
                'pii_key_backup_permission_denied',
                false,
                $user,
                null,
                ['surface' => 'pii_key_backup'],
                'authenticated_session'
            );

            abort(403, __('You are not authorized to back up the PII key.'));
        }

        $request->validate([
            'current_password' => ['required', 'string', 'max:255'],
            'acknowledge_raw_key_risk' => ['required', 'accepted'],
        ], [
            'acknowledge_raw_key_risk.accepted' => __(
                'Confirm that you will move the key to approved secure storage.'
            ),
        ]);

        $password = (string) $request->input('current_password', '');

        if ($password === ''
            || empty($user->password)
            || !Hash::check($password, (string) $user->password)) {
            $this->audit->record(
                'pii_key_backup_password_failed',
                false,
                $user,
                null,
                ['surface' => 'pii_key_backup'],
                'password_reentry'
            );

            return response()->json([
                'message' => __('Password confirmation failed.'),
                'errors' => [
                    'current_password' => [__('Password confirmation failed.')],
                ],
            ], 422);
        }

        try {
            // Read the key only after every authorization and authentication
            // check succeeds. It is never added to session, logs, or metadata.
            $contents = $this->backups->rawBase64Contents();
            $record = $this->backups->recordDownload($user);
        } catch (Throwable $exception) {
            $this->audit->record(
                'pii_key_backup_failed',
                false,
                $user,
                null,
                [
                    'surface' => 'pii_key_backup',
                    'key_version' => $this->backups->activeVersion(),
                ],
                'password_reentry'
            );
            report($exception);

            return response()->json([
                'message' => __('The PII key backup could not be generated.'),
            ], 500);
        }

        $this->audit->record(
            'pii_key_backup_downloaded',
            true,
            $user,
            null,
            [
                'surface' => 'pii_key_backup',
                'key_version' => $record->key_version,
                'backup_format' => 'raw_base64',
            ],
            'password_reentry'
        );

        $filename = 'pii-key-backup-' . $record->key_version . '-'
            . now()->format('Ymd-His') . '.key';

        return response()->streamDownload(
            function () use ($contents) {
                echo $contents;
            },
            $filename,
            [
                'Content-Type' => 'application/octet-stream',
                'Cache-Control' => 'no-store, private, max-age=0',
                'Pragma' => 'no-cache',
                'Expires' => '0',
                'X-Content-Type-Options' => 'nosniff',
            ]
        );
    }
}
