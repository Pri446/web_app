<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Tests\TestCase;
use Tests\Concerns\UsesIsolatedAuthDatabase;

class LoginSecurityHardeningTest extends TestCase
{
    use UsesIsolatedAuthDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpIsolatedAuthDatabase();

        Cache::flush();
        Auth::shouldReceive('guard')->andReturnSelf()->byDefault();
        Auth::shouldReceive('userResolver')->andReturn(fn () => null)->byDefault();
        Auth::shouldReceive('check')->andReturn(false)->byDefault();
        Auth::shouldReceive('user')->andReturn(null)->byDefault();
        Auth::shouldReceive('validate')->andReturn(false)->byDefault();
        Auth::shouldReceive('attempt')->andReturn(false)->byDefault();
    }

    public function test_web_pages_send_clickjacking_protection_headers(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertHeader('X-Frame-Options', 'DENY');
        $response->assertHeader('Content-Security-Policy', "frame-ancestors 'none'");
    }

    public function test_login_is_rate_limited_by_normalized_identity_and_ip(): void
    {
        $username = 'rate-limit-'.Str::uuid();
        $server = ['REMOTE_ADDR' => '203.0.113.10'];

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->withServerVariables($server)
                ->post(route('login.perform'), [
                    'username' => strtoupper($username),
                    'password' => 'invalid-password',
                ])
                ->assertRedirect();
        }

        $response = $this->withServerVariables($server)
            ->post(route('login.perform'), [
                'username' => strtolower($username),
                'password' => 'invalid-password',
            ]);

        $response->assertRedirect();
        $response->assertHeader('Retry-After');
        $response->assertSessionHasErrors('username');
    }

    public function test_api_login_uses_its_dedicated_rate_limit(): void
    {
        $email = 'api-rate-limit-'.Str::uuid().'@example.test';
        $server = ['REMOTE_ADDR' => '203.0.113.11'];

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->withServerVariables($server)
                ->postJson('/api/login', [
                    'email' => strtoupper($email),
                    'password' => 'invalid-password',
                ])
                ->assertStatus(401);
        }

        $response = $this->withServerVariables($server)
            ->postJson('/api/login', [
                'email' => strtolower($email),
                'password' => 'invalid-password',
            ]);

        $response->assertStatus(429);
        $response->assertHeader('Retry-After');
        $response->assertJsonPath('status', false);
    }

}
