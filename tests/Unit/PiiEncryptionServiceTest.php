<?php

namespace Tests\Unit;

use App\Services\PiiEncryptionService;
use Illuminate\Contracts\Encryption\DecryptException;
use Tests\TestCase;

class PiiEncryptionServiceTest extends TestCase
{
    private string $keyFile;

    protected function setUp(): void
    {
        parent::setUp();

        $this->keyFile = tempnam(
            sys_get_temp_dir(),
            'pii-test-'
        );

        file_put_contents(
            $this->keyFile,
            base64_encode(random_bytes(32))
        );

        config([
            'pii.key_file' => $this->keyFile,
            'pii.keys.v1.file' => $this->keyFile,
            'pii.cipher' => 'aes-256-gcm',
            'pii.prefix' => 'enc:',
            'pii.active_key_version' => 'v1',
            'pii.allow_legacy_plaintext' => true,
        ]);
    }

    protected function tearDown(): void
    {
        if (isset($this->keyFile) && is_file($this->keyFile)) {
            unlink($this->keyFile);
        }

        parent::tearDown();
    }

    public function test_it_encrypts_and_decrypts_a_string(): void
    {
        $service = app(PiiEncryptionService::class);

        $encrypted = $service->encrypt('Test Owner');

        $this->assertStringStartsWith('enc:v1:', $encrypted);
        $this->assertSame(
            'Test Owner',
            $service->decrypt($encrypted)
        );
    }

    public function test_it_preserves_null(): void
    {
        $service = app(PiiEncryptionService::class);

        $this->assertNull($service->encrypt(null));
        $this->assertNull($service->decrypt(null));
    }

    public function test_it_does_not_double_encrypt(): void
    {
        $service = app(PiiEncryptionService::class);

        $encrypted = $service->encrypt('Test Owner');

        $this->assertSame(
            $encrypted,
            $service->encrypt($encrypted)
        );
    }

    public function test_it_uses_randomized_encryption(): void
    {
        $service = app(PiiEncryptionService::class);

        $first = $service->encrypt('Test Owner');
        $second = $service->encrypt('Test Owner');

        $this->assertNotSame($first, $second);
    }

    public function test_it_temporarily_accepts_legacy_plaintext(): void
    {
        $service = app(PiiEncryptionService::class);

        $this->assertSame(
            'Legacy Owner',
            $service->decrypt('Legacy Owner')
        );
    }

    public function test_modified_ciphertext_cannot_be_decrypted(): void
    {
        $service = app(PiiEncryptionService::class);

        $encrypted = $service->encrypt('Test Owner');
        $modified = substr($encrypted, 0, -1).'X';

        $this->expectException(DecryptException::class);

        $service->decrypt($modified);
    }

    public function test_it_can_read_an_older_key_version_after_rotation(): void
    {
        $v1Service = app(PiiEncryptionService::class);
        $v1Ciphertext = $v1Service->encrypt('Test Owner');
        $v2KeyFile = tempnam(sys_get_temp_dir(), 'pii-test-v2-');
        file_put_contents($v2KeyFile, base64_encode(random_bytes(32)));

        try {
            config([
                'pii.keys.v2.file' => $v2KeyFile,
                'pii.active_key_version' => 'v2',
            ]);

            $v2Service = app(PiiEncryptionService::class);
            $v2Ciphertext = $v2Service->encrypt('Test Owner');

            $this->assertStringStartsWith('enc:v2:', $v2Ciphertext);
            $this->assertSame('Test Owner', $v2Service->decrypt($v1Ciphertext));
            $this->assertSame('Test Owner', $v2Service->decrypt($v2Ciphertext));
        } finally {
            if (is_file($v2KeyFile)) {
                unlink($v2KeyFile);
            }
        }
    }

    public function test_it_can_reject_legacy_plaintext(): void
    {
        config(['pii.allow_legacy_plaintext' => false]);

        $this->expectException(DecryptException::class);

        app(PiiEncryptionService::class)->decrypt('Legacy Owner');
    }
}
