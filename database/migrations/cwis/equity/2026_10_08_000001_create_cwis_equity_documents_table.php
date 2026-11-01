<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCwisEquityDocumentsTable extends Migration
{
    private const DOCUMENT_COLUMNS = [
        'training_requirement_document_id',
        'labor_rights_document_id',
        'safety_health_sop_document_id',
        'legal_recourse_document_id',
        'worker_union_registration_document_id',
        'right_to_unionize_document_id',
        'worker_union_operation_document_id',
        'worker_union_support_document_id',
        'social_security_coverage_document_id',
        'health_insurance_coverage_document_id',
    ];

    public function up()
    {
        Schema::create('cwis.equity_documents', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('disk', 30)->default('public');
            $table->text('path');
            $table->text('original_name');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });

        Schema::table('cwis.sanitation_worker_policy', function (Blueprint $table) {
            foreach (self::DOCUMENT_COLUMNS as $index => $column) {
                $table->foreign($column, 'fk_cwis_policy_document_' . $index)
                    ->references('id')->on('cwis.equity_documents');
            }
        });
    }

    public function down()
    {
        Schema::table('cwis.sanitation_worker_policy', function (Blueprint $table) {
            foreach (self::DOCUMENT_COLUMNS as $index => $column) {
                $table->dropForeign('fk_cwis_policy_document_' . $index);
            }
        });
        Schema::dropIfExists('cwis.equity_documents');
    }
}
