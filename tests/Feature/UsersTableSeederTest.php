<?php

namespace Tests\Feature;

use App\Exceptions\SeedUserConfigurationException;
use App\Models\User;
use App\Services\Deployment\SeedUserConfigurationValidator;
use Database\Seeders\UsersTableSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class UsersTableSeederTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
                'foreign_key_constraints' => false,
            ],
            'permission.table_names.roles' => 'roles',
            'permission.table_names.model_has_roles' => 'model_has_roles',
        ]);

        DB::purge('sqlite');
        DB::setDefaultConnection('sqlite');
        DB::statement("ATTACH DATABASE ':memory:' AS auth");

        Schema::create('auth.users', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('name');
            $table->string('gender')->default('');
            $table->string('username');
            $table->string('email');
            $table->string('password');
            $table->string('user_type')->default('');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('roles', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
        });

        Schema::create('model_has_roles', function (Blueprint $table): void {
            $table->unsignedInteger('role_id');
            $table->string('model_type');
            $table->unsignedInteger('model_id');
        });

        $this->app->make(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_seeding_is_idempotent_and_does_not_reset_existing_password(): void
    {
        Role::create(['name' => 'Super Admin', 'guard_name' => 'web']);
        $account = $this->validAccount();
        config(['seed_users.accounts' => ['admin' => $account]]);

        $seeder = new UsersTableSeeder();
        $validator = new SeedUserConfigurationValidator(8);
        $seeder->run($validator);

        $user = User::where('username', $account['username'])->firstOrFail();
        $this->assertTrue(Hash::check($account['password'], $user->password));
        $this->assertTrue($user->hasRole('Super Admin'));

        $replacementHash = Hash::make('Different9!Password');
        $user->password = $replacementHash;
        $user->save();

        $seeder->run($validator);

        $this->assertSame(1, User::where('username', $account['username'])->count());
        $this->assertSame($replacementHash, User::where('username', $account['username'])->value('password'));
    }

    public function test_invalid_configuration_stops_before_a_user_is_created(): void
    {
        $account = $this->validAccount();
        $email = $account['email'];
        $account['password'] = '';
        config(['seed_users.accounts' => ['admin' => $account]]);

        try {
            (new UsersTableSeeder())->run(new SeedUserConfigurationValidator(8));
            $this->fail('Seeding should have failed.');
        } catch (SeedUserConfigurationException $exception) {
            $this->assertSame(0, User::count());
            $this->assertStringNotContainsString($email, implode(' ', $exception->safeErrors()));
        }
    }

    private function validAccount(): array
    {
        return [
            'name' => 'Deployment Account',
            'username' => 'deployment_'.bin2hex(random_bytes(4)),
            'email' => bin2hex(random_bytes(4)).'@example.test',
            'password' => 'Aa9!'.bin2hex(random_bytes(8)),
            'role' => 'Super Admin',
            'user_type' => '',
            'environment_variables' => [
                'name' => 'SEED_TEST_NAME',
                'username' => 'SEED_TEST_USERNAME',
                'email' => 'SEED_TEST_EMAIL',
                'password' => 'SEED_TEST_PASSWORD',
            ],
        ];
    }
}
