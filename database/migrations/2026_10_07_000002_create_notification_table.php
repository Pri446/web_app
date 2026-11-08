<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        DB::statement('CREATE TABLE IF NOT EXISTS public.notification (
            id bigserial PRIMARY KEY,
            user_id integer NOT NULL,
            application_id integer NULL,
            message text NOT NULL,
            mode varchar(20) NOT NULL DEFAULT \'web\',
            status boolean NOT NULL DEFAULT false,
            created_at timestamp NULL,
            updated_at timestamp NULL
        )');
    }

    public function down()
    {
        DB::statement('DROP TABLE IF EXISTS public.notification');
    }
};
