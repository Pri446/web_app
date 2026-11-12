<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Connection;
use Illuminate\Database\Schema\Builder;
use Mockery;
use Tests\TestCase;

class OptionalDesludgingSchedulePiiMigrationTest extends TestCase
{
    public function test_missing_optional_schedule_table_is_a_safe_no_op(): void
    {
        require_once database_path(
            'migrations/2026_10_12_000001_remove_pii_from_desludging_schedule_temp.php'
        );

        $schema = Mockery::mock(Builder::class);
        $connection = Mockery::mock(Connection::class);
        $connection->shouldReceive('getSchemaBuilder')->twice()->andReturn($schema);
        DB::shouldReceive('connection')->twice()->andReturn($connection);

        $schema->shouldReceive('hasTable')
            ->twice()
            ->with('fsm.desludging_schedule_temp')
            ->andReturn(false);

        // Neither deployment nor rollback may try to alter or create a table
        // owned by an optional FSM feature that is not installed.
        DB::shouldReceive('statement')->never();

        $migration = new \RemovePiiFromDesludgingScheduleTemp();
        $migration->up();
        $migration->down();

        $this->assertTrue(true);
    }
}
