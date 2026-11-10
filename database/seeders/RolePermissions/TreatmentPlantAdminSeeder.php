<?php

namespace Database\Seeders\RolePermissions;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class TreatmentPlantAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        $roles = [
            [
                'name' => 'Treatment Plant - Admin',
            ]
          
        ];
        foreach ($roles as $role){
            $createdRole = Role::updateOrCreate($role);
            switch ($createdRole->name){
                case 'Treatment Plant - Admin':

                    // Containments Page permissions
                    $createdRole->givePermissionTo(Permission::all()->whereIn('group',['Containments'])
                    ->whereIn('name',['List Containments']));

                    $createdRole->givePermissionTo(Permission::all()->whereIn('group',['Service Providers','Desludging Vehicles','Applications','Emptyings'])
                    ->whereIn('type',['List','View'])->whereNotIn('type',['History']));

                    // Containments Page permissions
                    $createdRole->givePermissionTo(Permission::all()->whereIn('group',['Treatment Plants'])
                    ->whereIn('type',['List','View','View on map'])->whereNotIn('type',['History']));


                    // Treatment Plant Efficiency Tests permissions
                    $createdRole->givePermissionTo(Permission::all()->whereIn('group',['Treatment Plant Efficiency Tests'])->whereNotIn('type',['History']));

                    $createdRole->givePermissionTo(Permission::all()->whereIn('group',['Treatment Plant Efficiency Standards']));

                    // Sludge Collections
                    $createdRole->givePermissionTo(Permission::all()->whereIn('group',['Sludge Collections'])->whereNotIn('type',['History']));


                    // Building Dashboard permissions
                    $createdRole->givePermissionTo(Permission::all()->whereIn('group',[
                        'FSM Dashboard'])->whereIn('name',['Sludge Collection Trends by Treatment Plants Over the Last 5 Years Chart']));

                    // map tools data
                    $createdRole->givePermissionTo(Permission::all()->whereIn('group',['Maps'])->whereIn('name',[ 'Containments Map Layer', 'Sanitation System Map Layer']));
                    
                    // Mobile login
                    $createdRole->givePermissionTo(Permission::all()->whereIn('group',['API'])
                        ->where('name','Access Emptying Service API'));

                    // Mobile login
                    $createdRole->givePermissionTo(Permission::all()->whereIn('group',['API'])
                            ->where('name','Access Sludge Collection API'));
                        
                    break;

            }
        }
    }
}
