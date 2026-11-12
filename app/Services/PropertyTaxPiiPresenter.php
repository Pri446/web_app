<?php

namespace App\Services;

class PropertyTaxPiiPresenter
{
    private PiiEncryptionService $encryption;

    public function __construct(PiiEncryptionService $encryption)
    {
        $this->encryption = $encryption;
    }

    public function presentValue(?string $value, bool $revealed): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }

        return $revealed ? $this->encryption->decrypt($value) : '********';
    }

    public function decrypt(?string $value): ?string
    {
        return $this->encryption->decrypt($value);
    }
}
