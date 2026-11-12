<?php

namespace App\Console\Commands;

use App\Exceptions\SeedUserConfigurationException;
use App\Services\Deployment\SeedUserConfigurationValidator;
use Illuminate\Console\Command;

class ValidateSeedUserConfiguration extends Command
{
    protected $signature = 'seed-users:validate-config';

    protected $description = 'Validate deployment user configuration without displaying credential values';

    public function handle(SeedUserConfigurationValidator $validator): int
    {
        try {
            $validator->validateOrFail(config('seed_users.accounts', []));
        } catch (SeedUserConfigurationException $exception) {
            $this->error($exception->getMessage());

            foreach ($exception->safeErrors() as $error) {
                $this->line(' - '.$error);
            }

            return self::FAILURE;
        }

        $this->info('Seed user configuration is valid.');

        return self::SUCCESS;
    }
}
