<?php

namespace Tests\Unit;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Mockery;
use Tests\TestCase;

class PiiBasePermissionGateTest extends TestCase
{
    /**
     * @dataProvider piiPermissionMappingProvider
     */
    public function test_base_module_permission_grants_mapped_pii_permission(
        string $piiPermission,
        string $basePermission
    ): void {
        $user = $this->mappedPermissionUser($basePermission, true);

        $this->assertTrue(
            Gate::forUser($user)->allows($piiPermission),
            "{$basePermission} should grant {$piiPermission}."
        );
    }

    /**
     * @dataProvider piiPermissionMappingProvider
     */
    public function test_missing_base_module_permission_denies_mapped_pii_permission(
        string $piiPermission,
        string $basePermission
    ): void {
        $user = $this->mappedPermissionUser($basePermission, false);

        $this->assertFalse(
            Gate::forUser($user)->allows($piiPermission),
            "{$piiPermission} should require {$basePermission}."
        );
    }

    public function piiPermissionMappingProvider(): array
    {
        return [
            'building view' => [
                'View Owner PII',
                'Add Building Structure',
            ],
            'building unlock' => [
                'Unlock Owner PII',
                'Add Building Structure',
            ],
            'building export' => [
                'Export Owner PII',
                'Add Building Structure',
            ],
            'application view' => [
                'View Application Customer PII',
                'Add Application',
            ],
            'application unlock' => [
                'Unlock Application Customer PII',
                'Add Application',
            ],
            'application export' => [
                'Export Application Customer PII',
                'Add Application',
            ],
            'property tax view' => [
                'View Property Tax Owner PII',
                'Import Property Tax Collection From CSV',
            ],
            'property tax unlock' => [
                'Unlock Property Tax Owner PII',
                'Import Property Tax Collection From CSV',
            ],
            'property tax export' => [
                'Export Property Tax Owner PII',
                'Import Property Tax Collection From CSV',
            ],
        ];
    }

    private function mappedPermissionUser(
        string $basePermission,
        bool $hasBasePermission
    ): User {
        $user = Mockery::mock(User::class)->makePartial();
        $user->setRawAttributes(['id' => 999]);

        // Spatie registers its own Gate::before callback before the application
        // provider. Returning false here makes Spatie continue to the mapping
        // implemented by AuthServiceProvider instead of querying the test DB.
        $user->shouldReceive('checkPermissionTo')
            ->withAnyArgs()
            ->andReturn(false);

        $user->shouldReceive('hasRole')
            ->with('Super Admin')
            ->andReturn(false);

        // This is the only permission result that should determine the mapped
        // PII ability. It represents the role/direct permission assignment used
        // in production without invoking Laravel's Gate recursively.
        $user->shouldReceive('hasPermissionTo')
            ->with($basePermission)
            ->once()
            ->andReturn($hasBasePermission);

        return $user;
    }
}
