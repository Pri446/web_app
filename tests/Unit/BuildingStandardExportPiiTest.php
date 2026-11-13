<?php

namespace Tests\Unit;

use App\Http\Controllers\BuildingInfo\BuildingController;
use App\Models\User;
use App\Services\ApplicationPiiAccessService;
use App\Services\BuildingInfo\BuildingStructureService;
use App\Services\OwnerPiiAuditService;
use App\Services\OwnerPiiPresenter;
use App\Services\PiiAccessService;
use Illuminate\Support\Facades\Auth;
use Mockery;
use Tests\TestCase;

class BuildingStandardExportPiiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['pii.audit_enabled' => false]);
        $this->app['session']->start();
    }

    public function test_locked_standard_export_does_not_request_owner_pii(): void
    {
        $user = $this->authorizedUser(true);
        Auth::setUser($user);

        $service = Mockery::mock(BuildingStructureService::class);
        $service->shouldReceive('fetchExport')
            ->once()
            ->with(false)
            ->andReturn(response('export'));

        $this->controller($service)->export();
    }

    public function test_active_list_reveal_adds_owner_pii_to_standard_export(): void
    {
        $user = $this->authorizedUser(true);
        Auth::setUser($user);

        app(PiiAccessService::class)->unlock(
            $user,
            'password_reentry',
            ['list', 'view'],
            PiiAccessService::ALL_OWNERS_RESOURCE_ID
        );

        $service = Mockery::mock(BuildingStructureService::class);
        $service->shouldReceive('fetchExport')
            ->once()
            ->with(true)
            ->andReturn(response('export'));

        $this->controller($service)->export();
    }

    public function test_reveal_without_pii_export_permission_stays_non_pii(): void
    {
        $user = $this->authorizedUser(false);
        Auth::setUser($user);

        app(PiiAccessService::class)->unlock(
            $user,
            'password_reentry',
            ['list', 'view'],
            PiiAccessService::ALL_OWNERS_RESOURCE_ID
        );

        $service = Mockery::mock(BuildingStructureService::class);
        $service->shouldReceive('fetchExport')
            ->once()
            ->with(false)
            ->andReturn(response('export'));

        $this->controller($service)->export();
    }

    private function controller(
        BuildingStructureService $service
    ): BuildingController {
        return new BuildingController(
            $service,
            app(OwnerPiiPresenter::class),
            app(PiiAccessService::class),
            app(ApplicationPiiAccessService::class),
            app(OwnerPiiAuditService::class)
        );
    }

    private function authorizedUser(bool $canExportOwnerPii): User
    {
        $user = Mockery::mock(User::class)->makePartial();
        $user->setRawAttributes(['id' => 901]);
        $user->shouldReceive('can')
            ->with('View Owner PII')
            ->andReturn(true);
        $user->shouldReceive('can')
            ->with('Unlock Owner PII')
            ->andReturn(true);
        $user->shouldReceive('can')
            ->with('Export Owner PII')
            ->andReturn($canExportOwnerPii);

        return $user;
    }
}
