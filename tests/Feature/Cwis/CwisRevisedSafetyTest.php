<?php

namespace Tests\Feature\Cwis;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CwisRevisedSafetyTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\CwisSettingsSeeder::class);
        foreach (config('cwis_safety_settings') as $name => $parameter) {
            DB::table('public.site_settings')->where('category','cwis_setting')->where('name',$name)
                ->whereNull('deleted_at')->update(['value' => $parameter['default']]);
        }
        DB::unprepared(file_get_contents(database_path('sql/cwis_safety_revised.sql')));
    }

    private function result(string $code): ?float
    {
        $value = DB::selectOne('SELECT cwis.calculate_safety_revised(2099, ?) AS result', [$code])->result;
        return $value === null ? null : (float) $value;
    }

    private function plant(int $type, float $capacity): int
    {
        $id = DB::table('fsm.treatment_plants')->max('id') + 1000;
        DB::table('fsm.treatment_plants')->insert([
            'id' => $id, 'name' => 'Transactional Safety test', 'type' => $type,
            'capacity_per_day' => $capacity, 'status' => true,
        ]);
        return $id;
    }

    public function test_percentage_rounds_and_preserves_zero_and_no_data(): void
    {
        $row = DB::selectOne('SELECT cwis.safety_percentage(1,3) AS rounded,
            cwis.safety_percentage(0,2) AS zero, cwis.safety_percentage(1,0) AS missing,
            cwis.safety_percentage(NULL,2) AS unknown, cwis.safety_percentage(4,2) AS capacity');
        $this->assertEquals(33.333, $row->rounded);
        $this->assertEquals(0, $row->zero);
        $this->assertNull($row->missing);
        $this->assertNull($row->unknown);
        $this->assertEquals(200, $row->capacity);
    }

    public function test_household_population_annual_sludge_and_daily_wastewater_formulas(): void
    {
        DB::unprepared("CREATE OR REPLACE FUNCTION cwis.safety_buildings(_year integer)
            RETURNS TABLE(bin varchar, households numeric, private_households numeric,
                population numeric, private_population numeric, safe boolean,
                system_id integer, containment_type_id integer)
            LANGUAGE sql STABLE AS \$\$
            SELECT * FROM (VALUES
                ('A'::varchar,100::numeric,80::numeric,10000::numeric,8000::numeric,true,3,3),
                ('B'::varchar,100::numeric,50::numeric,5000::numeric,2500::numeric,false,4,9)
            ) AS fixture(bin,households,private_households,population,private_population,safe,system_id,containment_type_id)
            \$\$");
        DB::table('fsm.treatment_plants')->update(['deleted_at' => now()]);
        $this->plant(3, 10);
        $this->plant(1, 900);
        $this->assertEquals(40, $this->result('SF-1'));
        $this->assertEquals(53.333, $this->result('SF-1a'));
        $this->assertEquals(87.952, $this->result('SF-1d'));
        $this->assertEquals(100, $this->result('SF-1f'));
        DB::table('public.site_settings')->where('name','fs_generation_septic_m3_per_person_year')->update(['value' => '0.56']);
        DB::table('public.site_settings')->where('name','fs_generation_unlined_pit_m3_per_person_year')->update(['value' => '0.54']);
        $this->assertEquals(43.976, $this->result('SF-1d'));
    }

    public function test_bod_is_strictly_below_50_and_excludes_missing_and_deleted_tests(): void
    {
        DB::table('fsm.treatmentplant_tests')->update(['deleted_at' => now()]);
        $plant = $this->plant(3, 10);
        $id = DB::table('fsm.treatmentplant_tests')->max('id') + 1000;
        foreach ([40,50,60,null] as $bod) {
            DB::table('fsm.treatmentplant_tests')->insert([
                'id' => $id++, 'treatment_plant_id' => $plant, 'date' => '2099-03-01',
                'bod' => $bod, 'tss' => 999999, 'ecoli' => 999999,
            ]);
        }
        $this->assertEquals(33.333, $this->result('SF-1g'));
        DB::table('public.site_settings')->where('name','bod_compliance_threshold_mg_l')->update(['value' => '60']);
        $this->assertEquals(66.667, $this->result('SF-1g'));
        DB::table('public.site_settings')->where('name','active_country')->update(['value' => 'ALL']);
        DB::table('cwis.data_cwis')->insert([
            'year' => 2099, 'indicator_code' => 'SF-1g', 'outcome' => 'safety', 'label' => 'Transactional BOD test',
        ]);
        DB::select('SELECT public.update_data_into_cwis_table_sf_1g_newsan(2099,1,1,1)');
        $this->assertEquals(66.667, DB::table('cwis.data_cwis')->where('year',2099)->where('indicator_code','SF-1g')->value('data_value'));
        DB::table('fsm.treatmentplant_tests')->where('date','2099-03-01')->update(['deleted_at' => now()]);
        $this->assertNull($this->result('SF-1g'));
    }

    public function test_sf_1e_annualizes_previous_year_volume_as_specified_in_document(): void
    {
        DB::table('fsm.sludge_collections')->update(['deleted_at' => now()]);
        DB::table('fsm.treatment_plants')->update(['deleted_at' => now()]);
        $plant = $this->plant(3, 10);
        $id = DB::table('fsm.sludge_collections')->max('id') + 1000;
        foreach ([['2098-01-01',600],['2098-12-31',400],['2099-01-01',99999]] as [$date,$volume]) {
            DB::table('fsm.sludge_collections')->insert([
                'id' => $id++, 'treatment_plant_id' => $plant, 'date' => $date, 'volume_of_sludge' => $volume,
            ]);
        }
        $this->assertEquals(1, $this->result('SF-1e'));
    }

    public function test_live_source_snapshot_does_not_duplicate_buildings(): void
    {
        $row = DB::selectOne('SELECT count(*) AS total, count(DISTINCT bin) AS distinct_total FROM cwis.safety_buildings(2099)');
        $this->assertEquals($row->total, $row->distinct_total);
        foreach (['SF-1','SF-1a','SF-1b','SF-1c','SF-1d','SF-1e','SF-1f','SF-1g'] as $code) {
            $result = $this->result($code);
            $this->assertTrue($result === null || is_finite($result), $code);
        }
    }

    public function test_desludging_uses_distinct_previous_year_history_even_after_a_later_emptying(): void
    {
        $containments = DB::table('fsm.containments')->orderBy('id')->limit(2)->pluck('id');
        $this->assertCount(2, $containments);
        DB::table('fsm.applications')->update(['deleted_at' => now()]);
        DB::table('fsm.emptyings')->update(['deleted_at' => now()]);
        $first = str_replace("'", "''", $containments[0]);
        $second = str_replace("'", "''", $containments[1]);
        DB::unprepared("CREATE OR REPLACE FUNCTION cwis.safety_eligible_containments(_year integer)
            RETURNS TABLE(id varchar, emptied_status boolean, last_emptied_date date)
            LANGUAGE sql STABLE AS \$\$ SELECT * FROM (VALUES
                ('$first'::varchar,true,'2099-02-01'::date),
                ('$second'::varchar,false,NULL::date)) AS fixture(id,emptied_status,last_emptied_date) \$\$");
        $appId = DB::table('fsm.applications')->max('id') + 1000;
        $emptyId = DB::table('fsm.emptyings')->max('id') + 1000;
        foreach (['2098-01-01','2098-12-31','2099-02-01'] as $date) {
            DB::table('fsm.applications')->insert([
                'id' => $appId, 'containment_id' => $containments[0],
                'application_date' => $date, 'emptying_status' => 2,
            ]);
            DB::table('fsm.emptyings')->insert([
                'id' => $emptyId++, 'application_id' => $appId++, 'emptied_date' => $date,
                'volume_of_sludge' => 10,
            ]);
        }
        $this->assertEquals(50, $this->result('SF-1b'));
    }

    public function test_public_writer_respects_country_and_saved_generation_context(): void
    {
        $source = DB::table('cwis.data_source')->where('indicator_code', 'SF-1')->value('id');
        $this->assertNotNull($source);
        DB::table('cwis.indicator_country')->where('data_source_id', $source)->delete();
        DB::table('cwis.indicator_country')->insert(['data_source_id' => $source, 'country_code' => 'NP']);
        DB::table('public.site_settings')->where('name', 'active_country')->update(['value' => 'BD']);
        DB::table('cwis.data_cwis')->insert([
            'year' => 2099, 'indicator_code' => 'SF-1', 'outcome' => 'safety',
            'label' => 'Transactional test', 'data_value' => 'unchanged',
        ]);
        DB::select('SELECT public.update_data_into_cwis_table_sf_1_newsan(2099)');
        $this->assertSame('unchanged', DB::table('cwis.data_cwis')->where('year',2099)->where('indicator_code','SF-1')->value('data_value'));
        DB::table('cwis.generation_runs')->insert([
            'year' => 2099, 'country_code' => 'NP', 'generated_at' => now(),
        ]);
        DB::select('SELECT public.update_data_into_cwis_table_sf_1_newsan(2099)');
        $this->assertNotSame('unchanged', DB::table('cwis.data_cwis')->where('year',2099)->where('indicator_code','SF-1')->value('data_value'));
    }

    public function test_main_safety_card_distinguishes_missing_data_from_zero(): void
    {
        $template = 'cwis.cwis-dashboard.chart-layout.safety-main-card';
        $missing = view($template, ['sf1' => collect([(object)['data_value' => 'NaN']]), 'chartId' => 'testMissing'])->render();
        $zero = view($template, ['sf1' => collect([(object)['data_value' => '0.000']]), 'chartId' => 'testZero'])->render();
        $this->assertStringContainsString('No data', $missing);
        $this->assertStringNotContainsString('createDoughnutChart(', $missing);
        $this->assertStringContainsString('createDoughnutChart(0', $zero);
    }

    public function test_settings_page_saves_active_parameters_and_rejects_invalid_values(): void
    {
        $this->actingAs(\App\Models\User::query()->firstOrFail());
        $this->get('/fsm/cwis-setting')->assertOk()
            ->assertSee('name="fs_generation_septic_m3_per_person_year"', false)
            ->assertSee('name="fs_generation_unlined_pit_m3_per_person_year"', false)
            ->assertSee('name="bod_compliance_threshold_mg_l"', false)
            ->assertDontSee('name="fs_generation_from_containment_not_connected_to_sewer_lpcd"', false)
            ->assertDontSee('name="greywater_conversion_factor_connected_to_sewer"', false)
            ->assertSee('id="legacy-fs_generation_from_containment_not_connected_to_sewer_lpcd"', false)
            ->assertSee('id="legacy-fs_generation_from_permeable_or_unlined_pit_lpcd"', false)
            ->assertSee('id="legacy-greywater_conversion_factor_connected_to_sewer"', false)
            ->assertSee('readonly data-legacy-setting', false);
        $payload = array_map(function ($parameter) { return $parameter['default']; }, config('cwis_safety_settings'));
        $payload['active_country'] = 'NP';
        $payload['bod_compliance_threshold_mg_l'] = 45;
        $payload['average_water_consumption_lpcd'] = 125;
        $legacyValue = DB::table('public.site_settings')->where('name','fs_generation_from_containment_not_connected_to_sewer_lpcd')->value('value');
        $payload['fs_generation_from_containment_not_connected_to_sewer_lpcd'] = 999999;
        $this->post('/fsm/cwis-setting', $payload)->assertRedirect('/fsm/cwis-setting')->assertSessionHasNoErrors();
        $this->assertEquals(45, DB::table('public.site_settings')->where('name','bod_compliance_threshold_mg_l')->value('value'));
        $this->assertEquals(125, DB::table('public.site_settings')->where('name','average_water_consumption_lpcd')->value('value'));
        $this->assertSame($legacyValue, DB::table('public.site_settings')->where('name','fs_generation_from_containment_not_connected_to_sewer_lpcd')->value('value'));
        foreach (['waste_water_conversion_factor' => 101, 'greywater_conversion_factor_not_connected_to_sewer' => -1,
            'average_water_consumption_lpcd' => 0, 'bod_compliance_threshold_mg_l' => 0,
            'fs_generation_septic_m3_per_person_year' => -1] as $key => $invalid) {
            $this->post('/fsm/cwis-setting', array_replace($payload, [$key => $invalid]))->assertSessionHasErrors($key);
        }
    }

    public function test_wastewater_uses_saved_x_y_z_and_ignores_legacy_arguments(): void
    {
        DB::unprepared("CREATE OR REPLACE FUNCTION cwis.safety_buildings(_year integer)
            RETURNS TABLE(bin varchar, households numeric, private_households numeric,
                population numeric, private_population numeric, safe boolean, system_id integer, containment_type_id integer)
            LANGUAGE sql STABLE AS \$\$
            SELECT 'fixture'::varchar,1::numeric,1::numeric,1000::numeric,1000::numeric,true,s,ct
            FROM (VALUES (1,NULL::integer),(3,1),(3,3),(4,9)) groups(s,ct) \$\$");
        DB::table('fsm.treatment_plants')->update(['deleted_at' => now()]);
        $this->plant(1, 280);
        $this->assertEquals(100, $this->result('SF-1f'));
        foreach (['average_water_consumption_lpcd' => 200, 'waste_water_conversion_factor' => 50,
            'greywater_conversion_factor_not_connected_to_sewer' => 25,
            'greywater_conversion_factor_connected_to_sewer' => 99] as $name => $value) {
            DB::table('public.site_settings')->where('name',$name)->update(['value' => $value]);
        }
        $this->assertEquals(93.333, $this->result('SF-1f'));
        $row = DB::selectOne("SELECT cwis.calculate_safety_revised(2099,'SF-1f',999,99,99,99) AS value");
        $this->assertEquals(93.333, $row->value);
    }

    public function test_missing_setting_does_not_fall_back_to_a_hardcoded_value(): void
    {
        DB::table('public.site_settings')->where('name','fs_generation_septic_m3_per_person_year')->update(['deleted_at' => now()]);
        $this->expectException(\Illuminate\Database\QueryException::class);
        $this->expectExceptionMessage('Missing or invalid CWIS setting');
        $this->result('SF-1d');
    }

    public function test_database_rejects_invalid_percentage_even_if_ui_is_bypassed(): void
    {
        DB::table('public.site_settings')->where('name','waste_water_conversion_factor')->update(['value' => '101']);
        $this->expectException(\Illuminate\Database\QueryException::class);
        $this->expectExceptionMessage('Missing or invalid CWIS setting');
        $this->result('SF-1f');
    }
}
