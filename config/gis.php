<?php

return [
    'layers' => [
        'citypolys_layer' => ['permission' => 'Municipality Map Layer', 'export' => false],
        'wardboundary_layer' => ['permission' => 'Ward Boundary Map Layer', 'export' => false],
        'roadlines_layer' => ['permission' => 'Roads Map Layer', 'export' => true, 'filename' => 'Road Network'],
        'sewerlines_layer' => ['permission' => 'Sewers Line Map Layer', 'export' => true, 'filename' => 'Sewer Network'],
        'drains_layer' => ['permission' => 'Drains Map Layer', 'export' => true, 'filename' => 'Drain Network'],
        'watersupply_network_layer' => ['permission' => 'WaterSupply Network Map Layer', 'export' => true, 'filename' => 'Water Supply Network'],
        'places_layer' => ['permission' => 'Places Map Layer', 'export' => true],
        'buildings_layer' => ['permission' => 'Buildings Map Layer', 'export' => true],
        'containments_layer' => ['permission' => 'Containments Map Layer', 'export' => true],
        'treatmentplants_layer' => ['permission' => 'Treatment Plants Map Layer', 'export' => true],
        'sanitation_system_layer' => ['permission' => 'Sanitation System Map Layer', 'export' => true],
        'toilets_layer' => ['permission' => 'PT/CT Toilets Map Layer', 'export' => true],
        'waterborne_hotspots_layer' => ['permission' => 'Public Health Map Layer', 'export' => true],
        'water_samples_layer' => ['permission' => 'Water Samples Map Layer', 'export' => true],
        'buildings_tax_status_layer' => ['permission' => 'Tax Payment Status Buildings Map Layer', 'export' => true, 'filename' => 'Property Tax Collection ISS'],
        'buildings_water_payment_status_layer' => ['permission' => 'Water Payment Status Map Layer', 'export' => true, 'filename' => 'Water Supply ISS'],
        'buildings_swm_payment_status_layer' => ['permission' => 'Solid Waste Status Map Layer', 'export' => true, 'filename' => 'SWM ISS'],
        'low_income_communities_layer' => ['permission' => 'Low Income Community Map Layer', 'export' => true],
        'wards_layer' => ['permission' => 'Wards Map Layer', 'export' => false],
        'grids_layer' => ['permission' => 'Summarized Grids Map Layer', 'export' => false],
        'waterbodys_layer' => ['permission' => 'Water Body Map Layer', 'export' => true],
        'landuses_layer' => ['permission' => 'Land Use Map Layer', 'export' => true],
    ],
];
