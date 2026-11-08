<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\DashboardService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;

class DashboardOptimizationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        app()->setLocale('en');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_dashboard_cache_ttl_uses_configuration_with_a_safe_minimum(): void
    {
        $service = app(DashboardService::class);

        config()->set('dashboard.cache_ttl_seconds', 240);
        $this->assertSame(240, $service->dashboardCacheTtl());

        config()->set('dashboard.cache_ttl_seconds', 0);
        $this->assertSame(1, $service->dashboardCacheTtl());
    }

    public function test_filter_order_does_not_create_duplicate_cache_entries(): void
    {
        $this->mockAuthorizationLookups([[], []], [[], []]);

        $service = app(DashboardService::class);
        $user = $this->user(100, 10, 20);

        $first = $service->dashboardCacheKey($user, [
            'year' => 2026,
            'ward' => 4,
        ]);
        $second = $service->dashboardCacheKey($user, [
            'ward' => 4,
            'year' => 2026,
        ]);

        $this->assertSame($first, $second);
    }

    public function test_filter_value_changes_the_cache_key(): void
    {
        $this->mockAuthorizationLookups([[], []], [[], []]);

        $service = app(DashboardService::class);
        $user = $this->user(101, 10, 20);

        $this->assertNotSame(
            $service->dashboardCacheKey($user, ['year' => 2025]),
            $service->dashboardCacheKey($user, ['year' => 2026])
        );
    }

    public function test_user_provider_plant_and_locale_are_cache_isolation_boundaries(): void
    {
        $this->mockAuthorizationLookups([[], [], [], [], []], [[], [], [], [], []]);

        $service = app(DashboardService::class);
        $baseKey = $service->dashboardCacheKey($this->user(200, 10, 20));

        $this->assertNotSame(
            $baseKey,
            $service->dashboardCacheKey($this->user(201, 10, 20)),
            'Different users must never share authorized dashboard HTML.'
        );
        $this->assertNotSame(
            $baseKey,
            $service->dashboardCacheKey($this->user(200, 11, 20)),
            'Service-provider scope must be represented in the key.'
        );
        $this->assertNotSame(
            $baseKey,
            $service->dashboardCacheKey($this->user(200, 10, 21)),
            'Treatment-plant scope must be represented in the key.'
        );

        app()->setLocale('np');

        $this->assertNotSame(
            $baseKey,
            $service->dashboardCacheKey($this->user(200, 10, 20)),
            'Localized dashboard HTML must not leak between locales.'
        );
    }

    public function test_role_or_permission_changes_the_cache_key(): void
    {
        $this->mockAuthorizationLookups(
            [['Municipality - Super Admin'], ['Service Provider - Admin']],
            [['Dashboard'], ['Dashboard', 'FSM Dashboard']]
        );

        $service = app(DashboardService::class);
        $user = $this->user(300, 10, null);

        $this->assertNotSame(
            $service->dashboardCacheKey($user),
            $service->dashboardCacheKey($user),
            'Authorization changes must make previously rendered HTML unreachable.'
        );
    }

    public function test_dashboard_data_resolver_runs_once_for_a_warm_cache_key(): void
    {
        $service = app(DashboardService::class);
        $calls = 0;
        $cacheKey = 'dashboard:data:unit-warm-data';

        $first = $service->rememberDashboardData($cacheKey, function () use (&$calls): array {
            $calls++;
            return ['building_count' => 15990];
        });
        $second = $service->rememberDashboardData($cacheKey, function () use (&$calls): array {
            $calls++;
            return ['building_count' => 0];
        });

        $this->assertSame(['building_count' => 15990], $first);
        $this->assertSame($first, $second);
        $this->assertSame(1, $calls);
    }

    public function test_authorized_html_resolver_runs_once_while_entry_is_fresh(): void
    {
        config()->set('dashboard.cache_ttl_seconds', 180);

        $service = app(DashboardService::class);
        $calls = 0;
        $cacheKey = 'dashboard:data:unit-warm-html';

        $first = $service->rememberDashboardHtml($cacheKey, function () use (&$calls): string {
            $calls++;
            return '<section>authorized dashboard</section>';
        });
        $second = $service->rememberDashboardHtml($cacheKey, function () use (&$calls): string {
            $calls++;
            return '<section>unexpected replacement</section>';
        });

        $this->assertSame('<section>authorized dashboard</section>', $first);
        $this->assertSame($first, $second);
        $this->assertSame(1, $calls);
        $this->assertTrue($service->dashboardHtmlCacheHas($cacheKey));
    }

    public function test_stale_authorized_html_is_returned_then_refreshed_on_termination(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-04 12:00:00'));
        config()->set('dashboard.cache_ttl_seconds', 180);
        config()->set('dashboard.cache_stale_ttl_seconds', 1800);

        $service = app(DashboardService::class);
        $calls = 0;
        $cacheKey = 'dashboard:data:unit-stale-html';
        $htmlKey = $cacheKey.':authorized-html';

        Cache::put($htmlKey, [
            'html' => '<section>stale but authorized</section>',
            'fresh_until' => now()->subSecond()->timestamp,
        ], now()->addMinutes(10));

        $responseHtml = $service->rememberDashboardHtml($cacheKey, function () use (&$calls): string {
            $calls++;
            return '<section>refreshed dashboard</section>';
        });

        $this->assertSame('<section>stale but authorized</section>', $responseHtml);
        $this->assertSame(0, $calls, 'A stale response should not block the current request.');

        $this->app->terminate();

        $this->assertSame(1, $calls);
        $this->assertSame(
            '<section>refreshed dashboard</section>',
            Cache::get($htmlKey)['html']
        );
        $this->assertGreaterThan(now()->timestamp, Cache::get($htmlKey)['fresh_until']);
    }

    public function test_invalidation_changes_the_namespace_without_flushing_unrelated_cache(): void
    {
        $this->mockAuthorizationLookups([[], []], [[], []]);

        $service = app(DashboardService::class);
        $user = $this->user(400, null, null);
        Cache::put('unrelated-application-key', 'keep-me', 600);

        $before = $service->dashboardCacheKey($user);
        $service->invalidateDashboardCache();
        $after = $service->dashboardCacheKey($user);

        $this->assertNotSame($before, $after);
        $this->assertSame('keep-me', Cache::get('unrelated-application-key'));
    }

    /**
     * Mock the role and permission signature queries so these are true unit
     * tests and do not read or modify the project's configured database.
     */
    private function mockAuthorizationLookups(array $roleSets, array $permissionSets): void
    {
        $this->assertCount(count($roleSets), $permissionSets);

        $roleQueries = array_map(
            fn (array $roles) => $this->authorizationQuery($roles),
            $roleSets
        );
        $permissionQueries = array_map(
            fn (array $permissions) => $this->authorizationQuery($permissions),
            $permissionSets
        );

        DB::shouldReceive('table')
            ->with('auth.model_has_roles as user_roles')
            ->times(count($roleQueries))
            ->andReturn(...$roleQueries);
        DB::shouldReceive('table')
            ->with('auth.permissions as permissions')
            ->times(count($permissionQueries))
            ->andReturn(...$permissionQueries);
    }

    private function authorizationQuery(array $values)
    {
        $query = Mockery::mock();
        $query->shouldReceive('join')->zeroOrMoreTimes()->andReturnSelf();
        $query->shouldReceive('where')->zeroOrMoreTimes()->andReturnSelf();
        $query->shouldReceive('orderBy')->zeroOrMoreTimes()->andReturnSelf();
        $query->shouldReceive('pluck')->once()->andReturn(collect($values));

        return $query;
    }

    private function user(int $id, ?int $providerId, ?int $plantId): User
    {
        $user = Mockery::mock(User::class)->makePartial();
        $user->forceFill([
            'id' => $id,
            'service_provider_id' => $providerId,
            'treatment_plant_id' => $plantId,
        ]);

        return $user;
    }
}
