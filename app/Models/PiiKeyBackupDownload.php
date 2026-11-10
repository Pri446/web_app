<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PiiKeyBackupDownload extends Model
{
    public $timestamps = false;

    protected $table = 'auth.pii_key_backup_downloads';

    protected $fillable = [
        'user_id',
        'key_version',
        'downloaded_at',
    ];

    protected $casts = [
        'downloaded_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Backup history is security evidence and is append-only in application
    // code. Database administrators remain responsible for DB-level controls.
    protected static function booted()
    {
        static::updating(function () {
            return false;
        });

        static::deleting(function () {
            return false;
        });
    }
}
