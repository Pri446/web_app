<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        // Centralize implicit application permissions here so every controller,
        // service, route check and Blade @can statement receives the same result.
        // This avoids a security mismatch where a button is visible but its
        // endpoint denies the request, or an endpoint allows an action that the
        // interface hides.
        Gate::before(function ($user, $ability) {
            // Preserve the existing system-wide Super Admin override.
            if ($user->hasRole('Super Admin')) {
                return true;
            }

            // PII access follows the approved module-level eligibility rule:
            // users trusted to create/import a module's source records may also
            // reveal and export that module's PII. Existing controllers still
            // enforce List permission, password re-entry, temporary reveal
            // sessions, audit logging and full-dataset administrative roles.
            $piiPermissionMapping = [
                // Building owner PII is derived from Add Building eligibility.
                'View Owner PII' => 'Add Building Structure',
                'Unlock Owner PII' => 'Add Building Structure',
                'Export Owner PII' => 'Add Building Structure',

                // Application customer PII is derived from Add Application.
                'View Application Customer PII' => 'Add Application',
                'Unlock Application Customer PII' => 'Add Application',
                'Export Application Customer PII' => 'Add Application',

                // Property Tax owner PII is derived from CSV-import eligibility.
                'View Property Tax Owner PII'
                    => 'Import Property Tax Collection From CSV',
                'Unlock Property Tax Owner PII'
                    => 'Import Property Tax Collection From CSV',
                'Export Property Tax Owner PII'
                    => 'Import Property Tax Collection From CSV',
            ];

            if (isset($piiPermissionMapping[$ability])) {
                // Never call $user->can() from Gate::before: doing so would call
                // this Gate again and recurse. hasPermissionTo() checks the
                // underlying Spatie role/direct assignment without re-entering
                // Laravel's Gate pipeline.
                //
                // Grant immediately when the approved base permission exists.
                // When it does not, return null rather than false so existing
                // dedicated PII assignments and later policies/test callbacks
                // can still make their normal decision. The new rule is an
                // additional eligibility path, not a destructive permission
                // migration for users who were already explicitly authorized.
                return $user->hasPermissionTo(
                    $piiPermissionMapping[$ability]
                ) ? true : null;
            }

            // Returning null lets normal Spatie/Laravel authorization decide
            // every ability not covered by the mappings above.
            return null;
        });
    }
}
