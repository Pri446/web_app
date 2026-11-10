<?php

/*
| Deployment user values are read from the runtime environment only here.
| Seeders and commands use config('seed_users...') so config caching works.
*/

$account = static function (string $prefix, string $role, string $userType): array {
    return [
        'name' => env("{$prefix}_NAME"),
        'username' => env("{$prefix}_USERNAME"),
        'email' => env("{$prefix}_EMAIL"),
        'password' => env("{$prefix}_PASSWORD"),
        'role' => $role,
        'user_type' => $userType,
        'environment_variables' => [
            'name' => "{$prefix}_NAME",
            'username' => "{$prefix}_USERNAME",
            'email' => "{$prefix}_EMAIL",
            'password' => "{$prefix}_PASSWORD",
        ],
    ];
};

return [
    'password_min_length' => 8,
    'accounts' => [
        'super_admin' => $account('SEED_SUPER_ADMIN', 'Super Admin', ''),
        'municipality_super_admin' => $account('SEED_MUNICIPALITY_SUPER_ADMIN', 'Municipality - Super Admin', 'Municipality'),
        'municipality_executive' => $account('SEED_MUNICIPALITY_EXECUTIVE', 'Municipality - Executive', 'Municipality'),
        'municipality_it_admin' => $account('SEED_MUNICIPALITY_IT_ADMIN', 'Municipality - IT Admin', 'Municipality'),
        'municipality_building_permit' => $account('SEED_MUNICIPALITY_BUILDING_PERMIT', 'Municipality - Building Permit Department', 'Municipality'),
        'municipality_building_surveyor' => $account('SEED_MUNICIPALITY_BUILDING_SURVEYOR', 'Municipality - Building Surveyor', 'Municipality'),
        'municipality_infrastructure' => $account('SEED_MUNICIPALITY_INFRASTRUCTURE', 'Municipality - Infrastructure Department', 'Municipality'),
        'municipality_tax' => $account('SEED_MUNICIPALITY_TAX', 'Municipality - Tax Department', 'Municipality'),
        'municipality_water_billing' => $account('SEED_MUNICIPALITY_WATER_BILLING', 'Municipality - Water Billing Unit', 'Municipality'),
        'municipality_sanitation' => $account('SEED_MUNICIPALITY_SANITATION', 'Municipality - Sanitation Department', 'Municipality'),
        'municipality_public_health' => $account('SEED_MUNICIPALITY_PUBLIC_HEALTH', 'Municipality - Public Health Department', 'Municipality'),
        'municipality_solid_waste' => $account('SEED_MUNICIPALITY_SOLID_WASTE', 'Municipality - Solid Waste Management Department', 'Municipality'),
        'guest' => $account('SEED_GUEST', 'Guest', 'Guest'),
    ],
];
