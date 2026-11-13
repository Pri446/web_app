<?php

namespace Database\Seeders\RolePermissions;
use Spatie\Permission\Models\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
class ServiceProviderAdminSeeder extends Seeder
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
                'name' => 'Service Provider - Admin',
            ],
        ];
        foreach ($roles as $role){
            $createdRole = Role::updateOrCreate($role);
            switch ($createdRole->name){
                case 'Service Provider - Admin':
                    $createdRole->givePermissionTo(Permission::all()->whereIn('group',['Sludge Collections', 'Feedbacks', 'Building Structures',  'Low Income Communities', 'KPI Target' ])
                    ->whereIn('type',['View','List','View on map','Edit']));
                    $createdRole->givePermissionTo(Permission::all()->whereIn('group',['Containments'])
                     ->whereIn('type',['View','List','View on map', 'Export', 'Service History']));
                    $createdRole->givePermissionTo(Permission::all()->whereIn('group',['Applications','Emptyings'])
                    ->whereIn('type',['View','List','Export']));
                    $createdRole->givePermissionTo(Permission::all()->whereIn('group',['Schedule Desludging','Schedule Reintegration']));
                    $createdRole->givePermissionTo(Permission::all()->whereIn('group',['KPI Dashboard']));
                    $createdRole->givePermissionTo(Permission::all()->whereIn('group',['Help Desks','Desludging Vehicles','Employee Infos'])->whereNotIn('type',['History']));
                     $createdRole->givePermissionTo(Permission::all()->whereIn('group',['Treatment Plants'])->whereIn('type',['View','List','View on map','Export']));
                    $createdRole->givePermissionTo(Permission::all()->whereIn('group', ['Roads'])->whereIn('type', ['List', 'View', 'View on map']));
                    $createdRole->givePermissionTo(Permission::all()->whereIn('group',['Maps'])
                    ->whereIn('name',['Roads Map Layer','Sewers Line Map Layer','Drains Map Layer','WaterSupply Network Map Layer','Places Map Layer', 'Buildings Map Layer', 'Containments Map Layer','Sanitation System Map Layer', 'Water Body Map Layer', 'Land Use Map Layer','Service Delivery Map Tools','Applications Map Tools','Emptied Applications Not Reached to TP Map Tools','Containments Proposed To Be Emptied Map Tools','Service Feedback Map Tools',
                    'View Nearest Road To Containment On Map','View Nearest Road To Building On Map','View Road On Map','Treatment Plants Map Layer','Low Income Status Map Layer','Info Map Tools',
                'Buildings to Road Map Tools','Hard to Reach Buildings Map Tools','Decision Map Tools','Data Export Map Tools', 'Low Income Community Map Layer','PT/CT Toilets Map Layer','Ward Boundary Map Layer','Municipality Map Layer',
                
            ]));
                   
                    
                   
                   $createdRole->givePermissionTo(Permission::all()->whereIn('group', ['Dashboard'])
                   ->whereIn('name', [
                          'Ward-Wise Revenue Collected from Emptying Services Chart','Building Connections to Sanitation System Types Chart','FSM CountBox'
                                       
                    
               ])
               );
                   $createdRole->givePermissionTo(Permission::all()->whereIn('group', ['Building Dashboard'])
                   ->whereIn('name', [
                          'Building Use Composition Chart',
                                       'Ward-Wise Distribution of Buildings Chart','Sanitation CountBox','Building CountBox',
                    
               ])
               );
               $createdRole->givePermissionTo(Permission::all()->whereIn('group', ['FSM Dashboard'])
               ->whereIn('name', [
                      'Containment Type-Wise Emptying Services Over the Last 5 Years Chart','Sanitation Worker Compliance with PPE Guidelines Chart','Customer Satisfaction with FSM Service Quality Chart','Ward-Wise Distribution of Emptying Requests for the Next Four Weeks Chart',
                      'Emptying Requests for the Next Four Weeks Chart','Monthly Emptying Requests Processed by Service Providers Chart','Monthly Emptying Requests Processed by Service Providers Chart','Summary of Applications, Emptying Services, Sludge Disposal, and Feedback by Ward Chart',
                      'Containment Types Categorized by Land Use Chart','Containment Types Categorized by Building Usage Chart','Containment Types Categorized by Building Usage Chart','Ward-Wise Distribution of Containment Types in Residential Buildings Chart',
                      'Ward-Wise Distribution of Containment Types Chart','Proportion of Different Containment Types Chart','FSM Dashboard CountBox'
                
           ])
           );
           ///User Information Managaement///
           $createdRole->givePermissionTo(Permission::all()->whereIn('group', ['Users'])
           ->whereIn('type', [
               'List',
               'View',
               'Add',
               'Edit',
               'Delete',
               'Activity'
           ]));

           $createdRole->givePermissionTo(Permission::where('group','Schedule Desludging')->get());
           $createdRole->givePermissionTo(Permission::where('group','Schedule Reintegration')->get());

        break;
        }
        }
    }
}
