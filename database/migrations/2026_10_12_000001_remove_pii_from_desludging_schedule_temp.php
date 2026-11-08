<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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
        // The desludging scheduling queue belongs to an optional FSM feature
        // and is not installed in every Base IMIS deployment. In installations
        // without that feature there is no copied schedule PII to remove, so
        // complete this migration as a safe no-op instead of failing the whole
        // PII deployment.
        if (!Schema::hasTable('fsm.desludging_schedule_temp')) {
            return;
        }

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
        // Do not create an optional module table during rollback. Its own
        // feature migration remains responsible for creating that table.
        if (!Schema::hasTable('fsm.desludging_schedule_temp')) {
            return;
        }

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
