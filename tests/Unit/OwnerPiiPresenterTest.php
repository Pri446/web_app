<?php

namespace Tests\Unit;

use App\Models\BuildingInfo\Owner;
use App\Services\OwnerPiiPresenter;
use Tests\TestCase;

class OwnerPiiPresenterTest extends TestCase
{
    private string $keyFile;

    protected function setUp(): void
    {
        parent::setUp();

        $this->keyFile = tempnam(sys_get_temp_dir(), 'owner-presenter-test-');

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

    public function test_it_returns_fixed_masked_owner_pii(): void
    {
        $owner = new Owner();
        $owner->bin = 'TST0001';
        $owner->owner_name = 'Test Owner';
        $owner->owner_gender = 'Test Gender';
        $owner->owner_contact = '9800000000';
        $owner->nid = 'TEST-NID-123';

        $result = app(OwnerPiiPresenter::class)->presentMasked($owner);

        $this->assertSame('TST0001', $result['bin']);
        $this->assertSame('********', $result['owner_name']);
        $this->assertSame('********', $result['owner_gender']);
        $this->assertSame('********', $result['owner_contact']);
        $this->assertSame('********', $result['nid']);
    }

    public function test_it_masks_legacy_plaintext_during_migration(): void
    {
        $owner = new Owner();
        $owner->setRawAttributes([
            'bin' => 'TST0002',
            'owner_name' => 'Legacy Owner',
            'owner_gender' => 'Legacy Gender',
            'owner_contact' => '9812345678',
            'nid' => '123456789',
        ]);

        $result = app(OwnerPiiPresenter::class)->presentMasked($owner);

        $this->assertSame('********', $result['owner_name']);
        $this->assertSame('********', $result['owner_gender']);
        $this->assertSame('********', $result['owner_contact']);
        $this->assertSame('********', $result['nid']);
    }

    public function test_it_preserves_null_values(): void
    {
        $owner = new Owner();
        $owner->owner_name = null;
        $owner->owner_gender = null;
        $owner->owner_contact = null;
        $owner->nid = null;

        $result = app(OwnerPiiPresenter::class)->presentMasked($owner);

        $this->assertNull($result['owner_name']);
        $this->assertNull($result['owner_gender']);
        $this->assertNull($result['owner_contact']);
        $this->assertNull($result['nid']);
    }

    public function test_it_returns_plaintext_only_when_explicitly_requested(): void
    {
        $owner = new Owner();
        $owner->owner_name = 'Test Owner';
        $owner->owner_gender = 'Female';
        $owner->owner_contact = '9800000000';
        $owner->nid = 'TEST-NID-123';

        $result = app(OwnerPiiPresenter::class)->presentPlaintext($owner);

        $this->assertSame('Test Owner', $result['owner_name']);
        $this->assertSame('Female', $result['owner_gender']);
        $this->assertSame('9800000000', $result['owner_contact']);
        $this->assertSame('TEST-NID-123', $result['nid']);
    }

    public function test_it_can_reveal_one_list_value_explicitly(): void
    {
        $owner = new Owner();
        $owner->owner_name = 'List Owner';

        $ciphertext = $owner->getAttributes()['owner_name'];

        $this->assertSame(
            'List Owner',
            app(OwnerPiiPresenter::class)->presentPlaintextValue($ciphertext)
        );
    }

    public function test_short_values_are_fully_masked(): void
    {
        $presenter = app(OwnerPiiPresenter::class);

        $this->assertSame('***', $presenter->maskContact('123'));
        $this->assertSame('****', $presenter->maskNid('1234'));
    }

    public function test_full_mask_does_not_reveal_value_shape(): void
    {
        $presenter = app(OwnerPiiPresenter::class);

        $this->assertSame('********', $presenter->maskFully('A'));
        $this->assertSame('********', $presenter->maskFully('Long Owner Name'));
        $this->assertNull($presenter->maskFully(null));
    }

    public function test_owner_name_search_is_case_insensitive(): void
    {
        $owner = new Owner();
        $owner->owner_name = 'Test Owner Name';
        $ciphertext = $owner->getAttributes()['owner_name'];
        $presenter = app(OwnerPiiPresenter::class);

        $this->assertTrue($presenter->ownerNameContains($ciphertext, 'owner'));
        $this->assertTrue($presenter->ownerNameContains($ciphertext, 'TEST'));
        $this->assertFalse($presenter->ownerNameContains($ciphertext, 'missing'));
    }
}
