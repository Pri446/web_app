<?php

namespace Tests\Feature\Cwis;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CwisEquityWorkflowTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $admin = User::whereHas('roles', function ($q) { $q->where('name','Super Admin'); })->firstOrFail();
        $this->actingAs($admin);
        Storage::fake('local');
        DB::table('public.site_settings')->where('name','active_country')->update(['value' => 'ALL']);
        DB::table('cwis.equity_submissions')->whereNull('deleted_at')->update(['deleted_at' => now()]);
        $this->seed(\Database\Seeders\Cwis\Equity\CwisFormFieldsSeeder::class);
        // ALL now includes Sustainability; combined generation requires that source too.
        DB::table('cwis.sustainability_submissions')->where('country_code', 'ALL')->where('reporting_year', now()->year)->delete();
        $sustainabilityId = DB::table('cwis.sustainability_submissions')->insertGetId([
            'country_code' => 'ALL', 'reporting_year' => now()->year, 'status' => 'approved', 'approved_at' => now(),
        ]);
        $amounts = ['submission_id' => $sustainabilityId];
        foreach (config('cwis_sustainability.financial_sections') as $section) {
            foreach ($section['fields'] as $name => $label) { $amounts[$name] = 100; }
        }
        DB::table('cwis.sustainability_financials')->insert($amounts);
        foreach (array_keys(config('cwis_sustainability.campaigns')) as $code) {
            DB::table('cwis.sustainability_campaigns')->insert(['submission_id' => $sustainabilityId, 'indicator_code' => $code, 'answer' => 'No']);
        }
        // Combined ALL generation also requires the independent Responsibility source.
        $responsibilityId = DB::table('cwis.responsibility_submissions')->insertGetId([
            'country_code' => 'ALL', 'reporting_year' => now()->year, 'status' => 'approved', 'approved_at' => now(),
        ]);
        foreach (config('cwis_responsibility.groups') as $questions) {
            foreach ($questions as $code => $label) {
                DB::table('cwis.responsibility_answers')->insert(['submission_id' => $responsibilityId, 'indicator_code' => $code, 'answer' => 'No']);
            }
        }
        // ALL generation needs every independent outcome's approved source.
        foreach (['accountability' => \App\Services\Cwis\CwisAccountabilityFormService::class,
            'rpm' => \App\Services\Cwis\CwisRpmFormService::class] as $outcome => $service) {
            $sourceId = DB::table('cwis.'.$outcome.'_submissions')->insertGetId([
                'country_code' => 'ALL', 'reporting_year' => now()->year, 'status' => 'approved', 'approved_at' => now(),
            ]);
            foreach (app($service)->questions('ALL') as $code => $label) {
                DB::table('cwis.'.$outcome.'_answers')->insert(['submission_id' => $sourceId, 'indicator_code' => $code, 'answer' => 'No']);
            }
        }
        $safetyId = DB::table('cwis.safety_submissions')->insertGetId([
            'country_code' => 'ALL', 'reporting_year' => now()->year, 'status' => 'approved', 'approved_at' => now(), 'public_spaces_complete' => true,
        ]);
        foreach (config('cwis_safety_d22.policies') as $code) {
            DB::table('cwis.safety_answers')->insert(['submission_id' => $safetyId, 'indicator_code' => $code, 'answer' => 'No']);
        }
        foreach (app(\App\Services\Cwis\CwisSafetyFormService::class)->toilets('ALL') as $toilet) {
            $path = 'cwis/safety-documents/equity-regression-'.$toilet->id.'.pdf';
            Storage::disk('local')->put($path, '%PDF-1.4');
            DB::table('cwis.safety_waiting_times')->insert(['submission_id' => $safetyId, 'toilet_id' => $toilet->id,
                'waiting_minutes' => 12, 'number_users' => 4, 'document_path' => $path]);
        }
    }

    private function source(): int
    {
        $policies = [];
        foreach (app(\App\Services\Cwis\CwisFormService::class)->definitions() as $field) {
            if ($field->kind === 'policy' && !$field->parent_code) { $policies[$field->input_name] = 'No'; }
        }
        $this->post('/cwis/generator-data', [
            'country_code' => 'ALL', 'year' => now()->year, 'save_mode' => 'submitted',
            'inputs' => ['total_subsidies_amount_nss' => 60000, 'total_subsidies_amount_ss' => 30000,
                'women_employee_count' => 30, 'total_employee_count' => 100,
                'women_leadership_count' => 5, 'total_leadership_count' => 20,
                'average_salary_of_women_in_sanitation' => 40000, 'average_salary_of_men_in_sanitation' => 50000],
            'policies' => $policies,
        ])->assertSessionHasNoErrors();
        return DB::table('cwis.equity_submissions')->whereNull('deleted_at')->max('id');
    }

    public function test_submitted_data_cannot_generate_blank_official_results(): void
    {
        $id = $this->source();
        $count = DB::table('cwis.data_cwis')->count();
        $this->getJson('/cwis/cwis-df-mne/newsurvey?year=' . now()->year)
            ->assertStatus(422)->assertJsonValidationErrors('equity_approval');
        $this->assertSame($count, DB::table('cwis.data_cwis')->count());
        $this->post('/cwis/generator-data/' . $id . '/calculate')->assertSessionHasErrors('submission');
        $this->assertSame('submitted', DB::table('cwis.equity_submissions')->where('id',$id)->value('status'));
    }

    public function test_approval_then_rebuild_runs_individual_formulas_and_keeps_other_years(): void
    {
        $id = $this->source();
        $this->get('/cwis/generator-data/' . $id . '?country_code=ALL')->assertOk()->assertSee('/' . $id . '/review',false);
        $this->post('/cwis/generator-data/' . $id . '/approve')->assertSessionHasNoErrors();
        $record = DB::table('cwis.equity_submissions')->where('id',$id)->first();
        $this->assertSame('approved',$record->status);
        $this->assertNotNull($record->approved_at);
        $this->assertEquals(auth()->id(),$record->approved_by);
        $this->get('/cwis/generator-data/' . $id . '?country_code=ALL')->assertOk()
            ->assertSee('id="cwis-rebuild-loader"', false)
            ->assertSee('Rebuilding CWIS results may take some time. Please wait.')
            ->assertSee("button.disabled = true", false);
        $otherYears = DB::table('cwis.data_cwis')->where('year','!=',now()->year)->orderBy('id')->get()->toJson();
        $this->post('/cwis/generator-data/' . $id . '/calculate')->assertSessionHasNoErrors()
            ->assertRedirect(route('cwis.generator-data.index',['country_code' => 'ALL']))
            ->assertSessionHas('cwis_saved');
        $values = DB::table('cwis.data_cwis')->where('year',now()->year)->pluck('data_value','indicator_code');
        $this->assertEquals(2, $values['EQ-3']);
        $this->assertEquals(30, $values['EQ-4']);
        $this->assertEquals(25, $values['EQ-4a']);
        $this->assertEquals(0.2, $values['EQ-5']);
        $this->get('/cwis/cwis/getall?year=' . now()->year)->assertOk()
            ->assertViewHas('data', function ($dashboardValues) use ($values) {
                return $dashboardValues instanceof \Illuminate\Support\Collection
                    && (float) $dashboardValues->get('EQ-3') === (float) $values['EQ-3']
                    && (float) $dashboardValues->get('EQ-4') === (float) $values['EQ-4'];
            })->assertSee('data-indicator-code="EQ-3"', false)->assertSee('2.000');
        $this->assertEquals($id, DB::table('cwis.generation_runs')->where('year',now()->year)->value('equity_submission_id'));
        $this->assertSame($otherYears,DB::table('cwis.data_cwis')->where('year','!=',now()->year)->orderBy('id')->get()->toJson());
    }

    public function test_sanitation_role_does_not_gain_approval_permission(): void
    {
        $id = $this->source();
        $this->actingAs(User::whereHas('roles',function ($q) { $q->where('name','Municipality - Sanitation Department'); })->firstOrFail());
        $this->get('/cwis/generator-data/' . $id . '?country_code=ALL')->assertOk()->assertDontSee('/' . $id . '/approve',false);
        $this->post('/cwis/generator-data/' . $id . '/approve')->assertForbidden();
        $this->get('/cwis/generator-data/' . $id . '/review')->assertForbidden();
        $this->assertSame('submitted',DB::table('cwis.equity_submissions')->where('id',$id)->value('status'));
    }

    public function test_incomplete_saved_source_cannot_be_approved(): void
    {
        $id = $this->source();
        DB::table('cwis.equity_subsidies')->where('submission_id',$id)->update(['total_subsidies_amount_nss' => null]);
        $this->from('/cwis/generator-data')->post('/cwis/generator-data/' . $id . '/approve')
            ->assertSessionHasErrors('total_subsidies_amount_nss')
            ->assertRedirect(route('cwis.generator-data.show',['submission' => $id,'country_code' => 'ALL']));
        $this->assertSame('submitted',DB::table('cwis.equity_submissions')->where('id',$id)->value('status'));
    }

    public function test_failed_rebuild_restores_existing_results(): void
    {
        $id = $this->source();
        $this->post('/cwis/generator-data/' . $id . '/approve')->assertSessionHasNoErrors();
        $before = DB::table('cwis.data_cwis')->where('year',now()->year)->orderBy('id')->get()->toJson();
        DB::unprepared('CREATE OR REPLACE FUNCTION public.generate_cwis_with_equity(_year integer)
            RETURNS void LANGUAGE plpgsql AS $$ BEGIN RAISE EXCEPTION \'Transactional failure test\'; END; $$');
        $this->post('/cwis/generator-data/' . $id . '/calculate')
            ->assertRedirect(route('cwis.generator-data.show',['submission' => $id,'country_code' => 'ALL']))
            ->assertSessionHasErrors('calculation');
        $this->assertSame($before,DB::table('cwis.data_cwis')->where('year',now()->year)->orderBy('id')->get()->toJson());
    }

    public function test_yes_without_evidence_cannot_be_approved(): void
    {
        $id = $this->source();
        DB::table('cwis.sanitation_worker_policy')->where('submission_id',$id)->update([
            'training_certification_required' => true, 'training_requirement_document_id' => null,
        ]);
        $this->from('/cwis/generator-data')->post('/cwis/generator-data/' . $id . '/approve')
            ->assertSessionHasErrors()
            ->assertRedirect(route('cwis.generator-data.show',['submission' => $id,'country_code' => 'ALL']));
        $this->get('/cwis/generator-data/' . $id . '?country_code=ALL')->assertOk()
            ->assertSee('EQ-6.1 -')
            ->assertSee('class="table-danger"', false);
        $this->assertSame('submitted',DB::table('cwis.equity_submissions')->where('id',$id)->value('status'));
    }

    public function test_valid_draft_can_be_approved_from_the_listing_with_audit_fields(): void
    {
        $id = $this->source();
        DB::table('cwis.equity_submissions')->where('id',$id)->update([
            'status' => 'draft', 'submitted_by' => null, 'submitted_at' => null,
        ]);
        $response = $this->getJson('/cwis/generator-data/data?country_code=ALL');
        $response->assertOk()->assertJsonPath('data.0.status_label','Draft');
        $this->assertStringContainsString('/' . $id . '/review',$response->json('data.0.action'));
        $this->assertStringNotContainsString('/' . $id . '/edit',$response->json('data.0.action'));
        $this->post('/cwis/generator-data/' . $id . '/approve')->assertSessionHasNoErrors();
        $record = DB::table('cwis.equity_submissions')->where('id',$id)->first();
        $this->assertSame('approved',$record->status);
        $this->assertEquals(auth()->id(),$record->approved_by);
        $this->assertEquals(auth()->id(),$record->submitted_by);
        $this->assertNotNull($record->submitted_at);
        $this->assertNotNull($record->approved_at);
    }

    private function reviewData(): array
    {
        $policies = [];
        foreach (app(\App\Services\Cwis\CwisFormService::class)->definitions() as $field) {
            if ($field->kind === 'policy' && !$field->parent_code) { $policies[$field->input_name] = 'No'; }
        }
        return ['review_form' => 1, 'country_code' => 'ALL', 'year' => now()->year, 'save_mode' => 'submitted',
            'inputs' => ['total_subsidies_amount_nss' => 90000,'total_subsidies_amount_ss' => 30000,
                'women_employee_count' => 30,'total_employee_count' => 100,'women_leadership_count' => 5,
                'total_leadership_count' => 20,'average_salary_of_women_in_sanitation' => 40000,
                'average_salary_of_men_in_sanitation' => 50000], 'policies' => $policies];
    }

    public function test_new_year_approval_returns_to_listing_without_generating_results(): void
    {
        DB::table('cwis.data_cwis')->where('year', now()->year)->delete();
        $id = $this->source();
        $count = DB::table('cwis.data_cwis')->count();
        $this->post('/cwis/generator-data/' . $id . '/approve', $this->reviewData())
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('cwis.generator-data.index', ['country_code' => 'ALL']))
            ->assertSessionHas('cwis_saved');
        $this->assertSame($count, DB::table('cwis.data_cwis')->count());
        $this->assertDatabaseHas('cwis.equity_submissions', ['id' => $id, 'status' => 'approved']);
    }

    public function test_existing_results_approval_opens_details_with_explicit_rebuild_button(): void
    {
        $id = $this->source();
        $template = (array) DB::table('cwis.data_cwis')->first();
        unset($template['id']);
        $template['year'] = now()->year;
        DB::table('cwis.data_cwis')->where('year', now()->year)->delete();
        DB::table('cwis.data_cwis')->insert($template);
        $count = DB::table('cwis.data_cwis')->count();
        $url = route('cwis.generator-data.show', ['submission' => $id, 'country_code' => 'ALL']);
        $this->post('/cwis/generator-data/' . $id . '/approve')->assertSessionHasNoErrors()->assertRedirect($url);
        $this->get($url)->assertOk()->assertSee('Rebuild CWIS Results')
            ->assertSee('id="cwis-rebuild-form"', false)->assertSee('data-confirm=', false);
        $this->assertSame($count, DB::table('cwis.data_cwis')->count());
    }

    public function test_review_form_is_editable_and_requires_all_applicable_answers(): void
    {
        $id = $this->source();
        $response = $this->get('/cwis/generator-data/' . $id . '/review');
        $response->assertOk()->assertSee('Save and Approve')->assertSee('value="60000.00"',false)
            ->assertSee('name="review_form" value="1"',false)->assertDontSee('value="PUT"',false);
        $payload = $this->reviewData();
        $payload['policies'] = [];
        $this->postJson('/cwis/generator-data/' . $id . '/approve',$payload)->assertStatus(422)
            ->assertJsonValidationErrors('policies.EQ_6_1');
        $this->assertSame('submitted',DB::table('cwis.equity_submissions')->where('id',$id)->value('status'));
    }

    public function test_missing_review_evidence_rolls_back_edits_and_can_be_corrected_in_review(): void
    {
        $id = $this->source();
        $payload = $this->reviewData();
        $payload['policies']['EQ_6_1'] = 'Yes';
        $payload['policies']['EQ_6_1a'] = 'No';
        $payload['policies']['EQ_6_1b'] = 'No';
        $this->postJson('/cwis/generator-data/' . $id . '/approve',$payload)->assertStatus(422)
            ->assertJsonValidationErrors('evidence.EQ_6_1');
        $this->assertEquals(60000,DB::table('cwis.equity_subsidies')->where('submission_id',$id)->value('total_subsidies_amount_nss'));
        $this->assertSame('submitted',DB::table('cwis.equity_submissions')->where('id',$id)->value('status'));
        Storage::fake('public');
        $payload['evidence'] = ['EQ_6_1' => \Illuminate\Http\UploadedFile::fake()->createWithContent('review.pdf', "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\n%%EOF")];
        $this->post('/cwis/generator-data/' . $id . '/approve',$payload)->assertSessionHasNoErrors()
            ->assertSessionHas('cwis_saved');
        $this->assertSame('approved',DB::table('cwis.equity_submissions')->where('id',$id)->value('status'));
        $this->assertEquals(90000,DB::table('cwis.equity_subsidies')->where('submission_id',$id)->value('total_subsidies_amount_nss'));
        $this->get('/cwis/generator-data/' . $id . '/review')->assertForbidden();
    }
}
