<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\DashboardService;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\TestCase;

class DashboardServiceCacheTest extends TestCase
{
    public function test_cache_key_changes_when_user_or_data_scope_changes(): void
    {
        $service = app(DashboardService::class);
        $first = $this->user(10, 3, null);
        $second = $this->user(10, 4, null);
        $restricted = $this->user(12, 3, null);

        $this->assertNotSame(
            $service->dashboardCacheKey($first),
            $service->dashboardCacheKey($second)
        );
        $this->assertNotSame(
            $service->dashboardCacheKey($first),
            $service->dashboardCacheKey($restricted)
        );
    }

    public function test_dashboard_data_is_reused_until_invalidated(): void
    {
        $service = app(DashboardService::class);
        $user = $this->user(11, null, null);
        $calls = 0;
        $key = $service->dashboardCacheKey($user);

        $first = $service->rememberDashboardData($key, function () use (&$calls): array {
            $calls++;
            return ['value' => 'first'];
        });
        $second = $service->rememberDashboardData($key, function () use (&$calls): array {
            $calls++;
            return ['value' => 'second'];
        });

        $this->assertSame(['value' => 'first'], $first);
        $this->assertSame($first, $second);
        $this->assertSame(1, $calls);

        $service->invalidateDashboardCache();
        $this->assertNotSame($key, $service->dashboardCacheKey($user));
    }

    public function test_authorized_html_cache_is_isolated_by_the_scoped_key(): void
    {
        $service = app(DashboardService::class);
        $firstKey = $service->dashboardCacheKey($this->user(21, 3, null));
        $secondKey = $service->dashboardCacheKey($this->user(22, 3, null));

        $service->rememberDashboardHtml($firstKey, fn (): string => '<div>buildings</div>');

        $this->assertTrue($service->dashboardHtmlCacheHas($firstKey));
        $this->assertFalse($service->dashboardHtmlCacheHas($secondKey));
    }

    private function user(
        int $id,
        ?int $providerId,
        ?int $plantId
    ): User {
        $user = Mockery::mock(User::class)->makePartial();
        $user->forceFill([
            'id' => $id,
            'service_provider_id' => $providerId,
            'treatment_plant_id' => $plantId,
        ]);
        return $user;
    }
}
