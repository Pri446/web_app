<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCwisFormFieldsTable extends Migration
{
    public function up()
    {
        Schema::create('cwis.form_fields', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('data_source_id');
            $table->string('input_name')->unique();
            $table->string('kind', 20);
            $table->string('section');
            $table->string('label');
            $table->text('help_text')->nullable();
            $table->string('html_id');
            $table->string('display_code', 30);
            $table->string('validation_type', 20)->nullable();
            $table->decimal('maximum', 20, 2)->nullable();
            $table->string('step', 20)->nullable();
            $table->boolean('required_on_submit')->default(false);
            $table->string('parent_code', 30)->nullable();
            $table->text('evidence_hint')->nullable();
            $table->string('storage_table');
            $table->string('storage_column');
            $table->string('document_column')->nullable();
            $table->jsonb('not_greater_than')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('enabled')->default(true);
            $table->timestamps();
            $table->foreign('data_source_id')->references('id')->on('cwis.data_source');
            $table->index(['data_source_id', 'enabled']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('cwis.form_fields');
    }
}
