<?php

return [
    'layers' => [
        'citypolys_layer' => ['permission' => 'Municipality Map Layer', 'export' => false],
        'wardboundary_layer' => ['permission' => 'Ward Boundary Map Layer', 'export' => false],
        'roadlines_layer' => ['permission' => 'Roads Map Layer', 'export' => true, 'filename' => 'Road Network'],
        'sewerlines_layer' => ['permission' => 'Sewers Line Map Layer', 'export' => true, 'filename' => 'Sewer Network'],
        'drains_layer' => ['permission' => 'Drains Map Layer', 'export' => true, 'filename' => 'Drain Network'],
        'watersupply_network_layer' => ['permission' => 'WaterSupply Network Map Layer', 'export' => true, 'filename' => 'Water Supply Network'],
        'places_layer' => ['permission' => 'Places Map Layer', 'export' => true, 'filename' => 'Places'],
        'buildings_layer' => ['permission' => 'Buildings Map Layer', 'export' => true, 'filename' => 'Buildings'],
        'containments_layer' => ['permission' => 'Containments Map Layer', 'export' => true, 'filename' => 'Containments'],
        'treatmentplants_layer' => ['permission' => 'Treatment Plants Map Layer', 'export' => true, 'filename' => 'Treatment Plants'],
        'sanitation_system_layer' => ['permission' => 'Sanitation System Map Layer', 'export' => true, 'filename' => 'Sanitation System'],
        'toilets_layer' => ['permission' => 'PT/CT Toilets Map Layer', 'export' => true, 'filename' => 'PT CT Toilets'],
        'waterborne_hotspots_layer' => ['permission' => 'Public Health Map Layer', 'export' => true, 'filename' => 'Waterborne Hotspots'],
        'water_samples_layer' => ['permission' => 'Water Samples Map Layer', 'export' => true, 'filename' => 'Water Samples'],
        'buildings_tax_status_layer' => ['permission' => 'Tax Payment Status Buildings Map Layer', 'export' => true, 'filename' => 'Property Tax Collection ISS'],
        'buildings_water_payment_status_layer' => ['permission' => 'Water Payment Status Map Layer', 'export' => true, 'filename' => 'Water Supply ISS'],
        'buildings_swm_payment_status_layer' => ['permission' => 'Solid Waste Status Map Layer', 'export' => true, 'filename' => 'SWM ISS'],
        'low_income_communities_layer' => ['permission' => 'Low Income Community Map Layer', 'export' => true, 'filename' => 'Low Income Communities'],
        'wards_layer' => ['permission' => 'Wards Map Layer', 'export' => false],
        'grids_layer' => ['permission' => 'Summarized Grids Map Layer', 'export' => false],
        'waterbodys_layer' => ['permission' => 'Water Body Map Layer', 'export' => true, 'filename' => 'Water Bodies'],
        'landuses_layer' => ['permission' => 'Land Use Map Layer', 'export' => true, 'filename' => 'Land Use'],
    ],
    // Layer/style pairs allowed through the mobile WMS proxy.
    'mobile_wms_layers' => [
        'wards' => ['layer' => 'wards_layer', 'style' => 'wards_layer_none'],
        'roads' => ['layer' => 'roadlines_layer', 'style' => 'roadlines_layer_width'],
        'buildings' => ['layer' => 'buildings_layer', 'style' => 'buildings_layer_none'],
        'containments' => ['layer' => 'containments_layer', 'style' => 'containments_layer_none'],
        'sewers' => ['layer' => 'sewerlines_layer', 'style' => 'sewer_none'],
    ],
];
