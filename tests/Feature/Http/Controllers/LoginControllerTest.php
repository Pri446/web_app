<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\Feature\Http\Concerns\InputBehaviorTestHelpers;
use Tests\Feature\Http\Concerns\UiVisibilityTestHelpers;
use Tests\TestCase;

class LoginControllerTest extends TestCase
{
    use InputBehaviorTestHelpers;
    use UiVisibilityTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        config([
            'security.login_rate_limit.identity_ip.max_attempts' => 1000,
            'security.login_rate_limit.ip.max_attempts' => 1000,
            'security.login_rate_limit.identity.max_attempts' => 1000,
        ]);
    }

    public function test_login_fixture_has_the_required_case_structure(): void
    {
        foreach ($this->loadJsonFixture('Login/login.json') as $case) {
            $this->assertArrayHasKey('id', $case);
            $this->assertArrayHasKey('test-data-description', $case);
            $this->assertArrayHasKey('payload', $case);
            $this->assertArrayHasKey('expected', $case);
            $this->assertArrayHasKey('validation_errors', $case);
        }
    }

    public function test_guest_can_view_the_login_page_on_the_landing_page(): void
    {
        Auth::shouldReceive('check')->andReturn(false);

        $this->get('/')->assertOk();
    }

    public function test_guest_sees_the_login_controls(): void
    {
        Auth::shouldReceive('check')->andReturn(false);

        $response = $this->get('/');

        $response->assertSee('action="'.route('login.perform').'"', false);
        $response->assertSee('name="username"', false);
        $response->assertSee('name="password"', false);
        $response->assertSee('type="submit"', false);
        $this->assertStableElementIsVisible($response, 'showpassword');
        $this->assertStableElementIsVisible($response, 'remember');
        $response->assertSeeText('Forgot Your Password?');
    }

    public function test_authenticated_user_is_redirected_away_from_the_guest_login_route(): void
    {
        Auth::shouldReceive('guard')->with(null)->andReturnSelf();
        Auth::shouldReceive('check')->andReturn(true);
        Auth::shouldReceive('user')->andReturn((object) ['status' => 1]);

        $this->get(route('login.show'))->assertRedirect('/');
    }

    public function test_guest_is_rejected_from_protected_web_and_api_authentication_routes(): void
    {
        $this->get('/dashboard')->assertRedirect(route('login.show'));
        $this->post(route('logout.perform'))->assertRedirect(route('login.show'));
        $this->postJson('/api/logout')->assertUnauthorized();
    }

    public function test_web_required_fields_are_validated_without_flashing_the_password(): void
    {
        $this->mockGuestWebAuthentication();

        foreach (['missing web username', 'missing web password'] as $description) {
            $case = $this->fixtureCase('Login/login.json', $description);
            $response = $this->from('/')->post(route('login.perform'), $case['payload']);

            $response->assertRedirect('/');
            $response->assertSessionHasErrors($case['validation_errors']);
            $this->assertNull($response->getSession()->getOldInput('password'));
        }
    }

    public function test_api_validation_matches_the_controller_rules(): void
    {
        foreach (['missing API fields', 'invalid API email type and format'] as $description) {
            $case = $this->fixtureCase('Login/login.json', $description);
            $response = $this->postJson('/api/login', $case['payload']);

            $response->assertStatus(401);
            $response->assertJsonPath('status', $case['expected']['status']);
            $response->assertJsonPath('message', $case['expected']['message']);

            foreach ($case['validation_errors'] as $field) {
                $response->assertJsonValidationErrors($field);
            }
        }
    }

    public function test_web_email_is_trimmed_and_normalized_while_password_whitespace_is_preserved(): void
    {
        $case = $this->fixtureCase('Login/login.json', 'email whitespace and case normalization');
        $this->mockGuestWebAuthentication();
        Auth::shouldReceive('validate')
            ->once()
            ->with($case['expected']['credentials'])
            ->andReturn(false);

        $this->from('/')->post(route('login.perform'), $case['payload'])
            ->assertSessionHasErrors();
    }

    public function test_special_sql_looking_and_boundary_usernames_are_passed_as_literal_input(): void
    {
        $descriptions = [
            'special characters are ordinary username input',
            'SQL-looking text is ordinary username input',
            'one-character username boundary',
        ];
        $expectedCredentials = collect($descriptions)
            ->map(fn ($description) => $this->fixtureCase('Login/login.json', $description)['expected']['credentials'])
            ->all();

        $this->mockGuestWebAuthentication();
        Auth::shouldReceive('validate')
            ->times(count($expectedCredentials))
            ->andReturnUsing(function (array $credentials) use (&$expectedCredentials) {
                $this->assertContains($credentials, $expectedCredentials);

                return false;
            });

        foreach ($descriptions as $description) {
            $case = $this->fixtureCase('Login/login.json', $description);
            $this->from('/')->post(route('login.perform'), $case['payload'])->assertRedirect('/');
        }
    }

    public function test_web_login_currently_has_no_maximum_username_length_rule(): void
    {
        $case = $this->fixtureCase('Login/login.json', 'long username has no application validation maximum');
        $this->mockGuestWebAuthentication();
        Auth::shouldReceive('validate')
            ->once()
            ->with(Mockery::on(fn ($credentials) => $credentials['username'] === $case['payload']['username']))
            ->andReturn(false);

        $this->from('/')->post(route('login.perform'), $case['payload'])
            ->assertSessionDoesntHaveErrors(['username']);
    }

    /**
     * @dataProvider webDeniedRoleProvider
     */
    public function test_web_roles_explicitly_disallowed_by_the_controller_cannot_log_in(string $role): void
    {
        $case = $this->fixtureCase('Login/login.json', 'valid web login payload');
        $user = $this->webUserWithRole($role);

        $this->mockGuestWebAuthentication();
        $this->mockSuccessfulWebCredentialValidation($case['expected']['credentials'], $user);
        Auth::shouldReceive('logout')->once();
        Auth::shouldReceive('login')->never();

        $this->from('/')->post(route('login.perform'), $case['payload'])
            ->assertSessionHasErrors(['login' => 'You are not allowed to log in with your current role.']);
    }

    public function test_web_user_without_a_disallowed_role_can_log_in(): void
    {
        $case = $this->fixtureCase('Login/login.json', 'valid web login payload');
        $user = $this->webUserWithRole('Municipality - Super Admin');

        $this->mockGuestWebAuthentication();
        $this->mockSuccessfulWebCredentialValidation($case['expected']['credentials'], $user);
        Auth::shouldReceive('login')->once()->with($user, $case['expected']['remember']);

        $this->post(route('login.perform'), $case['payload'])->assertRedirect('/');
    }

    public function test_invalid_web_credentials_return_the_same_generic_error_for_any_identifier(): void
    {
        $this->mockGuestWebAuthentication();
        Auth::shouldReceive('validate')->twice()->andReturn(false);

        $knownLooking = $this->from('/')->post(route('login.perform'), [
            'username' => 'admin',
            'password' => 'wrong-password',
        ]);
        $unknownLooking = $this->from('/')->post(route('login.perform'), [
            'username' => 'missing-account',
            'password' => 'wrong-password',
        ]);

        $this->assertSame(
            $knownLooking->getSession()->get('errors')->all(),
            $unknownLooking->getSession()->get('errors')->all()
        );
        $this->assertSame([trans('auth.failed')], $knownLooking->getSession()->get('errors')->all());
    }

    public function test_invalid_api_credentials_are_generic_and_normalize_email_case(): void
    {
        $case = $this->fixtureCase('Login/login.json', 'valid API login payload');
        $this->mockApiAuthenticationResolver();
        Auth::shouldReceive('attempt')
            ->once()
            ->with($case['expected']['credentials'])
            ->andReturn(false);

        $response = $this->postJson('/api/login', $case['payload']);

        $response->assertStatus(401)->assertExactJson([
            'status' => false,
            'message' => 'Email & Password do not match our records.',
        ]);
        $this->assertStringNotContainsString($case['payload']['email'], $response->getContent());
    }

    public function test_api_user_without_an_allowed_role_receives_403_and_no_token(): void
    {
        $case = $this->fixtureCase('Login/login.json', 'valid API login payload');
        $user = $this->apiUserForRole('Guest', false);

        $this->mockApiAuthenticationResolver();
        Auth::shouldReceive('attempt')->once()->with($case['expected']['credentials'])->andReturn(true);
        Auth::shouldReceive('user')->once()->andReturn($user);

        $this->postJson('/api/login', $case['payload'])
            ->assertStatus(403)
            ->assertExactJson([
                'status' => false,
                'message' => 'Unauthorized: You do not have the required role to log in.',
            ]);
    }

    /**
     * @dataProvider apiAllowedRoleProvider
     */
    public function test_each_api_role_allowed_by_the_controller_can_receive_a_token(string $role): void
    {
        $case = $this->fixtureCase('Login/login.json', 'valid API login payload');
        $user = $this->apiUserForRole($role, true);

        $this->mockApiAuthenticationResolver();
        Auth::shouldReceive('attempt')->once()->with($case['expected']['credentials'])->andReturn(true);
        Auth::shouldReceive('user')->once()->andReturn($user);

        $response = $this->postJson('/api/login', $case['payload']);

        $response->assertOk();
        $response->assertJsonPath('status', true);
        $response->assertJsonPath('token', 'test-api-token');
        $response->assertJsonPath('data.name', 'API Test User');
        $response->assertJsonPath('data.permissions.building-survey', true);
        $response->assertJsonPath('data.permissions.save-emptying-service', false);
        $response->assertJsonPath('data.permissions.sewer-connection', true);
    }

    public static function webDeniedRoleProvider(): array
    {
        return [
            'building surveyor' => ['Municipality - Building Surveyor'],
            'emptying operator' => ['Service Provider - Emptying Operator'],
            'ward building surveyor' => ['Municipality - Building Surveyor (Ward)'],
        ];
    }

    public static function apiAllowedRoleProvider(): array
    {
        return [
            'super admin' => ['Super Admin'],
            'municipality super admin' => ['Municipality - Super Admin'],
            'building surveyor' => ['Municipality - Building Surveyor'],
            'infrastructure department' => ['Municipality - Infrastructure Department'],
            'emptying operator' => ['Service Provider - Emptying Operator'],
        ];
    }

    private function mockGuestWebAuthentication(): void
    {
        Auth::shouldReceive('guard')->with(null)->andReturnSelf()->byDefault();
        Auth::shouldReceive('check')->andReturn(false)->byDefault();
    }

    private function mockSuccessfulWebCredentialValidation(array $credentials, User $user): void
    {
        $provider = Mockery::mock();
        $provider->shouldReceive('retrieveByCredentials')->once()->with($credentials)->andReturn($user);

        Auth::shouldReceive('validate')->once()->with($credentials)->andReturn(true);
        Auth::shouldReceive('getProvider')->once()->andReturn($provider);
    }

    private function mockApiAuthenticationResolver(): void
    {
        Auth::shouldReceive('userResolver')->andReturn(fn () => null)->byDefault();
    }

    private function webUserWithRole(string $role): User
    {
        $roleQuery = Mockery::mock(BelongsToMany::class);
        $roleQuery->shouldReceive('whereIn')
            ->once()
            ->with('name', Mockery::on(fn ($roles) => is_array($roles)))
            ->andReturnSelf();
        $roleQuery->shouldReceive('exists')
            ->once()
            ->andReturn(in_array($role, self::blockedWebRoles(), true));

        $user = Mockery::mock(User::class)->makePartial();
        $user->shouldReceive('roles')->once()->andReturn($roleQuery);

        return $user;
    }

    private static function blockedWebRoles(): array
    {
        return [
            'Municipality - Building Surveyor',
            'Service Provider - Emptying Operator',
            'Municipality - Building Surveyor (Ward)',
        ];
    }

    private function apiUserForRole(string $role, bool $allowed): User
    {
        $user = Mockery::mock(User::class)->makePartial();
        $user->forceFill(['name' => 'API Test User', 'gender' => 'Other']);
        $user->setRelation('treatment_plant', null);
        $user->setRelation('help_desk', null);
        $user->setRelation('service_provider', null);
        $user->shouldReceive('hasAnyRole')
            ->once()
            ->with(Mockery::on(fn ($roles) => in_array($role, $roles, true) === $allowed))
            ->andReturn($allowed);

        if ($allowed) {
            $user->shouldReceive('createToken')->once()->with('API TOKEN')->andReturn((object) [
                'plainTextToken' => 'test-api-token',
            ]);
            $user->shouldReceive('can')->andReturnUsing(fn ($permission) => in_array($permission, [
                'Access Building Survey API',
                'Access Sewer Connection API',
            ], true));
        } else {
            $user->shouldReceive('createToken')->never();
        }

        return $user;
    }
}
