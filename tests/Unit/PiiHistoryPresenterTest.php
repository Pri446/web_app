<?php

namespace Tests\Unit;

use App\Contracts\PiiKeyProvider;
use App\Services\PiiEncryptionService;
use App\Services\PiiHistoryPresenter;
use Tests\TestCase;

class PiiHistoryPresenterTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'pii.active_key_version' => 'v1',
            'pii.cipher' => 'aes-256-gcm',
            'pii.allow_legacy_plaintext' => false,
        ]);

        $this->app->instance(PiiKeyProvider::class, new class implements PiiKeyProvider {
            public function key(string $version): string
            {
                return str_repeat('h', 32);
            }
        });
    }

    public function test_locked_history_uses_a_fixed_mask(): void
    {
        $encryption = app(PiiEncryptionService::class);
        $presenter = app(PiiHistoryPresenter::class);

        $this->assertSame(
            '********',
            $presenter->present($encryption->encrypt('Owner Name'), false)
        );
    }

    public function test_revealed_history_decrypts_the_value(): void
    {
        $encryption = app(PiiEncryptionService::class);
        $presenter = app(PiiHistoryPresenter::class);

        $this->assertSame(
            'Owner Name',
            $presenter->present($encryption->encrypt('Owner Name'), true)
        );
    }

    public function test_invalid_history_ciphertext_fails_closed(): void
    {
        $presenter = app(PiiHistoryPresenter::class);

        $this->assertSame(
            '********',
            $presenter->present('enc:v1:not-valid-ciphertext', true)
        );
    }

    public function test_different_ciphertexts_for_same_plaintext_are_not_a_change(): void
    {
        $encryption = app(PiiEncryptionService::class);
        $presenter = app(PiiHistoryPresenter::class);
        $oldValue = $encryption->encrypt('Female');
        $newValue = $encryption->encrypt('Female');

        $this->assertNotSame($oldValue, $newValue);
        $this->assertFalse(
            $presenter->hasMeaningfulChange($oldValue, $newValue)
        );
    }

    public function test_different_plaintext_is_a_meaningful_history_change(): void
    {
        $encryption = app(PiiEncryptionService::class);
        $presenter = app(PiiHistoryPresenter::class);

        $this->assertTrue($presenter->hasMeaningfulChange(
            $encryption->encrypt('Original Owner'),
            $encryption->encrypt('Updated Owner')
        ));
    }
}
