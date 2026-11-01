<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class ShareCountryChecksAcrossCwisIndicators extends Migration
{
    public function up()
    {
        DB::unprepared(file_get_contents(database_path('sql/cwis/equity/cwis_indicator_country_checks.sql')));
    }

    public function down()
    {
        DB::unprepared(file_get_contents(database_path('sql/cwis/equity/cwis_original_equity_submission_guard.sql')));
        DB::statement('DROP FUNCTION IF EXISTS cwis.require_indicator_country(varchar, varchar)');
        DB::statement('DROP FUNCTION IF EXISTS cwis.indicator_applies_to_country(varchar, varchar)');
        DB::statement('DROP FUNCTION IF EXISTS cwis.indicators_for_country(varchar)');
    }
}
