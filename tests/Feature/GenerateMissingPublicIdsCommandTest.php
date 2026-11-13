<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;

class GenerateMissingPublicIdsCommandTest extends TestCase
{
    public function test_command_executes_database_function_and_reports_counts(): void
    {
        $connection = Mockery::mock();
        $connection->shouldReceive('selectOne')
            ->once()
            ->with('SELECT * FROM building_info.generate_missing_public_ids()')
            ->andReturn((object) [
                'buildings_updated' => 3,
                'containments_updated' => 2,
            ]);

        DB::shouldReceive('connection')
            ->once()
            ->with(null)
            ->andReturn($connection);

        $this->artisan('public-id:generate')
            ->expectsOutput('Public-ID generation completed successfully.')
            ->expectsOutput('Buildings updated: 3')
            ->expectsOutput('Containments updated: 2')
            ->assertExitCode(0);
    }

    public function test_command_reports_when_no_missing_ids_exist(): void
    {
        $connection = Mockery::mock();
        $connection->shouldReceive('selectOne')
            ->once()
            ->andReturn((object) [
                'buildings_updated' => 0,
                'containments_updated' => 0,
            ]);

        DB::shouldReceive('connection')
            ->once()
            ->with(null)
            ->andReturn($connection);

        $this->artisan('public-id:generate')
            ->expectsOutput('No missing public IDs were found. Existing IDs were not changed.')
            ->assertExitCode(0);
    }
}
