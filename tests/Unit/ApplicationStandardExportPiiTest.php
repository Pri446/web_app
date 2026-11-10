<?php

namespace Tests\Unit;

use App\Http\Controllers\Fsm\ApplicationController;
use App\Models\User;
use App\Services\ApplicationPiiAccessService;
use App\Services\Fsm\ApplicationService;
use App\Services\OwnerPiiAuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Mockery;
use Tests\TestCase;

class ApplicationStandardExportPiiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['pii.audit_enabled' => false]);
        $this->app['session']->start();
    }

    public function test_locked_standard_export_does_not_request_customer_pii(): void
    {
        $user = $this->authorizedUser(true);
        Auth::setUser($user);
        $request = Request::create('/fsm/application/export', 'GET');

        $service = Mockery::mock(ApplicationService::class);
        $service->shouldReceive('export')
            ->once()
            ->with($request, false);

        $this->controller($service)->export($request);
    }

    public function test_active_list_reveal_adds_customer_pii_to_standard_export(): void
    {
        $user = $this->authorizedUser(true);
        Auth::setUser($user);
        $request = Request::create('/fsm/application/export', 'GET');

        app(ApplicationPiiAccessService::class)->unlock(
            $user,
            'password_reentry',
            ['list', 'view'],
            ApplicationPiiAccessService::ALL_APPLICATIONS_RESOURCE_ID
        );

        $service = Mockery::mock(ApplicationService::class);
        $service->shouldReceive('export')
            ->once()
            ->with($request, true);

        $this->controller($service)->export($request);
    }

    public function test_reveal_without_customer_pii_export_permission_stays_non_pii(): void
    {
        $user = $this->authorizedUser(false);
        Auth::setUser($user);
        $request = Request::create('/fsm/application/export', 'GET');

        app(ApplicationPiiAccessService::class)->unlock(
            $user,
            'password_reentry',
            ['list', 'view'],
            ApplicationPiiAccessService::ALL_APPLICATIONS_RESOURCE_ID
        );

        $service = Mockery::mock(ApplicationService::class);
        $service->shouldReceive('export')
            ->once()
            ->with($request, false);

        $this->controller($service)->export($request);
    }

    private function controller(ApplicationService $service): ApplicationController
    {
        return new ApplicationController(
            $service,
            app(ApplicationPiiAccessService::class),
            app(OwnerPiiAuditService::class)
        );
    }

    private function authorizedUser(bool $canExportCustomerPii): User
    {
        $user = Mockery::mock(User::class)->makePartial();
        $user->setRawAttributes(['id' => 902]);
        $user->shouldReceive('can')
            ->with('View Application Customer PII')
            ->andReturn(true);
        $user->shouldReceive('can')
            ->with('Unlock Application Customer PII')
            ->andReturn(true);
        $user->shouldReceive('can')
            ->with('Export Application Customer PII')
            ->andReturn($canExportCustomerPii);

        return $user;
    }
}
