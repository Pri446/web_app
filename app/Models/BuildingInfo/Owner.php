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

        $encryption = app(PiiEncryptionService::class);
        $currentValue = $this->attributes[$attribute] ?? null;

        /*
         * AES-GCM intentionally produces different ciphertext every time the
         * same plaintext is encrypted. Building edit forms submit all owner
         * fields, so blindly encrypting an unchanged value makes Eloquent and
         * Revisionable treat it as a real change. Preserve the current
         * ciphertext when its decrypted value equals the submitted plaintext.
         *
         * Only already-encrypted current values may take this shortcut. Legacy
         * plaintext must still pass through encrypt() during migration/write.
         */
        if ($normalizedValue !== null
            && $encryption->isEncrypted($currentValue)
            && $encryption->decrypt($currentValue) === $normalizedValue) {
            $this->attributes[$attribute] = $currentValue;

            return;
        }

        $this->attributes[$attribute] = $encryption->encrypt($normalizedValue);
    }
}
