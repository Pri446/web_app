<?php

namespace App\Services;

use App\Models\User;

class PiiAccessService
{
    public const ALL_OWNERS_RESOURCE_ID = 'all-owners';

    private const SESSION_KEY = 'owner_pii_privileged_access';
    private const LEGACY_SESSION_USER_KEY = 'owner_pii_unlocked_user_id';
    private const LEGACY_SESSION_EXPIRY_KEY = 'owner_pii_unlocked_until';

    public function unlock(
        User $user,
        string $authenticationMethod = 'authenticated_session',
        array $scopes = ['view', 'edit'],
        ?string $resourceId = null
    ): void
    {
        $now = now();

        session()->put(self::SESSION_KEY, [
            'user_id' => $user->getKey(),
            'authenticated_at' => $now->timestamp,
            'expires_at' => $now
                ->copy()
                ->addMinutes((int) config('pii.unlock_minutes', 5))
                ->timestamp,
            'authentication_method' => $authenticationMethod,
            'scopes' => array_values(array_unique($scopes)),
            'resource_id' => $resourceId,
        ]);

        session()->forget([
            self::LEGACY_SESSION_USER_KEY,
            self::LEGACY_SESSION_EXPIRY_KEY,
        ]);
    }

    public function lock(): void
    {
        session()->forget([
            self::SESSION_KEY,
            self::LEGACY_SESSION_USER_KEY,
            self::LEGACY_SESSION_EXPIRY_KEY,
        ]);
    }

    public function canUnlock(?User $user): bool
    {
        return $user
            && $user->can('View Owner PII')
            && $user->can('Unlock Owner PII');
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

        if (!is_array($state)) {
            return false;
        }

        if ((string) ($state['user_id'] ?? '') !== (string) $user->getKey()) {
            return false;
        }

        if ((int) ($state['expires_at'] ?? 0) <= now()->timestamp) {
            $this->lock();

            return false;
        }

        if (!in_array($scope, $state['scopes'] ?? [], true)) {
            return false;
        }

        $grantedResourceId = $state['resource_id'] ?? null;

        if ($grantedResourceId !== null
            && $grantedResourceId !== self::ALL_OWNERS_RESOURCE_ID
            && (string) $grantedResourceId !== (string) $resourceId) {
            return false;
        }

        return true;
    }

    public function secondsRemaining(
        User $user,
        string $scope = 'view',
        ?string $resourceId = null
    ): int
    {
        if (!$this->isUnlocked($user, $scope, $resourceId)) {
            return 0;
        }

        $state = session(self::SESSION_KEY, []);

        return max(
            0,
            (int) ($state['expires_at'] ?? 0) - now()->timestamp
        );
    }

    public function authenticationMethod(
        User $user,
        string $scope = 'view',
        ?string $resourceId = null
    ): ?string {
        if (!$this->isUnlocked($user, $scope, $resourceId)) {
            return null;
        }

        $state = session(self::SESSION_KEY, []);

        return isset($state['authentication_method'])
            ? (string) $state['authentication_method']
            : null;
    }
}
