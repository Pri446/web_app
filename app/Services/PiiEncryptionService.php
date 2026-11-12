<?php

namespace App\Services;

use App\Contracts\PiiKeyProvider;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Encryption\Encrypter;
use RuntimeException;
use Throwable;

class PiiEncryptionService
{
    private PiiKeyProvider $keys;
    private string $prefix;
    private string $activeVersion;
    private array $encrypters = [];

    public function __construct(PiiKeyProvider $keys)
    {
        $this->keys = $keys;
        $this->prefix = config('pii.prefix', 'enc:');
        $this->activeVersion = config('pii.active_key_version', 'v1');

        if (!preg_match('/^[A-Za-z0-9_-]+$/', $this->activeVersion)) {
            throw new RuntimeException(
                'The active PII key version is invalid.'
            );
        }
    }

    public function encrypt(?string $value): ?string
    {
        if ($value === null || $this->isEncrypted($value)) {
            return $value;
        }

        return $this->prefix
            . $this->activeVersion
            . ':'
            . $this->encrypter($this->activeVersion)->encryptString($value);
    }

    public function decrypt(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        /*
         * Temporary compatibility for existing plaintext records.
         * Remove this fallback after the data migration is verified.
         */
        $parts = $this->encryptedParts($value);

        if ($parts === null) {
            if ((bool) config('pii.allow_legacy_plaintext', true)) {
                return $value;
            }

            throw new DecryptException(
                'An unencrypted PII value was rejected.'
            );
        }

        [$version, $payload] = $parts;

        if ($payload === '') {
            throw new DecryptException(
                'The encrypted PII payload is invalid.'
            );
        }

        try {
            return $this->encrypter($version)->decryptString($payload);
        } catch (Throwable $exception) {
            /*
             * Do not include the value, payload, or key in this message.
             */
            throw new DecryptException(
                'The encrypted PII value could not be decrypted.',
                0,
                $exception
            );
        }
    }

    public function isEncrypted(?string $value): bool
    {
        return is_string($value) && $this->encryptedParts($value) !== null;
    }

    public function version(?string $value): ?string
    {
        $parts = is_string($value) ? $this->encryptedParts($value) : null;

        return $parts[0] ?? null;
    }

    private function encryptedParts(string $value): ?array
    {
        $pattern = '/^'
            . preg_quote($this->prefix, '/')
            . '([A-Za-z0-9_-]+):(.*)$/s';

        if (preg_match($pattern, $value, $matches) !== 1) {
            return null;
        }

        return [$matches[1], $matches[2]];
    }

    private function encrypter(string $version): Encrypter
    {
        if (!isset($this->encrypters[$version])) {
            $this->encrypters[$version] = new Encrypter(
                $this->keys->key($version),
                config('pii.cipher', 'aes-256-gcm')
            );
        }

        return $this->encrypters[$version];
    }
}
