<?php

namespace App\Services;

use App\Models\Fsm\Application;

class ApplicationCustomerPiiPresenter
{
    private PiiEncryptionService $encryption;

    public function __construct(PiiEncryptionService $encryption)
    {
        $this->encryption = $encryption;
    }

    public function presentMasked(Application $application): array
    {
        $attributes = $application->getAttributes();

        return [
            'customer_name' => $this->mask($attributes['customer_name'] ?? null),
            'customer_gender' => $this->mask($attributes['customer_gender'] ?? null),
            'customer_contact' => $this->mask($attributes['customer_contact'] ?? null),
        ];
    }

    public function presentPlaintext(Application $application): array
    {
        $attributes = $application->getAttributes();

        return [
            'customer_name' => $this->encryption->decrypt($attributes['customer_name'] ?? null),
            'customer_gender' => $this->encryption->decrypt($attributes['customer_gender'] ?? null),
            'customer_contact' => $this->encryption->decrypt($attributes['customer_contact'] ?? null),
        ];
    }

    public function presentPlaintextValue(?string $value): ?string
    {
        return $this->encryption->decrypt($value);
    }

    public function mask(?string $value): ?string
    {
        return $value === null || $value === '' ? $value : '********';
    }

    public function customerNameContains(?string $value, string $search): bool
    {
        $plaintext = $this->encryption->decrypt($value);

        return $plaintext !== null
            && mb_stripos($plaintext, $search) !== false;
    }
}
