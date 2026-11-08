<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement('ALTER TABLE fsm.applications ADD COLUMN IF NOT EXISTS anf_locality varchar(255) DEFAULT NULL');
        DB::statement('ALTER TABLE fsm.applications ADD COLUMN IF NOT EXISTS is_anf boolean DEFAULT false');
        DB::statement('ALTER TABLE fsm.applications ADD COLUMN IF NOT EXISTS anf_nearest_locality varchar(255)');
        DB::statement('ALTER TABLE fsm.applications ADD COLUMN IF NOT EXISTS desludging_vehicle_size integer');
        DB::statement('ALTER TABLE fsm.applications ADD COLUMN IF NOT EXISTS trip_count integer');

        // Create columns if they do not exist
        DB::statement('ALTER TABLE fsm.applications ADD COLUMN IF NOT EXISTS emptying_status integer');
        DB::statement('ALTER TABLE fsm.applications ADD COLUMN IF NOT EXISTS feedback_status integer');
        DB::statement('ALTER TABLE fsm.applications ADD COLUMN IF NOT EXISTS sludge_collection_status integer');

        // Remove existing defaults before changing data types
        DB::statement('ALTER TABLE fsm.applications ALTER COLUMN emptying_status DROP DEFAULT');
        DB::statement('ALTER TABLE fsm.applications ALTER COLUMN feedback_status DROP DEFAULT');
        DB::statement('ALTER TABLE fsm.applications ALTER COLUMN sludge_collection_status DROP DEFAULT');

        // Convert existing columns to integer
        DB::statement('ALTER TABLE fsm.applications ALTER COLUMN emptying_status TYPE integer USING emptying_status::integer');
        DB::statement('ALTER TABLE fsm.applications ALTER COLUMN feedback_status TYPE integer USING feedback_status::integer');
        DB::statement('ALTER TABLE fsm.applications ALTER COLUMN sludge_collection_status TYPE integer USING sludge_collection_status::integer');

        // Set new integer defaults if required
        DB::statement('ALTER TABLE fsm.applications ALTER COLUMN emptying_status SET DEFAULT 0');
        DB::statement('ALTER TABLE fsm.applications ALTER COLUMN feedback_status SET DEFAULT 0');
        DB::statement('ALTER TABLE fsm.applications ALTER COLUMN sludge_collection_status SET DEFAULT 0');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('ALTER TABLE fsm.applications DROP COLUMN IF EXISTS trip_count');
        DB::statement('ALTER TABLE fsm.applications DROP COLUMN IF EXISTS desludging_vehicle_size');
        DB::statement('ALTER TABLE fsm.applications DROP COLUMN IF EXISTS anf_nearest_locality');
        DB::statement('ALTER TABLE fsm.applications DROP COLUMN IF EXISTS is_anf');
        DB::statement('ALTER TABLE fsm.applications DROP COLUMN IF EXISTS anf_locality');
        DB::statement('ALTER TABLE fsm.applications DROP COLUMN IF EXISTS emptying_status');
        DB::statement('ALTER TABLE fsm.applications DROP COLUMN IF EXISTS feedback_status');
        DB::statement('ALTER TABLE fsm.applications DROP COLUMN IF EXISTS sludge_collection_status');
    }
};
