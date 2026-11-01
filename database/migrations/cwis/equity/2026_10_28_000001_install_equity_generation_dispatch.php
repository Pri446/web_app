<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class InstallEquityGenerationDispatch extends Migration
{
    public function up()
    {
        if (!DB::selectOne("SELECT to_regclass('cwis.generation_runs') IS NOT NULL AS present")->present) {
            Schema::create('cwis.generation_runs', function (Blueprint $table) {
                $table->integer('year')->primary();
                $table->string('country_code',3);
                $table->unsignedBigInteger('equity_submission_id')->nullable();
                $table->timestamp('generated_at');
                $table->foreign('equity_submission_id')->references('id')->on('cwis.equity_submissions');
            });
        }
        DB::statement('CREATE TABLE cwis.equity_generation_function_backup (signature text PRIMARY KEY, definition text NOT NULL)');
        DB::statement("INSERT INTO cwis.equity_generation_function_backup
            SELECT p.oid::regprocedure::text, pg_get_functiondef(p.oid)
            FROM pg_proc p JOIN pg_namespace n ON n.oid = p.pronamespace
            WHERE (n.nspname = 'cwis' AND p.proname IN ('generation_country','eq_2_population_totals','update_equity_indicator'))
                OR (n.nspname = 'public' AND (p.proname = 'generate_cwis_with_equity'
                    OR p.proname ~ '^update_data_into_cwis_table_eq_(2|3|4|4a|5|6_1|6_1a|6_1b|6_2|6_3|6_3a|6_3b|6_4|6_5)_newsan$'))");
        DB::unprepared(file_get_contents(database_path('sql/cwis/equity/cwis_equity_generation.sql')));
    }

    public function down()
    {
        foreach (DB::table('cwis.equity_generation_function_backup')->get() as $function) {
            DB::unprepared($function->definition);
        }
        DB::statement('DROP TABLE cwis.equity_generation_function_backup');
        // Keep generation history and newly installed routines to avoid deleting audit data/dependencies.
    }
}
