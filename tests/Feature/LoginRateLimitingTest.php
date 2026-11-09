<?php

namespace Tests\Feature;

use App\Http\Middleware\TrustProxies;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class LoginRateLimitingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        config([
            'security.login_rate_limit.identity_ip' => [
                'max_attempts' => 5,
                'decay_minutes' => 1,
            ],
            'security.login_rate_limit.ip' => [
                'max_attempts' => 20,
                'decay_minutes' => 1,
            ],
            'security.login_rate_limit.identity' => [
                'max_attempts' => 15,
                'decay_minutes' => 15,
            ],
        ]);

        Auth::shouldReceive('guard')->andReturnSelf()->byDefault();
        Auth::shouldReceive('userResolver')->andReturn(fn () => null)->byDefault();
        Auth::shouldReceive('check')->andReturn(false)->byDefault();
        Auth::shouldReceive('user')->andReturn(null)->byDefault();
        Auth::shouldReceive('validate')->andReturn(false)->byDefault();
        Auth::shouldReceive('attempt')->andReturn(false)->byDefault();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        Request::setTrustedProxies([], 0);

        parent::tearDown();
    }

    public function test_web_and_api_login_routes_use_separate_named_limiters(): void
    {
        $webMiddleware = app('router')->getRoutes()->getByName('login.perform')->gatherMiddleware();
        $apiRoute = collect(app('router')->getRoutes()->getRoutes())
            ->first(fn ($route) => $route->uri() === 'api/login' && in_array('POST', $route->methods(), true));

        $this->assertContains('throttle:web-login', $webMiddleware);
        $this->assertNotNull($apiRoute);
        $this->assertContains('throttle:api-login', $apiRoute->gatherMiddleware());
    }

    public function test_attempts_below_the_limit_reach_each_login_controller(): void
    {
        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->webAttempt('allowed-user', '203.0.113.10')->assertRedirect();
            $this->apiAttempt('allowed@example.test', '203.0.113.11')->assertStatus(401);
        }
    }

    public function test_web_limit_uses_a_safe_validation_style_response(): void
    {
        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->webAttempt('web-blocked', '203.0.113.12');
        }

        $response = $this->webAttempt('web-blocked', '203.0.113.12');

        $response->assertRedirect();
        $response->assertHeader('Retry-After');
        $response->assertSessionHasErrors('username');
        $response->assertSessionHasInput('username', 'web-blocked');
    }

    public function test_api_limit_returns_json_429_with_retry_after(): void
    {
        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->apiAttempt('api-blocked@example.test', '203.0.113.13');
        }

        $response = $this->apiAttempt('api-blocked@example.test', '203.0.113.13');

        $response->assertStatus(429);
        $response->assertHeader('Retry-After');
        $response->assertJsonPath('status', false);
        $this->assertStringNotContainsString('api-blocked@example.test', $response->getContent());
    }

    public function test_identifier_normalization_prevents_case_bypass(): void
    {
        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->apiAttempt('CASE-SENSITIVE@EXAMPLE.TEST', '203.0.113.14');
        }

        $this->apiAttempt('case-sensitive@example.test', '203.0.113.14')
            ->assertStatus(429);
    }

    public function test_combination_keys_separate_identifiers_and_client_ips(): void
    {
        $this->configureLimits(1, 100, 100);

        $this->apiAttempt('first@example.test', '203.0.113.15')->assertStatus(401);
        $this->apiAttempt('second@example.test', '203.0.113.15')->assertStatus(401);
        $this->apiAttempt('first@example.test', '203.0.113.16')->assertStatus(401);
    }

    public function test_ip_limit_cannot_be_bypassed_by_changing_identifiers(): void
    {
        $this->configureLimits(100, 2, 100);

        $this->apiAttempt('one@example.test', '203.0.113.17')->assertStatus(401);
        $this->apiAttempt('two@example.test', '203.0.113.17')->assertStatus(401);
        $this->apiAttempt('three@example.test', '203.0.113.17')->assertStatus(429);
    }

    public function test_identity_limit_applies_across_client_ips(): void
    {
        $this->configureLimits(100, 100, 2);

        $this->apiAttempt('target@example.test', '203.0.113.18')->assertStatus(401);
        $this->apiAttempt('target@example.test', '203.0.113.19')->assertStatus(401);
        $this->apiAttempt('target@example.test', '203.0.113.20')->assertStatus(429);
    }

    public function test_web_and_api_counters_are_separate(): void
    {
        $this->configureLimits(1, 100, 100);

        $this->webAttempt('shared@example.test', '203.0.113.21')->assertRedirect();
        $this->apiAttempt('shared@example.test', '203.0.113.21')->assertStatus(401);
    }

    public function test_lockout_expires_automatically(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-03 12:00:00'));
        $this->configureLimits(2, 100, 100);

        $this->apiAttempt('expiry@example.test', '203.0.113.22')->assertStatus(401);
        $this->apiAttempt('expiry@example.test', '203.0.113.22')->assertStatus(401);
        $this->apiAttempt('expiry@example.test', '203.0.113.22')->assertStatus(429);

        Carbon::setTestNow(Carbon::parse('2026-09-03 12:01:01'));

        $this->apiAttempt('expiry@example.test', '203.0.113.22')->assertStatus(401);
    }

    public function test_only_configured_proxies_can_supply_the_client_ip(): void
    {
        $middleware = new TrustProxies();
        $server = [
            'REMOTE_ADDR' => '10.0.0.10',
            'HTTP_X_FORWARDED_FOR' => '198.51.100.25',
        ];

        config(['security.trusted_proxies' => null]);
        $untrustedRequest = Request::create('/', 'GET', [], [], [], $server);
        $middleware->handle($untrustedRequest, fn () => null);
        $this->assertSame('10.0.0.10', $untrustedRequest->ip());

        config(['security.trusted_proxies' => '*']);
        $wildcardRequest = Request::create('/', 'GET', [], [], [], $server);
        $middleware->handle($wildcardRequest, fn () => null);
        $this->assertSame('10.0.0.10', $wildcardRequest->ip());

        config(['security.trusted_proxies' => '10.0.0.10']);
        $trustedRequest = Request::create('/', 'GET', [], [], [], $server);
        $middleware->handle($trustedRequest, fn () => null);
        $this->assertSame('198.51.100.25', $trustedRequest->ip());
    }

    private function configureLimits(int $identityIp, int $ip, int $identity): void
    {
        config([
            'security.login_rate_limit.identity_ip.max_attempts' => $identityIp,
            'security.login_rate_limit.ip.max_attempts' => $ip,
            'security.login_rate_limit.identity.max_attempts' => $identity,
        ]);
    }

    private function webAttempt(string $username, string $ip)
    {
        return $this->withServerVariables(['REMOTE_ADDR' => $ip])
            ->from('/')
            ->post(route('login.perform'), [
                'username' => $username,
                'password' => 'invalid-password',
            ]);
    }

    private function apiAttempt(string $email, string $ip)
    {
        return $this->withServerVariables(['REMOTE_ADDR' => $ip])
            ->postJson('/api/login', [
                'email' => $email,
                'password' => 'invalid-password',
            ]);
    }
}
