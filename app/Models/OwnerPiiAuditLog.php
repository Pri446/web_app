<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OwnerPiiAuditLog extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'auth.owner_pii_audit_logs';

    protected $fillable = [
        'user_id',
        'building_public_id',
        'event',
        'successful',
        'authentication_method',
        'ip_address',
        'user_agent',
        'context',
    ];

    protected $casts = [
        'successful' => 'boolean',
        'context' => 'array',
    ];

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
