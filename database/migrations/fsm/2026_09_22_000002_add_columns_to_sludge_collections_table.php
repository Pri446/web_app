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
        DB::statement('ALTER TABLE fsm.sludge_collections ADD COLUMN IF NOT EXISTS trip_no integer');
        DB::statement('ALTER TABLE fsm.sludge_collections ADD COLUMN IF NOT EXISTS tipping_fee_amount integer');
        DB::statement('ALTER TABLE fsm.sludge_collections ADD COLUMN IF NOT EXISTS total_time integer');
        DB::statement('ALTER TABLE fsm.sludge_collections ADD COLUMN IF NOT EXISTS tipping_fee_receipt_no varchar(255)');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('ALTER TABLE fsm.sludge_collections DROP COLUMN IF EXISTS total_time');
        DB::statement('ALTER TABLE fsm.sludge_collections DROP COLUMN IF EXISTS tipping_fee_amount');
        DB::statement('ALTER TABLE fsm.sludge_collections DROP COLUMN IF EXISTS trip_no');
        DB::statement('ALTER TABLE fsm.sludge_collections DROP COLUMN IF EXISTS tipping_fee_receipt_no');
    }
};
