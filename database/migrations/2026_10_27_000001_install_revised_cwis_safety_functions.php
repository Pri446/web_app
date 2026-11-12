<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class InstallRevisedCwisSafetyFunctions extends Migration
{
    public function up()
    {
        $master = DB::selectOne("SELECT pg_get_functiondef('public.update_data_into_cwis_table_revised_2024(integer)'::regprocedure) AS definition")->definition;
        DB::statement('CREATE TABLE cwis.safety_function_backup (signature text PRIMARY KEY, definition text NOT NULL)');
        DB::statement("INSERT INTO cwis.safety_function_backup SELECT p.oid::regprocedure::text, pg_get_functiondef(p.oid)
            FROM pg_proc p JOIN pg_namespace n ON n.oid = p.pronamespace
            WHERE n.nspname = 'public' AND (p.proname = 'update_data_into_cwis_table_revised_2024'
                OR p.proname ~ '^update_data_into_cwis_table_sf_1[a-g]?_newsan$')");
        DB::unprepared(file_get_contents(database_path('sql/cwis_safety_revised.sql')));

        $updated = preg_replace('/\bBEGIN\s*\n/', "BEGIN\n    IF _year IS NOT NULL AND EXISTS (SELECT 1 FROM cwis.data_cwis WHERE year = _year) THEN\n        PERFORM public.update_data_into_cwis_table_sf_1_newsan(_year);\n    END IF;\n", $master, 1, $count);
        if ($count !== 1) {
            throw new RuntimeException('Unable to locate the CWIS master function body. No Safety changes installed.');
        }
        DB::unprepared($updated);

        DB::statement('LOCK TABLE cwis.data_source IN SHARE ROW EXCLUSIVE MODE');
        if (!DB::table('cwis.data_source')->where('indicator_code', 'SF-1')->exists()) {
            DB::table('cwis.data_source')->insert([
                'id' => DB::table('cwis.data_source')->max('id') + 1,
                'outcome' => 'safety', 'indicator_code' => 'SF-1',
                'label' => 'Access to safely managed sanitation',
            ]);
        }
        // Follow the existing Safety country mapping; do not invent a Bangladesh assignment.
        DB::statement("INSERT INTO cwis.indicator_country(data_source_id, country_code)
            SELECT target.id, mapping.country_code FROM cwis.data_source target
            CROSS JOIN cwis.data_source original JOIN cwis.indicator_country mapping ON mapping.data_source_id = original.id
            WHERE target.indicator_code = 'SF-1' AND original.indicator_code = 'SF-1a'
            ON CONFLICT (data_source_id, country_code) DO NOTHING");
    }

    public function down()
    {
        foreach (DB::table('cwis.safety_function_backup')->get() as $function) {
            DB::unprepared($function->definition);
        }
        if (!DB::table('cwis.safety_function_backup')->where('signature', 'like', '%update_data_into_cwis_table_sf_1_newsan(%')->exists()) {
            DB::statement('DROP FUNCTION public.update_data_into_cwis_table_sf_1_newsan(integer)');
        }
        DB::statement('DROP FUNCTION cwis.write_safety_revised(integer,varchar,numeric,numeric,numeric,numeric)');
        DB::statement('DROP FUNCTION cwis.calculate_safety_revised(integer,varchar,numeric,numeric,numeric,numeric)');
        DB::statement('DROP FUNCTION cwis.safety_buildings(integer)');
        DB::statement('DROP FUNCTION cwis.safety_eligible_containments(integer)');
        DB::statement('DROP FUNCTION cwis.safety_percentage(numeric,numeric)');
        DB::statement('DROP FUNCTION IF EXISTS cwis.safety_setting_numeric(text,numeric,numeric)');
        DB::statement('DROP TABLE cwis.safety_function_backup');
        // Keep catalogue/history rows: rolling back must not delete generated results.
    }
}
