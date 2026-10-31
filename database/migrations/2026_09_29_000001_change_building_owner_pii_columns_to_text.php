<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class ChangeBuildingOwnerPiiColumnsToText extends Migration
{
    public function up()
    {
        // AES-256-GCM produces a versioned encryption envelope that is much
        // longer than the original human-readable value. PostgreSQL TEXT keeps
        // the encrypted payload intact without imposing a VARCHAR length that
        // could truncate ciphertext and make the PII permanently undecryptable.
        //
        // USING column::TEXT preserves existing plaintext or already-encrypted
        // values during the schema conversion. This migration changes only the
        // storage type; the separate PII backfill command performs encryption.
        DB::statement(
            'ALTER TABLE building_info.owners ' .
            'ALTER COLUMN owner_name TYPE TEXT USING owner_name::TEXT'
        );
        DB::statement(
            'ALTER TABLE building_info.owners ' .
            'ALTER COLUMN owner_gender TYPE TEXT USING owner_gender::TEXT'
        );
        DB::statement(
            'ALTER TABLE building_info.owners ' .
            'ALTER COLUMN owner_contact TYPE TEXT USING owner_contact::TEXT'
        );
        DB::statement(
            'ALTER TABLE building_info.owners ' .
            'ALTER COLUMN nid TYPE TEXT USING nid::TEXT'
        );
    }

    public function down()
    {
        // An automatic rollback to the former VARCHAR lengths is unsafe after
        // encryption because ciphertext may exceed those limits. Shrinking the
        // columns could truncate data, so rollback requires a reviewed manual
        // migration with confirmed lengths and a recovery plan.
        throw new RuntimeException(
            'Building owner PII TEXT columns require a reviewed manual rollback.'
        );
    }
}
