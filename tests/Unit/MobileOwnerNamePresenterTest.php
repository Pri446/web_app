<?php

namespace Tests\Unit;

use App\Models\BuildingInfo\Building;
use App\Models\BuildingInfo\Owner;
use App\Models\Fsm\Application;
use App\Services\Api\MobileApplicationPresenter;
use App\Services\Api\MobileBuildingPresenter;
use Tests\TestCase;

class MobileOwnerNamePresenterTest extends TestCase
{
    private string $keyFile;

    protected function setUp(): void
    {
        parent::setUp();

        $this->keyFile = tempnam(sys_get_temp_dir(), 'mobile-owner-pii-');
        file_put_contents($this->keyFile, base64_encode(random_bytes(32)));

        config([
            'pii.key_file' => $this->keyFile,
            'pii.keys.v1.file' => $this->keyFile,
            'pii.cipher' => 'aes-256-gcm',
            'pii.prefix' => 'enc:',
            'pii.active_key_version' => 'v1',
            'pii.allow_legacy_plaintext' => false,
        ]);
    }

    protected function tearDown(): void
    {
        if (isset($this->keyFile) && is_file($this->keyFile)) {
            unlink($this->keyFile);
        }

        parent::tearDown();
    }

    public function test_mobile_building_payload_returns_only_plaintext_owner_name(): void
    {
        $owner = new Owner();
        $owner->owner_name = 'Mobile Owner';
        $owner->owner_gender = 'Female';
        $owner->owner_contact = '9800000000';
        $owner->nid = '123456789';

        $building = new Building();
        $building->setRawAttributes(['bin' => 'B000001', 'ward' => 1]);
        $building->setRelation('Owners', $owner);

        $payload = app(MobileBuildingPresenter::class)->present($building);

        $this->assertSame('Mobile Owner', $payload['owners']['owner_name']);
        $this->assertArrayNotHasKey('owner_gender', $payload['owners']);
        $this->assertArrayNotHasKey('owner_contact', $payload['owners']);
        $this->assertArrayNotHasKey('nid', $payload['owners']);
    }

    public function test_mobile_application_payload_returns_only_plaintext_customer_name(): void
    {
        $application = new Application();
        $application->setRawAttributes(['id' => 10, 'bin' => 'B000001']);
        $application->customer_name = 'Application Owner';
        $application->customer_gender = 'Male';
        $application->customer_contact = '9811111111';

        $payload = app(MobileApplicationPresenter::class)
            ->present($application);

        $this->assertSame('Application Owner', $payload['customer_name']);
        $this->assertArrayNotHasKey('customer_gender', $payload);
        $this->assertArrayNotHasKey('customer_contact', $payload);
    }
}
