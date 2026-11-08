<?php
// Last Modified Date: 07-05-2024
// Developed By: Innovative Solution Pvt. Ltd. (ISPL)
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class PermissionsSeeder extends Seeder
{
    /**
     * HERE ARE THE PERMISSION TYPES CURRENTLY IN THE SYSTEM
     * FEEL FREE TO ADD NEW ONES AND UPDATE THIS LIST RESPECTIVELY AS WELL
     *
     * "Add"
     * "API - Emptying"
     * "API - Supervisor"
     * "API - Survey"
     * "Chart"
     * "Delete"
     * "Download"
     * "Edit"
     * "Export"
     * "History"
     * "Import"
     * "List"
     * "Map Layer"
     * "Map Tool"
     * "Miscellaneous"
     * "View"
     * "View on map"
     *
     */

    public function run()
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $grouped_permissions = [
            [
                "group" => "Users",
                "perms" => [
                    [
                        "type" => "List",
                        "name" => "List Users"
                    ],
                    [
                        "type" => "View",
                        "name" => "View User"
                    ],
                    [
                        "type" => "Add",
                        "name" => "Add User"
                    ],
                    [
                        "type" => "Edit",
                        "name" => "Edit User"
                    ],
                    [
                        "type" => "Delete",
                        "name" => "Delete User"
                    ],
                    [
                        "type" => "Activity",
                        "name" => "View User Login Activity"
                    ],
                    [
                        "type" => "Export",
                        "name" => "Export Users to CSV"
                    ],
                ]
            ],

            [
                "group" => "Language",
                "perms" => [
                    [
                        "type" => "List",
                        "name" => "List Languages"
                    ],
                    [
                        "type" => "View",
                        "name" => "View Language"
                    ],
                    [
                        "type" => "Add",
                        "name" => "Add Language"
                    ],
                    [
                        "type" => "Edit",
                        "name" => "Edit Language"
                    ],
                    [
                        "type" => "Delete",
                        "name" => "Delete Language"
                    ],

                    [
                        "type" => "Import",
                        "name" => "Import Translation"
                    ],
                    [
                        "type" => "Import",
                        "name" => "Generate Translation"
                    ],
                    [
                        "type" => "Add",
                        "name" => "Add Translation"
                    ],
                    [
                        "type" => "Export",
                        "name" => "Export CSV Template"
                    ],

                ]
            ],
            [
                "group" => "Roles",
                "perms" => [
                    [
                        "type" => "List",
                        "name" => "List Roles"
                    ],
                    [
                        "type" => "View",
                        "name" => "View Role"
                    ],
                    [
                        "type" => "Add",
                        "name" => "Add Role"
                    ],
                    [
                        "type" => "Edit",
                        "name" => "Edit Role"
                    ],
                    [
                        "type" => "Delete",
                        "name" => "Delete Role"
                    ],
                ]
            ],
            [
                "group" => "Roads",
                "perms" => [
                    [
                        "type" => "Add",
                        "name" => "Add Road On Map"
                    ],
                    [
                        "type" => "List",
                        "name" => "List Roadlines"
                    ],
                    [
                        "type" => "View",
                        "name" => "View Roadline"
                    ],
                    [
                        "type" => "History",
                        "name" => "View Roadline History"
                    ],
                    [
                        "type" => "Edit",
                        "name" => "Edit Roadline"
                    ],
                    [
                        "type" => "Delete",
                        "name" => "Delete Roadline"
                    ],
                    [
                        "type" => "Export",
                        "name" => "Export Roadlines to CSV"
                    ],
                    [
                        "type" => "Export",
                        "name" => "Export Roadlines to Shape"
                    ],
                    [
                        "type" => "Export",
                        "name" => "Export Roadlines to KML"
                    ],


                    [
                        "type" => "View on map",
                        "name" => "View Roadline On Map"
                    ],
                     [
                        "type" => "Edit on map",
                        "name" => "Edit Road on Map"
                    ],

                    
                ]
            ],
            [
                "group" => "Drain",
                "perms" => [
                    [
                        "type" => "Add",
                        "name" => "Add Drain On Map"
                    ],
                    [
                        "type" => "List",
                        "name" => "List Drains"
                    ],
                    [
                        "type" => "View",
                        "name" => "View Drain"
                    ],
                    [
                        "type" => "History",
                        "name" => "View Drain History"
                    ],
                    [
                        "type" => "Edit",
                        "name" => "Edit Drain"
                    ],
                    [
                        "type" => "Delete",
                        "name" => "Delete Drain"
                    ],
                    [
                        "type" => "Export",
                        "name" => "Export Drains to CSV"
                    ],
                    [
                        "type" => "Export",
                        "name" => "Export Drains to Shape"
                    ],
                    [
                        "type" => "Export",
                        "name" => "Export Drains to KML"
                    ],
                    [
                        "type" => "View on map",
                        "name" => "View Drain On Map"
                    ],
                     [
                        "type" => "Edit on map",
                        "name" => "Edit Drain on Map"
                    ],
                ]
            ],
            [
                "group" => "Sewers",
                "perms" => [
                    [
                        "type" => "Add",
                        "name" => "Add Sewer On Map"
                    ],
                    [
                        "type" => "List",
                        "name" => "List Sewers"
                    ],
                    [
                        "type" => "View",
                        "name" => "View Sewer"
                    ],
                    [
                        "type" => "History",
                        "name" => "View Sewer History"
                    ],
                    [
                        "type" => "Edit",
                        "name" => "Edit Sewer"
                    ],
                    [
                        "type" => "Delete",
                        "name" => "Delete Sewer"
                    ],
                    [
                        "type" => "Export",
                        "name" => "Export Sewers to CSV"
                    ],
                    [
                        "type" => "Export",
                        "name" => "Export Sewers to Shape"
                    ],
                    [
                        "type" => "Export",
                        "name" => "Export Sewers to KML"
                    ],
                    [
                        "type" => "View on map",
                        "name" => "View Sewer On Map"
                    ],
                     [
                        "type" => "Edit on map",
                        "name" => "Edit Sewer on Map"
                    ],
                ]
            ],
            [
                "group" => "WaterSupply Network",
                "perms" => [
                    [
                        "type" => "Add",
                        "name" => "Add WaterSupply On Map"
                    ],
                    [
                        "type" => "List",
                        "name" => "List WaterSupply Network"
                    ],
                    [
                        "type" => "View",
                        "name" => "View WaterSupply Network"
                    ],
                    [
                        "type" => "History",
                        "name" => "View WaterSupply Network History"
                    ],
                    [
                        "type" => "Edit",
                        "name" => "Edit WaterSupply Network"
                    ],
                    [
                        "type" => "Delete",
                        "name" => "Delete WaterSupply Network"
                    ],
                    [
                        "type" => "Export",
                        "name" => "Export WaterSupply Network to CSV"
                    ],
                    [
                        "type" => "Export",
                        "name" => "Export WaterSupply Network to Shape"
                    ],
                    [
                        "type" => "Export",
                        "name" => "Export WaterSupply Network to KML"
                    ],
                    [
                        "type" => "View on map",
                        "name" => "View WaterSupply Network On Map"
                    ],
                     [
                        "type" => "Edit on map",
                        "name" => "Edit WaterSupply on Map"
                    ],
                ]
            ],
            [
                "group" => "Help Desks",
                "perms" => [
                    [
                        "type" => "List",
                        "name" => "List Help Desks"
                    ],
                    [
                        "type" => "View",
                        "name" => "View Help Desk"
                    ],
                    [
                        "type" => "Add",
                        "name" => "Add Help Desk"
                    ],
                    [
                        "type" => "Edit",
                        "name" => "Edit Help Desk"
                    ],
                    [
                        "type" => "Delete",
                        "name" => "Delete Help Desk"
                    ],
                    [
                        "type" => "Export",
                        "name" => "Export Help Desk"
                    ],
                    [
                        "type" => "History",
                        "name" => "View Help Desk History"
                    ],
                ]
            ],
            [
                "group" => "Service Providers",
                "perms" => [
                    [
                        "type" => "List",
                        "name" => "List Service Providers"
                    ],
                    [
                        "type" => "View",
                        "name" => "View Service Provider"
                    ],
                    [
                        "type" => "Add",
                        "name" => "Add Service Provider"
                    ],
                    [
                        "type" => "Edit",
                        "name" => "Edit Service Provider"
                    ],
                    [
                        "type" => "Delete",
                        "name" => "Delete Service Provider"
                    ],
                    [
                        "type" => "History",
                        "name" => "View Service Provider History"
                    ],
                    [
                        "type" => "Export",
                        "name" => "Export Service Providers to CSV"
                    ]
                ]
            ],
            [
                "group" => "Treatment Plants",
                "perms" => [
                    [
                        "type" => "List",
                        "name" => "List Treatment Plants"
                    ],
                    [
                        "type" => "View",
                        "name" => "View Treatment Plant"
                    ],
                    [
                        "type" => "History",
                        "name" => "View Treatment Plant History"
                    ],
                    [
                        "type" => "Add",
                        "name" => "Add Treatment Plant"
                    ],
                    [
                        "type" => "Edit",
                        "name" => "Edit Treatment Plant"
                    ],
                    [
                        "type" => "Delete",
                        "name" => "Delete Treatment Plant"
                    ],
                    [
                        "type" => "View on map",
                        "name" => "View Treatment Plant on Map"
                    ],
                    [
                        "type" => "Export",
                        "name" => "Export Treatment Plants to CSV"
                    ],
                    [
                        "type" => "Export",
                        "name" => "Export Treatment Plants to Shape"
                    ],
                    [
                        "type" => "Export",
                        "name" => "Export Treatment Plants to KML"
                    ],
                ]
            ],
            [
                "group" => "Desludging Vehicles",
                "perms" => [
                    [
                        "type" => "List",
                        "name" => "List Desludging Vehicles"
                    ],
                    [
                        "type" => "View",
                        "name" => "View Desludging Vehicle"
                    ],
                    [
                        "type" => "Add",
                        "name" => "Add Desludging Vehicle"
                    ],
                    [
                        "type" => "Edit",
                        "name" => "Edit Desludging Vehicle"
                    ],
                    [
                        "type" => "Delete",
                        "name" => "Delete Desludging Vehicle"
                    ],
                    [
                        "type" => "Export",
                        "name" => "Export Desludging Vehicles"
                    ],
                    [
                        "type" => "History",
                        "name" => "View Desludging Vehicle History"
                    ],
                ]
            ],
            [
                "group" => "Treatment Plant Efficiency Standards",
                "perms" => [
                    [
                        "type" => "View",
                        "name" => "View Treatment Plant Efficiency Standard"
                    ],
                    [
                        "type" => "Edit",
                        "name" => "Edit Treatment Plant Efficiency Standard"
                    ],
                ]
            ],
            [
                "group" => "Containments",
                "perms" => [
                    [
                        "type" => "List",
                        "name" => "List Containments"
                    ],
                    [
                        "type" => "View",
                        "name" => "View Containment"
                    ],
                    [
                        "type" => "Add",
                        "name" => "Add Containment"
                    ],
                    [
                        "type" => "Edit",
                        "name" => "Edit Containment"
                    ],
                    [
                        "type" => "Delete",
                        "name" => "Delete Containment"
                    ],
                    [
                        "type" => "Export",
                        "name" => "Export Containments"
                    ],
                    [
                        "type" => "List",
                        "name" => "List Containment Buildings"
                    ],
                    [
                        "type" => "Add",
                        "name" => "Add Containment Building"
                    ],
                    [
                        "type" => "Delete",
                        "name" => "Delete Containment Building"
                    ],
                    [
                        "type" => "Edit",
                        "name" => "Delete Building from Containment"
                    ],
                    [
                        "type" => "History",
                        "name" => "View Containment History"
                    ],
                    [
                        "type" => "Service History",
                        "name" => "Emptying Service History"
                    ],
                    [
                        "type" => "View on map",
                        "name" => "View Containment On Map"
                    ],
                    [
                        "type" => "View on map",
                        "name" => "View Nearest Road To Containment On Map"
                    ],


                ]
            ],
            [
                "group" => "PT/CT Toilets",
                "perms" => [
                    [
                        "type" => "List",
                        "name" => "List PT/CT Toilets"
                    ],
                    [
                        "type" => "View",
                        "name" => "View PT/CT Toilet"
                    ],
                    [
                        "type" => "Add",
                        "name" => "Add PT/CT Toilet"
                    ],
                    [
                        "type" => "Edit",
                        "name" => "Edit PT/CT Toilet"
                    ],
                    [
                        "type" => "Delete",
                        "name" => "Delete PT/CT Toilet"
                    ],
                    [
                        "type" => "Export",
                        "name" => "Export PT/CT Toilets"
                    ],
                    [
                        "type" => "History",
                        "name" => "View PT/CT Toilet History"
                    ],
                    [
                        "type" => "View on map",
                        "name" => "View PT/CT Toilet on Map"
                    ]
                ]
            ],
            [
                "group" => "PT Users Logs",
                "perms" => [
                    [
                        "type" => "List",
                        "name" => "List PT Users Log"
                    ],
                    [
                        "type" => "View",
                        "name" => "View PT Users Log"
                    ],
                    [
                        "type" => "Add",
                        "name" => "Add PT Users Log"
                    ],
                    [
                        "type" => "Edit",
                        "name" => "Edit PT Users Log"
                    ],
                    [
                        "type" => "History",
                        "name" => "View PT Users Log History"
                    ],
                    [
                        "type" => "Delete",
                        "name" => "Delete PT Users Log"
                    ],
                    [
                        "type" => "Export",
                        "name" => "Export PT Users Logs"
                    ],
                ]
            ],
            [
                "group" => "Hotspots",
                "perms" => [
                    [
                        "type" => "List",
                        "name" => "List Hotspot Identifications"
                    ],
                    [
                        "type" => "View",
                        "name" => "View Hotspot Identification"
                    ],
                    [
                        "type" => "Add",
                        "name" => "Add Hotspot Identification"
                    ],
                    [
                        "type" => "Edit",
                        "name" => "Edit Hotspot Identification"
                    ],
                    [
                        "type" => "Delete",
                        "name" => "Delete Hotspot Identification"
                    ],
                    [
                        "type" => "Export",
                        "name" => "Export Hotspot Identifications"
                    ],
                    [
                        "type" => "View on map",
                        "name" => "View Hotspot Identification On Map"
                    ],
                    [
                        "type" => "History",
                        "name" => "View Hotspot Identification History"
                    ],
                ],

            ],
            [
                "group" => "Applications",
                "perms" => [
                    [
                        "type" => "List",
                        "name" => "List Applications"
                    ],
                    [
                        "type" => "View",
                        "name" => "View Application"
                    ],
                    [
                        "type" => "Add",
                        "name" => "Add Application"
                    ],
                    [
                        "type" => "Edit",
                        "name" => "Edit Application"
                    ],
                    [
                        "type" => "Delete",
                        "name" => "Delete Application"
                    ],
                    [
                        "type" => "Export",
                        "name" => "Export Applications"
                    ],
                    [
                        "type" => "Export",
                        "name" => "Generate Application Report"
                    ],
                    [
                        "type" => "History",
                        "name" => "View Application History"
                    ],
                ]
            ],
            [
                "group" => "Emptyings",
                "perms" => [
                    [
                        "type" => "List",
                        "name" => "List Emptyings"
                    ],
                    [
                        "type" => "View",
                        "name" => "View Emptying"
                    ],
                    [
                        "type" => "Add",
                        "name" => "Add Emptying"
                    ],
                    [
                        "type" => "Edit",
                        "name" => "Edit Emptying"
                    ],
                    [
                        "type" => "Delete",
                        "name" => "Delete Emptying"
                    ],
                    [
                        "type" => "Export",
                        "name" => "Export Emptyings"
                    ],
                    [
                        "type" => "History",
                        "name" => "View Emptyings History"
                    ],
                ]
            ],
            [
                "group" => "Sludge Collections",
                "perms" => [
                    [
                        "type" => "List",
                        "name" => "List Sludge Collections"
                    ],
                    [
                        "type" => "View",
                        "name" => "View Sludge Collection"
                    ],
                    [
                        "type" => "Add",
                        "name" => "Add Sludge Collection"
                    ],
                    [
                        "type" => "Edit",
                        "name" => "Edit Sludge Collection"
                    ],
                    [
                        "type" => "Delete",
                        "name" => "Delete Sludge Collection"
                    ],
                    [
                        "type" => "Export",
                        "name" => "Export Sludge Collections"
                    ],
                    [
                        "type" => "History",
                        "name" => "View Sludge Collection History"
                    ],
                ]
            ],
            [
                "group" => "API",
                "perms" => [
                    [
                        "type" => "API - Emptying",
                        "name" => "Access Emptying Service API"
                    ],
                    [
                        "type" => "API - Survey",
                        "name" => "Access Building Survey API"
                    ],
                    [
                        "type" => "API - Sewer Connection",
                        "name" => "Access Sewer Connection API"
                    ],
                    [
                        "type" => "API - Sludge Collection",
                        "name" => "Access Sludge Collection API"
                    ],
                ]
            ],
            [
                "group" => "Swm Service Payment",
                "perms" => [
                    [
                        "type" => "List",
                        "name" => "List SWM Service Payment Collection"
                    ],
                    [
                        "type" => "Export",
                        "name" => "Export SWM Service Payment Collection From CSV"
                    ],
                    [
                        "type" => "Import",
                        "name" => "Import SWM Service Payment Collection From CSV"
                    ],

                ]
            ],
            [
                "group" => "Building Structures",
                "perms" => [
                    [
                        "type" => "List",
                        "name" => "List Building Structures"
                    ],
                    [
                        "type" => "View",
                        "name" => "View Building Structure"
                    ],
                    [
                        "type" => "Add",
                        "name" => "Add Building Structure"
                    ],
                    [
                        "type" => "Edit",
                        "name" => "Edit Building Structure"
                    ],
                    [
                        "type" => "Delete",
                        "name" => "Delete Building Structure"
                    ],
                    [
                        "type" => "Export",
                        "name" => "Export Building Structures"
                    ],
                    [
                        "type" => "History",
                        "name" => "View Building Structures History"
                    ],
                    [
                        "type" => "View on map",
                        "name" => "View Building On Map"
                    ],
[
                        "type" => "View on map",
                        "name" => "View Nearest Road To Building On Map"
                    ],
[
                        "type" => "View",
                        "name" => "View Containments Connected to Buildings"
                    ],
                ]
            ],
            [
                "group" => "Building Surveys",
                "perms" => [
                    [
                        "type" => "List",
                        "name" => "List Building Surveys"
                    ],
                    [
                        "type" => "Add",
                        "name" => "Approve Building Survey"
                    ],
                    [
                        "type" => "Delete",
                        "name" => "Delete Building Survey"
                    ],
                    [
                        "type" => "View on map",
                        "name" => "Preview Building Survey"
                    ],
                    [
                        "type" => "Download",
                        "name" => "Download Building Survey"
                    ]
                ]
            ],
            [
                "group" => "Sewer Connection",
                "perms" => [
                    [
                        "type" => "List",
                        "name" => "List Sewer Connection"
                    ],
                    [
                        "type" => "Approve",
                        "name" => "Approve Sewer Connection"
                    ],
                    [
                        "type" => "Delete",
                        "name" => "Delete Sewer Connection"
                    ],
                    [
                        "type" => "View on map",
                        "name" => "Preview Sewer Connection"
                    ],
                ]
            ],
            [
                "group" => "Yearly Waterborne Cases",
                "perms" => [
                    [
                        "type" => "List",
                        "name" => "List Yearly Waterborne Cases"
                    ],
                    [
                        "type" => "View",
                        "name" => "View Yearly Waterborne Cases"
                    ],
                    [
                        "type" => "Add",
                        "name" => "Add Yearly Waterborne Cases"
                    ],
                    [
                        "type" => "Edit",
                        "name" => "Edit Yearly Waterborne Cases"
                    ],
                    [
                        "type" => "Delete",
                        "name" => "Delete Yearly Waterborne Cases"
                    ],
                    [
                        "type" => "Export",
                        "name" => "Export Yearly Waterborne Cases"
                    ],
                    [
                        "type" => "History",
                        "name" => "View Yearly Waterborne Case History"
                    ],
                ]
            ],



            [
                "group" => "Dashboard",
                "perms" => [

                    [
                        "type" => "CountBox",
                        "name" => "Utility CountBox"
                    ],
                    [
                        "type" => "CountBox",
                        "name" => "FSM CountBox"
                    ],
                    [
                        "type" => "CountBox",
                        "name" => "PTCT CountBox"
                    ],
                    [
                        "type" => "CountBox",
                        "name" => "Public Health CountBox"
                    ],

                    [
                        "type" => "Chart",
                        "name" => "Building Connections to Sanitation System Types Chart"
                    ],

                    [
                        "type" => "Chart",
                        "name" => "Ward-Wise Revenue Collected from Emptying Services Chart"
                    ],
                    [
                        "type" => "Chart",
                        "name" => "Property Tax Payment Chart"
                    ],
                    [
                        "type" => "Chart",
                        "name" => "Distribution of Water Supply Payment Dues Chart"
                    ],
                    [
                        "type" => "Chart",
                        "name" => "Distribution of Water Supply Services by Ward Chart"
                    ],
                    [
                        "type" => "Chart",
                        "name" => "Outstanding Payments for SWM Services Chart"
                    ],
                    [
                        "type" => "Chart",
                        "name" => "Distribution of SWM Services by Ward Chart"
                    ],

                    [
                        "type" => "Chart",
                        "name" => "Yearly Distribution of Waterborne Disease Chart"
                    ],
                    [
                        "type" => "Chart",
                        "name" => "Performance of Municipal Treatment Plants by Last 5 Years Chart"
                    ],
                ]
            ],

            [
                "group" => "Building Dashboard",
                "perms" => [
                    [
                        "type" => "CountBox",
                        "name" => "Building CountBox"
                    ],
                    [
                        "type" => "CountBox",
                        "name" => "Sanitation CountBox"
                    ],
                    [
                        "type" => "Chart",
                        "name" => "Ward-Wise Distribution of Buildings Chart"
                    ],
                    [
                        "type" => "Chart",
                        "name" => "Building Use Composition Chart"
                    ],
                ],
            ],

            [
                "group" => "FSM Dashboard",
                "perms" => [
                    [
                        "type" => "CountBox",
                        "name" => "FSM Dashboard CountBox"
                    ],
                    [
                        "type" => "Chart",
                        "name" => "Proportion of Different Containment Types Chart"
                    ],
                    [
                        "type" => "Chart",
                        "name" => "Ward-Wise Distribution of Containment Types Chart"
                    ],
                    [
                        "type" => "Chart",
                        "name" => "Ward-Wise Distribution of Containment Types in Residential Buildings Chart"
                    ],
                    [
                        "type" => "Chart",
                        "name" => "Containment Types Categorized by Building Usage Chart"
                    ],
                    [
                        "type" => "Chart",
                        "name" => "Containment Types Categorized by Land Use Chart"
                    ],
                    [
                        "type" => "Chart",
                        "name" => "Summary of Applications, Emptying Services, Sludge Disposal, and Feedback by Ward Chart"
                    ],
                    [
                        "type" => "Chart",
                        "name" => "Distribution of Emptying Requests by Structure Types Chart"
                    ],
                    [
                        "type" => "Chart",
                        "name" => "Comparison of Emptying Requests from Low-Income and Other Communities Chart"
                    ],
                    [
                        "type" => "Chart",
                        "name" => "Monthly Emptying Requests Processed by Service Providers Chart"
                    ],
                    [
                        "type" => "Chart",
                        "name" => "Emptying Requests for the Next Four Weeks Chart"
                    ],
                    [
                        "type" => "Chart",
                        "name" => "Ward-Wise Distribution of Emptying Requests for the Next Four Weeks Chart"
                    ],
                    [
                        "type" => "Chart",
                        "name" => "Customer Satisfaction with FSM Service Quality Chart"
                    ],
                    [
                        "type" => "Chart",
                        "name" => "Sanitation Worker Compliance with PPE Guidelines Chart"
                    ],
                    [
                        "type" => "Chart",
                        "name" => "Containment Type-Wise Emptying Services Over the Last 5 Years Chart"
                    ],
                    [
                        "type" => "Chart",
                        "name" => "Sludge Collection Trends by Treatment Plants Over the Last 5 Years Chart"
                    ],

                ],
            ],
            [
                "group" => "Utility Dashboard",
                "perms" => [
                    [
                        "type" => "CountBox",
                        "name" => "Road Count Box"
                    ],
                    [
                        "type" => "Chart",
                        "name" => "Ward-Wise Total Road Length Chart"
                    ],
                    [
                        "type" => "Chart",
                        "name" => "Ward-Wise Total Road Length by Surface Type Chart"
                    ],
                    [
                        "type" => "Chart",
                        "name" => "Ward-Wise Total Road Length by Hierarchy Chart"
                    ],
                    [
                        "type" => "Chart",
                        "name" => "Ward-Wise Total Road Length by Width Chart"
                    ],

                    [
                        "type" => "CountBox",
                        "name" => "Sewer Count Box"
                    ],
                    [
                        "type" => "Chart",
                        "name" => "Ward-Wise Sewer Network Length Chart"
                    ],
                    [
                        "type" => "Chart",
                        "name" => "Ward-Wise Sewer Length by Diameter Chart"
                    ],
                    [
                        "type" => "CountBox",
                        "name" => "Drain Count Box"
                    ],
                    [
                        "type" => "Chart",
                        "name" => "Ward-Wise Drain Length Chart"
                    ],
                    [
                        "type" => "Chart",
                        "name" => "Ward-Wise Drain Length by Type Chart"
                    ],
                    [
                        "type" => "Chart",
                        "name" => "Ward-Wise Drain Length by Size Chart"
                    ],
                    [
                        "type" => "Chart",
                        "name" => "Ward-Wise Drain Length by Surface Type Chart"
                    ],
                    [
                        "type" => "CountBox",
                        "name" => "Water Supply CountBox"
                    ],
                    [
                        "type" => "Chart",
                        "name" => "Ward-Wise Water Supply Length Chart"
                    ],
                    [
                        "type" => "Chart",
                        "name" => "Ward-Wise Water Supply Length by Diameter Chart"
                    ],

                ],
            ],
            [
                "group" => "Feedbacks",
                "perms" => [
                    [
                        "type" => "List",
                        "name" => "List Feedbacks"
                    ],
                    [
                        "type" => "View",
                        "name" => "View Feedback"
                    ],
                    [
                        "type" => "Add",
                        "name" => "Add Feedback"
                    ],
                    [
                        "type" => "Edit",
                        "name" => "Edit Feedback"
                    ],
                    [
                        "type" => "Delete",
                        "name" => "Delete Feedback"
                    ],
                    [
                        "type" => "Export",
                        "name" => "Export Feedbacks"
                    ],
                ]
            ],
            [
                "group" => "Employee Infos",
                "perms" => [
                    [
                        "type" => "List",
                        "name" => "List Employee Infos"
                    ],
                    [
                        "type" => "View",
                        "name" => "View Employee Info"
                    ],
                    [
                        "type" => "Add",
                        "name" => "Add Employee Info"
                    ],
                    [
                        "type" => "Edit",
                        "name" => "Edit Employee Info"
                    ],
                    [
                        "type" => "Delete",
                        "name" => "Delete Employee Info"
                    ],
                    [
                        "type" => "Export",
                        "name" => "Export Employee Infos"
                    ],
                    [
                        "type" => "History",
                        "name" => "View Employee Info History"
                    ],

                ]
            ],
            [
                "group" => "Maps",
                "perms" => [
                    [
                        "type" => "Map Layer",
                        "name" => "Municipality Map Layer"
                    ],
                    [
                        "type" => "Map Layer",
                        "name" => "Ward Boundary Map Layer"
                    ],
                    [
                        "type" => "Map Layer",
                        "name" => "Containments Map Layer"
                    ],
                    [
                        "type" => "Map Layer",
                        "name" => "Buildings Map Layer"
                    ],
                    [
                        "type" => "Map Layer",
                        "name" => "Tax Payment Status Buildings Map Layer"
                    ],
                    [
                        "type" => "Map Layer",
                        "name" => "Water Payment Status Map Layer"
                    ],
                    [
                        "type" => "Map Layer",
                        "name" => "Treatment Plants Map Layer"
                    ],
                    [
                        "type" => "Map Layer",
                        "name" => "Roads Map Layer"
                    ],
                    [
                        "type" => "Map Layer",
                        "name" => "Places Map Layer"
                    ],
                    [
                        "type" => "Map Layer",
                        "name" => "Land Use Map Layer"
                    ],
                    [
                        "type" => "Map Layer",
                        "name" => "Wards Map Layer"
                    ],
                    [
                        "type" => "Map Layer",
                        "name" => "Summarized Grids Map Layer"
                    ],
                    [
                        "type" => "Map Layer",
                        "name" => "Sewers Line Map Layer"
                    ],
                    [
                        "type" => "Map Layer",
                        "name" => "Sanitation System Map Layer"
                    ],
                    [
                        "type" => "Map Layer",
                        "name" => "PT/CT Toilets Map Layer"
                    ],
                    [
                        "type" => "Map Layer",
                        "name" => "Water Body Map Layer"
                    ],
                    [
                        "type" => "Map Layer",
                        "name" => "Solid Waste Status Map Layer"
                    ],
                    [
                        "type" => "Map Layer",
                        "name" => "Low Income Community Map Layer"
                    ],
                    [
                        "type" => "Map Layer",
                        "name" => "Water Samples Map Layer"
                    ],
                    [
                        "type" => "Map Tool",
                        "name" => "Info Map Tools"
                    ],
                    [
                        "type" => "Map Tool",
                        "name" => "Service Delivery Map Tools"
                    ],
                    [
                        "type" => "Map Tool",
                        "name" => "Applications Map Tools"
                    ],
                    [
                        "type" => "Map Tool",
                        "name" => "Emptied Applications Not Reached to TP Map Tools"
                    ],
                    [
                        "type" => "Map Tool",
                        "name" => "Containments Proposed To Be Emptied Map Tools"
                    ],
                    [
                        "type" => "Map Tool",
                        "name" => "Service Feedback Map Tools"
                    ],
                    [
                        "type" => "Map Tool",
                        "name" => "General Map Tools"
                    ],
                    [
                        "type" => "Map Tool",
                        "name" => "Building by Structure Map Tools"
                    ],
                    [
                        "type" => "Map Tool",
                        "name" => "Property Tax Map Tools"
                    ],
                    [
                        "type" => "Map Tool",
                        "name" => "Water Payment Status Map Tools"
                    ],
                    [
                        "type" => "Map Tool",
                        "name" => "Solid Waste Payment Status Map Tools"
                    ],
                    [
                        "type" => "Map Tool",
                        "name" => "Data Export Map Tools"
                    ],
                    [
                        "type" => "Map Tool",
                        "name" => "Filter by Wards Map Tools"
                    ],
                    [
                        "type" => "Map Tool",
                        "name" => "Export Data Map Tools"
                    ],
                    [
                        "type" => "Map Tool",
                        "name" => "Owner Information Map Tools"
                    ],
                    [
                        "type" => "Map Tool",
                        "name" => "Decision Map Tools"
                    ],
                    [
                        "type" => "Map Tool",
                        "name" => "Tax Due Map Tools"
                    ],
                    [
                        "type" => "Map Tool",
                        "name" => "Sewer Potential Map Tools"
                    ],
                    [
                        "type" => "Map Tool",
                        "name" => "Buildings to Sewer Map Tools"
                    ],
                    [
                        "type" => "Map Tool",
                        "name" => "Buildings to Road Map Tools"
                    ],
                    [
                        "type" => "Map Tool",
                        "name" => "Hard to Reach Buildings Map Tools"
                    ], [
                        "type" => "Map Tool",
                        "name" => "Building Close to Water Bodies Map Tools"
                    ],
                    [
                        "type" => "Map Tool",
                        "name" => "Community Toilets Map Tools"
                    ],
                    [
                        "type" => "Map Tool",
                        "name" => "Area Population Map Tools"
                    ],
                    [
                        "type" => "Map Tool",
                        "name" => "Summary Information Buffer Map Tools"
                    ],
                    [
                        "type" => "Map Tool",
                        "name" => "Summary Information Water Bodies Map Tools"
                    ],
                    [
                        "type" => "Map Tool",
                        "name" => "Summary Information Wards Map Tools"
                    ], [
                        "type" => "Map Tool",
                        "name" => "Summary Information Road Map Tools"
                    ],
                    [
                        "type" => "Map Tool",
                        "name" => "Summary Information Point Map Tools"
                    ],
                    [
                        "type" => "Export",
                        "name" => "Export in General Map Tools"
                    ],
                    [
                        "type" => "Export",
                        "name" => "Export in Decision Map Tools"
                    ],
                    [
                        "type" => "Export",
                        "name" => "Export in Summary Information Map Tools"
                    ],
                    [
                        "type" => "Map Layer",
                        "name" => "Public Health Map Layer"
                    ],
                    [
                        "type" => "Map Layer",
                        "name" => "Drains Map Layer"
                    ],

                    [
                        "type" => "Map Layer",
                        "name" => "WaterSupply Network Map Layer"
                    ],
                    [
                        "type" => "Map Tool",
                        "name" => "Containments Emptied Info Map Tools"
                    ],
                    [
                        "type" => "Map Tool",
                        "name" => "Toilet Isochrone Map Tools"
                    ],
                    [
                        "type" => "Export",
                        "name" => "Export Containment Report"
                    ],
                ],

            ],

            [
                "group" => "Water Samples",
                "perms" => [
                    [
                        "type" => "List",
                        "name" => "List Water Samples"
                    ],
                    [
                        "type" => "View",
                        "name" => "View Water Samples"
                    ],
                    [
                        "type" => "History",
                        "name" => "View Water Samples History"
                    ],
                    [
                        "type" => "Add",
                        "name" => "Add Water Samples"
                    ],
                    [
                        "type" => "Edit",
                        "name" => "Edit Water Samples"
                    ],
                    [
                        "type" => "View on map",
                        "name" => "View Water Samples On Map"
                    ],
                    [
                        "type" => "Delete",
                        "name" => "Delete Water Samples"
                    ],
                    [
                        "type" => "Export",
                        "name" => "Export Water Samples to CSV"
                    ],
                    [
                        "type" => "Export",
                        "name" => "Export Water Samples to Shape"
                    ],
                    [
                        "type" => "Export",
                        "name" => "Export Water Samples to KML"
                    ],
                ]
            ],
            [
                "group" => "KPI Dashboard",
                "perms" => [
                    [
                        "type" => "Card",
                        "name" => "KPI"
                    ],
                    [
                        "type" => "Chart",
                        "name" => "Application Response Chart"
                    ],
                    [
                        "type" => "Chart",
                        "name" => "Safe Desludging Chart"
                    ],
                    [
                        "type" => "Chart",
                        "name" => "Customer Satisfaction Chart"
                    ],
                    [
                        "type" => "Chart",
                        "name" => "PPE Compliance Chart"
                    ],
                    [
                        "type" => "Chart",
                        "name" => "Inclusion Chart"
                    ],
                    [
                        "type" => "Chart",
                        "name" => "Response Time Chart"
                    ],
                    [
                        "type" => "Chart",
                        "name" => "Faecal Sludge Collection Ratio Chart"
                    ]
                ]
            ],
            [
                "group" => "KPI Target",
                "perms" => [
                    [
                        "type" => "List",
                        "name" => "List KPI Target"
                    ],
                    [
                        "type" => "View",
                        "name" => "View KPI Target"
                    ],
                    [
                        "type" => "Add",
                        "name" => "Add KPI Target"
                    ],
                    [
                        "type" => "Edit",
                        "name" => "Edit KPI Target"
                    ],
                    [
                        "type" => "Delete",
                        "name" => "Delete KPI Target"
                    ],
                    [
                        "type" => "Export",
                        "name" => "Export KPI Target"
                    ],
                    [
                        "type" => "Export",
                        "name" => "View KPI Target History"
                    ]
                ]
            ],
            [
                "group" => "Property Tax Collection ISS",
                "perms" => [
                    [
                        "type" => "List",
                        "name" => "List Property Tax Collection"
                    ],
                    [
                        "type" => "Import",
                        "name" => "Import Property Tax Collection From CSV"
                    ],
                    [
                        "type" => "Export",
                        "name" => "Export Property Tax Collection Info"
                    ],

                ],
            ],
            [
                "group" => "Water Supply ISS",
                "perms" => [
                    [
                        "type" => "List",
                        "name" => "List Water Supply Collection"
                    ],
                    [
                        "type" => "Import",
                        "name" => "Import Water Supply Collection From CSV"
                    ],
                    [
                        "type" => "Export",
                        "name" => "Export Water Supply Collection Info"
                    ],

                ],
            ],
            [
                "group" => "Treatment Plant Efficiency Tests",
                "perms" => [
                    [
                        "type" => "List",
                        "name" => "List Treatment Plant Efficiency Tests"
                    ],
                    [
                        "type" => "View",
                        "name" => "View Treatment Plant Efficiency Test"
                    ],
                    [
                        "type" => "Add",
                        "name" => "Add Treatment Plant Efficiency Test"
                    ],
                    [
                        "type" => "Edit",
                        "name" => "Edit Treatment Plant Efficiency Test"
                    ],
                    [
                        "type" => "Delete",
                        "name" => "Delete Treatment Plant Efficiency Test"
                    ],
                    [
                        "type" => "Export",
                        "name" => "Export Treatment Plant Efficiency Tests"
                    ],
                    [
                        "type" => "History",
                        "name" => "View Treatment Plant Efficiency Test History"
                    ],
                ]
            ],
            [
                "group" => "Low Income Communities",
                "perms" => [
                    [
                        "type" => "List",
                        "name" => "List Low Income Communities"
                    ],
                    [
                        "type" => "View",
                        "name" => "View Low Income Community"
                    ],
                    [
                        "type" => "Add",
                        "name" => "Add Low Income Community"
                    ],
                    [
                        "type" => "Edit",
                        "name" => "Edit Low Income Community"
                    ],
                    [
                        "type" => "Delete",
                        "name" => "Delete Low Income Community"
                    ],
                    [
                        "type" => "Export",
                        "name" => "Export Low Income Communities"
                    ],
                    [
                        "type" => "History",
                        "name" => "View Low Income Community History"
                    ],
                    [
                        "type" => "View on map",
                        "name" => "View Low Income Community On Map"
                    ],
                ]
            ],
            [
                "group" => "Data Export",
                "perms" => [
                    [
                        "type" => "Export",
                        "name" => "Export Data in Urban Management"
                    ]
                ]
            ],
            [
                "group" => "CWIS",
                "perms" => [
                    [
                        "type" => "List",
                        "name" => "List CWIS"
                    ],
                    [
                        "type" => "View",
                        "name" => "View CWIS"
                    ],
                    [
                        "type" => "Add",
                        "name" => "Add CWIS"
                    ],
                    [
                        "type" => "Edit",
                        "name" => "Edit CWIS"
                    ],
                    [
                        "type" => "Export",
                        "name" => "Export CWIS"
                    ]
                ]
            ],
            [
                "group" => "NSD",
                "perms" => [
                    [
                       "type" => "Push",
                       "name" => "Push CWIS Indicator to NSD"
                    ],
                    [
                        "type" => "Show",
                        "name" => "Check Status of Indicator in NSD"
                    ]
                ]
            ],
            [
                "group" => "NSD Setting",
                "perms" => [
                    [
                        "type" => "List",
                        "name" => "LisT NSD Setting"
                    ],
                    [
                        "type" => "Save",
                        "name" => "Save NSD Setting"
                    ],
                    [
                        "type" => "Edit",
                        "name" => "Edit NSD Setting"
                    ]
                ]
            ],
            [
                "group" => "NSD",
                "perms" => [
                    [
                       "type" => "Push",
                       "name" => "Push CWIS Indicator to NSD"
                    ],
                    [
                        "type" => "Show",
                        "name" => "Check Status of Indicator in NSD"
                    ]
                ]
            ],
            [
                "group" => "Building Data Update API",
                "perms" => [
                    [
                        "type" => "Access",
                        "name" => "Access Building Data Update API"
                    ],
                ]
            ],
            [
            "group" => "Containment Data Update API",
            "perms" => [
                [
                    "type" => "Access",
                    "name" => "Access Containment Data Update API"
                ],
            ]
            ],
            [
                "group" => "Road Connection Data Update API",
                "perms" => [
                    [
                        "type" => "Access",
                        "name" => "Access Road Connection Data Update API"
                    ],
                ]
            ],
            [
                "group" => "Water Supply Connection Data Update API",
                "perms" => [
                    [
                        "type" => "Access",
                        "name" => "Access Water Supply Connection Data Update API"
                    ],
                ]
            ],
            [
                "group" => "Sewer Connection Data Update API",
                "perms" => [
                    [
                        "type" => "Access",
                        "name" => "Access Sewer Connection Data Update API"
                    ],
                ]
            ],
            [
                "group" => "Drain Connection Data Update API",
                "perms" => [
                    [
                        "type" => "Access",
                        "name" => "Access Drain Connection Data Update API"
                    ],
                ]
            ],
            [
                "group" => "Tax Payment Data Update API",
                "perms" => [
                    [
                        "type" => "Access",
                        "name" => "Access Tax Payment Data Update API"
                    ],
                ]
            ],
            [
                "group" => "Water Payment Data Update API",
                "perms" => [
                    [
                        "type" => "Access",
                        "name" => "Access Water Payment Data Update API"
                    ],
                ]
            ],
            [
                "group" => "Solid Waste Payment Data Update API",
                "perms" => [
                    [
                        "type" => "Access",
                        "name" => "Access Solid Waste Payment Data Update API"
                    ],
                ]
            ],
            [
                'group' => 'Schedule Desludging',
                'perms' => [
                    [
                        'type' => 'List',
                        'name' => 'List Schedule Desludging',
                    ],
                    [
                        'type' => 'View',
                        'name' => 'View Schedule Desludging',
                    ],
                    [
                        'type' => 'Regenerate',
                        'name' => 'Regenerate Schedule Desludging',
                    ],
                    [
                        'type' => 'Confirm',
                        'name' => 'Confirm Schedule Desludging',
                    ],
                    [
                        'type' => 'Delete',
                        'name' => 'Delete Schedule Desludging',
                    ],
                    [
                        'type' => 'Export',
                        'name' => 'Export Schedule Desludging',
                    ],
                    [
                        'type' => 'Reschedule',
                        'name' => 'Reschedule Schedule Desludging',
                    ],
                    [
                        'type' => 'Filter',
                        'name' => 'Filter Schedule Desludging',
                    ],
                ],
            ],
            [
                'group' => 'Schedule Reintegration',
                'perms' => [
                    [
                        'type' => 'List',
                        'name' => 'List Schedule Reintegration',
                    ],
                    [
                        'type' => 'View',
                        'name' => 'View Schedule Reintegration',
                    ],
                    [
                        'type' => 'Confirm',
                        'name' => 'Confirm Schedule Reintegration',
                    ],
                    [
                        'type' => 'Edit',
                        'name' => 'Edit Schedule Reintegration',
                    ],
                    [
                        'type' => 'Delete',
                        'name' => 'Delete Schedule Reintegration',
                    ],
                    [
                        'type' => 'Export',
                        'name' => 'Export Schedule Reintegration',
                    ],
                    [
                        'type' => 'Export',
                        'name' => 'Generate Schedule Reintegration Report',
                    ],
                    [
                        'type' => 'History',
                        'name' => 'View Schedule Reintegration History',
                    ],
                ],
            ],
        ];



        foreach ($grouped_permissions as $group) {
            foreach ($group['perms'] as $permission){
                Permission::query()->firstOrCreate(
                    [
                        'name' => $permission['name'],
                        'guard_name' => 'web',
                    ],
                    [
                        'type' => $permission['type'],
                        'group' => $group['group'],
                    ]
                );
            }
        }

    }
}
