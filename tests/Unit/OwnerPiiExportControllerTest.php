<?php

namespace Tests\Unit;

use App\Http\Controllers\BuildingInfo\OwnerPiiExportController;
use App\Models\User;
use App\Services\OwnerPiiAuditService;
use App\Services\OwnerPiiExportService;
use App\Services\PiiAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Mockery;
use Tests\TestCase;

class OwnerPiiExportControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['pii.audit_enabled' => false]);

        Gate::before(function () {
            return true;
        });
    }

    public function test_wrong_password_blocks_export_and_revokes_access(): void
    {
        $user = $this->userWithPassword('correct-password');
        $request = $this->request($user, 'wrong-password');
        $access = app(PiiAccessService::class);
        $access->unlock(
            $user,
            'password_reentry',
            ['list', 'view'],
            PiiAccessService::ALL_OWNERS_RESOURCE_ID
        );
        $exporter = Mockery::mock(OwnerPiiExportService::class);
        $exporter->shouldNotReceive('parseCsvText');
        $exporter->shouldNotReceive('rowsForAllOwners');

        $controller = new OwnerPiiExportController(
            $exporter,
            app(OwnerPiiAuditService::class),
            $access
        );
        $controller->export($request);

        $this->assertTrue(
            $request->session()->get('errors')->has('export_password')
        );
        $this->assertFalse(
            $access->isUnlocked(
                $user,
                'list',
                PiiAccessService::ALL_OWNERS_RESOURCE_ID
            )
        );
    }

    public function test_correct_password_returns_a_no_store_csv_download(): void
    {
        $user = $this->userWithPassword('correct-password');
        $request = $this->request($user, 'correct-password');
        $exporter = Mockery::mock(OwnerPiiExportService::class);
        $exporter->shouldReceive('parseCsvText')
            ->once()
            ->with("bin\nB016739\n")
            ->andReturn(['B016739']);
        $exporter->shouldReceive('rowsForBins')
            ->once()
            ->with(['B016739'])
            ->andReturn([
                'rows' => [[
                    'B016739',
                    'Test Owner',
                    '9800000000',
                    'Male',
                    '12345678',
                    'found',
                ]],
                'requested_count' => 1,
                'exported_count' => 1,
                'missing_count' => 0,
            ]);
        $exporter->shouldReceive('headers')
            ->once()
            ->andReturn([
                'bin',
                'owner_name',
                'owner_contact',
                'owner_gender',
                'nid',
                'status',
            ]);

        $controller = new OwnerPiiExportController(
            $exporter,
            app(OwnerPiiAuditService::class),
            app(PiiAccessService::class)
        );
        $response = $controller->export($request);

        $this->assertSame(200, $response->getStatusCode());
        $cacheControl = (string) $response->headers->get('Cache-Control');
        $this->assertStringContainsString('no-store', $cacheControl);
        $this->assertStringContainsString('private', $cacheControl);
        $this->assertStringContainsString('max-age=0', $cacheControl);
        $this->assertStringContainsString(
            'owner-pii-export-',
            (string) $response->headers->get('Content-Disposition')
        );
    }

    public function test_wrong_password_returns_json_for_async_export(): void
    {
        $user = $this->userWithPassword('correct-password');
        $request = $this->request($user, 'wrong-password');
        $request->headers->set('Accept', 'application/json');
        $exporter = Mockery::mock(OwnerPiiExportService::class);
        $exporter->shouldNotReceive('parseCsvText');
        $exporter->shouldNotReceive('rowsForAllOwners');

        $controller = new OwnerPiiExportController(
            $exporter,
            app(OwnerPiiAuditService::class),
            app(PiiAccessService::class)
        );
        $response = $controller->export($request);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame(
            'Password confirmation failed.',
            $response->getData(true)['message']
        );
    }

    public function test_authorized_admin_can_explicitly_export_all_owners(): void
    {
        $user = $this->userWithPassword('correct-password', true);
        $request = Request::create(
            '/building-info/owner-pii/export',
            'POST',
            [
                'export_mode' => 'all',
                'confirm_export_all' => '1',
                'export_password' => 'correct-password',
            ]
        );
        $request->setUserResolver(fn () => $user);
        $request->setLaravelSession($this->app['session']->driver());

        $exporter = Mockery::mock(OwnerPiiExportService::class);
        $exporter->shouldNotReceive('parseCsvText');
        $exporter->shouldNotReceive('rowsForBins');
        $exporter->shouldReceive('rowsForAllOwners')
            ->once()
            ->andReturn([
                'rows' => new \ArrayIterator([[
                    'B016739',
                    'Test Owner',
                    '9800000000',
                    'Male',
                    '12345678',
                    'found',
                ]]),
                'requested_count' => 1,
                'exported_count' => 1,
                'missing_count' => 0,
            ]);
        $exporter->shouldReceive('headers')->once()->andReturn([
            'bin',
            'owner_name',
            'owner_contact',
            'owner_gender',
            'nid',
            'status',
        ]);

        $controller = new OwnerPiiExportController(
            $exporter,
            app(OwnerPiiAuditService::class),
            app(PiiAccessService::class)
        );
        $response = $controller->export($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString(
            'owner-pii-export-all-',
            (string) $response->headers->get('Content-Disposition')
        );
    }

    public function test_non_admin_cannot_request_full_owner_export(): void
    {
        $user = $this->userWithPassword('correct-password', false);
        $request = Request::create(
            '/building-info/owner-pii/export',
            'POST',
            [
                'export_mode' => 'all',
                'confirm_export_all' => '1',
                'export_password' => 'correct-password',
            ]
        );
        $request->setUserResolver(fn () => $user);
        $request->setLaravelSession($this->app['session']->driver());
        $exporter = Mockery::mock(OwnerPiiExportService::class);
        $exporter->shouldNotReceive('rowsForAllOwners');

        $controller = new OwnerPiiExportController(
            $exporter,
            app(OwnerPiiAuditService::class),
            app(PiiAccessService::class)
        );

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $controller->export($request);
    }

    public function test_full_export_requires_explicit_confirmation(): void
    {
        $user = $this->userWithPassword('correct-password', true);
        $request = Request::create(
            '/building-info/owner-pii/export',
            'POST',
            [
                'export_mode' => 'all',
                'export_password' => 'correct-password',
            ]
        );
        $request->setUserResolver(fn () => $user);
        $request->setLaravelSession($this->app['session']->driver());
        $exporter = Mockery::mock(OwnerPiiExportService::class);
        $exporter->shouldNotReceive('rowsForAllOwners');

        $controller = new OwnerPiiExportController(
            $exporter,
            app(OwnerPiiAuditService::class),
            app(PiiAccessService::class)
        );

        $this->expectException(ValidationException::class);
        $controller->export($request);
    }

    public function test_bin_list_mode_cannot_fall_back_when_csv_is_missing(): void
    {
        $user = $this->userWithPassword('correct-password');
        $request = Request::create(
            '/building-info/owner-pii/export',
            'POST',
            [
                'export_mode' => 'bin_list',
                'export_password' => 'correct-password',
            ]
        );
        $request->setUserResolver(fn () => $user);
        $request->setLaravelSession($this->app['session']->driver());
        $exporter = Mockery::mock(OwnerPiiExportService::class);
        $exporter->shouldNotReceive('rowsForBins');
        $exporter->shouldNotReceive('rowsForAllOwners');

        $controller = new OwnerPiiExportController(
            $exporter,
            app(OwnerPiiAuditService::class),
            app(PiiAccessService::class)
        );

        $this->expectException(ValidationException::class);
        $controller->export($request);
    }

    private function request(User $user, string $password): Request
    {
        $request = Request::create(
            '/building-info/owner-pii/export',
            'POST',
            [
                'export_mode' => 'bin_list',
                'export_password' => $password,
                'bin_csv' => "bin\nB016739\n",
            ]
        );
        $request->setUserResolver(function () use ($user) {
            return $user;
        });
        $request->setLaravelSession($this->app['session']->driver());

        return $request;
    }

    private function userWithPassword(
        string $password,
        ?bool $canExportAll = null
    ): User
    {
        $user = $canExportAll === null
            ? new User()
            : Mockery::mock(User::class)->makePartial();

        if ($canExportAll !== null) {
            // Full-export authorization is checked independently from the
            // base permissions granted by Gate::before in this unit test.
            $user->shouldReceive('hasAnyRole')
                ->once()
                ->andReturn($canExportAll);
        }

        $user->setRawAttributes([
            'id' => 101,
            'password' => Hash::make($password),
        ]);

        return $user;
    }
}
