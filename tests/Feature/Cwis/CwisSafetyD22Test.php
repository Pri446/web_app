<?php

namespace Tests\Feature\Cwis;

use App\Models\User;
use App\Services\Cwis\CwisSafetyFormService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CwisSafetyD22Test extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        if (!app(CwisSafetyFormService::class)->ready()) {
            require_once database_path('migrations/2026_11_07_000001_create_cwis_safety_d22_sources.php');
            require_once database_path('migrations/2026_11_07_000002_install_cwis_safety_d22_functions.php');
            (new \CreateCwisSafetyD22Sources())->up();
            (new \InstallCwisSafetyD22Functions())->up();
        }
        $this->actingAs(User::whereHas('roles', function ($q) { $q->where('name', 'Super Admin'); })->firstOrFail());
        DB::table('public.site_settings')->where('name', 'active_country')->update(['value' => 'BD']);
        Storage::fake('local');
    }

    private function pdf(string $name = 'evidence.pdf')
    {
        return UploadedFile::fake()->createWithContent($name, "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\n%%EOF");
    }

    private function payload(string $mode = 'submitted'): array
    {
        $payload = ['country_code' => 'BD', 'year' => now()->year - 4, 'save_mode' => $mode, 'public_spaces_complete' => 1];
        foreach (app(CwisSafetyFormService::class)->questions('BD') as $code => $label) {
            if (isset(config('cwis_safety_d22.parents')[$code])) { continue; }
            $payload['answers'][$code] = 'No';
        }
        foreach (app(CwisSafetyFormService::class)->toilets('BD') as $toilet) {
            $payload['waiting_times'][$toilet->id] = ['minutes' => 12, 'users' => 4];
            $payload['waiting_evidence'][$toilet->id] = $this->pdf();
        }
        return $payload;
    }

    private function legacyDefinitions(): array
    {
        return DB::select("SELECT p.oid::regprocedure::text AS signature,pg_get_functiondef(p.oid) AS definition FROM pg_proc p JOIN pg_namespace n ON n.oid=p.pronamespace
            WHERE n.nspname='public' AND p.proname ~ '^update_data_into_cwis_table_sf_(2a|2b|2c|3|3b|3c|3e|4a|4b|4d|5|6|7|9)_newsan$' ORDER BY 1");
    }

    public function test_catalogue_functions_and_legacy_preservation(): void
    {
        $legacy = $this->legacyDefinitions();
        $this->assertCount(14, $legacy);
        DB::unprepared(file_get_contents(database_path('sql/cwis_safety_d22.sql')));
        $this->assertEquals($legacy, $this->legacyDefinitions());
        foreach (array_keys(config('cwis_safety_d22.indicators')) as $code) {
            $suffix = strtolower(substr($code, 3));
            $this->assertNotNull(DB::selectOne('SELECT to_regprocedure(?) AS fn', ['cwis.calculate_sf_'.$suffix.'(integer)'])->fn);
            $this->assertDatabaseHas('cwis.data_source', ['indicator_code' => $code, 'outcome' => 'safety']);
        }
        $this->get('/cwis/generator-data')->assertOk()->assertSee('id="add-outcome-safety"', false);
        $this->get('/cwis/generator-data/create?service_outcome=safety&year='.now()->year)
            ->assertRedirect(route('cwis.safety.create', ['year' => now()->year]));
        $this->get('/cwis/safety-data/create?year='.now()->year)->assertOk()->assertSee('SF-3d / SF-4c')
            ->assertSee('SF-11a')->assertSee('Save as Draft')->assertSee('Submit for Approval')->assertSee('novalidate', false);
    }

    public function test_draft_review_submission_approval_retains_files_and_does_not_generate_results(): void
    {
        $count = DB::table('cwis.data_cwis')->count();
        $payload = $this->payload('draft');
        $payload['answers']['SF-11c'] = 'Yes';
        $payload['evidence']['SF-11c'] = $this->pdf('retained.pdf');
        $this->post('/cwis/safety-data', $payload)->assertSessionHasNoErrors()->assertSessionHas('cwis_saved');
        $source = DB::table('cwis.safety_submissions')->where('country_code', 'BD')->where('reporting_year', $payload['year'])->first();
        $this->assertSame('draft', $source->status);
        $this->get('/cwis/safety-data/'.$source->id)->assertOk()->assertSee('Approve')->assertSee('retained.pdf');
        $review = '/cwis/safety-data/'.$source->id.'/review';
        $this->get($review)->assertOk()->assertSee('Save and Approve')->assertSee('retained.pdf');
        $saved = DB::table('cwis.safety_answers')->where('submission_id', $source->id)->where('indicator_code', 'SF-11c')->first();
        $this->get('/cwis/safety-data/'.$source->id.'/documents/'.$saved->id.'?preview=1')->assertOk();
        unset($payload['evidence'], $payload['waiting_evidence']);
        $payload['save_mode'] = 'submitted';
        $this->put('/cwis/safety-data/'.$source->id, $payload)->assertSessionHasNoErrors();
        $this->assertDatabaseHas('cwis.safety_submissions', ['id' => $source->id, 'status' => 'submitted']);
        $this->post('/cwis/safety-data/'.$source->id.'/approve', $payload)->assertSessionHasNoErrors()->assertSessionHas('cwis_saved')
            ->assertRedirect(route('cwis.generator-data.index', ['country_code' => 'BD']));
        $this->assertDatabaseHas('cwis.safety_submissions', ['id' => $source->id, 'status' => 'approved']);
        $this->assertSame($count, DB::table('cwis.data_cwis')->count());
        Storage::disk('local')->assertExists($saved->document_path);
        $this->get($review)->assertForbidden();
        $this->put('/cwis/safety-data/'.$source->id, $payload)->assertForbidden();
        $this->get('/cwis/safety-data/'.$source->id)->assertOk()->assertSee('CWIS Results');
        $this->getJson('/cwis/generator-data/data?service_outcome=safety&draw=1&start=0&length=10')->assertOk()->assertJsonFragment(['status_label' => 'Approved', 'service_outcome' => 'Safety']);
    }

    public function test_validation_parent_dependencies_evidence_and_year_country_isolation(): void
    {
        $payload = $this->payload();
        $payload['answers']['SF-11a'] = 'Yes';
        $payload['answers']['SF-11a1'] = 'Yes';
        $payload['answers']['SF-12'] = 'Yes';
        $payload['evidence']['SF-11a'] = $this->pdf();
        $this->postJson('/cwis/safety-data', $payload)->assertStatus(422)
            ->assertJsonValidationErrors(['evidence.SF-11a1', 'evidence.SF-12', 'biosolids_evidence.SF-12']);
        $payload = ['year' => now()->year - 4, 'country_code' => 'BD', 'save_mode' => 'draft'];
        $this->post('/cwis/safety-data', $payload)->assertSessionHasNoErrors();
        $id = DB::table('cwis.safety_submissions')->where('country_code','BD')->where('reporting_year',$payload['year'])->value('id');
        $review = '/cwis/safety-data/'.$id.'/review';
        $this->from($review)->post('/cwis/safety-data/'.$id.'/approve', $payload)->assertRedirect($review)->assertSessionHasErrors('answers.SF-11a');
        $this->post('/cwis/safety-data', $payload)->assertSessionHasErrors('year');
        $payload['year']--;
        $this->post('/cwis/safety-data', $payload)->assertSessionHasNoErrors();
        $this->get('/cwis/generator-data')->assertViewHas('inputYearsByOutcome', function ($years) use ($payload) { return !in_array($payload['year'], $years['safety']); });
        DB::table('public.site_settings')->where('name','active_country')->update(['value' => 'NP']);
        $this->get('/cwis/safety-data/'.$id)->assertNotFound();
        $this->post('/cwis/safety-data', $payload)->assertSessionHasErrors('country_code');
    }

    public function test_d22_safe_drain_requires_all_three_conditions(): void
    {
        $check = function ($tp, $cover, $surface) {
            return DB::selectOne("SELECT cwis.safety_d22_system_safe(2,NULL,NULL,NULL,'D',?,?,?) AS safe", [$tp,$cover,$surface])->safe;
        };
        $this->assertTrue($check(1,'Closed','Lined'));
        $this->assertFalse($check(null,'Closed','Lined'));
        $this->assertFalse($check(1,'Open','Lined'));
        $this->assertFalse($check(1,'Closed','Unlined'));
    }

    public function test_new_dispatch_runs_every_calculator_without_touching_legacy_results(): void
    {
        $year = 2088;
        DB::table('cwis.generation_runs')->insert(['year' => $year, 'country_code' => 'BD', 'generated_at' => now()]);
        $id = DB::table('cwis.safety_submissions')->insertGetId(['reporting_year' => $year, 'country_code' => 'BD',
            'status' => 'approved', 'approved_at' => now(), 'public_spaces_complete' => true]);
        foreach (config('cwis_safety_d22.policies') as $code) {
            DB::table('cwis.safety_answers')->insert(['submission_id' => $id, 'indicator_code' => $code, 'answer' => 'No']);
        }
        DB::table('cwis.data_cwis')->insert(['year' => $year, 'indicator_code' => 'SF-3', 'outcome' => 'safety', 'label' => 'Legacy sentinel', 'data_value' => '77.000']);
        DB::select('SELECT cwis.prepare_safety_d22_generation(?)', [$year]);
        DB::select('SELECT cwis.dispatch_safety_d22(?)', [$year]);
        $this->assertSame(20, DB::table('cwis.data_cwis')->where('year', $year)->whereIn('indicator_code', array_keys(config('cwis_safety_d22.indicators')))->count());
        $this->assertSame('77.000', DB::table('cwis.data_cwis')->where('year',$year)->where('indicator_code','SF-3')->value('data_value'));
        foreach (array_merge(['SF-1','SF-1a','SF-1b','SF-1c','SF-1d','SF-1e','SF-1f','SF-1g'],array_keys(config('cwis_safety_d22.indicators'))) as $code) {
            DB::select('SELECT cwis.calculate_safety_d22(?,?) AS value', [$year,$code]);
        }
        $this->assertSame('No', DB::selectOne('SELECT cwis.calculate_sf_11c(?) AS value',[$year])->value);
        $master = DB::selectOne("SELECT pg_get_functiondef('public.update_data_into_cwis_table_revised_2024(integer)'::regprocedure) AS definition")->definition;
        $this->assertStringContainsString('prepare_safety_d22_generation', $master);
        $this->assertStringContainsString('dispatch_safety_d22', $master);
        $this->assertStringContainsString('update_data_into_cwis_table_sf_1_newsan', strtolower($master));
    }

    public function test_private_households_waiting_snapshot_and_pending_scope(): void
    {
        DB::unprepared("CREATE OR REPLACE FUNCTION cwis.safety_buildings_d22(_year integer)
            RETURNS TABLE(bin varchar,households numeric,private_households numeric,population numeric,private_population numeric,safe boolean,
                system_id integer,containment_type_id integer,lic_id integer,functional_use_id integer,use_category_id integer)
            LANGUAGE sql STABLE AS $$ SELECT * FROM (VALUES ('A'::varchar,10::numeric,4::numeric,50::numeric,20::numeric,true,3,3,1,1,1),
            ('B'::varchar,10::numeric,8::numeric,50::numeric,40::numeric,false,4,9,NULL,1,1)) b $$");
        $this->assertEquals(20, DB::selectOne('SELECT cwis.calculate_sf_1(2091) AS value')->value);
        $this->assertEquals(20, DB::selectOne('SELECT cwis.calculate_sf_1a(2091) AS value')->value);
        $this->assertNull(DB::selectOne('SELECT cwis.calculate_sf_6f(2091) AS value')->value);
        $snapshot = ['formula_options' => ['safety_waiting_method' => 'literal'], 'waiting_times' => [
            ['toilet_type' => 'Community Toilet', 'operational' => true, 'waiting_minutes' => 12, 'number_users' => 4, 'document_path' => 'one.pdf'],
            ['toilet_type' => 'Community Toilet', 'operational' => true, 'waiting_minutes' => 10, 'number_users' => 5, 'document_path' => 'two.pdf']],
            'answers' => [['indicator_code' => 'SF-11a', 'answer' => 'No']]];
        DB::table('cwis.safety_generation_runs')->insert(['year' => 2091,'country_code' => 'BD','source_snapshot' => json_encode($snapshot),'generated_at' => now()]);
        $this->assertEquals(5, DB::selectOne('SELECT cwis.calculate_sf_3d(2091) AS value')->value);
        $this->assertSame('NA', DB::selectOne('SELECT cwis.calculate_sf_11a1(2091) AS value')->value);
        DB::table('public.site_settings')->where('name','safety_waiting_method')->update(['value' => 'weighted']);
        $this->assertEquals(5, DB::selectOne('SELECT cwis.calculate_sf_3d(2091) AS value')->value);
    }

    public function test_sf1e_uses_previous_year_transaction_volume_without_365_in_denominator(): void
    {
        DB::table('fsm.treatment_plants')->update(['deleted_at' => now()]);
        DB::table('fsm.sludge_collections')->update(['deleted_at' => now()]);
        $plant = (int) DB::table('fsm.treatment_plants')->max('id') + 1000;
        DB::table('fsm.treatment_plants')->insert(['id' => $plant, 'name' => 'D2.2 transaction test', 'type' => 3, 'capacity_per_day' => 10, 'status' => true]);
        $id = (int) DB::table('fsm.sludge_collections')->max('id') + 1000;
        foreach ([['2098-01-01',600],['2098-12-31',400],['2099-01-01',99999]] as [$date,$volume]) {
            DB::table('fsm.sludge_collections')->insert(['id' => $id++, 'treatment_plant_id' => $plant, 'date' => $date, 'volume_of_sludge' => $volume]);
        }
        $this->assertEquals(365, DB::selectOne('SELECT cwis.calculate_sf_1e(2099) AS value')->value);
        app(\App\Services\Fsm\CwisSettingService::class)->storeOrUpdate(['safety_sf1e_volume_basis' => 'daily_rate']);
        $this->assertEquals(1, DB::selectOne('SELECT cwis.calculate_sf_1e(2099) AS value')->value);
    }
}
