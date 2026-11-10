<?php

namespace App\Services;

use App\Models\User;

class UserRecordAccessService
{
    /** Preserve the removed UserPolicy's permission and organization boundaries. */
    public static function authorizeView(User $target, ?User $actor = null): void
    {
        $actor = $actor ?: auth()->user();
        abort_unless($actor, 403);

        if ($actor->hasRole('Super Admin')) {
            return;
        }

        $isSelf = $actor->getKey() !== null && $target->getKey() !== null
            && (string) $actor->getKey() === (string) $target->getKey();
        $sameProvider = $actor->service_provider_id !== null
            && $target->service_provider_id !== null
            && (string) $actor->service_provider_id === (string) $target->service_provider_id;
        $samePlant = $actor->treatment_plant_id !== null
            && $target->treatment_plant_id !== null
            && (string) $actor->treatment_plant_id === (string) $target->treatment_plant_id;

        abort_unless($actor->can('View User') && ($isSelf || $sameProvider || $samePlant), 403);
    }
}
