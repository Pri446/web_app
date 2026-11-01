<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class InstallCwisEquityCalculationFunctions extends Migration
{
    public function up()
    {
        DB::unprepared(file_get_contents(database_path('sql/cwis/equity/cwis_equity_calculations.sql')));
    }

    public function down()
    {
        foreach (['6_5', '6_4', '6_3b', '6_3a', '6_3', '6_2', '6_1b', '6_1a', '6_1', '5', '4a', '4', '3'] as $suffix) {
            DB::statement('DROP FUNCTION IF EXISTS cwis.calculate_eq_' . $suffix . '(bigint, varchar)');
        }
        DB::statement('DROP FUNCTION IF EXISTS cwis.calculate_eq_2(numeric, numeric, numeric, numeric)');
        DB::statement('DROP FUNCTION IF EXISTS cwis.equity_policy_result(boolean, bigint)');
        DB::statement('DROP FUNCTION IF EXISTS cwis.require_approved_equity_submission(bigint, varchar, varchar)');
    }
}
