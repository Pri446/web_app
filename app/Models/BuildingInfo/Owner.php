<?php

namespace App\Models\BuildingInfo;

use App\Services\PiiEncryptionService;
use Illuminate\Database\Eloquent\Model;
use Venturecraft\Revisionable\RevisionableTrait;

class Owner extends Model
{
    use RevisionableTrait;

    protected $table = 'building_info.owners';

    protected $primaryKey = 'id';

    protected $fillable = [
        'bin',
        'owner_name',
        'owner_gender',
        'owner_contact',
        'nid',
    ];

    protected $revisionCreationsEnabled = true;

    public function buildings()
    {
        return $this->hasMany(
            'App\Models\BuildingInfo\Building',
            'bin',
            'bin'
        );
    }

    public function setOwnerNameAttribute($value): void
    {
        $this->setEncryptedPiiAttribute('owner_name', $value);
    }

    public function setOwnerGenderAttribute($value): void
    {
        $this->setEncryptedPiiAttribute('owner_gender', $value);
    }

    public function setOwnerContactAttribute($value): void
    {
        $this->setEncryptedPiiAttribute('owner_contact', $value);
    }

    public function setNidAttribute($value): void
    {
        $this->setEncryptedPiiAttribute('nid', $value);
    }

    private function setEncryptedPiiAttribute(
        string $attribute,
        $value
    ): void {
        $normalizedValue = $value === null
            ? null
            : (string) $value;

        $this->attributes[$attribute] = app(
            PiiEncryptionService::class
        )->encrypt($normalizedValue);
    }
}
