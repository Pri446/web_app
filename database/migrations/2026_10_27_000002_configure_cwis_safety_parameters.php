<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class ConfigureCwisSafetyParameters extends Migration
{
    public function up()
    {
        DB::statement('CREATE TABLE cwis.safety_settings_function_backup (signature text PRIMARY KEY, definition text NOT NULL)');
        DB::statement("INSERT INTO cwis.safety_settings_function_backup
            SELECT p.oid::regprocedure::text, pg_get_functiondef(p.oid)
            FROM pg_proc p JOIN pg_namespace n ON n.oid = p.pronamespace
            WHERE (n.nspname = 'cwis' AND p.proname IN ('safety_percentage','safety_buildings',
                'safety_eligible_containments','calculate_safety_revised','write_safety_revised','safety_setting_numeric'))
            OR (n.nspname = 'public' AND p.proname ~ '^update_data_into_cwis_table_sf_1[a-g]?_newsan$')");
        DB::statement('LOCK TABLE public.site_settings IN SHARE ROW EXCLUSIVE MODE');
        foreach (config('cwis_safety_settings') as $name => $parameter) {
            if (!DB::table('public.site_settings')->where('category', 'cwis_setting')
                ->where('name', $name)->whereNull('deleted_at')->exists()) {
                DB::table('public.site_settings')->insert([
                    'id' => DB::table('public.site_settings')->max('id') + 1,
                    'name' => $name, 'value' => $parameter['default'], 'category' => 'cwis_setting',
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        }
        DB::unprepared(file_get_contents(database_path('sql/cwis_safety_revised.sql')));
    }

    public function down()
    {
        foreach (DB::table('cwis.safety_settings_function_backup')->get() as $function) {
            DB::unprepared($function->definition);
        }
        if (!DB::table('cwis.safety_settings_function_backup')->where('signature', 'like', '%safety_setting_numeric(%')->exists()) {
            DB::statement('DROP FUNCTION IF EXISTS cwis.safety_setting_numeric(text,numeric,numeric)');
        }
        DB::statement('DROP TABLE cwis.safety_settings_function_backup');
        // Preserve saved parameters and all generated results during rollback.
    }
}
