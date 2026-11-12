<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class InstallCwisSustainabilityCalculations extends Migration
{
    public function up()
    {
        Schema::create('cwis.sustainability_generation_runs', function (Blueprint $table) {
            $table->integer('year')->primary();
            $table->string('country_code', 3);
            $table->unsignedBigInteger('submission_id')->nullable();
            $table->integer('source_data_year');
            $table->jsonb('source_snapshot');
            $table->jsonb('plant_snapshot');
            $table->timestamp('generated_at');
            $table->foreign('submission_id')->references('id')->on('cwis.sustainability_submissions');
        });
        // Only add Sustainability routines; existing Equity/Safety database functions are untouched.
        DB::unprepared(file_get_contents(database_path('sql/cwis_sustainability_calculations.sql')));
    }

    public function down()
    {
        // Retain source snapshots and routines for audit rather than deleting generation history.
    }
}
