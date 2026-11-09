<?php

namespace App\Exceptions;

use RuntimeException;

class SeedUserConfigurationException extends RuntimeException
{
    /** @var string[] */
    private array $safeErrors;

    /**
     * @param string[] $safeErrors Messages may contain configuration keys only, never values.
     */
    public function __construct(array $safeErrors)
    {
        $this->safeErrors = array_values($safeErrors);

        parent::__construct('Seed user configuration is missing or invalid.');
    }

    /** @return string[] */
    public function safeErrors(): array
    {
        return $this->safeErrors;
    }
}
