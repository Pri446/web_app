<?php

namespace Tests\Unit;

use App\Imports\TaxImport;
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

    public function test_csv_import_encrypts_owner_fields_before_database_insert(): void
    {
        // Reproduce the important part of Laravel Excel's import pipeline:
        // normalize a CSV row through map(), then let model() create the
        // TaxPayment instance that will later be included in the batch insert.
        $import = new TaxImport();
        $mappedRow = $import->map([
            'tax_code' => 'TAX-IMPORT-1',
            'owner_name' => 'Imported Tax Owner',
            'owner_contact' => '9800000000',
            'last_payment_date' => '8/21/2022',
        ]);

        $record = $import->model($mappedRow);
        $attributes = $record->getAttributes();

        // getAttributes() reads the raw values that Eloquent will send to the
        // database. The enc:v1 prefix proves that neither imported PII value is
        // still plaintext at the database-write boundary.
        $this->assertStringStartsWith('enc:v1:', $attributes['owner_name']);
        $this->assertStringStartsWith('enc:v1:', $attributes['owner_contact']);

        // Decrypt the raw ciphertext in the test to prove it is valid and maps
        // back to the original CSV values, rather than merely having a prefix.
        $encryption = app(PiiEncryptionService::class);
        $this->assertSame(
            'Imported Tax Owner',
            $encryption->decrypt($attributes['owner_name'])
        );
        $this->assertSame(
            '9800000000',
            $encryption->decrypt($attributes['owner_contact'])
        );
        $this->assertSame('2022-08-21', $attributes['last_payment_date']);
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
