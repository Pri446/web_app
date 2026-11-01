<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class MigratePiiFeature extends Command
{
    protected $signature = 'migrate:pii
        {--database= : The database connection to use}
        {--pretend : Display the SQL without executing it}
        {--force : Run without confirmation in production}';

    protected $description = 'Run every migration required by the PII security feature in dependency order';

    /**
     * These remain separate migrations so Laravel can record and roll them
     * back correctly. This command only provides one safe deployment entry
     * point; it never invokes migration classes directly.
     */
    private const MIGRATION_PATHS = [
        'database/migrations/2026_08_31_000001_revoke_guest_access_to_emptyings.php',
        'database/migrations/2026_09_07_000001_add_pii_pin_hash_to_auth_users.php',
        'database/migrations/2026_09_08_000001_create_owner_pii_audit_logs_table.php',
        'database/migrations/2026_09_08_000002_drop_pii_pin_hash_from_auth_users.php',
        // Owner ciphertext must have unrestricted TEXT storage before the
        // existing-owner encryption backfill is run after this schema command.
        'database/migrations/2026_09_29_000001_change_building_owner_pii_columns_to_text.php',
        'database/migrations/2026_09_30_000001_change_application_customer_pii_columns_to_text.php',
        'database/migrations/2026_10_01_000001_change_property_tax_owner_pii_columns_to_text.php',
        'database/migrations/2026_10_09_000001_create_pii_key_backup_downloads_table.php',
        'database/migrations/2026_10_12_000001_remove_pii_from_desludging_schedule_temp.php',
    ];

    public function handle(): int
    {
        foreach (self::MIGRATION_PATHS as $path) {
            if (!is_file(base_path($path))) {
                $this->error("Required PII migration is missing: {$path}");

                return 1;
            }
        }

        $this->info('Running the PII feature migrations through Laravel migration tracking.');

        $arguments = [
            '--path' => self::MIGRATION_PATHS,
            '--pretend' => (bool) $this->option('pretend'),
            '--force' => (bool) $this->option('force'),
        ];

        if ($this->option('database')) {
            $arguments['--database'] = $this->option('database');
        }

        $exitCode = $this->call('migrate', $arguments);

        if ($exitCode !== 0) {
            $this->error('PII feature migration failed. Data backfill was not started.');

            return $exitCode;
        }

        if (!$this->option('pretend')) {
            $this->info('PII schema migrations completed.');
            $this->warn('This command changes schema only. Run the PII seeders, encryption backfills, and verification commands separately.');
        }

        return 0;
    }
}
