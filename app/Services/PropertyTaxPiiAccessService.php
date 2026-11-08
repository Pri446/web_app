<?php

namespace App\Services;

use App\Models\User;

class PropertyTaxPiiAccessService
{
    private const SESSION_KEY = 'property_tax_owner_pii_privileged_access';

    public function unlock(User $user): void
    {
        session()->put(self::SESSION_KEY, [
            'user_id' => $user->getKey(),
            'expires_at' => now()->addMinutes(
                (int) config('pii.unlock_minutes', 5)
            )->timestamp,
        ]);
    }

    public function lock(): void
    {
        session()->forget(self::SESSION_KEY);
    }

    public function canUnlock(?User $user): bool
    {
        return $user
            && $user->can('View Property Tax Owner PII')
            && $user->can('Unlock Property Tax Owner PII');
    }

    public function isUnlocked(User $user): bool
    {
        if (!$this->canUnlock($user)) {
            return false;
        }

        $state = session(self::SESSION_KEY, []);

        if (!is_array($state)
            || (string) ($state['user_id'] ?? '') !== (string) $user->getKey()
            || (int) ($state['expires_at'] ?? 0) <= now()->timestamp) {
            $this->lock();

            return false;
        }

        return true;
    }

    public function secondsRemaining(User $user): int
    {
        if (!$this->isUnlocked($user)) {
            return 0;
        }

        return max(
            0,
            (int) session(self::SESSION_KEY . '.expires_at') - now()->timestamp
        );
    }
}
