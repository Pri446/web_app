<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class InstallCwisSafetyCountIndicators extends Migration
{
    public function up()
    {
        $signature = 'public.update_data_into_cwis_table_revised_2024(integer)';
        $master = DB::selectOne('SELECT pg_get_functiondef(?::regprocedure) AS definition', [$signature])->definition;
        $updated = preg_replace('/\bBEGIN\s*\n/', "BEGIN\n    PERFORM cwis.dispatch_safety_counts(_year);\n", $master, 1, $count);
        if ($count !== 1 || strpos($master, 'cwis.dispatch_safety_counts(') !== false) {
            throw new RuntimeException('Unable to install the Safety count dispatch safely.');
        }
        DB::statement('CREATE TABLE cwis.safety_counts_function_backup (signature text PRIMARY KEY, definition text NOT NULL)');
        DB::table('cwis.safety_counts_function_backup')->insert(['signature' => $signature, 'definition' => $master]);
        DB::unprepared(file_get_contents(database_path('sql/cwis_safety_counts.sql')));
        DB::unprepared($updated);

        DB::statement('LOCK TABLE cwis.data_source IN SHARE ROW EXCLUSIVE MODE');
        foreach ([
            'SF-0a' => 'Number of buildings with safely managed sanitation',
            'SF-0b' => 'Number of households with safely managed sanitation',
            'SF-0c' => 'Population with safely managed sanitation',
        ] as $code => $label) {
            $existing = DB::table('cwis.data_source')->where('indicator_code', $code)->first();
            if ($existing) {
                DB::table('cwis.data_source')->where('id', $existing->id)->update(['outcome' => 'safety', 'label' => $label]);
            } else {
                DB::table('cwis.data_source')->insert([
                    'id' => ((int) DB::table('cwis.data_source')->max('id')) + 1,
                    'indicator_code' => $code, 'outcome' => 'safety', 'label' => $label,
                ]);
            }
        }
        // Inherit existing Safety applicability rather than inventing country requirements.
        DB::statement("INSERT INTO cwis.indicator_country(data_source_id, country_code)
            SELECT target.id, mapping.country_code FROM cwis.data_source target
            CROSS JOIN cwis.data_source original JOIN cwis.indicator_country mapping ON mapping.data_source_id = original.id
            WHERE target.indicator_code IN ('SF-0a','SF-0b','SF-0c') AND original.indicator_code = 'SF-1a'
            ON CONFLICT (data_source_id, country_code) DO NOTHING");
    }

    public function down()
    {
        foreach (DB::table('cwis.safety_counts_function_backup')->get() as $function) {
            DB::unprepared($function->definition);
        }
        DB::statement('DROP FUNCTION cwis.dispatch_safety_counts(integer)');
        foreach (['0a', '0b', '0c'] as $suffix) {
            DB::statement('DROP FUNCTION public.update_data_into_cwis_table_sf_' . $suffix . '_newsan(integer)');
        }
        DB::statement('DROP FUNCTION cwis.write_safety_count(integer,varchar)');
        foreach (['0a', '0b', '0c'] as $suffix) {
            DB::statement('DROP FUNCTION cwis.calculate_sf_' . $suffix . '(integer)');
        }
        DB::statement('DROP FUNCTION cwis.calculate_safety_count(integer,varchar)');
        DB::statement('DROP TABLE cwis.safety_counts_function_backup');
        // Retain catalogue mappings and generated history during rollback.
    }
}
