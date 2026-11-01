<?php

namespace Tests\Feature\Cwis;

use App\Models\User;
use App\Services\Cwis\CwisAccountabilityWorkflowService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CwisAccountabilityCalculationTest extends TestCase
{
    use DatabaseTransactions;

    private int $year = 2090;
    private int $sourceId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::whereHas('roles', function ($q) { $q->where('name', 'Super Admin'); })->firstOrFail());
        Storage::fake('local');
        $this->seed(\Database\Seeders\CwisAccountabilityDataSourceSeeder::class);
        DB::table('cwis.accountability_generation_runs')->delete();
        DB::table('cwis.accountability_submissions')->delete();
        DB::table('public.site_settings')->where('name', 'active_country')->update(['value' => 'BD']);
        $this->sourceId = DB::table('cwis.accountability_submissions')->insertGetId([
            'reporting_year' => $this->year, 'country_code' => 'BD', 'status' => 'approved',
            'approved_by' => auth()->id(), 'approved_at' => now(),
        ]);
        foreach (config('cwis_accountability.groups') as $questions) {
            foreach ($questions as $code => $label) {
                DB::table('cwis.accountability_answers')->insert([
                    'submission_id' => $this->sourceId, 'indicator_code' => $code, 'answer' => 'No',
                ]);
            }
        }
    }

    private function answer(string $code, array $values): void
    {
        DB::table('cwis.accountability_answers')->where('submission_id', $this->sourceId)->where('indicator_code', $code)->update($values);
    }

    private function context(string $country = 'BD'): void
    {
        DB::table('cwis.generation_runs')->insert(['year' => $this->year, 'country_code' => $country, 'generated_at' => now()]);
    }

    private function onlyAccountability(): void
    {
        $ids = DB::table('cwis.data_source')->where('outcome', 'accountability')->pluck('id');
        DB::table('cwis.indicator_country')->where('country_code', 'BD')->whereNotIn('data_source_id', $ids)->delete();
    }

    private function assertSqlFails(callable $operation, string $message): void
    {
        try {
            DB::transaction($operation);
            $this->fail('Expected SQL validation failure.');
        } catch (\Illuminate\Database\QueryException $exception) {
            $this->assertStringContainsString($message, $exception->getMessage());
        }
    }

    public function test_all_twenty_nine_individual_calculators_and_public_updaters(): void
    {
        DB::select('SELECT cwis.prepare_accountability_generation(?)', [$this->year]);
        $this->context();
        $definitions = DB::select('SELECT * FROM cwis.accountability_function_map()');
        $this->assertCount(29, $definitions);
        foreach ($definitions as $definition) {
            if (in_array($definition->code, ['AC-4a', 'AC-4b'], true)) { continue; }
            $this->assertSame('No', DB::selectOne('SELECT cwis.calculate_ac_' . $definition->suffix . '(?) AS value', [$this->year])->value);
            DB::select('SELECT public.update_data_into_cwis_table_ac_' . $definition->suffix . '_newsan(?)', [$this->year]);
            $this->assertSame('No', DB::table('cwis.data_cwis')->where('year', $this->year)->where('indicator_code', $definition->code)->value('data_value'));
        }
        $this->assertSame(27, DB::table('cwis.data_cwis')->where('year', $this->year)->where('outcome', 'accountability')->count());
    }

    public function test_all_context_includes_distinct_incentive_existence_and_application_answers(): void
    {
        DB::table('public.site_settings')->where('name', 'active_country')->update(['value' => 'ALL']);
        DB::table('cwis.accountability_submissions')->where('id', $this->sourceId)->update(['country_code' => 'ALL']);
        $this->answer('AC-4b', ['answer' => 'Yes', 'document_path' => 'enforcement.pdf']);
        DB::select('SELECT cwis.prepare_accountability_generation(?)', [$this->year]);
        $this->context('ALL');
        DB::select('SELECT cwis.dispatch_accountability(?)', [$this->year]);
        $this->assertSame(29, DB::table('cwis.data_cwis')->where('year', $this->year)->where('outcome', 'accountability')->count());
        $this->assertSame('No', DB::selectOne('SELECT cwis.calculate_ac_4a(?) AS value', [$this->year])->value);
        $this->assertSame('Yes', DB::selectOne('SELECT cwis.calculate_ac_4b(?) AS value', [$this->year])->value);
    }

    public function test_bangladesh_excludes_both_incentive_indicators(): void
    {
        foreach (['4a', '4b'] as $suffix) {
            $this->assertSqlFails(function () use ($suffix) {
                DB::select('SELECT cwis.calculate_ac_' . $suffix . '(?)', [$this->year]);
            }, 'unavailable');
        }
    }

    public function test_pdf_and_excel_use_saved_country_year_values_not_client_answers(): void
    {
        $this->context();
        DB::table('cwis.data_cwis')->insert([
            'year' => $this->year, 'indicator_code' => 'AC-1a.1', 'outcome' => 'accountability', 'data_value' => 'Yes',
        ]);
        $facade = \Barryvdh\Snappy\Facades\SnappyPdf::class;
        $pdf = \Mockery::mock();
        $pdf->shouldReceive('download')->once()->andReturn(response('pdf-output', 200, ['Content-Type' => 'application/pdf']));
        $facade::shouldReceive('loadHTML')->once()->with(\Mockery::on(function ($html) {
            return strpos($html, 'Accountability') !== false && strpos($html, '#45B0E1') !== false
                && strpos($html, 'National Accountability Mechanism') !== false
                && strpos($html, 'Performance-linked Incentives') === false
                && strpos($html, 'FORGED_VALUE') === false;
        }))->andReturn($pdf);
        $payload = ['year' => $this->year, 'answers' => ['AC-1a_1' => 'FORGED_VALUE']];
        foreach (['chartImage','eq1','sf3d','ss1Image','safety1aImage','safety1bImage','safety1cImage','safety1dImage',
            'safety1eImage','safety1fImage','safety1gImage','safety2aImage','safety2bImage','safety2cImage',
            'safety3aImage','safety3bImage','safety3cImage','safety4aImage','safety4bImage','safety4dImage',
            'sf5Image','sf6Image','sf7Image','sf9Image'] as $field) { $payload[$field] = ''; }
        $this->post('/cwis/chart/download-pdf', $payload)->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $html = (new \App\Exports\MneCsvExport($this->year))->view()->render();
        $this->assertStringContainsString('National Accountability Mechanism', $html);
        $this->assertStringNotContainsString('Performance-linked Incentives', $html);
        DB::table('cwis.generation_runs')->where('year', $this->year)->update(['country_code' => 'NP']);
        $values = (new \App\Exports\MneCsvExport($this->year))->view()->getData()['results']->pluck('data_value');
        $this->assertFalse($values->contains('Yes'));
    }

    public function test_yes_no_missing_evidence_and_independent_questions(): void
    {
        $this->answer('AC-1a.1', ['answer' => 'Yes', 'document_path' => 'mandate.pdf']);
        $this->answer('AC-1a.2', ['answer' => 'Yes']);
        $this->answer('AC-2.1', ['answer' => null]);
        $this->answer('AC-1b.8', ['answer' => 'Yes', 'document_path' => 'service.pdf']);
        DB::select('SELECT cwis.prepare_accountability_generation(?)', [$this->year]);
        $result = DB::selectOne('SELECT cwis.calculate_ac_1a_1(?) AS a, cwis.calculate_ac_1a_2(?) AS b,
            cwis.calculate_ac_2_1(?) AS c, cwis.calculate_ac_1b_8(?) AS d, cwis.calculate_ac_2_5(?) AS e', array_fill(0, 5, $this->year));
        $this->assertSame('Yes', $result->a);
        $this->assertNull($result->b);
        $this->assertNull($result->c);
        $this->assertSame('Yes', $result->d);
        $this->assertSame('No', $result->e);
    }

    public function test_snapshot_keeps_previous_answer_until_an_explicit_rebuild(): void
    {
        DB::select('SELECT cwis.prepare_accountability_generation(?)', [$this->year]);
        $this->answer('AC-2.5', ['answer' => 'Yes', 'document_path' => 'revenue.pdf']);
        $this->assertSame('No', DB::selectOne('SELECT cwis.calculate_ac_2_5(?) AS value', [$this->year])->value);
        DB::select('SELECT cwis.prepare_accountability_generation(?)', [$this->year]);
        $this->assertSame('Yes', DB::selectOne('SELECT cwis.calculate_ac_2_5(?) AS value', [$this->year])->value);
    }

    public function test_unapproved_wrong_year_and_wrong_country_sources_are_rejected(): void
    {
        DB::table('cwis.accountability_submissions')->where('id', $this->sourceId)->update(['status' => 'submitted']);
        $this->assertSqlFails(function () { DB::select('SELECT cwis.prepare_accountability_generation(?)', [$this->year]); }, 'Approve Accountability data');
        DB::table('cwis.accountability_submissions')->where('id', $this->sourceId)->update(['status' => 'approved']);
        $this->assertSqlFails(function () { DB::select('SELECT cwis.prepare_accountability_generation(?)', [$this->year + 1]); }, 'Approve Accountability data');
        DB::select('SELECT cwis.prepare_accountability_generation(?)', [$this->year]);
        DB::table('public.site_settings')->where('name', 'active_country')->update(['value' => 'ALL']);
        $this->assertSqlFails(function () { DB::select('SELECT cwis.calculate_ac_2_5(?)', [$this->year]); }, 'country mismatch');
        DB::table('public.site_settings')->where('name', 'active_country')->update(['value' => 'NP']);
        $this->assertSqlFails(function () { DB::select('SELECT cwis.calculate_ac_2_5(?)', [$this->year]); }, 'unavailable');
    }

    public function test_backed_up_rebuild_preserves_other_outcomes_and_is_visible_in_dashboard_and_export(): void
    {
        $this->context();
        DB::table('cwis.data_cwis')->insert([
            ['year' => $this->year, 'indicator_code' => 'EQ-3', 'outcome' => 'equity', 'data_value' => '7.777'],
            ['year' => $this->year, 'indicator_code' => 'SS-2', 'outcome' => 'sustainability', 'data_value' => '2.222'],
        ]);
        Storage::disk('local')->put('mandate.pdf', 'evidence');
        $this->answer('AC-1a.1', ['answer' => 'Yes', 'document_path' => 'mandate.pdf']);
        $this->post('/cwis/accountability-data/' . $this->sourceId . '/calculate')->assertSessionHasNoErrors()
            ->assertRedirect(route('cwis.generator-data.index', ['country_code' => 'BD']));
        $this->assertSame('7.777', DB::table('cwis.data_cwis')->where('year', $this->year)->where('indicator_code', 'EQ-3')->value('data_value'));
        $this->assertSame('2.222', DB::table('cwis.data_cwis')->where('year', $this->year)->where('indicator_code', 'SS-2')->value('data_value'));
        $this->assertSame('Yes', DB::table('cwis.data_cwis')->where('year', $this->year)->where('indicator_code', 'AC-1a.1')->value('data_value'));
        $this->assertNotEmpty(Storage::disk('local')->files('cwis/result-backups'));
        $this->get('/cwis/cwis/getall?year=' . $this->year)->assertOk()->assertSee('tab-accountability')->assertSee('National Accountability Mechanism');
        $this->assertStringContainsString('Accountability', (new \App\Exports\MneCsvExport($this->year))->view()->render());
        $this->get('/cwis/accountability-data/' . $this->sourceId)->assertOk()->assertSee('Rebuild CWIS Results')
            ->assertSee('cwis-rebuild-loader')->assertDontSee('window.confirm', false);
    }

    public function test_missing_physical_file_blocks_rebuild_and_preserves_existing_results(): void
    {
        $this->context();
        DB::table('cwis.data_cwis')->insert(['year' => $this->year, 'indicator_code' => 'AC-2.5', 'outcome' => 'accountability', 'data_value' => 'No']);
        $this->answer('AC-2.5', ['answer' => 'Yes', 'document_path' => 'missing.pdf']);
        $this->post('/cwis/accountability-data/' . $this->sourceId . '/calculate')->assertSessionHasErrors('AC-2_5');
        $this->assertSame('No', DB::table('cwis.data_cwis')->where('year', $this->year)->where('indicator_code', 'AC-2.5')->value('data_value'));
    }

    public function test_country_mismatch_and_duplicate_results_fail_without_partial_writes(): void
    {
        $this->context('NP');
        DB::table('cwis.data_cwis')->insert(['year' => $this->year, 'indicator_code' => 'AC-2.5', 'outcome' => 'accountability', 'data_value' => 'No']);
        $this->post('/cwis/accountability-data/' . $this->sourceId . '/calculate')->assertSessionHasErrors('calculation');
        DB::table('cwis.generation_runs')->where('year', $this->year)->update(['country_code' => 'BD']);
        DB::table('cwis.data_cwis')->insert(['year' => $this->year, 'indicator_code' => 'AC-2.5', 'outcome' => 'accountability', 'data_value' => 'No']);
        $this->post('/cwis/accountability-data/' . $this->sourceId . '/calculate')->assertSessionHasErrors('calculation');
        $this->assertSame(2, DB::table('cwis.data_cwis')->where('year', $this->year)->count());
        $this->assertFalse(DB::table('cwis.accountability_generation_runs')->where('year', $this->year)->exists());
    }

    public function test_new_generation_dispatches_accountability_and_does_not_overwrite_existing_year(): void
    {
        $this->onlyAccountability();
        $this->post('/cwis/accountability-data/' . $this->sourceId . '/calculate')->assertSessionHasNoErrors();
        $this->assertSame(27, DB::table('cwis.data_cwis')->where('year', $this->year)->where('outcome', 'accountability')->count());
        $this->assertSame($this->sourceId, (int) DB::table('cwis.accountability_generation_runs')->where('year', $this->year)->value('submission_id'));
        DB::table('cwis.data_cwis')->where('year', $this->year)->where('indicator_code', 'AC-2.5')->update(['data_value' => 'Yes']);
        DB::select('SELECT public.generate_cwis_with_accountability(?)', [$this->year]);
        $this->assertSame('Yes', DB::table('cwis.data_cwis')->where('year', $this->year)->where('indicator_code', 'AC-2.5')->value('data_value'));
    }

    public function test_new_generation_rejects_missing_approval_without_creating_results(): void
    {
        $this->onlyAccountability();
        DB::table('cwis.accountability_submissions')->where('id', $this->sourceId)->update(['status' => 'draft']);
        $this->assertSqlFails(function () { DB::select('SELECT public.generate_cwis_with_accountability(?)', [$this->year]); }, 'Approve Accountability data');
        $this->assertSame(0, DB::table('cwis.data_cwis')->where('year', $this->year)->count());
    }
}
