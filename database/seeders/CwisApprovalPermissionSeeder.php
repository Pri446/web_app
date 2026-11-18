<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class CwisApprovalPermissionSeeder extends Seeder
{
    public function run()
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        DB::transaction(function () {
            $permission = Permission::firstOrCreate(
                ['name' => 'Approve CWIS', 'guard_name' => 'web'],
                ['group' => 'CWIS', 'type' => 'Approve']
            );
            $role = Role::where('name', 'Municipality - Super Admin')->where('guard_name', 'web')->first();
            if ($role) {
                // Add only the missing approval grant; preserve all other role assignments.
                $role->givePermissionTo($permission);
            }
        });
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
