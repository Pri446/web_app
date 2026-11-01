<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateCwisRpmTables extends Migration
{
    public function up()
    {
        Schema::create('cwis.rpm_submissions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('reporting_year');
            $table->string('country_code', 3);
            $table->string('status', 20)->default('draft');
            foreach (['created_by', 'updated_by', 'submitted_by', 'approved_by'] as $column) {
                $table->unsignedBigInteger($column)->nullable();
            }
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->unique(['country_code', 'reporting_year'], 'uq_rpm_country_year');
        });
        DB::statement("ALTER TABLE cwis.rpm_submissions ADD CONSTRAINT chk_rpm_country CHECK (country_code IN ('ALL','NP','BD'))");
        DB::statement("ALTER TABLE cwis.rpm_submissions ADD CONSTRAINT chk_rpm_status CHECK (status IN ('draft','submitted','approved'))");
        Schema::create('cwis.rpm_answers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('submission_id');
            $table->string('indicator_code', 15);
            $table->string('answer', 3)->nullable();
            $table->text('document_path')->nullable();
            $table->text('document_name')->nullable();
            $table->timestamps();
            $table->unique(['submission_id', 'indicator_code'], 'uq_rpm_answer');
            $table->foreign('submission_id')->references('id')->on('cwis.rpm_submissions')->onDelete('cascade');
        });
        DB::statement("ALTER TABLE cwis.rpm_answers ADD CONSTRAINT chk_rpm_answer CHECK (answer IS NULL OR answer IN ('Yes','No'))");
    }

    public function down()
    {
        Schema::dropIfExists('cwis.rpm_answers');
        Schema::dropIfExists('cwis.rpm_submissions');
    }
}
