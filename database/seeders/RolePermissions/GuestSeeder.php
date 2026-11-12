<?php

namespace Database\Seeders\RolePermissions;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class GuestSeeder extends Seeder
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
                'name' => 'Guest',
            ],
        ];

        foreach ($roles as $role) {
            $createdRole = Role::updateOrCreate($role);
            switch ($createdRole->name) {
                case 'Guest':

                    //For Building Module
                    $createdRole->givePermissionTo(Permission::all()->whereIn('group', ['Building Structures'])->whereIn('type', ['List', 'View', 'View on map']));

                    //For Building Survey Module
                    $createdRole->givePermissionTo(Permission::all()->whereIn('group', ['Building Surveys'])->whereIn('type', ['List']));

                    //For FSM Module
                    $createdRole->givePermissionTo(Permission::all()->whereIn('group', ['Containments', 'Service Providers', 'Employee Infos','Desludging Vehicles', 'Treatment Plants','Treatment Plant Efficiency Tests', 'Applications', 'Sludge Collections', 'Feedbacks', 'Treatment Plant Efficiency Standards','Help Desks'])->whereIn('type', ['List', 'View']));

                    // Emptying records contain customer and operational data and
                    // must never be exposed to the read-only Guest role.
                    foreach (Permission::all()->where('group', 'Emptyings') as $permission) {
                        $createdRole->revokePermissionTo($permission);
                    }

                    //For PT/CT Module
                    $createdRole->givePermissionTo(Permission::all()->whereIn('group', ['PT/CT Toilets'])->whereIn('type', ['List', 'View', 'View on map']));

                    //For PT Users Log Module
                    $createdRole->givePermissionTo(Permission::all()->whereIn('group', ['PT Users Logs'])->whereIn('type', ['List', 'View']));

                    //For Utility Module
                    $createdRole->givePermissionTo(Permission::all()->whereIn('group', ['Roads', 'Sewers', 'WaterSupply Network', 'Drain'])->whereIn('type', ['List', 'View']));

                    //For Payment ISS module
                    $createdRole->givePermissionTo(Permission::all()->whereIn('group', ['Property Tax Collection ISS', 'Water Supply ISS'])->whereIn('type', ['List']));

                    //For Public Health Module
                    $createdRole->givePermissionTo(Permission::all()->whereIn('group', ['Hotspots', 'Yearly Waterborne Cases'])->whereIn('type', ['List', 'View', 'View on map']));

                    $createdRole->givePermissionTo(Permission::all()->whereIn('group', ['NSD Setting'])
                        ->whereIn('type', ['List']));

                    $createdRole->givePermissionTo(Permission::all()->whereIn('group', ['NSD'])
                        ->whereIn('type', ['Show']));

                    $createdRole->givePermissionTo(
                        Permission::all()->whereIn('group', ['Dashboard'])
                            ->whereIn('name', [
                                'Performance of Municipal Treatment Plants by Last 5 Years Chart',
                                'Yearly Distribution of Waterborne Disease Chart',
                                'Distribution of SWM Services by Ward Chart',
                                'Distribution of Water Supply Services by Ward Chart',
                                'Ward-Wise Revenue Collected from Emptying Services Chart',
                                'Building Connections to Sanitation System Types Chart',
                                'Public Health CountBox',
                                'PTCT CountBox',
                                'FSM CountBox',
                                'Utility CountBox'
                            ])
                    );

                    $createdRole->givePermissionTo(
                        Permission::all()->whereIn('group', ['Building Dashboard'])
                    );

                    //Dashboard For FSM
                    $createdRole->givePermissionTo(
                        Permission::all()->whereIn('group', ['FSM Dashboard'])
                    );

                    $createdRole->givePermissionTo(
                        Permission::all()->whereIn('group', ['Utility Dashboard'])
                    );

                    $createdRole->givePermissionTo(Permission::all()->whereIn('group', ['Maps'])

                    ->whereIn('name', ['Roads Map Layer', 'Sewers Line Map Layer', 'Drains Map Layer', 'WaterSupply Network Map Layer', 'Places Map Layer', 'Buildings Map Layer', 'Containments Map Layer', 'Treatment Plants', 'PT/CT Toilets Map Layer', 'Sanitation System Map Layer', 'Wards Map Layer', 'Summarized Grids Map Layer', 'Water Body Map Layer', 'Land Use Map Layer', 'Service Delivery Map Tools', 'Applications Map Tools', 'Emptied Applications Not Reached to TP Map Tools', 'Containments Proposed To Be Emptied Map Tools', 'Service Feedback Map Tools', 'Treatment Plants Map Layer','Low Income Community Map Layer', 'Water Samples Map Layer', 'Info Map Tools', 'Decision Map Tools', 'Sewer Potential Map Tools', 'Buildings to Sewer Map Tools', 'Buildings to Road Map Tools','Hard to Reach Buildings Map Tools', 'Building Close to Water Bodies Map Tools', 'Community Toilets Map Tools', 'Area Population Map Tools', 'Summary Information Buffer Map Tools', 'Summary Information Water Bodies Map Tools', 'Summary Information Wards Map Tools', 'Summary Information Road Map Tools', 'Summary Information Point Map Tools', 'Public Health Map Layer', 'Info Map Tools', 'Municipality Map Layer', 'Ward Boundary Map Layer', 'Containments Emptied Info Map Tools', 
                            'Toilet Isochrone Map Tools'
                           ]));

                    break;
            }
        }
    }
}
