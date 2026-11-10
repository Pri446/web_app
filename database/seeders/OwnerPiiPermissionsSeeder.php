<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class OwnerPiiPermissionsSeeder extends Seeder
{
    public function run()
    {
        $permissions = [
            ['name' => 'View Owner PII', 'type' => 'View PII'],
            ['name' => 'Unlock Owner PII', 'type' => 'Unlock PII'],
            ['name' => 'Export Owner PII', 'type' => 'Export PII'],
        ];

        foreach ($permissions as $permission) {
            Permission::updateOrCreate(
                [
                    'name' => $permission['name'],
                    'guard_name' => 'web',
                ],
                [
                    'group' => 'Building Structures',
                    'type' => $permission['type'],
                ]
            );
        }
    }
}
