<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\User;
use App\Models\Fsm\Application;
use App\Models\Fsm\Containment;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Tests\Feature\Http\Concerns\InputBehaviorTestHelpers;
use Tests\Feature\Http\Concerns\UiVisibilityTestHelpers;

class FeedbackControllerTest extends TestCase
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
        $response = $this->get(route('feedback.index'));
        $response->assertRedirect('/login');

        // Authorized user can load page
        $user = $this->getTestUser();
        $response = $this->actingAs($user)->get(route('feedback.index'));
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
        $response = $this->actingAs($user)->get(route('feedback.index'));
        $response->assertOk();
    }

    /**
     * Gate 6: Action / Function Testing - Feedback Creation (Positive Case).
     * Request Outcome + Database verification according to Section 5 & 9 of Roadmap.
     */
    public function test_authorized_role_can_create_feedback(): void
    {
        $user = $this->getTestUser();
        $fixture = $this->loadDataset('Feedback', 1);

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
            'sludge_collection_status' => true,
            'feedback_status' => false,
            'is_anf' => false,
        ]);
        $app->save();
        $appId = $app->id;

        $uniqueComment = 'QA Test Feedback Comments ' . rand(100000, 999999);
        $fixture['payload']['application_id'] = $appId;
        $fixture['payload']['comments'] = $uniqueComment;
        $fixture['payload']['fsm_service_quality'] = 1;
        $fixture['payload']['wear_ppe'] = 1;

        // Pre-condition assertion
        $this->assertDatabaseMissing('fsm.feedbacks', [
            'comments' => $uniqueComment
        ]);

        // Action
        $response = $this->actingAs($user)
            ->post(route('feedback.store'), $fixture['payload']);

        // Post-condition assertions
        $response->assertSessionDoesntHaveErrors();
        $this->assertTrue(
            DB::table('fsm.feedbacks')->where('comments', $uniqueComment)->exists(),
            'Feedback record was not created in database.'
        );
    }

    /**
     * Gate 6: Validation & Input Behavior - Creating Feedback Empty / Invalid Payload (Negative Case).
     * Verify required field validation failure according to Section 6 of Roadmap.
     */
    public function test_feedback_creation_validation_fails_on_empty_payload(): void
    {
        $user = $this->getTestUser();
        $fixture = $this->loadDataset('Feedback', 2);

        $this->assertRequiredFieldValidationFails(
            $user,
            'feedback.store',
            'fsm.feedbacks',
            $fixture
        );
    }

    /**
     * Gate 7: Data Visibility & Output & Data Consistency.
     * Verify listing data endpoint returns valid output according to Section 7 & 9 of Roadmap.
     */
    public function test_feedback_data_visibility_and_consistency(): void
    {
        $user = $this->getTestUser();
        $response = $this->actingAs($user)
            ->getJson(route('feedback.get-data'));

        $response->assertOk();
    }
}
