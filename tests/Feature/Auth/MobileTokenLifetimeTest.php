<?php

namespace Tests\Feature\Auth;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Tests\Concerns\UsesIsolatedAuthDatabase;
use Tests\TestCase;

class MobileTokenLifetimeTest extends TestCase
{
    use UsesIsolatedAuthDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpIsolatedAuthDatabase();

        Route::middleware(['api', 'auth:sanctum'])->get('/api/test-token-check', fn () => 'ok');
    }

    public function test_a_mobile_token_keeps_working_without_a_time_limit(): void
    {
        $user = $this->isolatedUser('Service Provider - Emptying Operator');
        $token = $user->createToken('API TOKEN')->plainTextToken;

        $this->travel(30)->days();

        $this->withToken($token)->getJson('/api/test-token-check')->assertOk();
    }

    public function test_changing_the_password_revokes_mobile_tokens(): void
    {
        $user = $this->isolatedUser('Service Provider - Emptying Operator');
        $token = $user->createToken('API TOKEN')->plainTextToken;

        $user->password = Hash::make('new-password');
        $user->save();

        $this->assertSame(0, $user->tokens()->count());
        $this->withToken($token)->getJson('/api/test-token-check')->assertUnauthorized();
    }

    public function test_deactivating_the_user_revokes_mobile_tokens(): void
    {
        $user = $this->isolatedUser('Service Provider - Emptying Operator');
        $user->createToken('API TOKEN');

        $user->status = 0;
        $user->save();

        $this->assertSame(0, $user->tokens()->count());
    }

    public function test_other_profile_changes_keep_mobile_tokens(): void
    {
        $user = $this->isolatedUser('Service Provider - Emptying Operator');
        $user->createToken('API TOKEN');

        $user->name = 'Renamed User';
        $user->status = 1;
        $user->save();

        $this->assertSame(1, $user->tokens()->count());
    }
}
