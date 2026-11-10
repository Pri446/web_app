<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CaseInsensitiveLoginTest extends TestCase
{
    use DatabaseTransactions;

    private function makeUser(bool $active = true): User
    {
        $token = Str::random(10);

        $user = new User();
        $user->name = 'Case Login Test';
        $user->username = 'Admin' . $token;
        $user->email = 'Mixed' . $token . '@Example.test';
        $user->password = Hash::make('secret-password');
        $user->user_type = 'Municipality';
        $user->status = $active ? 1 : 0;
        $user->save();

        return $user;
    }

    /** @test */
    public function username_login_is_case_insensitive(): void
    {
        $user = $this->makeUser();

        $this->post('/login', [
            'username' => mb_strtolower($user->username),
            'password' => 'secret-password',
        ])->assertRedirect('/');

        $this->assertAuthenticatedAs($user);
    }

    /** @test */
    public function email_login_is_case_insensitive(): void
    {
        $user = $this->makeUser();

        $this->post('/login', [
            'username' => mb_strtoupper($user->email),
            'password' => 'secret-password',
        ])->assertRedirect('/');

        $this->assertAuthenticatedAs($user);
    }

    /** @test */
    public function inactive_account_cannot_log_in(): void
    {
        $user = $this->makeUser(false);

        $this->from('/')->post('/login', [
            'username' => mb_strtolower($user->username),
            'password' => 'secret-password',
        ])->assertRedirect('/')->assertSessionHasErrors();

        $this->assertGuest();
    }

    /** @test */
    public function api_email_login_is_case_insensitive(): void
    {
        $user = $this->makeUser();
        $role = Role::firstOrCreate([
            'name' => 'Super Admin',
            'guard_name' => 'web',
        ]);
        $user->assignRole($role);

        $this->postJson('/api/login', [
            'email' => mb_strtoupper($user->email),
            'password' => 'secret-password',
        ])->assertOk()
            ->assertJsonPath('status', true)
            ->assertJsonStructure(['token']);
    }

    /** @test */
    public function inactive_account_cannot_log_in_through_the_api(): void
    {
        $user = $this->makeUser(false);

        $this->postJson('/api/login', [
            'email' => mb_strtoupper($user->email),
            'password' => 'secret-password',
        ])->assertUnauthorized()
            ->assertJsonPath('status', false);
    }
}
