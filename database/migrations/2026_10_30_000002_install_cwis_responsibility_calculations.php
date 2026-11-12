<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class InstallCwisResponsibilityCalculations extends Migration
{
    public function up()
    {
        Schema::create('cwis.responsibility_generation_runs', function (Blueprint $table) {
            $table->integer('year')->primary();
            $table->string('country_code', 3);
            $table->unsignedBigInteger('submission_id');
            $table->jsonb('source_snapshot');
            $table->timestamp('generated_at');
            $table->foreign('submission_id')->references('id')->on('cwis.responsibility_submissions');
        });
        DB::unprepared(file_get_contents(database_path('sql/cwis_responsibility_calculations.sql')));
    }

    public function down()
    {
        // Preserve approved source snapshots and routines for audit, as with Sustainability.
    }
}
