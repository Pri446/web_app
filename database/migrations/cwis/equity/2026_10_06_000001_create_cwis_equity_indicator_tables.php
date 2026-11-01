<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCwisEquityIndicatorTables extends Migration
{
    public function up()
    {
        Schema::create('cwis.equity_submissions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('reporting_year');
            $table->string('status', 20)->default('draft');
            $table->unsignedBigInteger('submitted_by')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['reporting_year', 'status'], 'idx_equity_submissions_year_status');
        });

        Schema::create('cwis.equity_subsidies', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('submission_id');
            $table->decimal('total_subsidies_amount_nss', 12, 2)->nullable();
            $table->decimal('total_subsidies_amount_ss', 12, 2)->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique('submission_id', 'uq_equity_subsidies_submission');
        });

        Schema::create('cwis.sanitation_personnel_snapshot', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('submission_id');
            $table->integer('women_employee_count')->nullable();
            $table->integer('total_employee_count')->nullable();
            $table->integer('women_leadership_count')->nullable();
            $table->integer('total_leadership_count')->nullable();
            $table->decimal('average_salary_of_women_in_sanitation', 18, 2)->nullable();
            $table->decimal('average_salary_of_men_in_sanitation', 18, 2)->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique('submission_id', 'uq_personnel_snapshot_submission');
        });

        Schema::create('cwis.sanitation_worker_policy', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('submission_id');
            $table->boolean('training_certification_required')->nullable();
            $table->unsignedBigInteger('training_requirement_document_id')->nullable();
            $table->boolean('covers_labor_rights_and_recourse')->nullable();
            $table->unsignedBigInteger('labor_rights_document_id')->nullable();
            $table->boolean('covers_safety_health_and_sop')->nullable();
            $table->unsignedBigInteger('safety_health_sop_document_id')->nullable();
            $table->boolean('legal_recourse_available_to_all')->nullable();
            $table->unsignedBigInteger('legal_recourse_document_id')->nullable();
            $table->boolean('worker_union_exists')->nullable();
            $table->unsignedBigInteger('worker_union_registration_document_id')->nullable();
            $table->boolean('right_to_unionize')->nullable();
            $table->unsignedBigInteger('right_to_unionize_document_id')->nullable();
            $table->boolean('worker_union_operational')->nullable();
            $table->unsignedBigInteger('worker_union_operation_document_id')->nullable();
            $table->boolean('city_supports_worker_union')->nullable();
            $table->unsignedBigInteger('worker_union_support_document_id')->nullable();
            $table->boolean('all_workers_social_security_covered')->nullable();
            $table->unsignedBigInteger('social_security_coverage_document_id')->nullable();
            $table->boolean('all_workers_health_insurance_covered')->nullable();
            $table->unsignedBigInteger('health_insurance_coverage_document_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique('submission_id', 'uq_worker_policy_submission');
        });

    }

    public function down()
    {
        Schema::dropIfExists('cwis.sanitation_worker_policy');
        Schema::dropIfExists('cwis.sanitation_personnel_snapshot');
        Schema::dropIfExists('cwis.equity_subsidies');
        Schema::dropIfExists('cwis.equity_submissions');
    }
}
