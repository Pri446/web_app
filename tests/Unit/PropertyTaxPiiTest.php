<?php

namespace Tests\Unit;

use App\Models\TaxPaymentInfo\TaxPayment;
use App\Models\User;
use App\Services\PiiEncryptionService;
use App\Services\PropertyTaxPiiAccessService;
use App\Services\PropertyTaxPiiExportService;
use App\Services\PropertyTaxPiiPresenter;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PropertyTaxPiiTest extends TestCase
{
    private string $keyFile;

    protected function setUp(): void
    {
        parent::setUp();
        $this->keyFile = tempnam(sys_get_temp_dir(), 'property-tax-pii-');
        file_put_contents($this->keyFile, base64_encode(random_bytes(32)));
        config([
            'pii.key_file' => $this->keyFile,
            'pii.keys.v1.file' => $this->keyFile,
            'pii.cipher' => 'aes-256-gcm',
            'pii.prefix' => 'enc:',
            'pii.active_key_version' => 'v1',
            'pii.allow_legacy_plaintext' => false,
        ]);
        Gate::before(function () { return true; });
    }

    protected function tearDown(): void
    {
        if (isset($this->keyFile) && is_file($this->keyFile)) {
            unlink($this->keyFile);
        }
        parent::tearDown();
    }

    public function test_property_tax_owner_fields_are_encrypted(): void
    {
        $record = new TaxPayment();
        $record->owner_name = 'Tax Owner';
        $record->owner_contact = '9800000000';
        $attributes = $record->getAttributes();

        $this->assertStringStartsWith('enc:v1:', $attributes['owner_name']);
        $this->assertStringStartsWith('enc:v1:', $attributes['owner_contact']);
        $this->assertSame('Tax Owner', app(PiiEncryptionService::class)->decrypt($attributes['owner_name']));
    }

    public function test_presenter_masks_and_reveals_explicitly(): void
    {
        $encryption = app(PiiEncryptionService::class);
        $value = $encryption->encrypt('Tax Owner');
        $presenter = app(PropertyTaxPiiPresenter::class);

        $this->assertSame('********', $presenter->presentValue($value, false));
        $this->assertSame('Tax Owner', $presenter->presentValue($value, true));
    }

    public function test_property_tax_unlock_is_separate_and_revocable(): void
    {
        $user = new User();
        $user->setRawAttributes(['id' => 701]);
        $access = app(PropertyTaxPiiAccessService::class);

        $access->unlock($user);
        $this->assertTrue($access->isUnlocked($user));
        $access->lock();
        $this->assertFalse($access->isUnlocked($user));
    }

    public function test_export_parser_deduplicates_tax_codes(): void
    {
        $service = app(PropertyTaxPiiExportService::class);
        $this->assertSame(
            ['TAX-1', 'TAX-2'],
            $service->parseCsvText("tax_code\nTAX-1\nTAX-2\nTAX-1\n")
        );
    }

    public function test_export_parser_requires_tax_code_header(): void
    {
        $this->expectException(ValidationException::class);
        app(PropertyTaxPiiExportService::class)->parseCsvText("bin\nB001\n");
    }
}
