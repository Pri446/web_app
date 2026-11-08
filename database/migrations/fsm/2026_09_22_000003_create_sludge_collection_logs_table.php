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
        DB::statement('CREATE SCHEMA IF NOT EXISTS fsm');

        DB::statement('
            CREATE TABLE IF NOT EXISTS fsm.sludge_collections_log (
                id serial PRIMARY KEY,
                application_id integer NULL,
                treatment_plant_id integer NULL,
                volume_of_sludge numeric NULL,
                date date NULL,
                entry_time time NULL,
                exit_time time NULL,
                desludging_vehicle_id integer NULL,
                user_id integer NULL,
                service_provider_id integer NULL,
                trip_no integer NULL,
                tipping_fee_amount numeric NULL,
                tipping_fee_receipt_no varchar(255) NULL,
                total_time integer NULL,
                created_at timestamp NULL,
                updated_at timestamp NULL,
                deleted_at timestamp NULL
            )
        ');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP TABLE IF EXISTS fsm.sludge_collections_log');
    }
};
