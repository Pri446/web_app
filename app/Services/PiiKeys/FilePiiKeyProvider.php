<?php

namespace App\Services\PiiKeys;

use App\Contracts\PiiKeyProvider;
use RuntimeException;

class FilePiiKeyProvider implements PiiKeyProvider
{
    public function key(string $version): string
    {
        $keyFile = config("pii.keys.{$version}.file");

        // Preserve compatibility with the original local v1 configuration.
        if ((!is_string($keyFile) || $keyFile === '') && $version === 'v1') {
            $keyFile = config('pii.key_file');
        }

        if (!is_string($keyFile) || $keyFile === '') {
            throw new RuntimeException(
                "PII encryption key file is not configured for {$version}."
            );
        }

        if (!is_file($keyFile) || !is_readable($keyFile)) {
            throw new RuntimeException(
                "PII encryption key file is unavailable for {$version}."
            );
        }

        $encodedKey = trim((string) file_get_contents($keyFile));
        $key = base64_decode($encodedKey, true);

        if ($key === false || strlen($key) !== 32) {
            throw new RuntimeException(
                "PII encryption key for {$version} must decode to exactly 32 bytes."
            );
        }

        return $key;
    }
}
