<?php

namespace App\Services;

use App\Models\User;

class ApplicationPiiAccessService
{
    public const ALL_APPLICATIONS_RESOURCE_ID = 'all-applications';

    private const SESSION_KEY = 'application_customer_pii_privileged_access';

    public function unlock(
        User $user,
        string $authenticationMethod = 'password_reentry',
        array $scopes = ['list', 'view'],
        ?string $resourceId = null
    ): void {
        $now = now();

        // Application PII receives its own session grant. It never inherits
        // the Building module's owner-PII unlock state. The grant may contain
        // an owner_lookup scope, but only after Application-specific password
        // re-entry and only for a user who can add Applications.
        session()->put(self::SESSION_KEY, [
            'user_id' => $user->getKey(),
            'authenticated_at' => $now->timestamp,
            'expires_at' => $now->copy()
                ->addMinutes((int) config('pii.unlock_minutes', 5))
                ->timestamp,
            'authentication_method' => $authenticationMethod,
            'scopes' => array_values(array_unique($scopes)),
            'resource_id' => $resourceId,
        ]);
    }

    public function lock(): void
    {
        session()->forget(self::SESSION_KEY);
    }

    public function canUnlock(?User $user): bool
    {
        return $user
            && $user->can('View Application Customer PII')
            && $user->can('Unlock Application Customer PII');
    }

    public function isUnlocked(
        User $user,
        string $scope = 'view',
        ?string $resourceId = null
    ): bool {
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

        if (!in_array($scope, $state['scopes'] ?? [], true)) {
            return false;
        }

        $granted = $state['resource_id'] ?? null;

        return $granted === null
            || $granted === self::ALL_APPLICATIONS_RESOURCE_ID
            || (string) $granted === (string) $resourceId;
    }

    public function secondsRemaining(
        User $user,
        string $scope = 'view',
        ?string $resourceId = null
    ): int {
        if (!$this->isUnlocked($user, $scope, $resourceId)) {
            return 0;
        }

        $state = session(self::SESSION_KEY, []);

        return max(0, (int) $state['expires_at'] - now()->timestamp);
    }

    public function authenticationMethod(
        User $user,
        string $scope = 'view',
        ?string $resourceId = null
    ): ?string {
        if (!$this->isUnlocked($user, $scope, $resourceId)) {
            return null;
        }

        return session(self::SESSION_KEY . '.authentication_method');
    }
}
