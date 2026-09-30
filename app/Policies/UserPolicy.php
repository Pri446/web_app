<?php

namespace App\Policies;

use App\Models\User;

/**
 * Record-level authorization rules for User records.
 *
 * A new developer should think of authorization as two separate checks:
 *
 * 1. The permission middleware on UserController checks whether the logged-in
 *    user may use an action in general, for example "View User".
 * 2. This policy checks whether that user may access the PARTICULAR User
 *    record requested through /auth/users/{id}.
 *
 * We need both checks. A general permission by itself does not prevent a user
 * from changing {id} and requesting a record belonging to another service
 * provider or treatment plant (an IDOR vulnerability).
 */
class UserPolicy
{
    /**
     * Decide whether the logged-in user may view the requested user record.
     *
     * Laravel supplies both arguments automatically when the controller calls:
     *
     *     $this->authorize('view', $userDetail);
     *
     * @param User $actor  The currently logged-in user making the request.
     * @param User $target The User record selected by the {id} in the URL.
     */
    public function view(User $actor, User $target): bool
    {
        // Super Admin is allowed to view records across organizational scopes.
        if ($actor->hasRole('Super Admin')) {
            return true;
        }

        /*
         * The policy can be called from controllers, jobs, commands, or future
         * API endpoints. For that reason, it also verifies the action-specific
         * permission instead of relying only on UserController middleware.
         *
         * Do NOT replace this with a check for "any Users permission". A user
         * who has only "Add User", "Delete User", or "Export Users to CSV"
         * must not automatically receive permission to view user details.
         */
        if (!$actor->can('View User')) {
            return false;
        }

        // A user with View User permission may view their own record.
        if ($actor->id === $target->id) {
            return true;
        }

        /*
         * Service-provider users may view another user only when both users
         * belong to the same service provider. The explicit null check is
         * important: two users with a null service_provider_id must NOT be
         * treated as belonging to the same organization.
         */
        if (
            $actor->service_provider_id !== null
            && $actor->service_provider_id === $target->service_provider_id
        ) {
            return true;
        }

        /*
         * Treatment-plant users follow the same rule: the actor must actually
         * belong to a treatment plant, and the target must belong to that exact
         * same treatment plant.
         */
        if (
            $actor->treatment_plant_id !== null
            && $actor->treatment_plant_id === $target->treatment_plant_id
        ) {
            return true;
        }

        // No rule matched, so access is denied. Laravel converts this to 403.
        return false;
    }
}
