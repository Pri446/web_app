<?php

namespace Tests\Feature\Cwis;

use App\Models\User;
use App\Services\Cwis\CwisResponsibilityFormService;
use Database\Seeders\CwisResponsibilityDataSourceSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CwisResponsibilityWorkflowTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::whereHas('roles', function ($query) { $query->where('name', 'Super Admin'); })->firstOrFail());
        DB::table('public.site_settings')->where('name', 'active_country')->update(['value' => 'BD']);
        Storage::fake('local');
    }

    private function payload(): array
    {
        $answers = [];
        foreach (app(CwisResponsibilityFormService::class)->questions('BD') as $code => $label) {
            $answers[str_replace('.', '_', $code)] = 'No';
        }
        return ['year' => now()->year, 'country_code' => 'BD', 'save_mode' => 'submitted', 'answers' => $answers];
    }

    private function draft(): int
    {
        $this->post('/cwis/responsibility-data', ['year' => now()->year, 'country_code' => 'BD', 'save_mode' => 'draft'])
            ->assertSessionHasNoErrors()->assertSessionHas('cwis_saved');
        return (int) DB::table('cwis.responsibility_submissions')->where('country_code', 'BD')->where('reporting_year', now()->year)->value('id');
    }

    public function test_picker_form_country_mapping_and_seeder_idempotency(): void
    {
        $this->seed(CwisResponsibilityDataSourceSeeder::class);
        $this->assertCount(21, app(CwisResponsibilityFormService::class)->questions('BD'));
        $this->assertCount(21, app(CwisResponsibilityFormService::class)->questions('ALL'));
        $this->assertCount(0, app(CwisResponsibilityFormService::class)->questions('NP'));
        $this->assertSame(21, DB::table('cwis.data_source')->where('outcome', 'responsibility')->count());
        $this->get('/cwis/generator-data')->assertOk()->assertSee('id="add-outcome-responsibility"', false)
            ->assertSee('id="add-outcome-equity"', false)->assertSee('id="add-outcome-sustainability"', false);
        $this->get('/cwis/generator-data/create?service_outcome=responsibility&country_code=BD&year=' . now()->year)
            ->assertRedirect(route('cwis.responsibility.create', ['year' => now()->year, 'country_code' => 'BD']));
        $this->get('/cwis/responsibility-data/create?year=' . now()->year)->assertOk()
            ->assertSee('Save as Draft')->assertSee('Submit for Approval')->assertSee('RS-1b.8')
            ->assertDontSee('name="currency_code"', false)->assertSee('novalidate', false);
        DB::table('public.site_settings')->where('name', 'active_country')->update(['value' => 'NP']);
        $this->get('/cwis/generator-data')->assertOk()->assertDontSee('id="add-outcome-responsibility"', false);
        $this->get('/cwis/responsibility-data/create?year=' . now()->year)->assertNotFound();
    }

    public function test_incomplete_drafts_preserve_other_years_and_hide_used_years_only_for_responsibility(): void
    {
        $this->draft();
        $this->post('/cwis/responsibility-data', ['year' => now()->year - 1, 'country_code' => 'BD'])->assertSessionHasNoErrors();
        $this->assertSame(2, DB::table('cwis.responsibility_submissions')->where('country_code', 'BD')->count());
        $this->get('/cwis/generator-data')->assertViewHas('inputYearsByOutcome', function ($years) {
            return !in_array(now()->year, $years['responsibility']) && !in_array(now()->year - 1, $years['responsibility']);
        });
        $this->post('/cwis/responsibility-data', ['year' => now()->year, 'country_code' => 'BD'])->assertSessionHasErrors('year');
    }

    public function test_submit_requires_all_applicable_answers_and_rejects_unknown_keys(): void
    {
        $payload = $this->payload();
        unset($payload['answers']['RS-1a_1']);
        $this->post('/cwis/responsibility-data', $payload)->assertSessionHasErrors('answers.RS-1a_1');
        $payload = $this->payload();
        $payload['answers']['RS-99'] = 'Yes';
        $this->post('/cwis/responsibility-data', $payload)->assertSessionHasErrors('answers');
        $payload = $this->payload();
        $payload['answers']['RS-2'] = 'Invalid';
        $this->post('/cwis/responsibility-data', $payload)->assertSessionHasErrors('answers.RS-2');
    }

    public function test_review_errors_and_approval_preserve_results_and_lock_the_source(): void
    {
        $id = $this->draft();
        $results = DB::table('cwis.data_cwis')->count();
        $review = '/cwis/responsibility-data/' . $id . '/review';
        $this->get('/cwis/responsibility-data/' . $id)->assertOk()->assertSee('Approve')->assertDontSee('Save as Draft');
        $this->get($review)->assertOk()->assertSee('Save and Approve');
        $this->from($review)->post('/cwis/responsibility-data/' . $id . '/approve', ['year' => now()->year, 'country_code' => 'BD'])
            ->assertRedirect($review)->assertSessionHasErrors('answers.RS-1a_1');
        $payload = $this->payload();
        $this->put('/cwis/responsibility-data/' . $id, $payload)->assertSessionHasNoErrors();
        $this->assertSame('submitted', DB::table('cwis.responsibility_submissions')->where('id', $id)->value('status'));
        $this->post('/cwis/responsibility-data/' . $id . '/approve', $payload)->assertSessionHasNoErrors()
            ->assertRedirect(route('cwis.generator-data.index', ['country_code' => 'BD']))->assertSessionHas('cwis_saved');
        $record = DB::table('cwis.responsibility_submissions')->find($id);
        $this->assertSame('approved', $record->status);
        $this->assertNotNull($record->approved_at);
        $this->assertSame($results, DB::table('cwis.data_cwis')->count());
        $this->get($review)->assertForbidden();
        $this->put('/cwis/responsibility-data/' . $id, $payload)->assertForbidden();
        $this->get('/cwis/responsibility-data/' . $id)->assertOk()->assertDontSee('Save and Approve');
    }

    public function test_yes_requires_evidence_on_approval_and_existing_upload_is_reused(): void
    {
        $id = $this->draft();
        $payload = $this->payload();
        $payload['answers']['RS-1a_1'] = 'Yes';
        $review = '/cwis/responsibility-data/' . $id . '/review';
        $this->from($review)->post('/cwis/responsibility-data/' . $id . '/approve', $payload)
            ->assertRedirect($review)->assertSessionHasErrors('evidence.RS-1a_1');
        $payload['evidence']['RS-1a_1'] = UploadedFile::fake()->create('mandate.pdf', 5, 'application/pdf');
        $this->put('/cwis/responsibility-data/' . $id, $payload)->assertSessionHasNoErrors();
        $answer = DB::table('cwis.responsibility_answers')->where('submission_id', $id)->where('indicator_code', 'RS-1a.1')->first();
        Storage::disk('local')->assertExists($answer->document_path);
        $this->get('/cwis/responsibility-data/' . $id . '/documents/' . $answer->id)->assertOk();
        unset($payload['evidence']);
        $this->post('/cwis/responsibility-data/' . $id . '/approve', $payload)->assertSessionHasNoErrors();
    }

    public function test_country_year_and_permissions_are_enforced(): void
    {
        $id = $this->draft();
        $payload = $this->payload();
        $payload['year']--;
        $this->put('/cwis/responsibility-data/' . $id, $payload)->assertSessionHasErrors('year');
        DB::table('public.site_settings')->where('name', 'active_country')->update(['value' => 'ALL']);
        $this->get('/cwis/responsibility-data/' . $id)->assertNotFound();
        $payload = $this->payload();
        $this->post('/cwis/responsibility-data', $payload)->assertSessionHasErrors('country_code');
        $this->get('/cwis/responsibility-data/' . $id . '/review')->assertNotFound();
        $user = User::whereDoesntHave('roles')->first();
        if (!$user) {
            $user = auth()->user()->replicate();
            $user->username = 'rs_test_' . bin2hex(random_bytes(6));
            $user->email = bin2hex(random_bytes(6)) . '@responsibility.test';
            $user->save();
            $user->unsetRelations();
        }
        $this->actingAs($user)->get('/cwis/responsibility-data/create?year=' . now()->year)->assertForbidden();
    }

    public function test_listing_unions_outcomes_and_applies_year_and_outcome_filters(): void
    {
        $id = $this->draft();
        $this->getJson('/cwis/generator-data/data?country_code=BD&service_outcome=responsibility&draw=1&start=0&length=10')
            ->assertOk()->assertJsonFragment(['service_outcome' => 'Responsibility', 'status_label' => 'Draft']);
        $this->get('/cwis/generator-data?service_outcome=responsibility')->assertOk();
    }
}
