<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class RemovePiiFromDesludgingScheduleTemp extends Migration
{
    /**
     * Remove legacy PII snapshots from the operational scheduling queue.
     *
     * The queue only needs building, containment, provider and scheduling
     * identifiers. Dropping these columns also removes previously copied PII,
     * so it cannot be returned accidentally by a future broad SELECT.
     */
    public function up()
    {
        DB::statement(
            'ALTER TABLE fsm.desludging_schedule_temp ' .
            'DROP COLUMN IF EXISTS owner_name, ' .
            'DROP COLUMN IF EXISTS owner_gender, ' .
            'DROP COLUMN IF EXISTS owner_contact, ' .
            'DROP COLUMN IF EXISTS respondent_name, ' .
            'DROP COLUMN IF EXISTS respondent_contact'
        );
    }

    /**
     * Restore only the empty schema on rollback; removed PII cannot be
     * reconstructed from this temporary queue.
     */
    public function down()
    {
        DB::statement(
            'ALTER TABLE fsm.desludging_schedule_temp ' .
            'ADD COLUMN IF NOT EXISTS owner_name VARCHAR(255) NULL, ' .
            'ADD COLUMN IF NOT EXISTS owner_gender VARCHAR(50) NULL, ' .
            'ADD COLUMN IF NOT EXISTS owner_contact VARCHAR(255) NULL, ' .
            'ADD COLUMN IF NOT EXISTS respondent_name VARCHAR(255) NULL, ' .
            'ADD COLUMN IF NOT EXISTS respondent_contact VARCHAR(255) NULL'
        );
    }
}
