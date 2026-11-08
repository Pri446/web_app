<?php

namespace App\Models\Concerns;

use Illuminate\Support\Str;

trait HasPublicId
{
    public static function bootHasPublicId(): void
    {
        static::creating(function ($model) {
            if (empty($model->public_id)) {
                $model->public_id = (string) Str::uuid();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    /**
     * Reject malformed route values before PostgreSQL attempts to compare them
     * with its native UUID column. Returning null makes implicit binding return
     * the normal 404 response instead of a database 500 error.
     */
    public function resolveRouteBinding($value, $field = null)
    {
        if (! Str::isUuid($value)) {
            return null;
        }

        return parent::resolveRouteBinding($value, $field ?: $this->getRouteKeyName());
    }
}
