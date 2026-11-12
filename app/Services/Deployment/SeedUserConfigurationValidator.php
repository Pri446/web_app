<?php

namespace App\Services\Deployment;

use App\Exceptions\SeedUserConfigurationException;

class SeedUserConfigurationValidator
{
    private int $minimumPasswordLength;

    public function __construct(?int $minimumPasswordLength = null)
    {
        $this->minimumPasswordLength = $minimumPasswordLength
            ?? (int) config('seed_users.password_min_length', 8);
    }

    /**
     * @param array<string, mixed> $accounts
     * @return array<string, array<string, string>>
     */
    public function validateOrFail(array $accounts): array
    {
        $errors = [];
        $usernames = [];
        $emails = [];

        if ($accounts === []) {
            throw new SeedUserConfigurationException(['seed_users.accounts is required.']);
        }

        foreach ($accounts as $accountKey => $account) {
            if (! is_array($account)) {
                $errors[] = "seed_users.accounts.{$accountKey} must be an account definition.";
                continue;
            }

            $environmentVariables = is_array($account['environment_variables'] ?? null)
                ? $account['environment_variables']
                : [];

            foreach (['name', 'username', 'email', 'password'] as $field) {
                if (! is_string($account[$field] ?? null) || trim($account[$field]) === '') {
                    $errors[] = $this->fieldReference($accountKey, $field, $environmentVariables).' is required.';
                }
            }

            if (! is_string($account['role'] ?? null) || trim($account['role']) === '') {
                $errors[] = "seed_users.accounts.{$accountKey}.role is invalid.";
            }

            if (! array_key_exists('user_type', $account) || ! is_string($account['user_type'])) {
                $errors[] = "seed_users.accounts.{$accountKey}.user_type is invalid.";
            }

            if (! $this->requiredStringsPresent($account)) {
                continue;
            }

            if (strlen($account['name']) > 255) {
                $errors[] = $this->fieldReference($accountKey, 'name', $environmentVariables).' exceeds 255 characters.';
            }

            if (! preg_match('/\A[A-Za-z0-9_.-]{3,255}\z/', $account['username'])) {
                $errors[] = $this->fieldReference($accountKey, 'username', $environmentVariables).' has an invalid format.';
            }

            if (strlen($account['email']) > 255 || filter_var($account['email'], FILTER_VALIDATE_EMAIL) === false) {
                $errors[] = $this->fieldReference($accountKey, 'email', $environmentVariables).' has an invalid format.';
            }

            if (! $this->passwordMeetsPolicy($account['password'])) {
                $errors[] = $this->fieldReference($accountKey, 'password', $environmentVariables).' does not meet the application password policy.';
            }

            $normalizedUsername = strtolower($account['username']);
            $normalizedEmail = strtolower($account['email']);

            if (isset($usernames[$normalizedUsername])) {
                $errors[] = $this->fieldReference($accountKey, 'username', $environmentVariables).' duplicates another configured username.';
            }

            if (isset($emails[$normalizedEmail])) {
                $errors[] = $this->fieldReference($accountKey, 'email', $environmentVariables).' duplicates another configured email.';
            }

            $usernames[$normalizedUsername] = true;
            $emails[$normalizedEmail] = true;
        }

        if ($errors !== []) {
            throw new SeedUserConfigurationException(array_values(array_unique($errors)));
        }

        return $accounts;
    }

    private function requiredStringsPresent(array $account): bool
    {
        foreach (['name', 'username', 'email', 'password'] as $field) {
            if (! is_string($account[$field] ?? null) || trim($account[$field]) === '') {
                return false;
            }
        }

        return true;
    }

    private function passwordMeetsPolicy(string $password): bool
    {
        return strlen($password) >= $this->minimumPasswordLength
            && preg_match('/[a-z]/', $password) === 1
            && preg_match('/[A-Z]/', $password) === 1
            && preg_match('/[0-9]/', $password) === 1
            && preg_match('/[^A-Za-z0-9]/', $password) === 1;
    }

    private function fieldReference(string $accountKey, string $field, array $environmentVariables): string
    {
        $environmentVariable = $environmentVariables[$field] ?? null;

        return is_string($environmentVariable) && $environmentVariable !== ''
            ? $environmentVariable
            : "seed_users.accounts.{$accountKey}.{$field}";
    }
}
