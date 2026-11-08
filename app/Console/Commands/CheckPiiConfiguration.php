<?php

namespace App\Console\Commands;

use App\Services\PiiEncryptionService;
use Illuminate\Console\Command;
use Throwable;

class CheckPiiConfiguration extends Command
{
    protected $signature = 'pii:check-configuration
        {--production : Enforce production-oriented configuration checks}';

    protected $description = 'Validate PII encryption configuration without displaying key material';

    public function handle(PiiEncryptionService $encryption): int
    {
        $productionCheck = (bool) $this->option('production');
        $failures = [];
        $warnings = [];

        try {
            $probe = base64_encode(random_bytes(32));
            $ciphertext = $encryption->encrypt($probe);

            if ($encryption->decrypt($ciphertext) !== $probe) {
                $failures[] = 'Encryption round-trip validation failed.';
            }
        } catch (Throwable $exception) {
            $failures[] = 'The active PII key cannot complete an encryption round trip.';
        }

        if ((bool) config('pii.allow_legacy_plaintext', true)) {
            $warnings[] = 'Legacy plaintext compatibility is enabled.';
        }

        if (!(bool) config('pii.audit_enabled', true)) {
            $warnings[] = 'PII security auditing is disabled.';
        }

        if ($productionCheck) {
            if (config('pii.provider', 'file') === 'file') {
                $failures[] = 'The file key provider is not approved by this production check.';
            }

            if ((bool) config('app.debug', false)) {
                $failures[] = 'APP_DEBUG must be false in production.';
            }

            if ((bool) config('pii.allow_legacy_plaintext', true)) {
                $failures[] = 'Legacy plaintext compatibility must be disabled after backfill verification.';
            }

            if (!(bool) config('pii.audit_enabled', true)) {
                $failures[] = 'PII security auditing must be enabled in production.';
            }
        }

        $this->table(
            ['Setting', 'Value'],
            [
                ['Provider', (string) config('pii.provider', 'file')],
                ['Active key version', (string) config('pii.active_key_version', 'v1')],
                ['Cipher', (string) config('pii.cipher', 'aes-256-gcm')],
                ['Legacy plaintext', config('pii.allow_legacy_plaintext', true) ? 'enabled' : 'disabled'],
                ['Audit', config('pii.audit_enabled', true) ? 'enabled' : 'disabled'],
            ]
        );

        foreach ($warnings as $warning) {
            $this->warn($warning);
        }

        foreach ($failures as $failure) {
            $this->error($failure);
        }

        if ($failures !== []) {
            return 1;
        }

        $this->info('PII configuration check passed. No key material was displayed.');

        return 0;
    }
}
