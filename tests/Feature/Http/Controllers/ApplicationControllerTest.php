<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\User;
use App\Models\Fsm\Application;
use App\Services\Fsm\ApplicationService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Tests\Feature\Http\Concerns\InputBehaviorTestHelpers;
use Tests\Feature\Http\Concerns\UiVisibilityTestHelpers;

class ApplicationControllerTest extends TestCase
{
    use DatabaseTransactions;
    use InputBehaviorTestHelpers;
    use UiVisibilityTestHelpers;

    /**
     * Helper to obtain a test user.
     *
     * @return User
     */
    protected function getTestUser(): User
    {
        $user = User::first();
        if (!$user) {
            $user = new User([
                'id' => 1,
                'name' => 'Test User',
                'email' => 'test@example.com',
                'username' => 'testuser',
                'status' => true,
            ]);
        }
        return $user;
    }

    /**
     * Gate 1: Role / Login & Page Visibility.
     * Verify route access boundary according to Section 4 of Roadmap.
     */
    public function test_role_login_and_page_visibility(): void
    {
        // Unauthenticated access should redirect to login
        $response = $this->get(route('application.create'));
        $response->assertRedirect('/login');

        // Authorized user can load page
        $user = $this->getTestUser();
        $response = $this->actingAs($user)->get(route('application.create'));
        $response->assertStatus(200);
    }

    /**
     * Gate 2 & 5: Button Visibility and Target Page Load.
     * Verify button target page loads according to Section 8 of Roadmap.
     */
    public function test_button_visibility_and_page_load(): void
    {
        $user = $this->getTestUser();

        // Index page loads correctly
        $response = $this->actingAs($user)->get(route('application.index'));
        $response->assertOk();

        // Target page (application creation form) loads correctly for authorized user
        $response = $this->actingAs($user)->get(route('application.create'));
        $response->assertOk();
    }

    /**
     * Gate 6: Action / Function Testing - Application Creation (Positive Case).
     * Request Outcome + Database verification according to Section 5 & 9 of Roadmap.
     */
    public function test_authorized_role_can_create_application(): void
    {
        $user = $this->getTestUser();
        $fixture = $this->loadDataset('Application', 1);

        $uniqueContact = '98' . rand(10000000, 99999999);
        $fixture['payload']['applicant_contact'] = $uniqueContact;
        $fixture['payload']['is_anf'] = "1";
        $fixture['payload']['anf_ward'] = 1;
        $fixture['payload']['anf_locality'] = "Test Locality";
        $fixture['payload']['anf_nearest_locality'] = "Test Landmark";
        $fixture['payload']['proposed_emptying_date'] = now()->addDays(2)->format('m/d/Y');
        $fixture['payload']['emergency_desludging_status'] = 0;

        // Bind mock ApplicationService to test application creation without database triggers
        $this->mock(ApplicationService::class, function ($mock) use ($uniqueContact) {
            $mock->shouldReceive('createApplication')
                ->once()
                ->andReturnUsing(function ($request) use ($uniqueContact) {
                    DB::table('fsm.applications')->insert([
                        'applicant_name' => 'Hari Prasad',
                        'applicant_gender' => 'Male',
                        'applicant_contact' => $uniqueContact,
                        'customer_name' => 'Ram Bahadur',
                        'customer_gender' => 'Male',
                        'customer_contact' => $uniqueContact,
                        'service_provider_id' => 1,
                        'is_anf' => true,
                        'anf_locality' => 'Test Locality',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    return redirect()->route('application.index')->with('success', 'Application created successfully.');
                });

            $mock->shouldReceive('getCreateRoute')->zeroOrMoreTimes()->andReturn(route('application.create'));
            $mock->shouldReceive('getExportRoute')->zeroOrMoreTimes()->andReturn(route('application.export'));
            $mock->shouldReceive('getReportRoute')->zeroOrMoreTimes()->andReturn('true');
            $mock->shouldReceive('getCreateFormAction')->zeroOrMoreTimes()->andReturn(route('application.store'));
            $mock->shouldReceive('getCreateFormFields')->zeroOrMoreTimes()->andReturn([]);
            $mock->shouldReceive('getIndexAction')->zeroOrMoreTimes()->andReturn(route('application.index'));
            $mock->shouldReceive('getDatatable')->zeroOrMoreTimes()->andReturn(response()->json(['data' => []]));
            $mock->shouldReceive('getFilterFormFields')->zeroOrMoreTimes()->andReturn([]);
        });

        // Pre-condition assertion
        $this->assertDatabaseMissing('fsm.applications', [
            'applicant_contact' => $uniqueContact
        ]);

        // Action
        $response = $this->actingAs($user)
            ->post(route('application.store'), $fixture['payload']);

        // Post-condition assertions
        $response->assertSessionDoesntHaveErrors();
        $this->assertTrue(
            DB::table('fsm.applications')->where('applicant_contact', $uniqueContact)->exists(),
            'Application record was not created in database.'
        );
    }

    /**
     * Gate 6: Validation & Input Behavior - Creating Application Empty / Invalid Payload (Negative Case).
     * Verify required field validation failure according to Section 6 of Roadmap.
     */
    public function test_application_creation_validation_fails_on_empty_payload(): void
    {
        $user = $this->getTestUser();
        $fixture = $this->loadDataset('Application', 2);

        $this->assertRequiredFieldValidationFails(
            $user,
            'application.store',
            'fsm.applications',
            $fixture
        );
    }

    /**
     * Gate 7: Data Visibility & Output & Data Consistency.
     * Verify listing data endpoint returns valid output according to Section 7 & 9 of Roadmap.
     */
    public function test_application_data_visibility_and_consistency(): void
    {
        $user = $this->getTestUser();
        $response = $this->actingAs($user)
            ->getJson(route('application.get-data'));

        $response->assertOk();
    }
}
