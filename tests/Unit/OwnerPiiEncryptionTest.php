<?php

namespace Tests\Unit;

use App\Models\BuildingInfo\Owner;
use App\Services\PiiEncryptionService;
use Tests\TestCase;

class OwnerPiiEncryptionTest extends TestCase
{
    private string $keyFile;

    protected function setUp(): void
    {
        parent::setUp();

        $this->keyFile = tempnam(
            sys_get_temp_dir(),
            'owner-pii-test-'
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
        if (
            isset($this->keyFile)
            && is_file($this->keyFile)
        ) {
            unlink($this->keyFile);
        }

        parent::tearDown();
    }

    public function test_owner_pii_attributes_are_encrypted(): void
    {
        $owner = new Owner();

        $owner->bin = 'TST0001';
        $owner->owner_name = 'Test Owner';
        $owner->owner_gender = 'Test Gender';
        $owner->owner_contact = '9800000000';
        $owner->nid = 'TEST-NID-123';

        $attributes = $owner->getAttributes();

        $this->assertSame('TST0001', $attributes['bin']);

        $this->assertStringStartsWith(
            'enc:v1:',
            $attributes['owner_name']
        );

        $this->assertStringStartsWith(
            'enc:v1:',
            $attributes['owner_gender']
        );

        $this->assertStringStartsWith(
            'enc:v1:',
            $attributes['owner_contact']
        );

        $this->assertStringStartsWith(
            'enc:v1:',
            $attributes['nid']
        );

        $this->assertNotSame(
            'Test Owner',
            $attributes['owner_name']
        );

        $this->assertNotSame(
            '9800000000',
            $attributes['owner_contact']
        );
    }

    public function test_encrypted_owner_attributes_can_be_decrypted(): void
    {
        $owner = new Owner();

        $owner->owner_name = 'Test Owner';
        $owner->owner_gender = 'Test Gender';
        $owner->owner_contact = '9800000000';
        $owner->nid = 'TEST-NID-123';

        $attributes = $owner->getAttributes();

        $pii = app(PiiEncryptionService::class);

        $this->assertSame(
            'Test Owner',
            $pii->decrypt($attributes['owner_name'])
        );

        $this->assertSame(
            'Test Gender',
            $pii->decrypt($attributes['owner_gender'])
        );

        $this->assertSame(
            '9800000000',
            $pii->decrypt($attributes['owner_contact'])
        );

        $this->assertSame(
            'TEST-NID-123',
            $pii->decrypt($attributes['nid'])
        );
    }

    public function test_null_owner_pii_remains_null(): void
    {
        $owner = new Owner();

        $owner->owner_name = null;
        $owner->owner_gender = null;
        $owner->owner_contact = null;
        $owner->nid = null;

        $attributes = $owner->getAttributes();

        $this->assertNull($attributes['owner_name']);
        $this->assertNull($attributes['owner_gender']);
        $this->assertNull($attributes['owner_contact']);
        $this->assertNull($attributes['nid']);
    }

    public function test_owner_attributes_are_not_double_encrypted(): void
    {
        $pii = app(PiiEncryptionService::class);
        $encryptedName = $pii->encrypt('Test Owner');

        $owner = new Owner();
        $owner->owner_name = $encryptedName;

        $this->assertSame(
            $encryptedName,
            $owner->getAttributes()['owner_name']
        );
    }
}
