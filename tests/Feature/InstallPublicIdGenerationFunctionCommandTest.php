<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;

class InstallPublicIdGenerationFunctionCommandTest extends TestCase
{
    public function test_command_installs_and_verifies_database_function(): void
    {
        $connection = Mockery::mock();
        $connection->shouldReceive('unprepared')
            ->once()
            ->with(Mockery::on(fn ($sql) => str_contains(
                $sql,
                'CREATE OR REPLACE FUNCTION building_info.generate_missing_public_ids()'
            )))
            ->andReturn(true);
        $connection->shouldReceive('selectOne')
            ->once()
            ->with("SELECT to_regprocedure('building_info.generate_missing_public_ids()') AS function_name")
            ->andReturn((object) [
                'function_name' => 'building_info.generate_missing_public_ids()',
            ]);

        DB::shouldReceive('connection')
            ->once()
            ->with(null)
            ->andReturn($connection);

        $this->artisan('public-id:install-function')
            ->expectsOutput('Public-ID generation function installed successfully.')
            ->expectsOutput('Function: building_info.generate_missing_public_ids()')
            ->expectsOutput('Next, run: php artisan public-id:generate')
            ->assertExitCode(0);
    }
}
