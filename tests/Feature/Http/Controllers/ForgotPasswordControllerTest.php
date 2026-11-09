<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Feature\Http\Concerns\InputBehaviorTestHelpers;
use Tests\TestCase;

class ForgotPasswordControllerTest extends TestCase
{
    use DatabaseTransactions;
    use InputBehaviorTestHelpers;

    /**
     * Keep this suite independent of development credentials and PostGIS migrations.
     * These disposable tables cover only the user/password-broker persistence used here.
     */
    public function createApplication()
    {
        $app = parent::createApplication();
        $app['config']->set([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'database.connections.sqlite.url' => null,
            'cache.default' => 'array',
            'session.driver' => 'array',
        ]);

        DB::purge('sqlite');
        DB::statement("ATTACH DATABASE ':memory:' AS auth");

        Schema::create('auth.users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('username')->nullable();
            $table->string('email');
            $table->string('password');
            $table->string('user_type');
            $table->integer('status')->default(1);
            $table->rememberToken();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('auth.password_resets', function (Blueprint $table) {
            $table->string('email');
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        // SQLite qualifies the index name, rather than the table, for attached schemas.
        DB::statement('CREATE UNIQUE INDEX auth.users_email_unique ON users (email)');
        DB::statement('CREATE INDEX auth.password_resets_email_index ON password_resets (email)');

        return $app;
    }

    public function test_fixture_has_the_required_case_structure(): void
    {
        foreach ($this->loadJsonFixture('ForgotPassword/forgot_password.json') as $case) {
            $this->assertArrayHasKey('id', $case);
            $this->assertArrayHasKey('test-data-description', $case);
            $this->assertArrayHasKey('payload', $case);
            $this->assertArrayHasKey('expected', $case);
            $this->assertArrayHasKey('validation_errors', $case);
        }
    }

    public function test_guest_can_view_the_password_reset_request_form(): void
    {
        $response = $this->get(route('password.request'));

        $response->assertOk();
        $response->assertViewIs('auth.passwords.email');
        $response->assertSee('action="'.route('password.email').'"', false);
        $response->assertSee('name="email"', false);
        $response->assertSeeText('Send Password Reset Link');
    }

    public function test_authenticated_user_is_redirected_away_from_guest_password_reset_routes(): void
    {
        $user = $this->createResettableUser();

        $this->actingAs($user)->get(route('password.request'))->assertRedirect('/');
        $this->actingAs($user)
            ->post(route('password.email'), ['email' => $user->email])
            ->assertRedirect('/');
    }

    public function test_registered_and_unregistered_emails_receive_equivalent_web_responses(): void
    {
        Notification::fake();
        $registered = $this->fixtureCase('ForgotPassword/forgot_password.json', 'registered email with whitespace and uppercase');
        $unregistered = $this->fixtureCase('ForgotPassword/forgot_password.json', 'unregistered valid email');
        $user = $this->createResettableUser($registered['expected']['normalized_email']);

        $registeredResponse = $this->from(route('password.request'))
            ->post(route('password.email'), $registered['payload']);
        $unregisteredResponse = $this->from(route('password.request'))
            ->post(route('password.email'), $unregistered['payload']);
        $genericMessage = trans('passwords.request_received');

        $registeredResponse->assertRedirect(route('password.request'));
        $registeredResponse->assertSessionHas('status', $genericMessage);
        $registeredResponse->assertSessionDoesntHaveErrors();
        $unregisteredResponse->assertRedirect(route('password.request'));
        $unregisteredResponse->assertSessionHas('status', $genericMessage);
        $unregisteredResponse->assertSessionDoesntHaveErrors();

        $this->assertSame($registeredResponse->getStatusCode(), $unregisteredResponse->getStatusCode());
        $this->assertSame($registeredResponse->headers->get('Location'), $unregisteredResponse->headers->get('Location'));
        $this->assertSame($registeredResponse->getSession()->get('status'), $unregisteredResponse->getSession()->get('status'));

        Notification::assertSentTo($user, ResetPassword::class);
        Notification::assertSentTimes(ResetPassword::class, 1);
    }

    public function test_unregistered_email_sends_no_reset_notification(): void
    {
        Notification::fake();
        $case = $this->fixtureCase('ForgotPassword/forgot_password.json', 'unregistered valid email');

        $this->post(route('password.email'), $case['payload'])
            ->assertSessionHas('status', $case['expected']['message']);

        Notification::assertNothingSent();
    }

    public function test_soft_deleted_email_is_not_notified_or_publicly_distinguished(): void
    {
        Notification::fake();
        $case = $this->fixtureCase('ForgotPassword/forgot_password.json', 'soft-deleted email is treated as unregistered');
        $user = $this->createResettableUser($case['payload']['email']);
        $user->delete();

        $this->post(route('password.email'), $case['payload'])
            ->assertSessionHas('status', $case['expected']['message'])
            ->assertSessionDoesntHaveErrors();

        Notification::assertNothingSent();
    }

    public function test_registered_and_unregistered_emails_receive_equivalent_json_responses(): void
    {
        Notification::fake();
        $registered = $this->fixtureCase('ForgotPassword/forgot_password.json', 'registered email with whitespace and uppercase');
        $unregistered = $this->fixtureCase('ForgotPassword/forgot_password.json', 'unregistered valid email');
        $user = $this->createResettableUser($registered['expected']['normalized_email']);

        $registeredResponse = $this->postJson(route('password.email'), $registered['payload']);
        $unregisteredResponse = $this->postJson(route('password.email'), $unregistered['payload']);
        $expectedJson = ['message' => trans('passwords.request_received')];

        $registeredResponse->assertOk()->assertExactJson($expectedJson);
        $unregisteredResponse->assertOk()->assertExactJson($expectedJson);
        $this->assertSame($registeredResponse->getStatusCode(), $unregisteredResponse->getStatusCode());
        $this->assertSame($registeredResponse->getContent(), $unregisteredResponse->getContent());

        Notification::assertSentTo($user, ResetPassword::class);
        Notification::assertSentTimes(ResetPassword::class, 1);
    }

    public function test_required_format_and_type_rules_return_the_expected_validation_structure(): void
    {
        foreach (['missing required email', 'invalid email format', 'invalid email data type'] as $description) {
            $case = $this->fixtureCase('ForgotPassword/forgot_password.json', $description);
            $response = $this->postJson(route('password.email'), $case['payload']);

            $response->assertStatus($case['expected']['status']);
            $response->assertJsonValidationErrors($case['validation_errors']);
            $response->assertJsonStructure(['message', 'errors' => ['email']]);
        }
    }

    public function test_special_and_sql_looking_valid_emails_are_treated_as_ordinary_input(): void
    {
        Notification::fake();

        foreach (['special characters in a valid email are ordinary input', 'SQL-looking valid email is ordinary input'] as $description) {
            $case = $this->fixtureCase('ForgotPassword/forgot_password.json', $description);

            $this->post(route('password.email'), $case['payload'])
                ->assertSessionHas('status', $case['expected']['message'])
                ->assertSessionDoesntHaveErrors();
        }

        Notification::assertNothingSent();
    }

    public function test_rate_limit_is_identical_for_registered_and_unknown_emails(): void
    {
        Notification::fake();
        $user = $this->createResettableUser('limited-known@example.test');
        $unknownEmail = 'limited-unknown@example.test';

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->postJson(route('password.email'), ['email' => $user->email])
                ->assertOk()->assertJsonPath('message', trans('passwords.request_received'));
            $this->postJson(route('password.email'), ['email' => $unknownEmail])
                ->assertOk()->assertJsonPath('message', trans('passwords.request_received'));
        }

        $known = $this->postJson(route('password.email'), ['email' => $user->email]);
        $unknown = $this->postJson(route('password.email'), ['email' => $unknownEmail]);
        $known->assertStatus(429)->assertHeader('Retry-After')->assertJsonPath('status', false);
        $unknown->assertStatus(429)->assertHeader('Retry-After')->assertJsonPath('status', false);
        $this->assertSame(array_keys($known->json()), array_keys($unknown->json()));
        $this->assertStringContainsString('Too many password reset requests.', $known->json('message'));
        $this->assertStringContainsString('Too many password reset requests.', $unknown->json('message'));
        Notification::assertSentTo($user, ResetPassword::class);
        Notification::assertSentTimes(ResetPassword::class, 1);
    }

    public function test_public_response_never_uses_account_not_found_wording(): void
    {
        $case = $this->fixtureCase('ForgotPassword/forgot_password.json', 'unregistered valid email');
        $response = $this->postJson(route('password.email'), $case['payload']);

        $response->assertOk();
        $this->assertSame($case['expected']['message'], $response->json('message'));
        $this->assertStringNotContainsString(trans('passwords.user'), $response->getContent());
    }

    private function createResettableUser(string $email = 'password.reset.authenticated@example.test'): User
    {
        $user = new User();
        $user->forceFill([
            'name' => 'Password Reset Test User',
            'username' => 'password-reset-test-user',
            'email' => $email,
            'password' => Hash::make('test-password'),
            'user_type' => 'test',
            'status' => 1,
        ]);
        $user->save();

        return $user;
    }
}
