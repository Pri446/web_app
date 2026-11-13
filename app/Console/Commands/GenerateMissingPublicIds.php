<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class GenerateMissingPublicIds extends Command
{
    protected $signature = 'public-id:generate
        {--database= : The database connection to use}
        {--force : Run in production without refusing the operation}';

    protected $description = 'Generate UUID public IDs for Building and Containment records where public_id is missing';

    public function handle(): int
    {
        if ($this->laravel->environment('production') && ! $this->option('force')) {
            $this->error('Refusing to generate public IDs in production without --force.');

            return Command::FAILURE;
        }

        try {
            $result = DB::connection($this->option('database'))
                ->selectOne('SELECT * FROM building_info.generate_missing_public_ids()');
        } catch (Throwable $exception) {
            $this->error('Public-ID generation failed: '.$exception->getMessage());
            $this->line('Verify that building_info.generate_missing_public_ids() exists in PostgreSQL.');

            return Command::FAILURE;
        }

        $buildingCount = (int) ($result->buildings_updated ?? 0);
        $containmentCount = (int) ($result->containments_updated ?? 0);

        $this->info('Public-ID generation completed successfully.');
        $this->line("Buildings updated: {$buildingCount}");
        $this->line("Containments updated: {$containmentCount}");

        if ($buildingCount === 0 && $containmentCount === 0) {
            $this->comment('No missing public IDs were found. Existing IDs were not changed.');
        }

        return Command::SUCCESS;
    }
}
