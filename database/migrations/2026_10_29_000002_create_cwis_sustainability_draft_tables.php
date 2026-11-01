<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateCwisSustainabilityDraftTables extends Migration
{
    public function up()
    {
        Schema::create('cwis.sustainability_submissions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('reporting_year');
            $table->string('country_code', 3);
            $table->string('status', 20)->default('draft');
            $table->string('currency_code', 3)->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->unique(['country_code', 'reporting_year'], 'uq_sustainability_country_year');
        });
        DB::statement("ALTER TABLE cwis.sustainability_submissions ADD CONSTRAINT chk_sustainability_country CHECK (country_code IN ('ALL','NP','BD'))");
        Schema::create('cwis.sustainability_financials', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('submission_id')->unique();
            foreach (['ct_pt_user_fee_revenue', 'ct_pt_om_expenditure', 'fsm_chain_revenue',
                'sewerage_charges_revenue', 'sanitation_chain_om_expenditure',
                'gov_funded_treatment_capex', 'gov_funded_ct_pt_capex', 'gov_funded_desludging_capex',
                'annualized_treatment_capex', 'annualized_ct_pt_capex', 'annualized_desludging_capex'] as $column) {
                $table->decimal($column, 18, 2)->nullable();
            }
            $table->timestamps();
            $table->foreign('submission_id')->references('id')->on('cwis.sustainability_submissions')->onDelete('cascade');
        });
        Schema::create('cwis.sustainability_campaigns', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('submission_id');
            $table->string('indicator_code', 15);
            $table->string('answer', 3)->nullable();
            $table->text('document_path')->nullable();
            $table->text('document_name')->nullable();
            $table->timestamps();
            $table->unique(['submission_id', 'indicator_code'], 'uq_sustainability_campaign');
            $table->foreign('submission_id')->references('id')->on('cwis.sustainability_submissions')->onDelete('cascade');
        });
        DB::statement("ALTER TABLE cwis.sustainability_campaigns ADD CONSTRAINT chk_sustainability_answer CHECK (answer IS NULL OR answer IN ('Yes','No'))");
    }

    public function down()
    {
        Schema::dropIfExists('cwis.sustainability_campaigns');
        Schema::dropIfExists('cwis.sustainability_financials');
        Schema::dropIfExists('cwis.sustainability_submissions');
    }
}
