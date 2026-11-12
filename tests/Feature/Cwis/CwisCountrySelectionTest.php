<?php

namespace Tests\Feature\Cwis;

use App\Http\Controllers\Cwis\CwisGeneratorDataController;
use App\Services\Cwis\CwisIndicatorService;
use Database\Seeders\CwisIndicatorCountryExampleSeeder;
use Database\Seeders\Cwis\Equity\CwisFormFieldsSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use App\Models\User;

class CwisCountrySelectionTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        // Isolate submission tests without deleting rows referenced by generation history.
        DB::table('cwis.equity_submissions')->whereNull('deleted_at')->update(['deleted_at' => now()]);
        DB::table('cwis.indicator_country')->delete();
        $this->seed(CwisIndicatorCountryExampleSeeder::class);
        $this->seed(CwisFormFieldsSeeder::class);
        $this->withoutMiddleware();
    }

    public function test_service_selects_equity_and_safety_for_each_country(): void
    {
        $service = app(CwisIndicatorService::class);
        $nepal = $service->forCountry('NP')->pluck('indicator_code');
        $bangladesh = $service->forCountry('BD')->pluck('indicator_code');
        $this->assertTrue($nepal->contains('EQ-4'));
        $this->assertFalse($nepal->contains('EQ-5'));
        $this->assertTrue($bangladesh->contains('EQ-5'));
        $this->assertFalse($bangladesh->contains('EQ-4'));
        $this->assertTrue($nepal->contains('SF-1d'));
        $this->assertFalse($bangladesh->contains('SF-1d'));
        $this->assertCount(DB::table('cwis.data_source')->count(), $service->forCountry('ALL'));
    }

    public function test_listing_reads_saved_submissions_and_scopes_country_year_and_status(): void
    {
        $this->actingAs(User::query()->firstOrFail());
        DB::table('public.site_settings')->where('name','active_country')->update(['value' => 'NP']);
        $id = DB::table('cwis.equity_submissions')->insertGetId([
            'reporting_year' => now()->year, 'country_code' => 'NP', 'status' => 'draft',
        ]);
        DB::table('cwis.equity_submissions')->insert([
            ['reporting_year' => now()->year, 'country_code' => 'BD', 'status' => 'submitted'],
            ['reporting_year' => now()->year - 1, 'country_code' => 'NP', 'status' => 'submitted'],
        ]);
        $response = $this->getJson('/cwis/generator-data/data?country_code=NP&year=' . now()->year);
        $response->assertOk()->assertJsonPath('recordsTotal', 1)->assertJsonPath('data.0.id', $id)
            ->assertJsonPath('data.0.status', 'draft')->assertJsonPath('data.0.service_outcome', 'Equity');
        $this->assertStringContainsString('/cwis/generator-data/' . $id, $response->json('data.0.action'));
        $this->getJson('/cwis/generator-data/data?country_code=NP&service_outcome=safety')->assertJsonPath('recordsTotal', 0);
        $this->get('/cwis/generator-data/' . $id . '?country_code=NP')->assertOk()->assertSee('Women Employees');
        $this->get('/cwis/generator-data/' . $id . '?country_code=BD')->assertNotFound();
        DB::table('cwis.equity_submissions')->where('id', $id)->update(['deleted_at' => now()]);
        $this->getJson('/cwis/generator-data/data?country_code=NP&year=' . now()->year)->assertJsonPath('recordsTotal', 0);
    }

    public function test_generator_exposes_new_equity_result_rows_and_navigation(): void
    {
        $this->withMiddleware();
        $this->actingAs(User::query()->firstOrFail());
        DB::table('public.site_settings')->where('name', 'active_country')->update(['value' => 'ALL']);
        $this->get('/cwis/cwis/cwis-df-mne')->assertOk()
            ->assertDontSee('>Indicator Data</a>', false)->assertSee('Equity of Subsidies')
            ->assertSee('EQ-6.5')->assertSee('readonly', false);
        $this->get('/cwis/generator-data')->assertOk()->assertSee('Updated At')->assertSee('Status');
    }

    public function test_country_without_input_fields_does_not_offer_a_broken_form(): void
    {
        $this->withMiddleware();
        $this->actingAs(User::query()->firstOrFail());
        DB::table('public.site_settings')->where('name','active_country')->update(['value' => 'NP']);
        DB::table('cwis.indicator_country')->where('country_code', 'NP')->delete();
        $sourceId = DB::table('cwis.data_source')->where('indicator_code', 'EQ-1')->value('id');
        DB::table('cwis.indicator_country')->insert(['data_source_id' => $sourceId, 'country_code' => 'NP']);

        $this->get('/cwis/generator-data?country_code=NP')->assertOk()
            ->assertViewHas('inputOutcomes', [])
            ->assertSee('No indicator input form is available');
        $this->get('/cwis/generator-data/create?country_code=NP&year=' . now()->year . '&service_outcome=equity')
            ->assertRedirect(action('Cwis\\CwisGeneratorDataController@index', [
                'country_code' => 'NP', 'year' => now()->year,
            ]))->assertSessionHas('cwis_input_notice');
        DB::table('public.site_settings')->where('name','active_country')->update(['value' => 'BD']);
        $this->get('/cwis/generator-data?country_code=BD')->assertOk()
            ->assertViewHas('inputOutcomes', ['equity' => 'Equity']);
        DB::table('public.site_settings')->where('name','active_country')->update(['value' => 'ALL']);
        $this->get('/cwis/generator-data?country_code=ALL')->assertOk()
            ->assertViewHas('inputOutcomes', ['equity' => 'Equity']);
    }

    public function test_database_metadata_controls_rendering_validation_and_storage(): void
    {
        DB::table('cwis.form_fields')->where('input_name', 'total_subsidies_amount_nss')->update([
            'label' => 'Custom NSS amount', 'maximum' => 500,
        ]);
        $view = app(CwisGeneratorDataController::class)->create(Request::create('/', 'GET', ['country_code' => 'ALL']));
        $this->assertSame('Custom NSS amount', $view->getData()['sections'][0]['fields'][0][3]);
        $payload = ['country_code' => 'ALL', 'year' => now()->year, 'save_mode' => 'draft',
            'inputs' => ['total_subsidies_amount_nss' => 501]];
        $this->post('/cwis/generator-data', $payload)->assertSessionHasErrors('inputs.total_subsidies_amount_nss');
        session()->forget('errors');
        $payload['inputs']['total_subsidies_amount_nss'] = 499;
        $this->post('/cwis/generator-data', $payload)->assertSessionHasNoErrors();
        $submissionId = DB::table('cwis.equity_submissions')->max('id');
        $this->assertEquals(499, DB::table('cwis.equity_subsidies')->where('submission_id', $submissionId)->value('total_subsidies_amount_nss'));
        DB::table('cwis.form_fields')->where('input_name', 'total_subsidies_amount_nss')->update(['enabled' => false]);
        $this->post('/cwis/generator-data', $payload)->assertSessionHasErrors('inputs');
    }

    public function test_seeding_preserves_customized_metadata(): void
    {
        $count = DB::table('cwis.form_fields')->count();
        DB::table('cwis.form_fields')->where('input_name', 'total_subsidies_amount_nss')->update(['label' => 'Local label']);
        $this->seed(CwisFormFieldsSeeder::class);
        $this->assertSame($count, DB::table('cwis.form_fields')->count());
        $this->assertSame('Local label', DB::table('cwis.form_fields')->where('input_name', 'total_subsidies_amount_nss')->value('label'));
    }

    public function test_untrusted_fields_and_audit_storage_mappings_are_rejected(): void
    {
        $this->post('/cwis/generator-data', [
            'country_code' => 'ALL', 'year' => now()->year, 'save_mode' => 'draft',
            'inputs' => ['unexpected_column' => 10],
        ])->assertSessionHasErrors('inputs');
        DB::table('cwis.form_fields')->where('input_name', 'total_subsidies_amount_nss')->update(['storage_column' => 'created_by']);
        $this->expectException(\LogicException::class);
        app(\App\Services\Cwis\CwisFormService::class)->definitions();
    }

    public function test_settings_and_country_filtered_form_render(): void
    {
        $this->withMiddleware();
        $user = User::query()->firstOrFail();
        $this->actingAs($user);
        $this->get('/fsm/cwis-setting')->assertOk()->assertSee('name="active_country"', false)
            ->assertSee('value="NP"', false)->assertSee('value="BD"', false);
        $this->get('/cwis/generator-data/create?country_code=BD')->assertOk()
            ->assertSee('Bangladesh')->assertSee('inputs[average_salary_of_women_in_sanitation]', false)
            ->assertDontSee('inputs[women_employee_count]', false);
        $this->get('/cwis/generator-data/create?country_code=NP')->assertOk()
            ->assertSee('Nepal')->assertSee('inputs[women_employee_count]', false)
            ->assertDontSee('inputs[average_salary_of_women_in_sanitation]', false);
    }

    public function test_settings_save_drives_selection_and_rejects_unknown_country(): void
    {
        $settings = DB::table('public.site_settings')->where('category', 'cwis_setting')
            ->pluck('value', 'name')->all();
        $settings['active_country'] = 'BD';
        $this->post('/fsm/cwis-setting', $settings)->assertSessionHasNoErrors()
            ->assertRedirect('/fsm/cwis-setting');
        $this->assertSame('BD', app(CwisIndicatorService::class)->activeCountry());
        $settings['active_country'] = 'UNKNOWN';
        $this->post('/fsm/cwis-setting', $settings)->assertSessionHasErrors('active_country');
        $this->assertSame('BD', app(CwisIndicatorService::class)->activeCountry());
    }

    public function test_form_definitions_follow_country_selection(): void
    {
        $controller = app(CwisGeneratorDataController::class);
        foreach (['NP', 'BD', 'ALL'] as $country) {
            $view = $controller->create(Request::create('/cwis/generator-data/create', 'GET', ['country_code' => $country]));
            $data = $view->getData();
            $names = collect($data['sections'])->pluck('fields')->flatten(1)->pluck(1)->all();
            $this->assertSame($country, $data['country']);
            $this->assertSame($country !== 'BD', in_array('women_employee_count', $names, true));
            $this->assertSame($country !== 'NP', in_array('average_salary_of_women_in_sanitation', $names, true));
            $policyCodes = array_column($data['policies'], 0);
            $this->assertSame($country !== 'BD', in_array('EQ-6.4', $policyCodes, true));
            $this->assertSame($country !== 'NP', in_array('EQ-6.5', $policyCodes, true));
        }
    }

    public function test_draft_keeps_its_country_after_active_setting_changes(): void
    {
        Storage::fake('public');
        DB::table('public.site_settings')->where('name', 'active_country')->update(['value' => 'BD']);
        $this->post('/cwis/generator-data', [
            'country_code' => 'NP', 'year' => now()->year, 'save_mode' => 'draft',
            'inputs' => ['total_subsidies_amount_nss' => 100],
            'policies' => ['EQ_6_1' => 'Yes'],
            'evidence' => ['EQ_6_1' => UploadedFile::fake()->create('evidence.pdf', 1)],
        ])->assertSessionHasNoErrors()->assertSessionHas('cwis_saved');
        $submission = DB::table('cwis.equity_submissions')->orderByDesc('id')->first();
        $this->assertSame('NP', $submission->country_code);
        $this->assertSame('draft', $submission->status);
        $policy = DB::table('cwis.sanitation_worker_policy')->where('submission_id', $submission->id)->first();
        $document = DB::table('cwis.equity_documents')->where('id', $policy->training_requirement_document_id)->first();
        Storage::disk('public')->assertExists($document->path);
    }

    public function test_excluded_inputs_and_inapplicable_children_are_rejected(): void
    {
        $count = DB::table('cwis.equity_submissions')->count();
        $this->post('/cwis/generator-data', [
            'country_code' => 'BD', 'year' => now()->year, 'save_mode' => 'draft',
            'inputs' => ['women_employee_count' => 10],
        ])->assertSessionHasErrors('inputs.women_employee_count');
        $this->post('/cwis/generator-data', [
            'country_code' => 'NP', 'year' => now()->year, 'save_mode' => 'draft',
            'policies' => ['EQ_6_1' => 'No', 'EQ_6_1a' => 'Yes'],
        ])->assertSessionHasErrors('policies.EQ_6_1a');
        $this->assertSame($count, DB::table('cwis.equity_submissions')->count());
    }

    public function test_submission_requires_only_its_applicable_numeric_fields(): void
    {
        $this->post('/cwis/generator-data', [
            'country_code' => 'BD', 'year' => now()->year, 'save_mode' => 'submitted',
            'inputs' => [
                'total_subsidies_amount_nss' => 100, 'total_subsidies_amount_ss' => 50,
                'average_salary_of_women_in_sanitation' => 200, 'average_salary_of_men_in_sanitation' => 250,
            ],
        ])->assertSessionHasNoErrors()->assertSessionHas('cwis_saved');
        $submission = DB::table('cwis.equity_submissions')->orderByDesc('id')->first();
        $this->assertSame('BD', $submission->country_code);
        $this->assertSame('submitted', $submission->status);
        $this->post('/cwis/generator-data', [
            'country_code' => 'NP', 'year' => now()->year, 'save_mode' => 'submitted',
            'inputs' => ['total_subsidies_amount_nss' => 100, 'total_subsidies_amount_ss' => 50],
        ])->assertSessionHasErrors('inputs.women_employee_count');
    }
}
