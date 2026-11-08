<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class ChangePropertyTaxOwnerPiiColumnsToText extends Migration
{
    public function up()
    {
        // AES-GCM envelopes are longer than the original owner values.
        DB::statement('ALTER TABLE taxpayment_info.tax_payments ALTER COLUMN owner_name TYPE TEXT USING owner_name::TEXT');
        DB::statement('ALTER TABLE taxpayment_info.tax_payments ALTER COLUMN owner_contact TYPE TEXT USING owner_contact::TEXT');

        // Rebuild the derived map/status table without owner PII. This also
        // replaces the database function so future CSV imports stay safe.
        DB::unprepared(Config::get('taxpayment-info.fnc_create_taxpaymentstatus'));
        DB::statement('select taxpayment_info.fnc_taxpaymentstatus()');
    }

    public function down()
    {
        throw new RuntimeException(
            'Property Tax owner PII TEXT columns require a reviewed manual rollback.'
        );
    }
}
