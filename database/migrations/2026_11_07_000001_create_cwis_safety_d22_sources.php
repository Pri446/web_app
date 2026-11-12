<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateCwisSafetyD22Sources extends Migration
{
    public function up()
    {
        Schema::create('cwis.safety_submissions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('reporting_year');
            $table->string('country_code', 3);
            $table->string('status', 20)->default('draft');
            foreach (['created_by', 'updated_by', 'submitted_by', 'approved_by'] as $field) { $table->unsignedBigInteger($field)->nullable(); }
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->boolean('public_spaces_complete')->nullable();
            $table->timestamps();
            $table->unique(['country_code', 'reporting_year'], 'uq_safety_country_year');
        });
        DB::statement("ALTER TABLE cwis.safety_submissions ADD CHECK (country_code IN ('ALL','NP','BD')), ADD CHECK (status IN ('draft','submitted','approved')), ADD CHECK (status <> 'approved' OR approved_at IS NOT NULL)");
        Schema::create('cwis.safety_answers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('submission_id');
            $table->string('indicator_code', 15);
            $table->string('answer', 3)->nullable();
            foreach (['document_path', 'document_name', 'biosolids_document_path', 'biosolids_document_name'] as $field) { $table->text($field)->nullable(); }
            $table->timestamps();
            $table->unique(['submission_id', 'indicator_code'], 'uq_safety_answer');
            $table->foreign('submission_id')->references('id')->on('cwis.safety_submissions');
        });
        DB::statement("ALTER TABLE cwis.safety_answers ADD CHECK (answer IS NULL OR answer IN ('Yes','No')), ADD CHECK (indicator_code IN ('SF-11a','SF-11a1','SF-11b','SF-11b1','SF-11c','SF-12'))");
        Schema::create('cwis.safety_waiting_times', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('submission_id');
            $table->integer('toilet_id');
            $table->decimal('waiting_minutes', 12, 3)->nullable();
            $table->integer('number_users')->nullable();
            $table->text('document_path')->nullable();
            $table->text('document_name')->nullable();
            $table->timestamps();
            $table->unique(['submission_id', 'toilet_id'], 'uq_safety_waiting_toilet');
            $table->foreign('submission_id')->references('id')->on('cwis.safety_submissions');
            $table->foreign('toilet_id')->references('id')->on('fsm.toilets');
        });
        DB::statement('ALTER TABLE cwis.safety_waiting_times ADD CHECK (waiting_minutes IS NULL OR waiting_minutes BETWEEN 0 AND 1440), ADD CHECK (number_users IS NULL OR number_users > 0)');
        Schema::create('cwis.safety_public_spaces', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('submission_id');
            $table->string('name');
            $table->integer('toilet_id')->nullable();
            $table->timestamps();
            $table->unique(['submission_id', 'name'], 'uq_safety_public_space');
            $table->foreign('submission_id')->references('id')->on('cwis.safety_submissions');
            $table->foreign('toilet_id')->references('id')->on('fsm.toilets');
        });
        Schema::create('cwis.safety_generation_runs', function (Blueprint $table) {
            $table->integer('year')->primary();
            $table->string('country_code', 3);
            $table->unsignedBigInteger('submission_id')->nullable();
            $table->jsonb('source_snapshot');
            $table->timestamp('generated_at');
            $table->foreign('submission_id')->references('id')->on('cwis.safety_submissions');
        });
        DB::statement('LOCK TABLE public.site_settings IN SHARE ROW EXCLUSIVE MODE');
        $nextSettingId = (int) DB::table('public.site_settings')->max('id');
        foreach (config('cwis_safety_d22.decisions') as $key => $decision) {
            if (!DB::table('public.site_settings')->where('category', 'cwis_setting')->where('name', $key)->exists()) {
                DB::table('public.site_settings')->insert(['id' => ++$nextSettingId, 'name' => $key, 'value' => $decision['default'] ?? 'pending', 'category' => 'cwis_setting', 'created_at' => now(), 'updated_at' => now()]);
            }
        }
    }

    public function down()
    {
        // Approved evidence and annual source records must not be silently discarded.
        throw new RuntimeException('Safety source tables contain audit data. Restore a reviewed database backup instead of dropping them automatically.');
    }
}
