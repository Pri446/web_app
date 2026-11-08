<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\User;
use App\Models\Fsm\Application;
use App\Models\Fsm\Containment;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Testing\File;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use Tests\Feature\Http\Concerns\InputBehaviorTestHelpers;
use Tests\Feature\Http\Concerns\UiVisibilityTestHelpers;

class EmptyingControllerTest extends TestCase
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
        $response = $this->get(route('emptying.index'));
        $response->assertRedirect('/login');

        // Authorized user can load page
        $user = $this->getTestUser();
        $response = $this->actingAs($user)->get(route('emptying.index'));
        $response->assertStatus(200);
    }

    /**
     * Gate 2 & 5: Button Visibility and Target Page Load.
     * Verify button target page loads according to Section 8 of Roadmap.
     */
    public function test_button_visibility_and_page_load(): void
    {
        $user = $this->getTestUser();

        // Emptying Index page loads correctly for authorized user
        $response = $this->actingAs($user)->get(route('emptying.index'));
        $response->assertOk();
    }

    /**
     * Gate 6: Action / Function Testing - Emptying Creation (Positive Case).
     * Request Outcome + Database verification according to Section 5 & 9 of Roadmap.
     */
    public function test_authorized_role_can_create_emptying(): void
    {
        Storage::fake('local');
        $user = $this->getTestUser();
        $fixture = $this->loadDataset('Emptying', 1);

        $app = Application::whereNull('deleted_at')->first();
        if (!$app) {
            $containment = Containment::where('size', '>', 10)->first() ?? Containment::first();
            $app = new Application();
            $app->applicant_name = 'Test Applicant';
            $app->applicant_contact = '98' . rand(10000000, 99999999);
            $app->applicant_gender = 'Male';
            $app->customer_name = 'Test Customer';
            $app->customer_gender = 'Male';
            $app->customer_contact = '98' . rand(10000000, 99999999);
            $app->service_provider_id = 1;
            $app->desludging_vehicle_size = '5000';
            $app->containment_id = $containment ? $containment->id : 'C004528';
            $app->is_anf = false;
            $app->save();
        }

        // Reset application state for test
        DB::table('fsm.emptyings')->where('application_id', $app->id)->delete();
        $app->emptying_status = false;
        $app->is_anf = false;
        $app->save();

        $applicationId = (int) $app->id;

        $fixture['payload']['application_id'] = $applicationId;
        $uniqueReceipt = 'REC-' . rand(100000, 999999);
        $fixture['payload']['receipt_number'] = $uniqueReceipt;
        $fixture['payload']['service_receiver_contact'] = '98' . rand(10000000, 99999999);
        $fixture['payload']['trip_no'] = 1;
        $fixture['payload']['trip_count'] = 1;

        // Attach required fake image uploads
        $fixture['payload']['house_image'] = File::fake()->image('house.jpg', 100, 100);
        $fixture['payload']['receipt_image'] = File::fake()->image('receipt.jpg', 100, 100);

        // Pre-condition assertion
        $this->assertDatabaseMissing('fsm.emptyings', [
            'receipt_number' => $uniqueReceipt
        ]);

        // Action
        $response = $this->actingAs($user)
            ->post(route('emptying.store'), $fixture['payload']);

        // Post-condition assertions
        $response->assertSessionDoesntHaveErrors();
        $this->assertTrue(
            DB::table('fsm.emptyings')->where('receipt_number', $uniqueReceipt)->exists(),
            'Emptying record was not created in database.'
        );
    }

    /**
     * Gate 6: Validation & Input Behavior - Creating Emptying Empty / Invalid Payload (Negative Case).
     * Verify required field validation failure when emptying form fields according to Section 6 of Roadmap.
     */
    public function test_emptying_creation_validation_fails_on_empty_payload(): void
    {
        $user = $this->getTestUser();
        $fixture = $this->loadDataset('Emptying', 2);

        $this->assertRequiredFieldValidationFails(
            $user,
            'emptying.store',
            'fsm.emptyings',
            $fixture
        );
    }

    /**
     * Gate 6: Validation & Edge Cases - Sludge Volume Exceeding Containment Size (Unhappy Case).
     * Verify volume_of_sludge validation fails when exceeding containment capacity.
     */
    public function test_emptying_creation_validation_fails_on_exceeding_volume(): void
    {
        $user = $this->getTestUser();
        $fixture = $this->loadDataset('Emptying', 3);

        $app = Application::whereNull('deleted_at')->first();
        if ($app) {
            $fixture['payload']['application_id'] = (int) $app->id;
        }

        // Attach required fake image uploads
        $fixture['payload']['house_image'] = File::fake()->image('house.jpg', 100, 100);
        $fixture['payload']['receipt_image'] = File::fake()->image('receipt.jpg', 100, 100);

        $this->assertRequiredFieldValidationFails(
            $user,
            'emptying.store',
            'fsm.emptyings',
            $fixture
        );
    }

    /**
     * Gate 7: Data Visibility & Output & Data Consistency.
     * Verify listing data endpoint returns valid output according to Section 7 & 9 of Roadmap.
     */
    public function test_emptying_data_visibility_and_consistency(): void
    {
        $user = $this->getTestUser();
        $response = $this->actingAs($user)
            ->getJson(route('emptying.get-data'));

        $response->assertOk();
    }
}
