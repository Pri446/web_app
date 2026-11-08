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
        DB::statement('ALTER TABLE fsm.emptyings ADD COLUMN IF NOT EXISTS total_time int DEFAULT NULL');
        DB::statement('ALTER TABLE fsm.emptyings ADD COLUMN IF NOT EXISTS trip_no int DEFAULT NULL');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('ALTER TABLE fsm.emptyings DROP COLUMN IF EXISTS total_time');
        DB::statement('ALTER TABLE fsm.emptyings DROP COLUMN IF EXISTS trip_no');
    }
};
