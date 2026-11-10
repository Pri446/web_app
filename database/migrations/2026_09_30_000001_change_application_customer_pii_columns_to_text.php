<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class ChangeApplicationCustomerPiiColumnsToText extends Migration
{
    public function up()
    {
        // AES-GCM envelopes are longer than the original customer values.
        DB::statement('ALTER TABLE fsm.applications ALTER COLUMN customer_name TYPE TEXT USING customer_name::TEXT');
        DB::statement('ALTER TABLE fsm.applications ALTER COLUMN customer_gender TYPE TEXT USING customer_gender::TEXT');
        DB::statement('ALTER TABLE fsm.applications ALTER COLUMN customer_contact TYPE TEXT USING customer_contact::TEXT');
    }

    public function down()
    {
        // Encryption cannot be safely reversed by a schema rollback.
        throw new RuntimeException(
            'Application customer PII TEXT columns require a reviewed manual rollback.'
        );
    }
}
