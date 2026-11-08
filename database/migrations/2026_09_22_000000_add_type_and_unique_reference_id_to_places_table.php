<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('layer_info.places', function (Blueprint $table) {
            if (!Schema::hasColumn('layer_info.places', 'type')) {
                $table->string('type')->nullable();
            }
            if (!Schema::hasColumn('layer_info.places', 'unique_reference_id')) {
                $table->string('unique_reference_id')->nullable()->unique();
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('layer_info.places', function (Blueprint $table) {
            if (Schema::hasColumn('layer_info.places', 'unique_reference_id')) {
                $table->dropColumn('unique_reference_id');
            }
            if (Schema::hasColumn('layer_info.places', 'type')) {
                $table->dropColumn('type');
            }
        });
    }
};
