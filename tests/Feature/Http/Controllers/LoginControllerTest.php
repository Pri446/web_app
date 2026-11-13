<?php
namespace Tests\Feature\Http\Controllers;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\Concerns\UsesIsolatedAuthDatabase;
use Tests\TestCase;
class LoginControllerTest extends TestCase
{
    use UsesIsolatedAuthDatabase;
    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpIsolatedAuthDatabase();
        config(['security.login_rate_limit.identity_ip.max_attempts' => 1000,
            'security.login_rate_limit.ip.max_attempts' => 1000,
            'security.login_rate_limit.identity.max_attempts' => 1000]);
    }
    public function test_guest_can_view_login_controls(): void
    {
        $this->get('/')->assertOk()->assertSee('name="username"', false)
            ->assertSee('name="password"', false);
    }
    public function test_required_web_fields_do_not_flash_passwords(): void
    {
        $this->post(route('login.perform'), ['password' => 'private'])
            ->assertSessionHasErrors('username')->assertSessionMissing('_old_input.password');
    }
    public function test_api_requires_valid_email_and_password(): void
    {
        $this->postJson('/api/login', ['email' => 'invalid'])->assertStatus(401)
            ->assertJsonValidationErrors(['email', 'password'], 'errors');
    }
    /** @dataProvider webIdentifiers */
    public function test_web_login_accepts_case_insensitive_trimmed_identifiers(string $identifier): void
    {
        $user = $this->isolatedUser();
        $this->post('/login', ['username' => $identifier, 'password' => 'correct-password'])
            ->assertRedirect('/');
        $this->assertAuthenticatedAs($user);
    }
    public static function webIdentifiers(): array
    {
        return [[' mixed.USER '], [' MIXED.USER@EXAMPLE.TEST ']];
    }
    public function test_password_whitespace_is_preserved(): void
    {
        $user = $this->isolatedUser('Super Admin', ['password' => Hash::make(' secret ')]);
        $this->post('/login', ['username' => 'mixed.user', 'password' => ' secret '])->assertRedirect('/');
        $this->assertAuthenticatedAs($user);
    }
    public function test_special_and_long_identifiers_are_literal_query_values(): void
    {
        $this->isolatedUser();
        foreach (["' OR 1=1 --", str_repeat('x', 1000)] as $identifier) {
            $this->from('/')->post('/login', ['username' => $identifier, 'password' => 'wrong'])
                ->assertRedirect('/')->assertSessionHasErrors();
            $this->assertGuest();
        }
    }
    public function test_invalid_web_credentials_are_generic(): void
    {
        $this->isolatedUser();
        foreach (['mixed.user', 'missing'] as $identifier) {
            $this->from('/')->post('/login', ['username' => $identifier, 'password' => 'wrong'])
                ->assertSessionHasErrors();
            $this->assertSame([trans('auth.failed')], session('errors')->all());
        }
    }
    /** @dataProvider blockedRoles */
    public function test_blocked_web_roles_cannot_log_in(string $role): void
    {
        $this->isolatedUser($role);
        $this->post('/login', ['username' => 'mixed.user', 'password' => 'correct-password'])
            ->assertSessionHasErrors('login');
        $this->assertGuest();
    }
    public static function blockedRoles(): array
    {
        return [['Municipality - Building Surveyor'], ['Service Provider - Emptying Operator'],
            ['Municipality - Building Surveyor (Ward)']];
    }
    public function test_inactive_users_are_rejected_by_web_and_api(): void
    {
        $this->isolatedUser('Super Admin', ['status' => 0]);
        $this->post('/login', ['username' => 'mixed.user', 'password' => 'correct-password'])
            ->assertSessionHasErrors();
        $this->assertGuest();
        $this->postJson('/api/login', ['email' => 'MIXED.USER@EXAMPLE.TEST', 'password' => 'correct-password'])
            ->assertUnauthorized()->assertJsonPath('status', false);
    }
    public function test_invalid_api_credentials_are_generic(): void
    {
        $this->postJson('/api/login', ['email' => 'missing@example.test', 'password' => 'wrong'])
            ->assertUnauthorized()->assertExactJson(['status' => false,
                'message' => __('Email & Password do not match our records.')]);
    }
    public function test_api_rejects_roles_outside_the_allowlist(): void
    {
        $this->isolatedUser('Guest');
        $this->postJson('/api/login', ['email' => 'MIXED.USER@EXAMPLE.TEST', 'password' => 'correct-password'])
            ->assertForbidden()->assertJsonPath('status', false);
        $this->assertSame(0, DB::table('personal_access_tokens')->count());
    }
    /** @dataProvider apiRoles */
    public function test_allowed_api_roles_receive_a_token(string $role): void
    {
        $this->isolatedUser($role);
        $this->postJson('/api/login', ['email' => 'MIXED.USER@EXAMPLE.TEST', 'password' => 'correct-password'])
            ->assertOk()->assertJsonPath('status', true)->assertJsonStructure(['token']);
        $this->assertSame(1, DB::table('personal_access_tokens')->count());
    }
    public static function apiRoles(): array
    {
        return [['Super Admin'], ['Municipality - Super Admin'], ['Municipality - Building Surveyor'],
            ['Municipality - Infrastructure Department'], ['Service Provider - Emptying Operator'],
            ['Treatment Plant - Admin']];
    }
}
