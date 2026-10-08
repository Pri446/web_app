<?php

namespace App\Services\Fsm;

use App\Models\Fsm\Application;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;

/**
 * Applies the simple FSM data-segregation rule agreed with the technical lead.
 * Permission middleware decides whether an action is allowed; this service
 * limits which organisation's records the three operational roles may access.
 */
class OperationalDataAccessService
{
    private const SERVICE_PROVIDER_ROLES = [
        'Service Provider - Admin',
        'Service Provider - Emptying Operator',
    ];

    public static function isServiceProviderUser(User $user): bool
    {
        return $user->hasAnyRole(self::SERVICE_PROVIDER_ROLES);
    }

    public static function isTreatmentPlantUser(User $user): bool
    {
        return $user->hasRole('Treatment Plant - Admin');
    }

    public static function scopeQuery(
        $query,
        string $serviceProviderColumn,
        string $treatmentPlantColumn,
        ?User $user = null
    ) {
        $user = $user ?: auth()->user();

        if (self::isServiceProviderUser($user)) {
            return $query->where($serviceProviderColumn, $user->service_provider_id ?? -1);
        }

        if (self::isTreatmentPlantUser($user)) {
            return $query->where($treatmentPlantColumn, $user->treatment_plant_id ?? -1);
        }

        return $query;
    }

    /**
     * Applications store their SP directly, while disposal TP is stored on
     * Emptying. A missing disposal place is therefore hidden from TP users.
     */
    public static function scopeApplicationQuery(
        EloquentBuilder $query,
        ?User $user = null
    ): EloquentBuilder {
        $user = $user ?: auth()->user();

        if (self::isServiceProviderUser($user)) {
            return $query->where(
                'applications.service_provider_id',
                $user->service_provider_id ?? -1
            );
        }

        if (self::isTreatmentPlantUser($user)) {
            return $query->whereHas('emptying', function ($emptyingQuery) use ($user) {
                $emptyingQuery->where(
                    'treatment_plant_id',
                    $user->treatment_plant_id ?? -1
                );
            });
        }

        return $query;
    }

    /** Stop direct-ID access when a record is outside the linked SP or TP. */
    public static function authorizeRecord(
        ?int $serviceProviderId,
        ?int $treatmentPlantId,
        ?User $user = null
    ): void {
        $user = $user ?: auth()->user();

        if (self::isServiceProviderUser($user)) {
            abort_unless(
                $user->service_provider_id !== null
                && (int) $user->service_provider_id === (int) $serviceProviderId,
                403
            );
        }

        if (self::isTreatmentPlantUser($user)) {
            abort_unless(
                $user->treatment_plant_id !== null
                && $treatmentPlantId !== null
                && (int) $user->treatment_plant_id === (int) $treatmentPlantId,
                403
            );
        }
    }

    public static function authorizeApplication(
        Application $application,
        ?User $user = null
    ): void {
        $treatmentPlantId = $application->emptying()
            ->whereNull('deleted_at')
            ->value('treatment_plant_id');

        self::authorizeRecord(
            $application->service_provider_id,
            $treatmentPlantId,
            $user
        );
    }
}
