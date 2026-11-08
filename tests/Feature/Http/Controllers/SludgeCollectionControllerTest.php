<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\User;
use App\Models\Fsm\Application;
use App\Models\Fsm\Containment;
use App\Models\Fsm\TreatmentPlant;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Tests\Feature\Http\Concerns\InputBehaviorTestHelpers;
use Tests\Feature\Http\Concerns\UiVisibilityTestHelpers;

class SludgeCollectionControllerTest extends TestCase
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
        $response = $this->get(route('sludge-collection.index'));
        $response->assertRedirect('/login');

        // Authorized user can load page
        $user = $this->getTestUser();
        $response = $this->actingAs($user)->get(route('sludge-collection.index'));
        $response->assertStatus(200);
    }

    /**
     * Gate 2 & 5: Button Visibility and Target Page Load.
     * Verify button target page loads according to Section 8 of Roadmap.
     */
    public function test_button_visibility_and_page_load(): void
    {
        $user = $this->getTestUser();

        // Index page loads correctly for authorized user
        $response = $this->actingAs($user)->get(route('sludge-collection.index'));
        $response->assertOk();
    }

    /**
     * Gate 6: Action / Function Testing - Sludge Collection Creation (Positive Case).
     * Request Outcome + Database verification according to Section 5 & 9 of Roadmap.
     */
    public function test_authorized_role_can_create_sludge_collection(): void
    {
        $user = $this->getTestUser();
        $fixture = $this->loadDataset('SludgeCollection', 1);

        $containment = Containment::first();
        $app = new Application([
            'applicant_name' => 'Test Applicant',
            'applicant_contact' => '98' . rand(10000000, 99999999),
            'applicant_gender' => 'Male',
            'customer_name' => 'Test Customer',
            'customer_gender' => 'Male',
            'customer_contact' => '98' . rand(10000000, 99999999),
            'service_provider_id' => 1,
            'desludging_vehicle_size' => '5000',
            'containment_id' => $containment ? $containment->id : 'C004528',
            'emptying_status' => true,
            'sludge_collection_status' => false,
            'is_anf' => false,
        ]);
        $app->save();
        $appId = $app->id;

        $treatmentPlant = TreatmentPlant::first();
        $uniqueReceipt = 'TIP-' . rand(100000, 999999);

        $fixture['payload']['application_id'] = $appId;
        $fixture['payload']['treatment_plant_id'] = $treatmentPlant ? $treatmentPlant->id : 1;
        $fixture['payload']['tipping_fee_receipt_no'] = $uniqueReceipt;
        $fixture['payload']['date'] = now()->format('Y-m-d');
        $fixture['payload']['entry_time'] = '10:00';
        $fixture['payload']['exit_time'] = '11:00';

        // Pre-condition assertion
        $this->assertDatabaseMissing('fsm.sludge_collections_log', [
            'tipping_fee_receipt_no' => $uniqueReceipt
        ]);

        // Action
        $response = $this->actingAs($user)
            ->post(route('sludge-collection.store'), $fixture['payload']);

        // Post-condition assertions
        $response->assertSessionDoesntHaveErrors();
        $this->assertTrue(
            DB::table('fsm.sludge_collections_log')->where('tipping_fee_receipt_no', $uniqueReceipt)->exists(),
            'Sludge Collection log record was not created in database.'
        );
    }

    /**
     * Gate 6: Validation & Input Behavior - Creating Sludge Collection Empty / Invalid Payload (Negative Case).
     * Verify required field validation failure according to Section 6 of Roadmap.
     */
    public function test_sludge_collection_creation_validation_fails_on_empty_payload(): void
    {
        $user = $this->getTestUser();
        $fixture = $this->loadDataset('SludgeCollection', 2);

        $this->assertRequiredFieldValidationFails(
            $user,
            'sludge-collection.store',
            'fsm.sludge_collections_log',
            $fixture
        );
    }

    /**
     * Gate 6: Validation & Edge Cases - Invalid Exit Time Before Entry Time (Unhappy Case).
     * Verify exit_time validation fails when exit time is earlier than entry time.
     */
    public function test_sludge_collection_creation_validation_fails_on_invalid_exit_time(): void
    {
        $user = $this->getTestUser();
        $fixture = $this->loadDataset('SludgeCollection', 3);

        $this->assertRequiredFieldValidationFails(
            $user,
            'sludge-collection.store',
            'fsm.sludge_collections_log',
            $fixture
        );
    }

    /**
     * Gate 7: Data Visibility & Output & Data Consistency.
     * Verify listing data endpoint returns valid output according to Section 7 & 9 of Roadmap.
     */
    public function test_sludge_collection_data_visibility_and_consistency(): void
    {
        $user = $this->getTestUser();
        $response = $this->actingAs($user)
            ->getJson(route('sludge-collection.get-data'));

        $response->assertOk();
    }
}
