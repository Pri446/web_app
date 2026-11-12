<?php

namespace Tests\Feature\Cwis;

use App\Models\User;
use App\Services\Cwis\CwisResponsibilityWorkflowService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CwisResponsibilityCalculationTest extends TestCase
{
    use DatabaseTransactions;

    private int $year = 2091;
    private int $sourceId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::whereHas('roles', function ($q) { $q->where('name', 'Super Admin'); })->firstOrFail());
        Storage::fake('local');
        DB::table('public.site_settings')->where('name', 'active_country')->update(['value' => 'BD']);
        $this->sourceId = DB::table('cwis.responsibility_submissions')->insertGetId([
            'reporting_year' => $this->year, 'country_code' => 'BD', 'status' => 'approved',
            'approved_by' => auth()->id(), 'approved_at' => now(),
        ]);
        foreach (config('cwis_responsibility.groups') as $questions) {
            foreach ($questions as $code => $label) {
                DB::table('cwis.responsibility_answers')->insert([
                    'submission_id' => $this->sourceId, 'indicator_code' => $code, 'answer' => 'No',
                ]);
            }
        }
    }

    private function answer(string $code, array $values): void
    {
        DB::table('cwis.responsibility_answers')->where('submission_id', $this->sourceId)->where('indicator_code', $code)->update($values);
    }

    private function context(string $country = 'BD'): void
    {
        DB::table('cwis.generation_runs')->insert(['year' => $this->year, 'country_code' => $country, 'generated_at' => now()]);
    }

    private function onlyResponsibility(): void
    {
        $ids = DB::table('cwis.data_source')->where('outcome', 'responsibility')->pluck('id');
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

    public function test_all_twenty_one_individual_calculators_and_public_updaters(): void
    {
        DB::select('SELECT cwis.prepare_responsibility_generation(?)', [$this->year]);
        $this->context();
        $definitions = DB::select('SELECT * FROM cwis.responsibility_function_map()');
        $this->assertCount(21, $definitions);
        foreach ($definitions as $definition) {
            $this->assertSame('No', DB::selectOne('SELECT cwis.calculate_rs_' . $definition->suffix . '(?) AS value', [$this->year])->value);
            DB::select('SELECT public.update_data_into_cwis_table_rs_' . $definition->suffix . '_newsan(?)', [$this->year]);
            $this->assertSame('No', DB::table('cwis.data_cwis')->where('year', $this->year)->where('indicator_code', $definition->code)->value('data_value'));
        }
        $this->assertSame(21, DB::table('cwis.data_cwis')->where('year', $this->year)->where('outcome', 'responsibility')->count());
    }

    public function test_yes_no_missing_evidence_and_independent_questions(): void
    {
        $this->answer('RS-1a.1', ['answer' => 'Yes', 'document_path' => 'mandate.pdf']);
        $this->answer('RS-1a.2', ['answer' => 'Yes']);
        $this->answer('RS-2', ['answer' => null]);
        $this->answer('RS-1b.8', ['answer' => 'Yes', 'document_path' => 'service.pdf']);
        DB::select('SELECT cwis.prepare_responsibility_generation(?)', [$this->year]);
        $result = DB::selectOne('SELECT cwis.calculate_rs_1a_1(?) AS a, cwis.calculate_rs_1a_2(?) AS b,
            cwis.calculate_rs_2(?) AS c, cwis.calculate_rs_1b_8(?) AS d, cwis.calculate_rs_5(?) AS e', array_fill(0, 5, $this->year));
        $this->assertSame('Yes', $result->a);
        $this->assertNull($result->b);
        $this->assertNull($result->c);
        $this->assertSame('Yes', $result->d);
        $this->assertSame('No', $result->e);
    }

    public function test_snapshot_keeps_previous_answer_until_an_explicit_rebuild(): void
    {
        DB::select('SELECT cwis.prepare_responsibility_generation(?)', [$this->year]);
        $this->answer('RS-5', ['answer' => 'Yes', 'document_path' => 'revenue.pdf']);
        $this->assertSame('No', DB::selectOne('SELECT cwis.calculate_rs_5(?) AS value', [$this->year])->value);
        DB::select('SELECT cwis.prepare_responsibility_generation(?)', [$this->year]);
        $this->assertSame('Yes', DB::selectOne('SELECT cwis.calculate_rs_5(?) AS value', [$this->year])->value);
    }

    public function test_unapproved_wrong_year_and_wrong_country_sources_are_rejected(): void
    {
        DB::table('cwis.responsibility_submissions')->where('id', $this->sourceId)->update(['status' => 'submitted']);
        $this->assertSqlFails(function () { DB::select('SELECT cwis.prepare_responsibility_generation(?)', [$this->year]); }, 'Approve Responsibility data');
        DB::table('cwis.responsibility_submissions')->where('id', $this->sourceId)->update(['status' => 'approved']);
        $this->assertSqlFails(function () { DB::select('SELECT cwis.prepare_responsibility_generation(?)', [$this->year + 1]); }, 'Approve Responsibility data');
        DB::select('SELECT cwis.prepare_responsibility_generation(?)', [$this->year]);
        DB::table('public.site_settings')->where('name', 'active_country')->update(['value' => 'ALL']);
        $this->assertSqlFails(function () { DB::select('SELECT cwis.calculate_rs_5(?)', [$this->year]); }, 'country mismatch');
        DB::table('public.site_settings')->where('name', 'active_country')->update(['value' => 'NP']);
        $this->assertSqlFails(function () { DB::select('SELECT cwis.calculate_rs_5(?)', [$this->year]); }, 'unavailable');
    }

    public function test_backed_up_rebuild_preserves_other_outcomes_and_is_visible_in_dashboard_and_export(): void
    {
        $this->context();
        DB::table('cwis.data_cwis')->insert([
            ['year' => $this->year, 'indicator_code' => 'EQ-3', 'outcome' => 'equity', 'data_value' => '7.777'],
            ['year' => $this->year, 'indicator_code' => 'SS-2', 'outcome' => 'sustainability', 'data_value' => '2.222'],
        ]);
        Storage::disk('local')->put('mandate.pdf', 'evidence');
        $this->answer('RS-1a.1', ['answer' => 'Yes', 'document_path' => 'mandate.pdf']);
        $this->post('/cwis/responsibility-data/' . $this->sourceId . '/calculate')->assertSessionHasNoErrors()
            ->assertRedirect(route('cwis.generator-data.index', ['country_code' => 'BD']));
        $this->assertSame('7.777', DB::table('cwis.data_cwis')->where('year', $this->year)->where('indicator_code', 'EQ-3')->value('data_value'));
        $this->assertSame('2.222', DB::table('cwis.data_cwis')->where('year', $this->year)->where('indicator_code', 'SS-2')->value('data_value'));
        $this->assertSame('Yes', DB::table('cwis.data_cwis')->where('year', $this->year)->where('indicator_code', 'RS-1a.1')->value('data_value'));
        $this->assertNotEmpty(Storage::disk('local')->files('cwis/result-backups'));
        $this->get('/cwis/cwis/getall?year=' . $this->year)->assertOk()->assertSee('tab-responsibility')->assertSee('Clear and Non-overlapping Mandate');
        $this->assertStringContainsString('Responsibility', (new \App\Exports\MneCsvExport($this->year))->view()->render());
        $this->get('/cwis/responsibility-data/' . $this->sourceId)->assertOk()->assertSee('Rebuild CWIS Results')
            ->assertSee('cwis-rebuild-loader')->assertDontSee('window.confirm', false);
    }

    public function test_missing_physical_file_blocks_rebuild_and_preserves_existing_results(): void
    {
        $this->context();
        DB::table('cwis.data_cwis')->insert(['year' => $this->year, 'indicator_code' => 'RS-5', 'outcome' => 'responsibility', 'data_value' => 'No']);
        $this->answer('RS-5', ['answer' => 'Yes', 'document_path' => 'missing.pdf']);
        $this->post('/cwis/responsibility-data/' . $this->sourceId . '/calculate')->assertSessionHasErrors('RS-5');
        $this->assertSame('No', DB::table('cwis.data_cwis')->where('year', $this->year)->where('indicator_code', 'RS-5')->value('data_value'));
    }

    public function test_country_mismatch_and_duplicate_results_fail_without_partial_writes(): void
    {
        $this->context('NP');
        DB::table('cwis.data_cwis')->insert(['year' => $this->year, 'indicator_code' => 'RS-5', 'outcome' => 'responsibility', 'data_value' => 'No']);
        $this->post('/cwis/responsibility-data/' . $this->sourceId . '/calculate')->assertSessionHasErrors('calculation');
        DB::table('cwis.generation_runs')->where('year', $this->year)->update(['country_code' => 'BD']);
        DB::table('cwis.data_cwis')->insert(['year' => $this->year, 'indicator_code' => 'RS-5', 'outcome' => 'responsibility', 'data_value' => 'No']);
        $this->post('/cwis/responsibility-data/' . $this->sourceId . '/calculate')->assertSessionHasErrors('calculation');
        $this->assertSame(2, DB::table('cwis.data_cwis')->where('year', $this->year)->count());
        $this->assertFalse(DB::table('cwis.responsibility_generation_runs')->where('year', $this->year)->exists());
    }

    public function test_new_generation_dispatches_responsibility_and_does_not_overwrite_existing_year(): void
    {
        $this->onlyResponsibility();
        $this->post('/cwis/responsibility-data/' . $this->sourceId . '/calculate')->assertSessionHasNoErrors();
        $this->assertSame(21, DB::table('cwis.data_cwis')->where('year', $this->year)->where('outcome', 'responsibility')->count());
        $this->assertSame($this->sourceId, (int) DB::table('cwis.responsibility_generation_runs')->where('year', $this->year)->value('submission_id'));
        DB::table('cwis.data_cwis')->where('year', $this->year)->where('indicator_code', 'RS-5')->update(['data_value' => 'Yes']);
        DB::select('SELECT public.generate_cwis_with_responsibility(?)', [$this->year]);
        $this->assertSame('Yes', DB::table('cwis.data_cwis')->where('year', $this->year)->where('indicator_code', 'RS-5')->value('data_value'));
    }

    public function test_new_generation_rejects_missing_approval_without_creating_results(): void
    {
        $this->onlyResponsibility();
        DB::table('cwis.responsibility_submissions')->where('id', $this->sourceId)->update(['status' => 'draft']);
        $this->assertSqlFails(function () { DB::select('SELECT public.generate_cwis_with_responsibility(?)', [$this->year]); }, 'Approve Responsibility data');
        $this->assertSame(0, DB::table('cwis.data_cwis')->where('year', $this->year)->count());
    }
}
