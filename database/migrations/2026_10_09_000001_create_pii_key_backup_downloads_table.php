<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePiiKeyBackupDownloadsTable extends Migration
{
    public function up()
    {
        Schema::create('auth.pii_key_backup_downloads', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id');
            $table->string('key_version', 32);
            $table->timestampTz('downloaded_at')->useCurrent();

            // Only metadata is stored here. The encryption key and downloaded
            // backup file must never be written to this table.
            $table->index(
                ['key_version', 'downloaded_at'],
                'pii_key_backup_version_downloaded_idx'
            );
        });
    }

    public function down()
    {
        Schema::dropIfExists('auth.pii_key_backup_downloads');
    }
}
