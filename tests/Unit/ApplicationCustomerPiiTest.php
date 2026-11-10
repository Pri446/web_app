<?php

namespace Tests\Unit;

use App\Models\Fsm\Application;
use App\Models\User;
use App\Services\ApplicationCustomerPiiPresenter;
use App\Services\ApplicationPiiAccessService;
use App\Services\PiiEncryptionService;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class ApplicationCustomerPiiTest extends TestCase
{
    private string $keyFile;

    protected function setUp(): void
    {
        parent::setUp();

        $this->keyFile = tempnam(sys_get_temp_dir(), 'application-pii-test-');
        file_put_contents($this->keyFile, base64_encode(random_bytes(32)));
        config([
            'pii.key_file' => $this->keyFile,
            'pii.keys.v1.file' => $this->keyFile,
            'pii.cipher' => 'aes-256-gcm',
            'pii.prefix' => 'enc:',
            'pii.active_key_version' => 'v1',
            'pii.allow_legacy_plaintext' => false,
        ]);

        Gate::before(function () {
            return true;
        });
    }

    protected function tearDown(): void
    {
        if (isset($this->keyFile) && is_file($this->keyFile)) {
            unlink($this->keyFile);
        }

        parent::tearDown();
    }

    public function test_only_customer_fields_are_encrypted(): void
    {
        $application = new Application();
        $application->customer_name = 'Customer Name';
        $application->customer_gender = 'Female';
        $application->customer_contact = '9800000000';
        $application->applicant_name = 'Different Applicant';
        $application->applicant_gender = 'Male';
        $application->applicant_contact = '9811111111';
        $attributes = $application->getAttributes();

        $this->assertStringStartsWith('enc:v1:', $attributes['customer_name']);
        $this->assertStringStartsWith('enc:v1:', $attributes['customer_gender']);
        $this->assertStringStartsWith('enc:v1:', $attributes['customer_contact']);
        $this->assertSame('Different Applicant', $attributes['applicant_name']);
        $this->assertSame('Male', $attributes['applicant_gender']);
        $this->assertSame('9811111111', $attributes['applicant_contact']);
    }

    public function test_presenter_masks_and_explicitly_decrypts_customer_fields(): void
    {
        $application = new Application();
        $application->customer_name = 'Customer Name';
        $application->customer_gender = 'Female';
        $application->customer_contact = '9800000000';
        $presenter = app(ApplicationCustomerPiiPresenter::class);

        $this->assertSame([
            'customer_name' => '********',
            'customer_gender' => '********',
            'customer_contact' => '********',
        ], $presenter->presentMasked($application));

        $this->assertSame([
            'customer_name' => 'Customer Name',
            'customer_gender' => 'Female',
            'customer_contact' => '9800000000',
        ], $presenter->presentPlaintext($application));
    }

    public function test_customer_fields_are_not_double_encrypted(): void
    {
        $encryption = app(PiiEncryptionService::class);
        $ciphertext = $encryption->encrypt('Customer Name');
        $application = new Application();
        $application->customer_name = $ciphertext;

        $this->assertSame(
            $ciphertext,
            $application->getAttributes()['customer_name']
        );
    }

    public function test_application_grant_is_scoped_and_revocable(): void
    {
        $service = app(ApplicationPiiAccessService::class);
        $user = new User();
        $user->setRawAttributes(['id' => 501]);

        $service->unlock(
            $user,
            'password_reentry',
            ['list', 'view', 'owner_lookup'],
            ApplicationPiiAccessService::ALL_APPLICATIONS_RESOURCE_ID
        );

        $this->assertTrue($service->isUnlocked($user, 'list'));
        $this->assertTrue($service->isUnlocked($user, 'view', '123'));
        $this->assertTrue($service->isUnlocked($user, 'owner_lookup', '123'));
        $this->assertFalse($service->isUnlocked($user, 'edit', '123'));

        $service->lock();
        $this->assertFalse($service->isUnlocked($user, 'view', '123'));
    }
}
