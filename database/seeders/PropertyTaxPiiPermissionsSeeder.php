<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PropertyTaxPiiPermissionsSeeder extends Seeder
{
    public function run()
    {
        foreach ([
            ['name' => 'View Property Tax Owner PII', 'type' => 'View Owner PII'],
            ['name' => 'Unlock Property Tax Owner PII', 'type' => 'Unlock Owner PII'],
            ['name' => 'Export Property Tax Owner PII', 'type' => 'Export Owner PII'],
        ] as $permission) {
            Permission::updateOrCreate(
                ['name' => $permission['name'], 'guard_name' => 'web'],
                ['group' => 'Property Tax Collection ISS', 'type' => $permission['type']]
            );
        }
    }
}
