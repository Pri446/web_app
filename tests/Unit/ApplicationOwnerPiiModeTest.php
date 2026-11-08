<?php

namespace Tests\Unit;

use App\Http\Controllers\Fsm\ApplicationController;
use App\Http\Controllers\Fsm\ApplicationPiiAccessController;
use App\Models\User;
use App\Services\ApplicationPiiAccessService;
use App\Services\Fsm\ApplicationService;
use App\Services\OwnerPiiAuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Mockery;
use Tests\TestCase;

class ApplicationOwnerPiiModeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // These tests exercise scope enforcement, not the permission package.
        // Granting test permissions keeps the assertions focused on whether a
        // valid password-confirmed session is required for plaintext lookup.
        Gate::before(function () {
            return true;
        });
    }

    public function test_application_list_unlock_grants_owner_lookup_scope(): void
    {
        $user = $this->user(701, 'correct-password');
        $request = Request::create('/fsm/application/customer-pii/unlock-list', 'POST', [
            'current_password' => 'correct-password',
        ]);
        $request->setUserResolver(fn () => $user);
        $request->setLaravelSession(app('session.store'));

        $audit = Mockery::mock(OwnerPiiAuditService::class);
        $audit->shouldReceive('record')
            ->once()
            ->with(
                'application_pii_bulk_reveal_granted',
                true,
                $user,
                null,
                Mockery::on(function (array $context): bool {
                    return $context['surface'] === 'application_data_table';
                }),
                'password_reentry'
            );

        $access = app(ApplicationPiiAccessService::class);
        $controller = new ApplicationPiiAccessController($access, $audit);
        $controller->unlockList($request);

        $this->assertTrue($access->isUnlocked(
            $user,
            'owner_lookup',
            ApplicationPiiAccessService::ALL_APPLICATIONS_RESOURCE_ID
        ));
    }

    public function test_bin_lookup_is_masked_without_owner_lookup_scope(): void
    {
        $user = $this->user(702);
        $request = $this->lookupRequest($user, 'B000001');
        $applicationService = Mockery::mock(ApplicationService::class);
        $applicationService->shouldReceive('getBuildingDetails')
            ->once()
            ->with($request, false)
            ->andReturn(response()->json([
                'owner_pii_locked' => true,
                'customer_name' => null,
            ]));

        $audit = Mockery::mock(OwnerPiiAuditService::class);
        $audit->shouldNotReceive('record');

        $controller = new ApplicationController(
            $applicationService,
            app(ApplicationPiiAccessService::class),
            $audit
        );
        $response = $controller->buildingDetails($request);

        $this->assertTrue($response->getData(true)['owner_pii_locked']);
        $this->assertNull($response->getData(true)['customer_name']);
    }

    public function test_bin_lookup_can_reveal_after_application_owner_mode_is_unlocked(): void
    {
        $user = $this->user(703);
        $request = $this->lookupRequest($user, 'B000002');
        $access = app(ApplicationPiiAccessService::class);
        $access->unlock(
            $user,
            'password_reentry',
            ['list', 'view', 'owner_lookup'],
            ApplicationPiiAccessService::ALL_APPLICATIONS_RESOURCE_ID
        );

        $applicationService = Mockery::mock(ApplicationService::class);
        $applicationService->shouldReceive('getBuildingDetails')
            ->once()
            ->with($request, true)
            ->andReturn(response()->json([
                'owner_pii_locked' => false,
                'customer_name' => 'Test Owner',
            ]));

        $audit = Mockery::mock(OwnerPiiAuditService::class);
        $audit->shouldReceive('record')
            ->once()
            ->with(
                'application_owner_pii_lookup_viewed',
                true,
                $user,
                null,
                [
                    'surface' => 'application_create',
                    'bin' => 'B000002',
                ],
                'password_reentry'
            );

        $controller = new ApplicationController(
            $applicationService,
            $access,
            $audit
        );
        $response = $controller->buildingDetails($request);

        $this->assertFalse($response->getData(true)['owner_pii_locked']);
        $this->assertSame('Test Owner', $response->getData(true)['customer_name']);
    }

    private function user(int $id, string $password = 'unused'): User
    {
        $user = new User();
        $user->setRawAttributes([
            'id' => $id,
            'password' => Hash::make($password),
        ]);
        Auth::setUser($user);

        return $user;
    }

    private function lookupRequest(User $user, string $bin): Request
    {
        $request = Request::create('/fsm/application/getBuildingDetails', 'GET', [
            'bin' => $bin,
        ]);
        $request->setUserResolver(fn () => $user);

        return $request;
    }
}
