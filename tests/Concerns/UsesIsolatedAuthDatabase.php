<?php
namespace Tests\Concerns;
use App\Models\User;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
trait UsesIsolatedAuthDatabase
{
    protected function setUpIsolatedAuthDatabase(): void
    {
        config(['database.default' => 'sqlite', 'database.connections.sqlite' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => false,
        ]]);
        DB::purge('sqlite');
        DB::setDefaultConnection('sqlite');
        DB::statement("ATTACH DATABASE ':memory:' AS auth");
        DB::statement("ATTACH DATABASE ':memory:' AS public");
        DB::statement('CREATE TABLE auth.users (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, username TEXT, email TEXT, password TEXT, gender TEXT, status INTEGER, user_type TEXT, service_provider_id INTEGER, treatment_plant_id INTEGER, help_desk_id INTEGER, remember_token TEXT, created_at TEXT, updated_at TEXT, deleted_at TEXT)');
        DB::statement('CREATE TABLE roles (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, guard_name TEXT, created_at TEXT, updated_at TEXT)');
        DB::statement('CREATE TABLE permissions (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, guard_name TEXT, "group" TEXT, created_at TEXT, updated_at TEXT)');
        DB::statement('CREATE TABLE model_has_roles (role_id INTEGER, model_type TEXT, model_id INTEGER)');
        DB::statement('CREATE TABLE model_has_permissions (permission_id INTEGER, model_type TEXT, model_id INTEGER)');
        DB::statement('CREATE TABLE role_has_permissions (role_id INTEGER, permission_id INTEGER)');
        DB::statement('CREATE TABLE personal_access_tokens (id INTEGER PRIMARY KEY AUTOINCREMENT, tokenable_type TEXT, tokenable_id INTEGER, name TEXT, token TEXT, abilities TEXT, last_used_at TEXT, created_at TEXT, updated_at TEXT)');
        DB::statement('CREATE TABLE public.notification (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER, application_id INTEGER, message TEXT, mode TEXT, status INTEGER, created_at TEXT, updated_at TEXT)');
        foreach (['roles', 'permissions', 'model_has_roles', 'model_has_permissions', 'role_has_permissions'] as $table) {
            config(['permission.table_names.'.$table => $table]);
        }
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        Event::fake([Login::class, Logout::class]);
    }
    protected function isolatedUser(string $role = 'Super Admin', array $attributes = []): User
    {
        $user = new User();
        $user->forceFill(array_merge([
            'name' => 'Test User', 'username' => 'Mixed.User', 'email' => 'Mixed.User@Example.test',
            'password' => Hash::make('correct-password'), 'status' => 1, 'gender' => 'Other',
        ], $attributes))->save();
        $user->assignRole(Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']));
        return $user;
    }
}
