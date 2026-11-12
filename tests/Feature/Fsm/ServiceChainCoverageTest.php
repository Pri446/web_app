<?php

namespace Tests\Feature\Fsm;

use App\Enums\ApplicationStatus;
use App\Models\Fsm\Application;
use App\Models\Fsm\EmployeeInfo;
use App\Models\Fsm\ServiceProvider;
use App\Models\Fsm\VacutugType;
use App\Models\User;
use App\Services\Fsm\ServiceProviderService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;
use Spatie\Permission\Middlewares\PermissionMiddleware;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ServiceChainCoverageTest extends TestCase
{
    use DatabaseTransactions;

    private function makeProvider(bool $active = true): ServiceProvider
    {
        $token = Str::lower(Str::random(10));
        $provider = new ServiceProvider();
        $provider->company_name = 'Coverage Provider ' . $token;
        $provider->email = $token . '@example.test';
        $provider->ward = 1;
        $provider->company_location = 'Coverage Address';
        $provider->contact_person = 'Coverage Contact';
        $provider->contact_gender = 'Male';
        $provider->contact_number = '9800000000';
        $provider->service_area = '1,2';
        $provider->status = $active;
        $provider->save();

        return $provider;
    }

    private function providerData(ServiceProvider $provider, bool $active): array
    {
        return [
            'company_name' => $provider->company_name,
            'email' => $provider->email,
            'ward' => $provider->ward,
            'company_location' => $provider->company_location,
            'contact_person' => $provider->contact_person,
            'contact_gender' => $provider->contact_gender,
            'contact_number' => $provider->contact_number,
            'service_area' => [1, 2],
            'status' => $active,
            'contract_document_pdf' => $provider->contract_document_pdf,
        ];
    }

    private function makeUser(ServiceProvider $provider, bool $active = true): User
    {
        $token = Str::lower(Str::random(10));
        $user = new User();
        $user->name = 'Coverage User ' . $token;
        $user->username = 'coverage-' . $token;
        $user->email = $token . '@example.test';
        $user->password = Hash::make('secret-password');
        $user->user_type = 'Service Provider';
        $user->service_provider_id = $provider->id;
        $user->status = $active;
        $user->remember_token = Str::random(60);
        $user->save();

        return $user;
    }

    private function makeEmployee(ServiceProvider $provider, bool $active = true): EmployeeInfo
    {
        $employee = new EmployeeInfo();
        $employee->service_provider_id = $provider->id;
        $employee->name = 'Coverage Employee ' . Str::random(8);
        $employee->gender = 'Male';
        $employee->contact_number = '9800000000';
        $employee->employee_type = 'Management';
        $employee->employment_start = now()->toDateString();
        $employee->status = $active;
        $employee->save();

        return $employee;
    }

    private function makeVehicle(ServiceProvider $provider, bool $active = true): VacutugType
    {
        $vehicle = new VacutugType();
        $vehicle->service_provider_id = $provider->id;
        $vehicle->license_plate_number = 'TEST-' . Str::upper(Str::random(7));
        $vehicle->capacity = 5;
        $vehicle->width = 2;
        $vehicle->comply_with_maintainance_standards = true;
        $vehicle->status = $active;
        $vehicle->save();

        return $vehicle;
    }

    private function makeApplication(
        ServiceProvider $provider,
        int $status,
        int $tripCount = 1,
        ?int $completedTrips = null,
        bool $softDeleteEmptying = false
    ): Application {
        $roadCode = 'COVERAGE-' . Str::upper(Str::random(8));
        DB::table('utility_info.roads')->insert([
            'code' => $roadCode,
            'name' => 'Coverage Road',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $application = new Application();
        $application->service_provider_id = $provider->id;
        $application->road_code = $roadCode;
        $application->bin = 'BIN-' . Str::upper(Str::random(8));
        $application->ward = 1;
        $application->emptying_status = $status;
        $application->trip_count = $tripCount;
        $application->emergency_desludging_status = false;
        $application->approved_status = false;
        $application->feedback_status = false;
        $application->sludge_collection_status = false;
        $application->is_anf = false;
        $application->save();

        if ($completedTrips !== null) {
            DB::table('fsm.emptyings')->insert([
                'application_id' => $application->id,
                'trip_no' => $completedTrips,
                'deleted_at' => $softDeleteEmptying ? now() : null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $application;
    }

    private function employeePayload(?int $providerId): array
    {
        return [
            'service_provider_id' => $providerId,
            'name' => 'Validated Employee ' . Str::random(8),
            'gender' => 'Male',
            'contact_number' => 9800000000,
            'dob' => null,
            'address' => null,
            'employee_type' => 'Management',
            'year_of_experience' => null,
            'wage' => null,
            'license_number' => null,
            'license_issue_date' => null,
            'training_status' => null,
            'employment_start' => now()->toDateString(),
            'status' => 1,
            'employment_end' => null,
        ];
    }

    private function role(string $name): Role
    {
        return Role::firstOrCreate([
            'name' => $name,
            'guard_name' => 'web',
        ]);
    }

    /** @test */
    public function application_state_and_trip_progress_control_inactivation(): void
    {
        $service = app(ServiceProviderService::class);

        $unstarted = $this->makeProvider();
        $this->makeApplication($unstarted, ApplicationStatus::NotStarted);
        $this->assertTrue($service->hasActiveApplications($unstarted->id));

        $processing = $this->makeProvider();
        $this->makeApplication($processing, ApplicationStatus::Processing, 1, 1);
        $this->assertTrue($service->hasActiveApplications($processing->id));

        $completedSingle = $this->makeProvider();
        $this->makeApplication($completedSingle, ApplicationStatus::Completed, 1, 1);
        $this->assertFalse($service->hasActiveApplications($completedSingle->id));

        $incompleteMultiTrip = $this->makeProvider();
        $this->makeApplication($incompleteMultiTrip, ApplicationStatus::Completed, 3, 2);
        $this->assertTrue($service->hasActiveApplications($incompleteMultiTrip->id));

        $completedMultiTrip = $this->makeProvider();
        $this->makeApplication($completedMultiTrip, ApplicationStatus::Completed, 3, 3);
        $this->assertFalse($service->hasActiveApplications($completedMultiTrip->id));

        $deletedTrip = $this->makeProvider();
        $this->makeApplication($deletedTrip, ApplicationStatus::Completed, 1, 1, true);
        $this->assertTrue($service->hasActiveApplications($deletedTrip->id));
    }

    /** @test */
    public function allowed_inactivation_disables_dependants_and_revokes_credentials(): void
    {
        $provider = $this->makeProvider();
        $user = $this->makeUser($provider);
        $employee = $this->makeEmployee($provider);
        $vehicle = $this->makeVehicle($provider);
        $token = hash('sha256', Str::random(40));
        $sessionId = Str::random(40);

        DB::table('personal_access_tokens')->insert([
            'tokenable_type' => User::class,
            'tokenable_id' => $user->id,
            'name' => 'coverage-token',
            'token' => $token,
            'abilities' => '["*"]',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('sessions')->insert([
            'id' => $sessionId,
            'user_id' => $user->id,
            'payload' => '',
            'last_activity' => now()->timestamp,
        ]);

        app(ServiceProviderService::class)->storeOrUpdate(
            $provider->id,
            $this->providerData($provider, false)
        );

        $this->assertFalse((bool) $provider->fresh()->status);
        $this->assertSame(0, (int) $user->fresh()->status);
        $this->assertNull($user->fresh()->remember_token);
        $this->assertFalse((bool) $employee->fresh()->status);
        $this->assertFalse((bool) $vehicle->fresh()->status);
        $this->assertDatabaseMissing('personal_access_tokens', ['token' => $token]);
        $this->assertDatabaseMissing('sessions', ['id' => $sessionId]);
    }

    /** @test */
    public function completed_application_allows_inactivation(): void
    {
        $provider = $this->makeProvider();
        $this->makeApplication($provider, ApplicationStatus::Completed, 2, 2);

        app(ServiceProviderService::class)->storeOrUpdate(
            $provider->id,
            $this->providerData($provider, false)
        );

        $this->assertFalse((bool) $provider->fresh()->status);
    }

    /** @test */
    public function activation_enables_only_provider_admin_credentials(): void
    {
        $provider = $this->makeProvider(false);
        $admin = $this->makeUser($provider, false);
        $operator = $this->makeUser($provider, false);
        $employee = $this->makeEmployee($provider, false);
        $vehicle = $this->makeVehicle($provider, false);
        $admin->assignRole($this->role('Service Provider - Admin'));
        $operator->assignRole($this->role('Service Provider - Emptying Operator'));

        app(ServiceProviderService::class)->storeOrUpdate(
            $provider->id,
            $this->providerData($provider, true)
        );

        $this->assertTrue((bool) $provider->fresh()->status);
        $this->assertSame(1, (int) $admin->fresh()->status);
        $this->assertSame(0, (int) $operator->fresh()->status);
        $this->assertFalse((bool) $employee->fresh()->status);
        $this->assertFalse((bool) $vehicle->fresh()->status);
    }

    /** @test */
    public function activation_response_sets_manual_review_modal_flag(): void
    {
        $provider = $this->makeProvider(false);

        $this->withoutMiddleware()->patch(
            '/fsm/service-providers/' . $provider->id,
            $this->providerData($provider, true)
        )->assertRedirect('/fsm/service-providers')
            ->assertSessionHas('activation_review', true);

        $view = file_get_contents(resource_path('views/fsm/service-providers/index.blade.php'));
        $this->assertStringContainsString("session('activation_review')", $view);
        $this->assertStringContainsString('manually review', $view);
    }

    /** @test */
    public function failed_cascade_rolls_back_provider_and_user_statuses(): void
    {
        $provider = $this->makeProvider();
        $user = $this->makeUser($provider);
        $this->makeEmployee($provider);
        $throwOnce = true;

        DB::listen(function ($query) use (&$throwOnce) {
            if ($throwOnce && str_contains($query->sql, 'update "fsm"."employees"')) {
                $throwOnce = false;
                throw new RuntimeException('Simulated employee cascade failure.');
            }
        });

        try {
            app(ServiceProviderService::class)->storeOrUpdate(
                $provider->id,
                $this->providerData($provider, false)
            );
            $this->fail('The simulated cascade failure should be rethrown.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Simulated employee cascade failure.', $exception->getMessage());
        }

        $this->assertTrue((bool) $provider->fresh()->status);
        $this->assertSame(1, (int) $user->fresh()->status);
    }

    /** @test */
    public function employee_create_rejects_missing_inactive_and_deleted_providers(): void
    {
        $missing = $this->employeePayload(null);
        $this->withoutMiddleware()->post('/fsm/employee-infos', $missing)
            ->assertSessionHasErrors('service_provider_id');

        $inactive = $this->makeProvider(false);
        $this->withoutMiddleware()->post('/fsm/employee-infos', $this->employeePayload($inactive->id))
            ->assertSessionHasErrors('service_provider_id');

        $deleted = $this->makeProvider();
        $deleted->delete();
        $this->withoutMiddleware()->post('/fsm/employee-infos', $this->employeePayload($deleted->id))
            ->assertSessionHasErrors('service_provider_id');
    }

    /** @test */
    public function employee_create_accepts_an_active_provider(): void
    {
        $provider = $this->makeProvider();
        $payload = $this->employeePayload($provider->id);

        $this->withoutMiddleware()->post('/fsm/employee-infos', $payload)
            ->assertRedirect('/fsm/employee-infos')
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('fsm.employees', [
            'service_provider_id' => $provider->id,
            'name' => $payload['name'],
        ]);
    }

    /** @test */
    public function provider_user_cannot_assign_an_employee_to_another_provider(): void
    {
        $ownProvider = $this->makeProvider();
        $otherProvider = $this->makeProvider();
        $user = $this->makeUser($ownProvider);
        $payload = $this->employeePayload($otherProvider->id);

        $this->actingAs($user)->withoutMiddleware()
            ->post('/fsm/employee-infos', $payload)
            ->assertRedirect('/fsm/employee-infos')
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('fsm.employees', [
            'service_provider_id' => $ownProvider->id,
            'name' => $payload['name'],
        ]);
        $this->assertDatabaseMissing('fsm.employees', [
            'service_provider_id' => $otherProvider->id,
            'name' => $payload['name'],
        ]);
    }

    /** @test */
    public function employee_update_rejects_an_inactive_provider(): void
    {
        $activeProvider = $this->makeProvider();
        $inactiveProvider = $this->makeProvider(false);
        $employee = $this->makeEmployee($activeProvider);
        $payload = $this->employeePayload($inactiveProvider->id);
        $user = $this->makeUser($activeProvider);
        $user->service_provider_id = null;
        $user->user_type = 'Municipality';
        $user->save();

        $this->actingAs($user)
            ->withoutMiddleware(PermissionMiddleware::class)
            ->patch('/fsm/employee-infos/' . $employee->id, $payload)
            ->assertSessionHasErrors('service_provider_id');

        $this->assertSame($activeProvider->id, $employee->fresh()->service_provider_id);
    }

    /** @test */
    public function employee_deletion_is_blocked_for_every_emptying_assignment(): void
    {
        $provider = $this->makeProvider();

        foreach (['driver', 'emptier1', 'emptier2'] as $assignment) {
            $employee = $this->makeEmployee($provider);
            DB::table('fsm.emptyings')->insert([
                $assignment => $employee->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->withoutMiddleware()->delete('/fsm/employee-infos/' . $employee->id)
                ->assertSessionHas('error');
            $this->assertNotNull(EmployeeInfo::find($employee->id));
        }
    }

    /** @test */
    public function vehicle_deletion_is_blocked_by_emptying_and_sludge_dependencies(): void
    {
        $provider = $this->makeProvider();
        $emptyingVehicle = $this->makeVehicle($provider);
        DB::table('fsm.emptyings')->insert([
            'desludging_vehicle_id' => $emptyingVehicle->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->withoutMiddleware()->delete('/fsm/desludging-vehicles/' . $emptyingVehicle->id)
            ->assertSessionHas('error');
        $this->assertNotNull(VacutugType::find($emptyingVehicle->id));

        $sludgeVehicle = $this->makeVehicle($provider);
        DB::table('fsm.sludge_collections')->insert([
            'desludging_vehicle_id' => $sludgeVehicle->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->withoutMiddleware()->delete('/fsm/desludging-vehicles/' . $sludgeVehicle->id)
            ->assertSessionHas('error');
        $this->assertNotNull(VacutugType::find($sludgeVehicle->id));
    }

    /** @test */
    public function dependency_free_employee_and_vehicle_are_soft_deleted(): void
    {
        $provider = $this->makeProvider();
        $employee = $this->makeEmployee($provider);
        $vehicle = $this->makeVehicle($provider);

        $this->withoutMiddleware()->delete('/fsm/employee-infos/' . $employee->id)
            ->assertSessionHas('success');
        $this->withoutMiddleware()->delete('/fsm/desludging-vehicles/' . $vehicle->id)
            ->assertSessionHas('success');

        $this->assertNull(EmployeeInfo::find($employee->id));
        $this->assertNotNull(EmployeeInfo::withTrashed()->find($employee->id)->deleted_at);
        $this->assertNull(VacutugType::find($vehicle->id));
        $this->assertNotNull(VacutugType::withTrashed()->find($vehicle->id)->deleted_at);
    }
}
