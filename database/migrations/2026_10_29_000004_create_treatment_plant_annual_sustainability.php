<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateTreatmentPlantAnnualSustainability extends Migration
{
    public function up()
    {
        Schema::create('cwis.treatment_plant_annual_sustainability', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('treatment_plant_id');
            $table->integer('data_year');
            $table->decimal('treated_effluent_generated_volume', 18, 3)->nullable();
            $table->decimal('treated_effluent_reused_volume', 18, 3)->nullable();
            $table->decimal('treated_biosolids_generated_volume', 18, 3)->nullable();
            $table->decimal('treated_biosolids_reused_volume', 18, 3)->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->unique(['treatment_plant_id', 'data_year'], 'uq_plant_sustainability_year');
            $table->index('data_year');
            $table->foreign('treatment_plant_id')->references('id')->on('fsm.treatment_plants')->onDelete('restrict');
        });
        DB::statement('ALTER TABLE cwis.treatment_plant_annual_sustainability ADD CONSTRAINT chk_plant_sustainability_year CHECK (data_year BETWEEN 1900 AND 9999)');
        foreach (['effluent', 'biosolids'] as $kind) {
            DB::statement("ALTER TABLE cwis.treatment_plant_annual_sustainability ADD CONSTRAINT chk_plant_{$kind}_volumes CHECK (
                (treated_{$kind}_generated_volume IS NULL OR treated_{$kind}_generated_volume >= 0)
                AND (treated_{$kind}_reused_volume IS NULL OR treated_{$kind}_reused_volume >= 0)
                AND (treated_{$kind}_reused_volume IS NULL OR treated_{$kind}_generated_volume IS NULL
                    OR treated_{$kind}_reused_volume <= treated_{$kind}_generated_volume))");
        }
    }

    public function down()
    {
        Schema::dropIfExists('cwis.treatment_plant_annual_sustainability');
    }
}
