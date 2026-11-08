<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        DB::statement('CREATE SCHEMA IF NOT EXISTS fsm');
        DB::statement('CREATE SEQUENCE IF NOT EXISTS fsm.service_provider_sequence_id_seq');
        DB::statement("CREATE TABLE IF NOT EXISTS fsm.service_provider_sequence (
            id integer NOT NULL DEFAULT nextval('fsm.service_provider_sequence_id_seq'::regclass),
            current_sequence boolean,
            desludging_vehicle_size integer,
            sequence_order integer,
            service_provider_id integer,
            CONSTRAINT service_provider_sequence_pkey PRIMARY KEY (id)
        )");
        DB::statement('ALTER SEQUENCE fsm.service_provider_sequence_id_seq OWNED BY fsm.service_provider_sequence.id');
    }

    public function down()
    {
        // Preserve existing scheduling data: this table may predate the migration.
    }
};
