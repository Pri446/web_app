<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use Cookie;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadMigrationsFrom(database_path('migrations/cwis/equity'));
        if (env('APP_ENV') === 'production') {
            URL::forceScheme('https');
        }
        $this->setAppLocale();
    }

    // function to set languge as base lang or selected lang
    private function setAppLocale()
    {
        $locale = 'en';

        if (!empty(Cookie::get('app_language'))) {
            try {
                $decrypted = \Crypt::decryptString(Cookie::get('app_language'));
                $locale = explode('|', $decrypted)[1];
            } catch (\Exception $e) {
            }
        }
        App::setLocale($locale);
    }
}
