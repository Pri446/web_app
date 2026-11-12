<?php

namespace Tests\Unit;

use App\Exceptions\SeedUserConfigurationException;
use App\Services\Deployment\SeedUserConfigurationValidator;
use PHPUnit\Framework\TestCase;

class SeedUserConfigurationValidatorTest extends TestCase
{
    public function test_it_accepts_valid_configuration(): void
    {
        $accounts = ['admin' => $this->validAccount()];

        $this->assertSame($accounts, (new SeedUserConfigurationValidator(8))->validateOrFail($accounts));
    }

    public function test_it_rejects_missing_configuration_without_exposing_values(): void
    {
        $account = $this->validAccount();
        $email = $account['email'];
        $account['password'] = '';

        try {
            (new SeedUserConfigurationValidator(8))->validateOrFail(['admin' => $account]);
            $this->fail('Validation should have failed.');
        } catch (SeedUserConfigurationException $exception) {
            $output = implode(' ', $exception->safeErrors());
            $this->assertStringContainsString('SEED_TEST_PASSWORD', $output);
            $this->assertStringNotContainsString($email, $output);
        }
    }

    public function test_it_rejects_invalid_username_email_and_password(): void
    {
        $account = $this->validAccount();
        $account['username'] = 'bad username';
        $account['email'] = 'not-an-email';
        $account['password'] = 'invalid';

        $this->expectException(SeedUserConfigurationException::class);

        (new SeedUserConfigurationValidator(8))->validateOrFail(['admin' => $account]);
    }

    public function test_it_rejects_duplicate_usernames_and_emails_case_insensitively(): void
    {
        $first = $this->validAccount();
        $second = $this->validAccount();
        $second['username'] = strtoupper($first['username']);
        $second['email'] = strtoupper($first['email']);

        $this->expectException(SeedUserConfigurationException::class);

        (new SeedUserConfigurationValidator(8))->validateOrFail([
            'first' => $first,
            'second' => $second,
        ]);
    }

    private function validAccount(): array
    {
        return [
            'name' => 'Deployment Account',
            'username' => 'deployment_'.bin2hex(random_bytes(4)),
            'email' => bin2hex(random_bytes(4)).'@example.test',
            'password' => 'Aa9!'.bin2hex(random_bytes(8)),
            'role' => 'Super Admin',
            'user_type' => '',
            'environment_variables' => [
                'name' => 'SEED_TEST_NAME',
                'username' => 'SEED_TEST_USERNAME',
                'email' => 'SEED_TEST_EMAIL',
                'password' => 'SEED_TEST_PASSWORD',
            ],
        ];
    }
}
