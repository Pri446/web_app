<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Owner PII encryption
    |--------------------------------------------------------------------------
    */

    // The file provider is intended for local development and controlled
    // transitional deployments. Production can bind a KMS/Vault provider.
    'provider' => env('PII_KEY_PROVIDER', 'file'),

    // Backward-compatible alias used by the original v1 implementation.
    'key_file' => env('PII_KEY_FILE'),

    'active_key_version' => env('PII_ACTIVE_KEY_VERSION', 'v1'),

    'keys' => [
        'v1' => [
            'file' => env('PII_KEY_V1_FILE', env('PII_KEY_FILE')),
        ],
        'v2' => [
            'file' => env('PII_KEY_V2_FILE'),
        ],
    ],

    'cipher' => 'aes-256-gcm',

    'prefix' => 'enc:',

    // Keep true only while existing plaintext rows are being migrated.
    'allow_legacy_plaintext' => env('PII_ALLOW_LEGACY_PLAINTEXT', true),

    'unlock_minutes' => 5,

    'export' => [
        'max_bins' => (int) env('PII_EXPORT_MAX_BINS', 5000),
        'max_application_ids' => (int) env('PII_EXPORT_MAX_APPLICATION_IDS', 5000),
        'max_tax_codes' => (int) env('PII_EXPORT_MAX_TAX_CODES', 5000),
        'max_file_kb' => (int) env('PII_EXPORT_MAX_FILE_KB', 2048),
    ],

    'audit_enabled' => env('PII_AUDIT_ENABLED', true),

    'key_backup' => [
        // These values are configurable so the reminder policy can change
        // without altering application code.
        'interval_days' => (int) env('PII_KEY_BACKUP_INTERVAL_DAYS', 60),
        'warning_days' => (int) env('PII_KEY_BACKUP_WARNING_DAYS', 5),
    ],
];
