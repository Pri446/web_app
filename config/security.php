<?php

return [
    'login_rate_limit' => [
        'identity_ip' => [
            'max_attempts' => (int) env('LOGIN_RATE_LIMIT_IDENTITY_IP_ATTEMPTS', 5),
            'decay_minutes' => (int) env('LOGIN_RATE_LIMIT_IDENTITY_IP_DECAY_MINUTES', 1),
        ],
        'ip' => [
            'max_attempts' => (int) env('LOGIN_RATE_LIMIT_IP_ATTEMPTS', 20),
            'decay_minutes' => (int) env('LOGIN_RATE_LIMIT_IP_DECAY_MINUTES', 1),
        ],
        'identity' => [
            'max_attempts' => (int) env('LOGIN_RATE_LIMIT_IDENTITY_ATTEMPTS', 15),
            'decay_minutes' => (int) env('LOGIN_RATE_LIMIT_IDENTITY_DECAY_MINUTES', 15),
        ],
    ],

    'password_reset_rate_limit' => [
        'identity_ip' => [
            'max_attempts' => (int) env('PASSWORD_RESET_RATE_LIMIT_IDENTITY_IP_ATTEMPTS', 5),
            'decay_minutes' => (int) env('PASSWORD_RESET_RATE_LIMIT_IDENTITY_IP_DECAY_MINUTES', 1),
        ],
        'ip' => [
            'max_attempts' => (int) env('PASSWORD_RESET_RATE_LIMIT_IP_ATTEMPTS', 20),
            'decay_minutes' => (int) env('PASSWORD_RESET_RATE_LIMIT_IP_DECAY_MINUTES', 1),
        ],
        'identity' => [
            'max_attempts' => (int) env('PASSWORD_RESET_RATE_LIMIT_IDENTITY_ATTEMPTS', 15),
            'decay_minutes' => (int) env('PASSWORD_RESET_RATE_LIMIT_IDENTITY_DECAY_MINUTES', 15),
        ],
    ],

    'contact_rate_limit' => [
        'identity_ip' => [
            'max_attempts' => (int) env('CONTACT_RATE_LIMIT_IDENTITY_IP_ATTEMPTS', 5),
            'decay_minutes' => (int) env('CONTACT_RATE_LIMIT_IDENTITY_IP_DECAY_MINUTES', 1),
        ],
        'ip' => [
            'max_attempts' => (int) env('CONTACT_RATE_LIMIT_IP_ATTEMPTS', 20),
            'decay_minutes' => (int) env('CONTACT_RATE_LIMIT_IP_DECAY_MINUTES', 1),
        ],
        'identity' => [
            'max_attempts' => (int) env('CONTACT_RATE_LIMIT_IDENTITY_ATTEMPTS', 15),
            'decay_minutes' => (int) env('CONTACT_RATE_LIMIT_IDENTITY_DECAY_MINUTES', 15),
        ],
    ],

    // Comma-separated proxy IPs/CIDRs. Keep null when requests reach PHP directly.
    'trusted_proxies' => env('TRUSTED_PROXIES'),

];
