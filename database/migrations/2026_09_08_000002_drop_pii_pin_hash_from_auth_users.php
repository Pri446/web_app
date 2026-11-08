<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropPiiPinHashFromAuthUsers extends Migration
{
    public function up()
    {
        if (Schema::hasColumn('auth.users', 'pii_pin_hash')) {
            Schema::table('auth.users', function (Blueprint $table) {
                $table->dropColumn('pii_pin_hash');
            });
        }
    }

    public function down()
    {
        if (!Schema::hasColumn('auth.users', 'pii_pin_hash')) {
            Schema::table('auth.users', function (Blueprint $table) {
                $table->string('pii_pin_hash')->nullable();
            });
        }
    }
}
