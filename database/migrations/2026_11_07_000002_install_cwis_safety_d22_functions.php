<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class InstallCwisSafetyD22Functions extends Migration
{
    public function up()
    {
        DB::statement('CREATE TABLE cwis.safety_d22_function_backup (signature text PRIMARY KEY, definition text NOT NULL)');
        DB::statement("INSERT INTO cwis.safety_d22_function_backup SELECT p.oid::regprocedure::text,pg_get_functiondef(p.oid)
            FROM pg_proc p JOIN pg_namespace n ON n.oid=p.pronamespace WHERE n.nspname='public'
            AND (p.proname='update_data_into_cwis_table_revised_2024' OR p.proname ~ '^update_data_into_cwis_table_sf_1[a-g]?_newsan$')");
        DB::unprepared(file_get_contents(database_path('sql/cwis_safety_d22.sql')));
        $master = DB::selectOne("SELECT pg_get_functiondef('public.update_data_into_cwis_table_revised_2024(integer)'::regprocedure) AS definition")->definition;
        $updated = preg_replace('/\bBEGIN\s*\n/', "BEGIN\n    PERFORM cwis.prepare_safety_d22_generation(_year);\n    PERFORM cwis.dispatch_safety_d22(_year);\n", $master, 1, $count);
        if ($count !== 1) { throw new RuntimeException('Could not locate the CWIS master. No Safety changes installed.'); }
        DB::unprepared($updated);
        (new \Database\Seeders\CwisSafetyD22DataSourceSeeder())->run();
    }

    public function down()
    {
        // Reverting just this migration would leave newly registered indicators without their calculators.
        throw new RuntimeException('Safety D2.2 rollback requires a reviewed coordinated restore. Original public functions are preserved in cwis.safety_d22_function_backup.');
    }
}
