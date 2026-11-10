<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddPublicIdsToBuildingsAndContainments extends Migration
{
    public function up()
    {
        // Fail clearly instead of waiting indefinitely when an application session
        // holds a lock on either table during deployment.
        DB::statement("SET LOCAL lock_timeout = '15s'");

        Schema::table('building_info.buildings', function (Blueprint $table) {
            $table->uuid('public_id')->nullable();
        });
        Schema::table('fsm.containments', function (Blueprint $table) {
            $table->uuid('public_id')->nullable();
        });

        DB::statement('UPDATE building_info.buildings SET public_id = gen_random_uuid() WHERE public_id IS NULL');

        // These summary triggers refresh materialized views after every containment
        // update. public_id is not used by either summary, so skip them for this
        // one-time backfill. The migration transaction keeps the table locked until
        // both triggers have been enabled again.
        DB::statement('ALTER TABLE fsm.containments DISABLE TRIGGER tgr_set_builtupperwardsummary');
        DB::statement('ALTER TABLE fsm.containments DISABLE TRIGGER tgr_set_landusesummary');
        DB::statement('UPDATE fsm.containments SET public_id = gen_random_uuid() WHERE public_id IS NULL');
        DB::statement('ALTER TABLE fsm.containments ENABLE TRIGGER tgr_set_landusesummary');
        DB::statement('ALTER TABLE fsm.containments ENABLE TRIGGER tgr_set_builtupperwardsummary');

        DB::statement('ALTER TABLE building_info.buildings ALTER COLUMN public_id SET NOT NULL');
        DB::statement('ALTER TABLE fsm.containments ALTER COLUMN public_id SET NOT NULL');

        Schema::table('building_info.buildings', function (Blueprint $table) {
            $table->unique('public_id', 'buildings_public_id_unique');
        });
        Schema::table('fsm.containments', function (Blueprint $table) {
            $table->unique('public_id', 'containments_public_id_unique');
        });
    }

    public function down()
    {
        Schema::table('building_info.buildings', function (Blueprint $table) {
            $table->dropUnique('buildings_public_id_unique');
            $table->dropColumn('public_id');
        });
        Schema::table('fsm.containments', function (Blueprint $table) {
            $table->dropUnique('containments_public_id_unique');
            $table->dropColumn('public_id');
        });
    }
}
