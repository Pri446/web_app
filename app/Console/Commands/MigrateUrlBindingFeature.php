<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class MigrateUrlBindingFeature extends Command
{
    protected $signature = 'migrate:url-binding
        {--database= : The database connection to use}
        {--pretend : Display the SQL without executing it}
        {--force : Run without confirmation in production}';

    protected $description = 'Run all migrations required by public-ID URL binding';

    /**
     * Keep the original migration as the tracked unit. The command is an
     * orchestration layer and therefore preserves normal migrate/rollback
     * behavior.
     */
    private const MIGRATION_PATHS = [
        'database/migrations/2026_08_12_000001_add_public_ids_to_buildings_and_containments.php',
    ];

    public function handle(): int
    {
        foreach (self::MIGRATION_PATHS as $path) {
            if (!is_file(base_path($path))) {
                $this->error("Required URL-binding migration is missing: {$path}");

                return 1;
            }
        }

        $this->info('Running the public-ID URL-binding migrations through Laravel migration tracking.');

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
            $this->error('URL-binding migration failed.');

            return $exitCode;
        }

        if (!$this->option('pretend')) {
            $this->info('Public-ID URL-binding migrations completed.');
        }

        return 0;
    }
}
