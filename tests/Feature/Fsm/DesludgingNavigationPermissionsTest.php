<?php

namespace Tests\Feature\Fsm;

use Database\Seeders\PermissionsSeeder;
use Database\Seeders\RolePermissions\MunicipalityHelpDeskSeeder;
use Database\Seeders\RolePermissions\ServiceProviderAdminSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\Feature\Fsm\Concerns\CreatesDesludgingTestData;
use Tests\TestCase;

class DesludgingNavigationPermissionsTest extends TestCase
{
    use DatabaseTransactions;
    use CreatesDesludgingTestData;

    private const REQUIRED_PERMISSIONS = [
        'List Schedule Desludging',
        'List Schedule Reintegration',
        'Regenerate Schedule Desludging',
    ];

    /** @dataProvider affectedRoles */
    public function test_seeders_restore_navigation_and_regenerate_for_existing_roles(string $roleName): void
    {
        $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
        $role->syncPermissions([]);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->seedSchedulePermissions();
        $this->seedSchedulePermissions(); // The seeders must be safe to repeat.

        foreach (self::REQUIRED_PERMISSIONS as $permission) {
            $this->assertTrue($role->fresh()->hasPermissionTo($permission), $permission);
        }
        // Schedule access alone must also reveal the FSM parent menu.
        $role->syncPermissions(self::REQUIRED_PERMISSIONS);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $user = $this->createDesludgingUser();
        $user->assignRole($role->fresh());
        $this->actingAs($user);

        foreach (self::REQUIRED_PERMISSIONS as $permission) {
            $this->assertTrue($user->can($permission), $permission);
        }

        $sidebar = view('includes.sidebar')->render();
        $this->assertStringContainsString(url('fsm/desludging-schedule'), $sidebar);
        $this->assertStringContainsString(route('desludging-reintegration.index'), $sidebar);

        $this->get('/fsm/desludging-schedule')
            ->assertOk()
            ->assertSee('id="regenerate-schedule"', false);
        $this->get('/fsm/desludging-reintegration')->assertOk();
    }

    public static function affectedRoles(): array
    {
        return [
            'municipality help desk' => ['Municipality - Help Desk'],
            'service provider admin' => ['Service Provider - Admin'],
        ];
    }

    public function test_seeders_create_missing_permissions_and_preserve_unrelated_grants(): void
    {
        Permission::whereIn('name', self::REQUIRED_PERMISSIONS)->get()->each->delete();
        $unrelatedPermission = Permission::firstOrCreate([
            'name' => 'PHPUnit unrelated permission', 'guard_name' => 'web',
        ], ['group' => 'PHPUnit unrelated group', 'type' => 'List']);
        $affectedRole = Role::firstOrCreate(['name' => 'Municipality - Help Desk', 'guard_name' => 'web']);
        $affectedRole->givePermissionTo($unrelatedPermission);
        $unrelatedRole = Role::create(['name' => 'PHPUnit unrelated role', 'guard_name' => 'web']);
        $unrelatedRole->givePermissionTo($unrelatedPermission);

        $this->seedSchedulePermissions();

        $this->assertTrue($affectedRole->fresh()->hasPermissionTo($unrelatedPermission));
        $this->assertSame([$unrelatedPermission->name], $unrelatedRole->fresh()->permissions->pluck('name')->all());
        foreach (self::REQUIRED_PERMISSIONS as $permissionName) {
            // Spatie's cached lookup omits custom group/type columns.
            $permission = Permission::where('name', $permissionName)->where('guard_name', 'web')->firstOrFail();
            $this->assertNotEmpty($permission->group);
            $this->assertNotEmpty($permission->type);
            $this->assertTrue($affectedRole->fresh()->hasPermissionTo($permission));
        }
    }

    private function seedSchedulePermissions(): void
    {
        $this->seed([
            PermissionsSeeder::class,
            MunicipalityHelpDeskSeeder::class,
            ServiceProviderAdminSeeder::class,
        ]);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
