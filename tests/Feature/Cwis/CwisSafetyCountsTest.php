<?php

namespace Tests\Feature\Cwis;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CwisSafetyCountsTest extends TestCase
{
    use DatabaseTransactions;

    private string $originalMaster;
    private string $originalBuildings;

    protected function setUp(): void
    {
        parent::setUp();
        require_once database_path('migrations/2026_11_04_000001_install_cwis_safety_count_indicators.php');
        if (DB::selectOne("SELECT to_regclass('cwis.safety_counts_function_backup') AS backup_table")->backup_table !== null) {
            (new \InstallCwisSafetyCountIndicators())->down();
        }
        $this->originalMaster = DB::selectOne("SELECT pg_get_functiondef('public.update_data_into_cwis_table_revised_2024(integer)'::regprocedure) AS definition")->definition;
        (new \InstallCwisSafetyCountIndicators())->up();
        require_once database_path('migrations/2026_11_04_000002_align_cwis_emptying_status_helper.php');
        if (DB::selectOne("SELECT to_regclass('cwis.emptying_status_function_backup') AS backup_table")->backup_table === null) {
            (new \AlignCwisEmptyingStatusHelper())->up();
        }
        $this->originalBuildings = DB::selectOne("SELECT pg_get_functiondef('cwis.safety_buildings(integer)'::regprocedure) AS definition")->definition;
        $this->fixture("SELECT * FROM (VALUES
            ('A'::varchar,10::numeric,8::numeric,60::numeric,40::numeric,true,3,3,2000),
            ('B'::varchar,5::numeric,3::numeric,25::numeric,15::numeric,true,3,3,2000),
            ('C'::varchar,100::numeric,90::numeric,500::numeric,450::numeric,false,4,9,2000),
            ('FUTURE'::varchar,20::numeric,20::numeric,100::numeric,100::numeric,true,3,3,2101)
        ) AS b(bin,households,private_households,population,private_population,safe,system_id,containment_type_id,built)
        WHERE built <= _year");
    }

    private function fixture(string $sql): void
    {
        DB::unprepared('CREATE OR REPLACE FUNCTION cwis.safety_buildings(_year integer)
            RETURNS TABLE(bin varchar, households numeric, private_households numeric,
                population numeric, private_population numeric, safe boolean,
                system_id integer, containment_type_id integer)
            LANGUAGE sql STABLE AS $$ SELECT bin,households,private_households,population,
                private_population,safe,system_id,containment_type_id FROM (' . $sql . ') fixture $$');
    }

    private function totals(int $year = 2099): object
    {
        return DB::selectOne('SELECT cwis.calculate_sf_0a(?) AS buildings,
            cwis.calculate_sf_0b(?) AS households, cwis.calculate_sf_0c(?) AS population', [$year, $year, $year]);
    }

    public function test_counts_use_private_households_and_population_without_percentages(): void
    {
        $totals = $this->totals();
        $this->assertEquals(2, $totals->buildings);
        $this->assertEquals(11, $totals->households);
        $this->assertEquals(55, $totals->population);
        $this->assertEquals(3, $this->totals(2101)->buildings);
        $this->assertEquals(31, $this->totals(2101)->households);
        $this->assertEquals(155, $this->totals(2101)->population);
    }

    public function test_empty_eligible_set_is_a_real_zero(): void
    {
        $totals = $this->totals(1999);
        $this->assertEquals(0, $totals->buildings);
        $this->assertEquals(0, $totals->households);
        $this->assertEquals(0, $totals->population);
    }

    public function test_unknown_private_totals_are_not_fabricated_as_zero(): void
    {
        $this->fixture("SELECT 'A'::varchar AS bin, 10::numeric AS households, NULL::numeric AS private_households,
            60::numeric AS population, NULL::numeric AS private_population, true AS safe,
            3 AS system_id, 3 AS containment_type_id");
        $totals = $this->totals();
        $this->assertEquals(1, $totals->buildings);
        $this->assertNull($totals->households);
        $this->assertNull($totals->population);
    }

    public function test_dispatch_uses_saved_country_and_preserves_other_results(): void
    {
        $codes = ['SF-0a', 'SF-0b', 'SF-0c'];
        $ids = DB::table('cwis.data_source')->whereIn('indicator_code', $codes)->pluck('id');
        DB::table('cwis.indicator_country')->whereIn('data_source_id', $ids)->delete();
        foreach ($ids as $id) {
            DB::table('cwis.indicator_country')->insert(['data_source_id' => $id, 'country_code' => 'NP']);
        }
        DB::table('public.site_settings')->where('name', 'active_country')->update(['value' => 'BD']);
        DB::table('cwis.generation_runs')->updateOrInsert(['year' => 2099], ['country_code' => 'NP', 'generated_at' => now()]);
        foreach (array_merge($codes, ['EQ-1']) as $code) {
            DB::table('cwis.data_cwis')->insert(['year' => 2099, 'indicator_code' => $code,
                'outcome' => $code === 'EQ-1' ? 'equity' : 'safety', 'label' => 'Count test', 'data_value' => '999']);
        }
        DB::select('SELECT cwis.dispatch_safety_counts(2099)');
        foreach (array_combine($codes, [2, 11, 55]) as $code => $expected) {
            $this->assertEquals($expected, DB::table('cwis.data_cwis')->where('year', 2099)->where('indicator_code', $code)->value('data_value'));
        }
        $this->assertEquals(999, DB::table('cwis.data_cwis')->where('year', 2099)->where('indicator_code', 'EQ-1')->value('data_value'));
        DB::table('cwis.generation_runs')->where('year', 2099)->update(['country_code' => 'BD']);
        DB::table('cwis.data_cwis')->where('year', 2099)->whereIn('indicator_code', $codes)->update(['data_value' => '777']);
        DB::select('SELECT cwis.dispatch_safety_counts(2099)');
        $this->assertEquals(3, DB::table('cwis.data_cwis')->where('year', 2099)->whereIn('indicator_code', $codes)->where('data_value', '777.000')->count());
    }

    public function test_migration_registers_once_and_rollback_restores_master(): void
    {
        $codes = ['SF-0a', 'SF-0b', 'SF-0c'];
        $this->assertEquals(3, DB::table('cwis.data_source')->where('outcome', 'safety')->whereIn('indicator_code', $codes)->count());
        $master = DB::selectOne("SELECT pg_get_functiondef('public.update_data_into_cwis_table_revised_2024(integer)'::regprocedure) AS definition")->definition;
        $this->assertSame(1, substr_count($master, 'cwis.dispatch_safety_counts(_year)'));
        (new \InstallCwisSafetyCountIndicators())->down();
        $restored = DB::selectOne("SELECT pg_get_functiondef('public.update_data_into_cwis_table_revised_2024(integer)'::regprocedure) AS definition")->definition;
        $this->assertSame($this->originalMaster, $restored);
        $this->assertNull(DB::selectOne("SELECT to_regprocedure('cwis.calculate_sf_0a(integer)') AS routine")->routine);
        $this->assertEquals(3, DB::table('cwis.data_source')->whereIn('indicator_code', $codes)->count());
    }

    public function test_dashboard_and_pdf_use_saved_count_values_and_country_subset(): void
    {
        $indicators = DB::table('cwis.data_source')->whereIn('indicator_code', ['SF-0a', 'SF-0b'])->get();
        $data = collect(['SF-0a' => '0', 'SF-0b' => '11.000']);
        $html = view('cwis.cwis-dashboard.chart-layout.safety-count-cards', ['safetyIndicators' => $indicators, 'data' => $data])->render();
        $this->assertStringContainsString('data-indicator-code="SF-0a"', $html);
        $this->assertStringContainsString('data-indicator-code="SF-0b"', $html);
        $this->assertStringNotContainsString('data-indicator-code="SF-0c"', $html);
        $this->assertStringContainsString('>0</div>', $html);
        $this->assertStringNotContainsString('<canvas', $html);
        $pdf = view('cwis.cwis-dashboard.chart-layout.safety-count-pdf-cards', [
            'safetyCountIndicators' => $indicators, 'safetyCountValues' => $data,
            'countryLabel' => 'Nepal', 'reportingYear' => 2099,
        ])->render();
        $this->assertStringContainsString('>11</div>', $pdf);
        $this->assertStringNotContainsString('11.000', $pdf);
        $this->assertStringContainsString('Nepal', $pdf);
    }

    public function test_real_building_helper_is_callable_and_has_one_row_per_building(): void
    {
        DB::unprepared($this->originalBuildings);
        $duplicates = DB::selectOne('SELECT count(*) - count(DISTINCT bin) AS duplicates FROM cwis.safety_buildings(2026)')->duplicates;
        $this->assertEquals(0, $duplicates);
        $this->assertGreaterThanOrEqual(0, $this->totals(2026)->buildings);
    }

    public function test_emptying_status_fix_preserves_column_type_and_rolls_back(): void
    {
        (new \AlignCwisEmptyingStatusHelper())->down();
        $signature = 'public.execute_select_build_sanisys_nd_criterias_part3()';
        $original = DB::selectOne('SELECT pg_get_functiondef(?::regprocedure) AS definition', [$signature])->definition;
        $type = DB::selectOne("SELECT data_type FROM information_schema.columns
            WHERE table_schema='fsm' AND table_name='applications' AND column_name='emptying_status'")->data_type;
        (new \AlignCwisEmptyingStatusHelper())->up();
        $updated = DB::selectOne('SELECT pg_get_functiondef(?::regprocedure) AS definition', [$signature])->definition;
        if ($type !== 'boolean') {
            $this->assertStringNotContainsString('a.emptying_status IS TRUE', $updated);
            $this->assertStringContainsString('a.emptying_status IN (1, 2)', $updated);
        }
        $this->assertSame($type, DB::selectOne("SELECT data_type FROM information_schema.columns
            WHERE table_schema='fsm' AND table_name='applications' AND column_name='emptying_status'")->data_type);
        DB::select('SELECT * FROM public.execute_select_build_sanisys_nd_criterias_part3() LIMIT 1');
        (new \AlignCwisEmptyingStatusHelper())->down();
        $this->assertSame($original, DB::selectOne('SELECT pg_get_functiondef(?::regprocedure) AS definition', [$signature])->definition);
    }

    public function test_invalid_reporting_year_is_rejected(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);
        DB::select('SELECT cwis.calculate_sf_0a(0)');
    }
}
