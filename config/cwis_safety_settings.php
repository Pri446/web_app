<?php

return [
    'average_water_consumption_lpcd' => [
        'label' => 'Per-Capita Water Consumption - SF-1f z (L/person/day)',
        'default' => 100, 'min' => 0.000001, 'max' => 1000000,
    ],
    'waste_water_conversion_factor' => [
        'label' => 'Wastewater Conversion Factor - SF-1f x (%)',
        'default' => 80, 'min' => 0, 'max' => 100,
    ],
    'greywater_conversion_factor_not_connected_to_sewer' => [
        'label' => 'Greywater Conversion Factor - SF-1f y (%)',
        'default' => 60, 'min' => 0, 'max' => 100,
    ],
    'fs_generation_septic_m3_per_person_year' => [
        'label' => 'Annual FS Generation From Septic/Holding/Lined Systems - SF-1d x1 (m3/person/year)',
        'default' => 0.28, 'min' => 0, 'max' => 1000000,
    ],
    'fs_generation_unlined_pit_m3_per_person_year' => [
        'label' => 'Annual FS Generation From Permeable/Unlined Pits - SF-1d x2 (m3/person/year)',
        'default' => 0.27, 'min' => 0, 'max' => 1000000,
    ],
    'bod_compliance_threshold_mg_l' => [
        'label' => 'BOD Compliance Threshold - SF-1g (mg/L)',
        'default' => 50, 'min' => 0.000001, 'max' => 1000000,
    ],
];
