<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPiiPinHashToAuthUsers extends Migration
{
    public function up()
    {
        Schema::table('auth.users', function (Blueprint $table) {
            $table->string('pii_pin_hash')->nullable();
        });
    }

    public function down()
    {
        Schema::table('auth.users', function (Blueprint $table) {
            $table->dropColumn('pii_pin_hash');
        });
    }
}
