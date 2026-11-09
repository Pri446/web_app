<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class ApplicationPiiPermissionsSeeder extends Seeder
{
    public function run()
    {
        foreach ([
            ['name' => 'View Application Customer PII', 'type' => 'View Customer PII'],
            ['name' => 'Unlock Application Customer PII', 'type' => 'Unlock Customer PII'],
            ['name' => 'Export Application Customer PII', 'type' => 'Export Customer PII'],
        ] as $permission) {
            Permission::updateOrCreate(
                ['name' => $permission['name'], 'guard_name' => 'web'],
                ['group' => 'Applications', 'type' => $permission['type']]
            );
        }
    }
}
