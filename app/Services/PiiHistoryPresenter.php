<?php

namespace App\Services;

use Throwable;

class PiiHistoryPresenter
{
    public const BUILDING_OWNER_FIELDS = [
        'owner_name',
        'owner_gender',
        'owner_contact',
        'nid',
    ];

    public const APPLICATION_CUSTOMER_FIELDS = [
        'customer_name',
        'customer_gender',
        'customer_contact',
    ];

    private PiiEncryptionService $encryption;

    public function __construct(PiiEncryptionService $encryption)
    {
        $this->encryption = $encryption;
    }

    /**
     * Render one historical PII value without ever falling back to ciphertext.
     *
     * History tables contain the encrypted value that existed at the time of
     * each change. Locked history always receives a fixed mask. Revealed
     * history decrypts only while the module-specific temporary grant is
     * active. A missing/rotated key or damaged value fails closed to the same
     * mask instead of exposing the stored ciphertext or an error detail.
     */
    public function present($value, bool $revealed): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }

        if (!$revealed) {
            return '********';
        }

        try {
            return $this->encryption->decrypt((string) $value);
        } catch (Throwable $exception) {
            return '********';
        }
    }

    /**
     * Randomized encryption can leave legacy revision rows whose ciphertexts
     * differ even though their decrypted values are identical. Those are not
     * real user changes and should not be presented as history events.
     *
     * If either value cannot be decrypted, retain the event and let present()
     * mask it. Failing open here would hide evidence of damaged legacy data.
     */
    public function hasMeaningfulChange($oldValue, $newValue): bool
    {
        try {
            return $this->plaintext($oldValue) !== $this->plaintext($newValue);
        } catch (Throwable $exception) {
            return true;
        }
    }

    private function plaintext($value): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }

        return $this->encryption->decrypt((string) $value);
    }
}
