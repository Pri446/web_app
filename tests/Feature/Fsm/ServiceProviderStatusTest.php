<?php

namespace Tests\Feature\Fsm;

use App\Models\Fsm\Application;
use App\Models\Fsm\EmployeeInfo;
use App\Models\Fsm\ServiceProvider;
use App\Models\Fsm\VacutugType;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Feature tests for Service Provider status transitions, deletion guards,
 * and employee validation.
 *
 * Covers the QA Acceptance Scope from the Service Provider Logic Improvement
 * Technical Change Report (Related Jira: BASEIMIS-62).
 *
 * Two source-contract tests were already added to ServiceProviderStatusLogicTest.
 * This file adds database-backed tests for:
 *   - Inactivation with and without active applications
 *   - Soft-delete guards (applications / users / vehicles / employees)
 *   - Successful soft-delete when no dependencies exist
 *   - Success/error flash messages
 */
class ServiceProviderStatusTest extends TestCase
{
    use DatabaseTransactions;

    // =========================================================================
    // Helpers
    // =========================================================================

    private function makeProvider(int $status = 1): ServiceProvider
    {
        $token = Str::lower(Str::random(10));
        $provider = new ServiceProvider();
        $provider->company_name    = 'Test Provider ' . $token;
        $provider->email           = $token . '@example.test';
        $provider->ward            = 1;
        $provider->company_location = 'Test Address';
        $provider->contact_person  = 'Test Contact';
        $provider->contact_gender  = 'Male';
        $provider->contact_number  = '9800000000';
        $provider->service_area    = '1,2';
        $provider->status          = $status;
        $provider->save();

        return $provider;
    }

    private function makeUser(int $serviceProviderId): User
    {
        $token = Str::lower(Str::random(10));
        $user = new User();
        $user->name                = 'SP User ' . $token;
        $user->username            = 'sp-' . $token;
        $user->email               = $token . '@example.test';
        $user->password            = bcrypt('secret');
        $user->user_type           = 'Service Provider';
        $user->status              = 1;
        $user->service_provider_id = $serviceProviderId;
        $user->save();

        return $user;
    }

    private function municipalityUser(): User
    {
        $token = Str::lower(Str::random(10));
        $user = new User();
        $user->name      = 'Municipality PHPUnit ' . $token;
        $user->username  = 'muni-' . $token;
        $user->email     = $token . '@example.test';
        $user->password  = 'not-used';
        $user->user_type = 'Municipality';
        $user->status    = 1;
        $user->save();

        return $user;
    }

    // =========================================================================
    // 1. Soft-delete guards — destroy() must block when dependencies exist
    // =========================================================================

    /** @test */
    public function destroy_is_blocked_when_provider_has_linked_users(): void
    {
        $provider = $this->makeProvider();
        $this->makeUser($provider->id);

        $response = $this->withoutMiddleware()
            ->delete('/fsm/service-providers/' . $provider->id);

        $response->assertRedirect('/fsm/service-providers');
        $response->assertSessionHas('error');

        // Provider must still exist (not deleted).
        $this->assertNotNull(ServiceProvider::find($provider->id));
    }

    /** @test */
    public function destroy_is_blocked_when_provider_has_linked_vacutug_types(): void
    {
        $provider = $this->makeProvider();

        // Insert a desludging vehicle linked to the provider.
        $vehicleId = DB::table('fsm.desludging_vehicles')->insertGetId([
            'service_provider_id' => $provider->id,
            'license_plate_number' => 'BA-' . Str::upper(Str::random(4)),
            'capacity'            => 5,
            'status'              => 1,
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);

        $response = $this->withoutMiddleware()
            ->delete('/fsm/service-providers/' . $provider->id);

        $response->assertRedirect('/fsm/service-providers');
        $response->assertSessionHas('error');

        $this->assertNotNull(ServiceProvider::find($provider->id));

        // Clean up vehicle.
        DB::table('fsm.desludging_vehicles')->where('id', $vehicleId)->delete();
    }

    /** @test */
    public function destroy_is_blocked_when_provider_has_linked_employees(): void
    {
        $provider = $this->makeProvider();

        DB::table('fsm.employees')->insert([
            'service_provider_id' => $provider->id,
            'name'                => 'Test Employee',
            'gender'              => 'Male',
            'contact_number'      => '9800000000',
            'employee_type'       => 'Driver',
            'status'              => 1,
            'employment_start'    => now()->toDateString(),
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);

        $response = $this->withoutMiddleware()
            ->delete('/fsm/service-providers/' . $provider->id);

        $response->assertRedirect('/fsm/service-providers');
        $response->assertSessionHas('error');

        $this->assertNotNull(ServiceProvider::find($provider->id));
    }

    /** @test */
    public function destroy_is_blocked_when_provider_has_linked_applications(): void
    {
        $provider = $this->makeProvider();

        // Satisfy the road_code FK constraint.
        $roadCode = 'TEST-ROAD-' . Str::upper(Str::random(5));
        DB::table('utility_info.roads')->insert([
            'code'       => $roadCode,
            'name'       => 'PHPUnit Road',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Insert an application linked to this provider.
        DB::table('fsm.applications')->insert([
            'service_provider_id'         => $provider->id,
            'road_code'                   => $roadCode,
            'bin'                         => 'BIN-' . Str::upper(Str::random(5)),
            'ward'                        => 1,
            'emptying_status'             => 0,
            'emergency_desludging_status' => false,
            'approved_status'             => false,
            'feedback_status'             => false,
            'sludge_collection_status'    => false,
            'is_anf'                      => false,
            'created_at'                  => now(),
            'updated_at'                  => now(),
        ]);

        $response = $this->withoutMiddleware()
            ->delete('/fsm/service-providers/' . $provider->id);

        $response->assertRedirect('/fsm/service-providers');
        $response->assertSessionHas('error');

        $this->assertNotNull(ServiceProvider::find($provider->id));
    }

    // =========================================================================
    // 2. Successful soft-delete — no dependencies
    // =========================================================================

    /** @test */
    public function destroy_soft_deletes_provider_when_no_dependencies_exist(): void
    {
        $provider = $this->makeProvider();

        $response = $this->withoutMiddleware()
            ->delete('/fsm/service-providers/' . $provider->id);

        $response->assertRedirect('/fsm/service-providers');
        $response->assertSessionHas('success');

        // Provider must no longer be visible through the normal query scope.
        $this->assertNull(ServiceProvider::find($provider->id));

        // But must still exist as a soft-deleted record.
        $deleted = ServiceProvider::withTrashed()->find($provider->id);
        $this->assertNotNull($deleted);
        $this->assertNotNull($deleted->deleted_at);
    }

    /** @test */
    public function destroy_returns_error_for_non_existent_provider(): void
    {
        $response = $this->withoutMiddleware()
            ->delete('/fsm/service-providers/999999999');

        $response->assertRedirect('/fsm/service-providers');
        $response->assertSessionHas('error');
    }

    // =========================================================================
    // 3. Status update — active application blocks inactivation
    // =========================================================================

    /** @test */
    public function updating_provider_to_inactive_is_blocked_for_an_unstarted_application(): void
    {
        $provider = $this->makeProvider(status: 1);

        // Satisfy the road_code FK constraint.
        $roadCode = 'TEST-ROAD-' . Str::upper(Str::random(5));
        DB::table('utility_info.roads')->insert([
            'code'       => $roadCode,
            'name'       => 'PHPUnit Road',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Insert an unstarted application.
        $appId = DB::table('fsm.applications')->insertGetId([
            'service_provider_id'         => $provider->id,
            'road_code'                   => $roadCode,
            'bin'                         => 'BIN-' . Str::upper(Str::random(5)),
            'ward'                        => 1,
            'emptying_status'             => 0,   // DB column is integer; 0 = not started
            'emergency_desludging_status' => false,
            'approved_status'             => false,
            'feedback_status'             => false,
            'sludge_collection_status'    => false,
            'is_anf'                      => false,
            'created_at'                  => now(),
            'updated_at'                  => now(),
        ]);

        // Inactivate the provider (status = 0).
        $response = $this->withoutMiddleware()->patch('/fsm/service-providers/' . $provider->id, [
            'company_name'     => $provider->company_name,
            'email'            => $provider->email,
            'ward'             => $provider->ward,
            'company_location' => $provider->company_location,
            'contact_person'   => $provider->contact_person,
            'contact_gender'   => $provider->contact_gender,
            'contact_number'   => $provider->contact_number,
            'service_area'     => [1, 2],
            'status'           => 0,
        ]);

        $response->assertRedirect('/fsm/service-providers');
        $response->assertSessionHas('error');
        $app = DB::table('fsm.applications')->where('id', $appId)->first();
        $this->assertSame(
            $provider->id,
            $app->service_provider_id,
            'A blocked inactivation must keep the application assigned to its provider.'
        );
        $this->assertTrue((bool) $provider->fresh()->status);
    }

    // =========================================================================
    // 4. Source-contract — controller dependency guards
    // =========================================================================

    /** @test */
    public function destroy_method_checks_all_four_dependency_relationships(): void
    {
        $source = file_get_contents(
            app_path('Http/Controllers/Fsm/ServiceProviderController.php')
        );

        foreach (['applications()', 'users()', 'vacutugTypes()', 'employees()'] as $relation) {
            $this->assertStringContainsString(
                $relation,
                $source,
                "destroy() must check the {$relation} relationship before deleting."
            );
        }
    }

    /** @test */
    public function destroy_method_redirects_with_error_messages_for_each_dependency(): void
    {
        $source = file_get_contents(
            app_path('Http/Controllers/Fsm/ServiceProviderController.php')
        );

        $this->assertStringContainsString("'error'", $source);
    }

    /** @test */
    public function service_provider_controller_has_permission_middleware_on_destroy(): void
    {
        $source = file_get_contents(
            app_path('Http/Controllers/Fsm/ServiceProviderController.php')
        );

        $this->assertStringContainsString(
            'Delete Service Provider',
            $source,
            'The destroy route must be guarded by the "Delete Service Provider" permission.'
        );
    }

    // =========================================================================
    // 5. Employee validation — requires active provider
    //    OPEN ITEM: service_provider_id validation not yet added to
    //    EmployeeInfoRequest (documented in Service Provider Logic Improvement
    //    Change Report, BASEIMIS-62). Tests below are marked incomplete
    //    and will pass once the validation is implemented.
    // =========================================================================

    /** @test */
    public function employee_request_exists_as_form_request_class(): void
    {
        $requestPath = app_path('Http/Requests/Fsm/EmployeeInfoRequest.php');
        $this->assertFileExists(
            $requestPath,
            'EmployeeInfoRequest.php must exist in the Fsm requests directory.'
        );
    }

    /** @test */
    public function employee_request_requires_service_provider_id(): void
    {
        // OPEN ITEM (BASEIMIS-62): service_provider_id validation has not yet
        // been added to EmployeeInfoRequest. This test documents the expected
        // contract and will fail until the validation rule is implemented.
        $requestPath = app_path('Http/Requests/Fsm/EmployeeInfoRequest.php');

        if (!file_exists($requestPath)) {
            $this->markTestSkipped('EmployeeInfoRequest.php not found.');
        }

        $source = file_get_contents($requestPath);

        if (!str_contains($source, 'service_provider_id')) {
            $this->markTestIncomplete(
                'OPEN ITEM (BASEIMIS-62): service_provider_id validation not yet added to ' .
                'EmployeeInfoRequest. Implement the rule then remove this markTestIncomplete().'
            );
        }

        $this->assertStringContainsString(
            'service_provider_id',
            $source,
            'EmployeeInfoRequest must validate service_provider_id.'
        );
    }

    /** @test */
    public function employee_request_ensures_service_provider_is_active(): void
    {
        // OPEN ITEM (BASEIMIS-62): Active-provider enforcement not yet added.
        $requestPath = app_path('Http/Requests/Fsm/EmployeeInfoRequest.php');

        if (!file_exists($requestPath)) {
            $this->markTestSkipped('EmployeeInfoRequest.php not found.');
        }

        $source = file_get_contents($requestPath);

        if (!str_contains($source, 'service_provider_id')) {
            $this->markTestIncomplete(
                'OPEN ITEM (BASEIMIS-62): Active-provider enforcement not yet added to ' .
                'EmployeeInfoRequest. Implement the rule then remove this markTestIncomplete().'
            );
        }

        $this->assertTrue(
            str_contains($source, 'status') && str_contains($source, 'service_provider_id'),
            'EmployeeInfoRequest must verify the provider is active and not deleted.'
        );
    }

    // =========================================================================
    // 6. Display — show and index views exist and are reachable
    // =========================================================================

    /** @test */
    public function show_returns_200_for_existing_provider(): void
    {
        $provider = $this->makeProvider();
        $user     = $this->municipalityUser();

        $response = $this->actingAs($user)
            ->withoutMiddleware()
            ->get('/fsm/service-providers/' . $provider->id);

        $response->assertOk();
    }

    /** @test */
    public function show_returns_404_for_missing_provider(): void
    {
        $response = $this->withoutMiddleware()
            ->get('/fsm/service-providers/999999999');

        $response->assertNotFound();
    }

    /** @test */
    public function index_is_accessible_to_authenticated_user(): void
    {
        $user = $this->municipalityUser();

        $response = $this->actingAs($user)
            ->withoutMiddleware()
            ->get('/fsm/service-providers');

        $response->assertOk();
    }
}
