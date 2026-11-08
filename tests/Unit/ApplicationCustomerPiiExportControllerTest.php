<?php

namespace Tests\Unit;

use App\Http\Controllers\Fsm\ApplicationCustomerPiiExportController;
use App\Models\User;
use App\Services\ApplicationCustomerPiiExportService;
use App\Services\ApplicationPiiAccessService;
use App\Services\OwnerPiiAuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Mockery;
use Tests\TestCase;

class ApplicationCustomerPiiExportControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['pii.audit_enabled' => false]);

        Gate::before(function () {
            return true;
        });
    }

    public function test_selected_bin_export_returns_a_no_store_download(): void
    {
        $user = $this->userWithPassword('correct-password');
        $request = $this->binRequest($user, 'correct-password');
        $exporter = Mockery::mock(ApplicationCustomerPiiExportService::class);
        $exporter->shouldReceive('parseBinCsvText')
            ->once()
            ->with("bin\nB016739\n")
            ->andReturn(['B016739']);
        $exporter->shouldReceive('rowsForBins')
            ->once()
            ->with(['B016739'], $user)
            ->andReturn($this->result());
        $exporter->shouldReceive('headers')->once()->andReturn($this->headers());

        $response = $this->controller($exporter)->export($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString(
            'no-store',
            (string) $response->headers->get('Cache-Control')
        );
        $this->assertStringContainsString(
            'application-customer-pii-export-bin_list-',
            (string) $response->headers->get('Content-Disposition')
        );
    }

    public function test_authorized_admin_can_explicitly_export_all_applications(): void
    {
        $user = $this->userWithPassword('correct-password', true);
        $request = $this->request($user, [
            'application_export_mode' => 'all',
            'confirm_application_export_all' => '1',
            'application_export_password' => 'correct-password',
        ]);
        $exporter = Mockery::mock(ApplicationCustomerPiiExportService::class);
        $exporter->shouldNotReceive('parseBinCsvText');
        $exporter->shouldNotReceive('rowsForBins');
        $exporter->shouldReceive('rowsForAllApplications')
            ->once()
            ->with($user)
            ->andReturn($this->result(true));
        $exporter->shouldReceive('headers')->once()->andReturn($this->headers());

        $response = $this->controller($exporter)->export($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString(
            'application-customer-pii-export-all-',
            (string) $response->headers->get('Content-Disposition')
        );
    }

    public function test_non_admin_cannot_request_full_export(): void
    {
        $user = $this->userWithPassword('correct-password', false);
        $request = $this->request($user, [
            'application_export_mode' => 'all',
            'confirm_application_export_all' => '1',
            'application_export_password' => 'correct-password',
        ]);
        $exporter = Mockery::mock(ApplicationCustomerPiiExportService::class);
        $exporter->shouldNotReceive('rowsForAllApplications');

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->controller($exporter)->export($request);
    }

    public function test_full_export_requires_explicit_confirmation(): void
    {
        $user = $this->userWithPassword('correct-password', true);
        $request = $this->request($user, [
            'application_export_mode' => 'all',
            'application_export_password' => 'correct-password',
        ]);
        $exporter = Mockery::mock(ApplicationCustomerPiiExportService::class);
        $exporter->shouldNotReceive('rowsForAllApplications');

        $this->expectException(ValidationException::class);
        $this->controller($exporter)->export($request);
    }

    public function test_bin_mode_cannot_fall_back_when_csv_is_missing(): void
    {
        $user = $this->userWithPassword('correct-password');
        $request = $this->request($user, [
            'application_export_mode' => 'bin_list',
            'application_export_password' => 'correct-password',
        ]);
        $exporter = Mockery::mock(ApplicationCustomerPiiExportService::class);
        $exporter->shouldNotReceive('rowsForBins');
        $exporter->shouldNotReceive('rowsForAllApplications');

        $this->expectException(ValidationException::class);
        $this->controller($exporter)->export($request);
    }

    public function test_wrong_password_blocks_both_export_paths(): void
    {
        $user = $this->userWithPassword('correct-password');
        $request = $this->binRequest($user, 'wrong-password');
        $request->headers->set('Accept', 'application/json');
        $exporter = Mockery::mock(ApplicationCustomerPiiExportService::class);
        $exporter->shouldNotReceive('parseBinCsvText');
        $exporter->shouldNotReceive('rowsForBins');
        $exporter->shouldNotReceive('rowsForAllApplications');

        $response = $this->controller($exporter)->export($request);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame(
            'Password confirmation failed.',
            $response->getData(true)['message']
        );
    }

    private function controller(
        ApplicationCustomerPiiExportService $exporter
    ): ApplicationCustomerPiiExportController {
        return new ApplicationCustomerPiiExportController(
            $exporter,
            app(OwnerPiiAuditService::class),
            app(ApplicationPiiAccessService::class)
        );
    }

    private function binRequest(User $user, string $password): Request
    {
        return $this->request($user, [
            'application_export_mode' => 'bin_list',
            'application_bin_csv' => "bin\nB016739\n",
            'application_export_password' => $password,
        ]);
    }

    private function request(User $user, array $data): Request
    {
        $request = Request::create(
            '/fsm/application/customer-pii/export',
            'POST',
            $data
        );
        $request->setUserResolver(fn () => $user);
        $request->setLaravelSession($this->app['session']->driver());

        return $request;
    }

    private function userWithPassword(
        string $password,
        ?bool $canExportAll = null
    ): User {
        $user = $canExportAll === null
            ? new User()
            : Mockery::mock(User::class)->makePartial();

        if ($canExportAll !== null) {
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

    private function headers(): array
    {
        return [
            'application_id',
            'bin',
            'customer_name',
            'customer_gender',
            'customer_contact',
            'status',
        ];
    }

    private function result(bool $lazy = false): array
    {
        $rows = [[
            '123',
            'B016739',
            'Test Customer',
            'Female',
            '9800000000',
            'found',
        ]];

        return [
            'rows' => $lazy ? new \ArrayIterator($rows) : $rows,
            'requested_count' => 1,
            'exported_count' => 1,
            'missing_count' => 0,
        ];
    }
}
