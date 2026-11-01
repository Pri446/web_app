<?php

namespace Tests\Feature\Cwis;

use App\Models\User;
use App\Services\Cwis\CwisAccountabilityFormService;
use Database\Seeders\CwisAccountabilityDataSourceSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CwisAccountabilityWorkflowTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::whereHas('roles', function ($query) { $query->where('name', 'Super Admin'); })->firstOrFail());
        DB::table('public.site_settings')->where('name', 'active_country')->update(['value' => 'BD']);
        Storage::fake('local');
        $this->seed(\Database\Seeders\CwisAccountabilityDataSourceSeeder::class);
        DB::table('cwis.accountability_generation_runs')->delete();
        DB::table('cwis.accountability_submissions')->delete();
    }

    private function payload(): array
    {
        $answers = [];
        foreach (app(CwisAccountabilityFormService::class)->questions('BD') as $code => $label) {
            $answers[str_replace('.', '_', $code)] = 'No';
        }
        return ['year' => now()->year, 'country_code' => 'BD', 'save_mode' => 'submitted', 'answers' => $answers];
    }

    private function draft(): int
    {
        $this->post('/cwis/accountability-data', ['year' => now()->year, 'country_code' => 'BD', 'save_mode' => 'draft'])
            ->assertSessionHasNoErrors()->assertSessionHas('cwis_saved');
        return (int) DB::table('cwis.accountability_submissions')->where('country_code', 'BD')->where('reporting_year', now()->year)->value('id');
    }

    public function test_picker_form_country_mapping_and_seeder_idempotency(): void
    {
        $this->seed(CwisAccountabilityDataSourceSeeder::class);
        $this->assertCount(27, app(CwisAccountabilityFormService::class)->questions('BD'));
        $this->assertCount(29, app(CwisAccountabilityFormService::class)->questions('ALL'));
        $this->assertCount(0, app(CwisAccountabilityFormService::class)->questions('NP'));
        $this->assertSame(29, DB::table('cwis.data_source')->where('outcome', 'accountability')->count());
        $this->get('/cwis/generator-data')->assertOk()->assertSee('id="add-outcome-accountability"', false)
            ->assertSee('id="add-outcome-equity"', false)->assertSee('id="add-outcome-sustainability"', false);
        $this->get('/cwis/generator-data/create?service_outcome=accountability&country_code=BD&year=' . now()->year)
            ->assertRedirect(route('cwis.accountability.create', ['year' => now()->year, 'country_code' => 'BD']));
        $this->get('/cwis/accountability-data/create?year=' . now()->year)->assertOk()
            ->assertSee('Save as Draft')->assertSee('Submit for Approval')->assertSee('AC-1b.8')
            ->assertDontSee('name="currency_code"', false)->assertSee('novalidate', false);
        DB::table('public.site_settings')->where('name', 'active_country')->update(['value' => 'NP']);
        $this->get('/cwis/generator-data')->assertOk()->assertDontSee('id="add-outcome-accountability"', false);
        $this->get('/cwis/accountability-data/create?year=' . now()->year)->assertNotFound();
    }

    public function test_incomplete_drafts_preserve_other_years_and_hide_used_years_only_for_accountability(): void
    {
        $this->draft();
        $this->post('/cwis/accountability-data', ['year' => now()->year - 1, 'country_code' => 'BD'])->assertSessionHasNoErrors();
        $this->assertSame(2, DB::table('cwis.accountability_submissions')->where('country_code', 'BD')->count());
        $this->get('/cwis/generator-data')->assertViewHas('inputYearsByOutcome', function ($years) {
            return !in_array(now()->year, $years['accountability']) && !in_array(now()->year - 1, $years['accountability']);
        });
        $this->post('/cwis/accountability-data', ['year' => now()->year, 'country_code' => 'BD'])->assertSessionHasErrors('year');
    }

    public function test_submit_requires_all_applicable_answers_and_rejects_unknown_keys(): void
    {
        $payload = $this->payload();
        unset($payload['answers']['AC-1a_1']);
        $this->post('/cwis/accountability-data', $payload)->assertSessionHasErrors('answers.AC-1a_1');
        $payload = $this->payload();
        $payload['answers']['AC-99'] = 'Yes';
        $this->post('/cwis/accountability-data', $payload)->assertSessionHasErrors('answers');
        $payload = $this->payload();
        $payload['answers']['AC-2_1'] = 'Invalid';
        $this->post('/cwis/accountability-data', $payload)->assertSessionHasErrors('answers.AC-2_1');
    }

    public function test_review_errors_and_approval_preserve_results_and_lock_the_source(): void
    {
        $id = $this->draft();
        $results = DB::table('cwis.data_cwis')->count();
        $review = '/cwis/accountability-data/' . $id . '/review';
        $this->get('/cwis/accountability-data/' . $id)->assertOk()->assertSee('Approve')->assertDontSee('Save as Draft');
        $this->get($review)->assertOk()->assertSee('Save and Approve');
        $this->from($review)->post('/cwis/accountability-data/' . $id . '/approve', ['year' => now()->year, 'country_code' => 'BD'])
            ->assertRedirect($review)->assertSessionHasErrors('answers.AC-1a_1');
        $payload = $this->payload();
        $this->put('/cwis/accountability-data/' . $id, $payload)->assertSessionHasNoErrors();
        $this->assertSame('submitted', DB::table('cwis.accountability_submissions')->where('id', $id)->value('status'));
        $this->post('/cwis/accountability-data/' . $id . '/approve', $payload)->assertSessionHasNoErrors()
            ->assertRedirect(route('cwis.generator-data.index', ['country_code' => 'BD']))->assertSessionHas('cwis_saved');
        $record = DB::table('cwis.accountability_submissions')->find($id);
        $this->assertSame('approved', $record->status);
        $this->assertNotNull($record->approved_at);
        $this->assertSame($results, DB::table('cwis.data_cwis')->count());
        $this->get($review)->assertForbidden();
        $this->put('/cwis/accountability-data/' . $id, $payload)->assertForbidden();
        $this->get('/cwis/accountability-data/' . $id)->assertOk()->assertDontSee('Save and Approve');
    }

    public function test_yes_requires_evidence_on_approval_and_existing_upload_is_reused(): void
    {
        $id = $this->draft();
        $payload = $this->payload();
        $payload['answers']['AC-1a_1'] = 'Yes';
        $review = '/cwis/accountability-data/' . $id . '/review';
        $this->from($review)->post('/cwis/accountability-data/' . $id . '/approve', $payload)
            ->assertRedirect($review)->assertSessionHasErrors('evidence.AC-1a_1');
        $payload['evidence']['AC-1a_1'] = UploadedFile::fake()->create('mandate.pdf', 5, 'application/pdf');
        $this->put('/cwis/accountability-data/' . $id, $payload)->assertSessionHasNoErrors();
        $answer = DB::table('cwis.accountability_answers')->where('submission_id', $id)->where('indicator_code', 'AC-1a.1')->first();
        Storage::disk('local')->assertExists($answer->document_path);
        $this->get('/cwis/accountability-data/' . $id . '/documents/' . $answer->id)->assertOk();
        unset($payload['evidence']);
        $this->post('/cwis/accountability-data/' . $id . '/approve', $payload)->assertSessionHasNoErrors();
    }

    public function test_country_year_and_permissions_are_enforced(): void
    {
        $id = $this->draft();
        $payload = $this->payload();
        $payload['year']--;
        $this->put('/cwis/accountability-data/' . $id, $payload)->assertSessionHasErrors('year');
        DB::table('public.site_settings')->where('name', 'active_country')->update(['value' => 'ALL']);
        $this->get('/cwis/accountability-data/' . $id)->assertNotFound();
        $payload = $this->payload();
        $this->post('/cwis/accountability-data', $payload)->assertSessionHasErrors('country_code');
        $this->get('/cwis/accountability-data/' . $id . '/review')->assertNotFound();
        $user = User::whereDoesntHave('roles')->first();
        if (!$user) {
            $user = auth()->user()->replicate();
            $user->username = 'rs_test_' . bin2hex(random_bytes(6));
            $user->email = bin2hex(random_bytes(6)) . '@accountability.test';
            $user->save();
            $user->unsetRelations();
        }
        $this->actingAs($user)->get('/cwis/accountability-data/create?year=' . now()->year)->assertForbidden();
    }

    public function test_listing_unions_outcomes_and_applies_year_and_outcome_filters(): void
    {
        $id = $this->draft();
        $this->getJson('/cwis/generator-data/data?country_code=BD&service_outcome=accountability&draw=1&start=0&length=10')
            ->assertOk()->assertJsonFragment(['service_outcome' => 'Accountability', 'status_label' => 'Draft']);
        $this->get('/cwis/generator-data?service_outcome=accountability')->assertOk();
    }
}
