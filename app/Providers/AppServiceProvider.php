<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use Cookie;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use App\Services\DashboardService;
use Illuminate\Support\Facades\Event;


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
        if (env('APP_ENV') === 'production') {
            URL::forceScheme('https');
        }
        $this->setAppLocale();
        $this->registerDashboardCacheInvalidation();
    }

    /**
     * Invalidate the dashboard namespace after data that feeds a widget changes.
     * Versioned invalidation works consistently with file, database and Redis stores.
     */
    private function registerDashboardCacheInvalidation(): void
    {
        $models = [
            \App\Models\BuildingInfo\Building::class,
            \App\Models\BuildingInfo\FunctionalUse::class,
            \App\Models\BuildingInfo\SanitationSystem::class,
            \App\Models\Fsm\Application::class,
            \App\Models\Fsm\Containment::class,
            \App\Models\Fsm\Ctpt::class,
            \App\Models\Fsm\CtptUsers::class,
            \App\Models\Fsm\Emptying::class,
            \App\Models\Fsm\Feedback::class,
            \App\Models\Fsm\ServiceProvider::class,
            \App\Models\Fsm\SludgeCollection::class,
            \App\Models\Fsm\TreatmentPlant::class,
            \App\Models\Fsm\VacutugType::class,
            \App\Models\LayerInfo\LandUse::class,
            \App\Models\LayerInfo\Ward::class,
            \App\Models\PublicHealth\Hotspots::class,
            \App\Models\PublicHealth\YearlyWaterborne::class,
            \App\Models\UtilityInfo\Drain::class,
            \App\Models\UtilityInfo\Roadline::class,
            \App\Models\UtilityInfo\SewerLine::class,
            \App\Models\UtilityInfo\WaterSupplys::class,
            \App\Models\WaterSupplyInfo\WaterSupply::class,
        ];

        Event::listen(
            ['eloquent.saved: *', 'eloquent.deleted: *', 'eloquent.restored: *'],
            function (string $eventName, array $payload) use ($models): void {
                $model = $payload[0] ?? null;
                if ($model && in_array(get_class($model), $models, true)) {
                    app(DashboardService::class)->invalidateDashboardCache();
                }
            }
        );
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
