<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOwnerPiiAuditLogsTable extends Migration
{
    public function up()
    {
        Schema::create('auth.owner_pii_audit_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->uuid('building_public_id')->nullable();
            $table->string('event', 64);
            $table->boolean('successful');
            $table->string('authentication_method', 32)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->json('context')->nullable();
            $table->timestampTz('created_at')->useCurrent();

            $table->index(
                ['user_id', 'created_at'],
                'owner_pii_audit_user_created_idx'
            );
            $table->index(
                ['building_public_id', 'created_at'],
                'owner_pii_audit_building_created_idx'
            );
            $table->index(
                ['event', 'created_at'],
                'owner_pii_audit_event_created_idx'
            );
        });
    }

    public function down()
    {
        Schema::dropIfExists('auth.owner_pii_audit_logs');
    }
}
