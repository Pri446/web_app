<?php

namespace App\Services;

use App\Models\BuildingInfo\Owner;

class OwnerPiiPresenter
{
    private PiiEncryptionService $encryption;

    public function __construct(PiiEncryptionService $encryption)
    {
        $this->encryption = $encryption;
    }

    public function presentMasked(Owner $owner): array
    {
        $attributes = $owner->getAttributes();

        // Locked Building surfaces use one fixed mask for every populated PII
        // value. This keeps the detail page consistent with the DataTable and
        // avoids revealing the value's initials, length, or formatting.
        return [
            'bin' => $attributes['bin'] ?? null,
            'owner_name' => $this->maskFully($attributes['owner_name'] ?? null),
            'owner_gender' => $this->maskFully($attributes['owner_gender'] ?? null),
            'owner_contact' => $this->maskFully($attributes['owner_contact'] ?? null),
            'nid' => $this->maskFully($attributes['nid'] ?? null),
        ];
    }

    public function presentPlaintext(Owner $owner): array
    {
        $attributes = $owner->getAttributes();

        return [
            'bin' => $attributes['bin'] ?? null,
            'owner_name' => $this->encryption->decrypt($attributes['owner_name'] ?? null),
            'owner_gender' => $this->encryption->decrypt($attributes['owner_gender'] ?? null),
            'owner_contact' => $this->encryption->decrypt($attributes['owner_contact'] ?? null),
            'nid' => $this->encryption->decrypt($attributes['nid'] ?? null),
        ];
    }

    public function presentPlaintextValue(?string $value): ?string
    {
        return $this->encryption->decrypt($value);
    }

    public function maskName(?string $value): ?string
    {
        $plaintext = $this->encryption->decrypt($value);

        if ($plaintext === null || $plaintext === '') {
            return $plaintext;
        }

        $parts = preg_split('/(\s+)/u', $plaintext, -1, PREG_SPLIT_DELIM_CAPTURE);

        if ($parts === false) {
            return null;
        }

        return implode('', array_map(function (string $part): string {
            if (preg_match('/^\s+$/u', $part) === 1) {
                return $part;
            }

            $length = mb_strlen($part);

            if ($length === 0) {
                return '';
            }

            return mb_substr($part, 0, 1)
                . str_repeat('*', max(1, $length - 1));
        }, $parts));
    }

    public function maskContact(?string $value): ?string
    {
        return $this->maskKeepingEdges(
            $this->encryption->decrypt($value),
            2,
            2
        );
    }

    public function maskNid(?string $value): ?string
    {
        return $this->maskKeepingEdges(
            $this->encryption->decrypt($value),
            2,
            2
        );
    }

    public function maskGender(?string $value): ?string
    {
        $plaintext = $this->encryption->decrypt($value);

        if ($plaintext === null || $plaintext === '') {
            return $plaintext;
        }

        return '***';
    }

    public function maskFully(?string $value): ?string
    {
        $plaintext = $this->encryption->decrypt($value);

        if ($plaintext === null || $plaintext === '') {
            return $plaintext;
        }

        return '********';
    }

    public function ownerNameContains(
        ?string $value,
        string $search
    ): bool {
        $needle = trim($search);

        if ($needle === '') {
            return true;
        }

        $plaintext = $this->encryption->decrypt($value);

        return $plaintext !== null
            && mb_stripos($plaintext, $needle) !== false;
    }

    private function maskKeepingEdges(
        ?string $value,
        int $visibleStart,
        int $visibleEnd
    ): ?string {
        if ($value === null || $value === '') {
            return $value;
        }

        $length = mb_strlen($value);

        if ($length <= $visibleStart + $visibleEnd) {
            return str_repeat('*', $length);
        }

        return mb_substr($value, 0, $visibleStart)
            . str_repeat('*', $length - $visibleStart - $visibleEnd)
            . mb_substr($value, -$visibleEnd);
    }
}
