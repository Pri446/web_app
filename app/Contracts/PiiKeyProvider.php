<?php

namespace App\Contracts;

interface PiiKeyProvider
{
    /**
     * Return the raw binary encryption key for a ciphertext version.
     */
    public function key(string $version): string;
}
