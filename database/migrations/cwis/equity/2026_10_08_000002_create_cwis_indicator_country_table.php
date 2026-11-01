<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateCwisIndicatorCountryTable extends Migration
{
    public function up()
    {
        Schema::create('cwis.indicator_country', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('data_source_id');
            $table->string('country_code', 2);
            $table->unique(['data_source_id', 'country_code'], 'uq_cwis_indicator_country');
            $table->index('country_code', 'idx_cwis_indicator_country_code');
            $table->foreign('data_source_id', 'fk_cwis_indicator_country_source')
                ->references('id')->on('cwis.data_source');
        });

        DB::statement("ALTER TABLE cwis.indicator_country ADD CONSTRAINT chk_cwis_indicator_country_code CHECK (country_code IN ('NP', 'BD'))");
    }

    public function down()
    {
        Schema::dropIfExists('cwis.indicator_country');
    }
}
