<?php

namespace Tests\Feature;

use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Mockery;
use Tests\TestCase;

class PublicFormRateLimitingTest extends TestCase
{
    private int $contactDeliveries = 0;
    private int $resetRequests = 0;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        foreach (['password_reset_rate_limit', 'contact_rate_limit'] as $configuration) {
            config([
                'security.'.$configuration.'.identity_ip' => ['max_attempts' => 5, 'decay_minutes' => 1],
                'security.'.$configuration.'.ip' => ['max_attempts' => 20, 'decay_minutes' => 1],
                'security.'.$configuration.'.identity' => ['max_attempts' => 15, 'decay_minutes' => 15],
            ]);
        }

        Auth::shouldReceive('guard')->andReturnSelf()->byDefault();
        Auth::shouldReceive('check')->andReturn(false)->byDefault();
        Auth::shouldReceive('user')->andReturn(null)->byDefault();
        Auth::shouldReceive('userResolver')->andReturn(fn () => null)->byDefault();

        // Count calls at the real controller boundary without sending mail or querying users.
        Mail::shouldReceive('send')->andReturnUsing(function () {
            $this->contactDeliveries++;
        });
        $broker = Mockery::mock();
        $broker->shouldReceive('sendResetLink')->andReturnUsing(function () {
            $this->resetRequests++;

            return Password::RESET_LINK_SENT;
        });
        Password::shouldReceive('broker')->andReturn($broker);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public static function formProvider(): array
    {
        return [
            'forgot password' => ['password.email', 'password_reset_rate_limit', 'email'],
            'contact us' => ['contact.send', 'contact_rate_limit', 'contact_rate_limit'],
        ];
    }

    /** @dataProvider formProvider */
    public function test_allowed_requests_reach_the_controller_and_blocked_requests_do_not(string $route, string $configuration, string $error): void
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->submit($route)->assertRedirect();
        }

        $this->submit($route, '203.0.113.10', '  TEST@EXAMPLE.TEST  ')
            ->assertRedirect($route === 'contact.send' ? url('/').'#contact' : route('password.request'))
            ->assertHeader('Retry-After')
            ->assertSessionHasErrors($error);

        $this->assertSame(5, $this->serviceCalls($route));
    }

    /** @dataProvider formProvider */
    public function test_json_block_is_429_with_generic_message_and_retry_information(string $route, string $configuration): void
    {
        $this->limits($configuration, 1, 100, 100);
        $this->submit($route)->assertRedirect();
        $response = $this->submit($route, '203.0.113.10', 'test@example.test', true);

        $response->assertStatus(429)->assertHeader('Retry-After')->assertJsonPath('status', false);
        $response->assertJsonStructure(['message']);
        $this->assertStringNotContainsString('test@example.test', $response->getContent());
        $this->assertSame(1, $this->serviceCalls($route));
    }

    /** @dataProvider formProvider */
    public function test_rotating_emails_does_not_bypass_the_ip_limit(string $route, string $configuration): void
    {
        $this->limits($configuration, 100, 2, 100);
        $this->submit($route, '203.0.113.10', 'one@example.test', true)->assertStatus($route === 'contact.send' ? 302 : 200);
        $this->submit($route, '203.0.113.10', 'two@example.test', true)->assertStatus($route === 'contact.send' ? 302 : 200);
        $this->submit($route, '203.0.113.10', 'three@example.test', true)->assertStatus(429);

        $this->assertSame(2, $this->serviceCalls($route));
    }

    /** @dataProvider formProvider */
    public function test_rotating_ips_does_not_bypass_the_email_limit(string $route, string $configuration): void
    {
        $this->limits($configuration, 100, 100, 2);
        $this->submit($route, '203.0.113.10', 'test@example.test', true)->assertStatus($route === 'contact.send' ? 302 : 200);
        $this->submit($route, '203.0.113.11', 'test@example.test', true)->assertStatus($route === 'contact.send' ? 302 : 200);
        $this->submit($route, '203.0.113.12', 'test@example.test', true)->assertStatus(429);

        $this->assertSame(2, $this->serviceCalls($route));
    }

    /** @dataProvider formProvider */
    public function test_different_emails_and_ips_have_separate_combination_keys(string $route, string $configuration): void
    {
        $this->limits($configuration, 1, 100, 100);
        $this->submit($route, '203.0.113.10', 'one@example.test', true)->assertStatus($route === 'contact.send' ? 302 : 200);
        $this->submit($route, '203.0.113.10', 'two@example.test', true)->assertStatus($route === 'contact.send' ? 302 : 200);
        $this->submit($route, '203.0.113.11', 'one@example.test', true)->assertStatus($route === 'contact.send' ? 302 : 200);
        $this->assertSame(3, $this->serviceCalls($route));
    }

    /** @dataProvider formProvider */
    public function test_one_minute_lockout_expires_without_manual_action(string $route, string $configuration): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-16 10:00:00'));
        $this->limits($configuration, 1, 100, 100);
        $this->submit($route, '203.0.113.10', 'test@example.test', true)->assertStatus($route === 'contact.send' ? 302 : 200);
        $this->submit($route, '203.0.113.10', 'test@example.test', true)->assertStatus(429);
        Carbon::setTestNow(Carbon::now()->addSeconds(61));
        $this->submit($route, '203.0.113.10', 'test@example.test', true)->assertStatus($route === 'contact.send' ? 302 : 200);
        $this->assertSame(2, $this->serviceCalls($route));
    }

    /** @dataProvider formProvider */
    public function test_longer_email_limit_remains_until_its_own_expiry(string $route, string $configuration): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-16 10:00:00'));
        $this->limits($configuration, 100, 100, 1);
        $this->submit($route, '203.0.113.10', 'test@example.test', true)->assertStatus($route === 'contact.send' ? 302 : 200);
        Carbon::setTestNow(Carbon::now()->addSeconds(61));
        $this->submit($route, '203.0.113.11', 'test@example.test', true)->assertStatus(429);
        Carbon::setTestNow(Carbon::now()->addMinutes(15));
        $this->submit($route, '203.0.113.11', 'test@example.test', true)->assertStatus($route === 'contact.send' ? 302 : 200);
        $this->assertSame(2, $this->serviceCalls($route));
    }

    public function test_contact_and_password_reset_counters_are_independent(): void
    {
        $this->limits('contact_rate_limit', 1, 100, 100);
        $this->limits('password_reset_rate_limit', 1, 100, 100);
        $this->submit('contact.send')->assertRedirect();
        $this->submit('contact.send', '203.0.113.10', 'test@example.test', true)->assertStatus(429);
        $this->submit('password.email')->assertRedirect();
        $this->assertSame(1, $this->contactDeliveries);
        $this->assertSame(1, $this->resetRequests);
    }

    public function test_contact_error_is_visible_and_only_allowlisted_input_is_flashed(): void
    {
        $this->limits('contact_rate_limit', 1, 100, 100);
        $this->submit('contact.send');
        $response = $this->post(route('contact.send'), array_merge($this->payload('test@example.test'), [
            'password' => 'must-not-be-retained',
            'unexpected' => 'must-not-be-retained',
        ]));
        $response->assertRedirect(url('/').'#contact')->assertSessionHasErrors('contact_rate_limit');
        $this->assertNull($response->getSession()->getOldInput('password'));
        $this->assertNull($response->getSession()->getOldInput('unexpected'));
        $this->get('/')->assertSee('data-testid="contact-rate-limit-error"', false)->assertSeeText('Too many contact requests.');
    }

    public function test_missing_and_array_emails_are_validated_without_reset_delivery(): void
    {
        foreach ([[], ['email' => ['bad']]] as $payload) {
            $this->postJson(route('password.email'), $payload)->assertStatus(422)->assertJsonValidationErrors('email');
        }
        $this->assertSame(0, $this->resetRequests);
    }

    private function limits(string $configuration, int $combination, int $ip, int $identity): void
    {
        config([
            'security.'.$configuration.'.identity_ip.max_attempts' => $combination,
            'security.'.$configuration.'.ip.max_attempts' => $ip,
            'security.'.$configuration.'.identity.max_attempts' => $identity,
        ]);
    }

    private function submit(string $route, string $ip = '203.0.113.10', string $email = 'test@example.test', bool $json = false)
    {
        $this->withServerVariables(['REMOTE_ADDR' => $ip]);
        return $json
            ? $this->postJson(route($route), $this->payload($email))
            : $this->post(route($route), $this->payload($email));
    }

    private function payload(string $email): array
    {
        return ['name' => 'Test Visitor', 'email' => $email, 'subject' => 'Test subject', 'message' => 'Test message'];
    }

    private function serviceCalls(string $route): int
    {
        return $route === 'contact.send' ? $this->contactDeliveries : $this->resetRequests;
    }
}
