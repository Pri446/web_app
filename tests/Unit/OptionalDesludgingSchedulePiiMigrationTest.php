<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class OptionalDesludgingSchedulePiiMigrationTest extends TestCase
{
    public function test_missing_optional_schedule_table_is_a_safe_no_op(): void
    {
        require_once database_path(
            'migrations/2026_10_12_000001_remove_pii_from_desludging_schedule_temp.php'
        );

        Schema::shouldReceive('hasTable')
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
