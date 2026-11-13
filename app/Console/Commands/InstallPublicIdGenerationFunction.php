<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class InstallPublicIdGenerationFunction extends Command
{
    protected $signature = 'public-id:install-function
        {--database= : The database connection to use}
        {--force : Run in production without refusing the operation}';

    protected $description = 'Create or replace the PostgreSQL function used to generate missing public IDs';

    private const SQL_FILE = 'database/sql/public_id/generate_missing_public_ids.sql';

    public function handle(): int
    {
        if ($this->laravel->environment('production') && ! $this->option('force')) {
            $this->error('Refusing to install the database function in production without --force.');

            return Command::FAILURE;
        }

        $sqlPath = base_path(self::SQL_FILE);

        if (! is_file($sqlPath)) {
            $this->error('Public-ID function SQL file is missing: '.self::SQL_FILE);

            return Command::FAILURE;
        }

        try {
            $connection = DB::connection($this->option('database'));
            $connection->unprepared(file_get_contents($sqlPath));

            $installed = $connection->selectOne(
                "SELECT to_regprocedure('building_info.generate_missing_public_ids()') AS function_name"
            );
        } catch (Throwable $exception) {
            $this->error('Public-ID function installation failed: '.$exception->getMessage());

            return Command::FAILURE;
        }

        if (empty($installed->function_name)) {
            $this->error('PostgreSQL did not report the public-ID function after installation.');

            return Command::FAILURE;
        }

        $this->info('Public-ID generation function installed successfully.');
        $this->line('Function: building_info.generate_missing_public_ids()');
        $this->comment('Next, run: php artisan public-id:generate');

        return Command::SUCCESS;
    }
}
