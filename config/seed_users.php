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
    ],
];
