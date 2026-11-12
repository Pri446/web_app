<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RevokeGuestAccessToEmptyings extends Migration
{
    /**
     * Remove permissions already assigned in deployed databases. Updating the
     * seeder alone would leave existing Guest roles vulnerable.
     */
    public function up()
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $guest = Role::where('name', 'Guest')->first();

        if ($guest) {
            foreach (Permission::where('group', 'Emptyings')->get() as $permission) {
                $guest->revokePermissionTo($permission);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * This security migration is intentionally irreversible. Restoring Guest
     * access to sensitive emptying records would recreate the vulnerability.
     */
    public function down()
    {
        // No-op.
    }
}
