<?php

namespace App\Providers;

use App\Contracts\PiiKeyProvider;
use App\Services\PiiKeys\FilePiiKeyProvider;
use Illuminate\Support\ServiceProvider;
use RuntimeException;

class PiiServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind(PiiKeyProvider::class, function () {
            $provider = config('pii.provider', 'file');

            if ($provider === 'file') {
                return new FilePiiKeyProvider();
            }

            throw new RuntimeException(
                "Unsupported PII key provider: {$provider}."
            );
        });
    }
}
