<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateDesludgingScheduleTempTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        DB::statement('CREATE SCHEMA IF NOT EXISTS fsm');

        Schema::create('fsm.desludging_schedule_temp', function (Blueprint $table) {
            $table->bigIncrements('id');

            // Service provider snapshot
            $table->unsignedBigInteger('service_provider_id');
            $table->string('service_provider_name')->nullable();

            // Building information
            $table->string('bin');
            $table->integer('ward')->nullable();
            $table->string('house_number')->nullable();
            $table->string('house_locality')->nullable();
            $table->string('road_code')->nullable();

            // Containment and scheduling information
            $table->string('containment_id');
            $table->decimal('fstp_distance', 12, 2)->nullable();
            $table->smallInteger('priority')->nullable();
            $table->unsignedInteger('sequence')->nullable();
            $table->smallInteger('status')->nullable()->default(0);

            // Queue-generation audit
            $table->unsignedBigInteger('generated_by')->nullable();
            $table->timestamp('generated_at')->nullable();

            $table->timestamps();

            // Prevent the same containment from appearing twice
            // for the same service provider.
            $table->unique(
                ['service_provider_id', 'containment_id'],
                'desludging_schedule_provider_containment_unique'
            );

            // Scheduling and filtering indexes
            $table->index(
                ['service_provider_id', 'status', 'sequence'],
                'desludging_schedule_provider_status_sequence_idx'
            );

            $table->index(
                ['priority', 'fstp_distance'],
                'desludging_schedule_priority_distance_idx'
            );

            $table->index('containment_id');
            $table->index('bin');
            $table->index('ward');
            $table->index('generated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('fsm.desludging_schedule_temp');
    }
}
