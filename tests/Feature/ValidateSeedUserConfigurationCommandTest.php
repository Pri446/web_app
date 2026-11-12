<?php

namespace Tests\Feature;

use Tests\TestCase;

class ValidateSeedUserConfigurationCommandTest extends TestCase
{
    public function test_command_fails_when_required_configuration_is_missing(): void
    {
        config(['seed_users.accounts' => []]);

        $this->artisan('seed-users:validate-config')
            ->expectsOutput('Seed user configuration is missing or invalid.')
            ->assertExitCode(1);
    }

    public function test_command_fails_without_printing_an_invalid_secret(): void
    {
        $account = $this->validAccount();
        $account['password'] = 'invalid';
        config(['seed_users.accounts' => ['admin' => $account]]);

        $this->artisan('seed-users:validate-config')
            ->doesntExpectOutput('invalid')
            ->assertExitCode(1);
    }

    public function test_command_succeeds_for_valid_configuration(): void
    {
        config(['seed_users.accounts' => ['admin' => $this->validAccount()]]);

        $this->artisan('seed-users:validate-config')
            ->expectsOutput('Seed user configuration is valid.')
            ->assertExitCode(0);
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
