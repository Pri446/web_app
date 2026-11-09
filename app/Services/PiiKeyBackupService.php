<?php

namespace App\Services;

use App\Contracts\PiiKeyProvider;
use App\Models\PiiKeyBackupDownload;
use App\Models\User;
use Carbon\Carbon;
use Throwable;

class PiiKeyBackupService
{
    private PiiKeyProvider $keys;

    public function __construct(PiiKeyProvider $keys)
    {
        $this->keys = $keys;
    }

    public function canBackup(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        // This is intentionally limited to the two roles approved for raw-key
        // custody. Municipality - Super Admin is not included.
        return $user->hasAnyRole([
            'Super Admin',
            'Municipality - IT Admin',
        ]);
    }

    public function activeVersion(): string
    {
        return (string) config('pii.active_key_version', 'v1');
    }

    public function rawBase64Contents(): string
    {
        // PiiKeyProvider returns validated raw bytes. Base64 encoding makes the
        // downloaded text restorable by FilePiiKeyProvider, but does not add
        // encryption; authorization and endpoint controls are therefore vital.
        return base64_encode($this->keys->key($this->activeVersion()));
    }

    public function recordDownload(User $user): PiiKeyBackupDownload
    {
        return PiiKeyBackupDownload::create([
            'user_id' => $user->getKey(),
            'key_version' => $this->activeVersion(),
            'downloaded_at' => now(),
        ]);
    }

    public function statusFor(?User $user): ?array
    {
        if (!$this->canBackup($user)) {
            return null;
        }

        $intervalDays = max(
            1,
            (int) config('pii.key_backup.interval_days', 60)
        );
        $warningDays = min(
            $intervalDays,
            max(0, (int) config('pii.key_backup.warning_days', 5))
        );
        $version = $this->activeVersion();

        try {
            $latest = PiiKeyBackupDownload::query()
                ->with('user:id,name')
                ->where('key_version', $version)
                ->latest('downloaded_at')
                ->first();
        } catch (Throwable $exception) {
            // Keep unrelated pages usable before/while the migration is being
            // applied, but visibly report that reminder tracking is unavailable.
            report($exception);

            return [
                'state' => 'unavailable',
                'key_version' => $version,
                'interval_days' => $intervalDays,
                'warning_days' => $warningDays,
                'last_downloaded_at' => null,
                'last_downloaded_by' => null,
                'due_at' => null,
                'days_remaining' => null,
            ];
        }

        if (!$latest) {
            return [
                'state' => 'never',
                'key_version' => $version,
                'interval_days' => $intervalDays,
                'warning_days' => $warningDays,
                'last_downloaded_at' => null,
                'last_downloaded_by' => null,
                'due_at' => null,
                'days_remaining' => 0,
            ];
        }

        $lastDownloadedAt = Carbon::parse($latest->downloaded_at);
        $dueAt = $lastDownloadedAt->copy()->addDays($intervalDays);
        $secondsRemaining = now()->diffInSeconds($dueAt, false);
        $daysRemaining = $secondsRemaining > 0
            ? (int) ceil($secondsRemaining / 86400)
            : 0;

        if ($secondsRemaining <= 0) {
            $state = 'overdue';
        } elseif ($daysRemaining <= $warningDays) {
            $state = 'due_soon';
        } else {
            $state = 'current';
        }

        return [
            'state' => $state,
            'key_version' => $version,
            'interval_days' => $intervalDays,
            'warning_days' => $warningDays,
            'last_downloaded_at' => $lastDownloadedAt,
            'last_downloaded_by' => optional($latest->user)->name,
            'due_at' => $dueAt,
            'days_remaining' => $daysRemaining,
        ];
    }
}
