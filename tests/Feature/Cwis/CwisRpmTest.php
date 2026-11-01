<?php

namespace Tests\Feature\Cwis;

use App\Models\User;
use App\Services\Cwis\CwisRpmFormService;
use Database\Seeders\CwisRpmDataSourceSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CwisRpmTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::whereHas('roles', function ($q) { $q->where('name', 'Super Admin'); })->firstOrFail());
        DB::table('public.site_settings')->where('name', 'active_country')->update(['value' => 'BD']);
        Storage::fake('local');
        $this->seed(CwisRpmDataSourceSeeder::class);
        DB::table('cwis.rpm_generation_runs')->delete();
        DB::table('cwis.rpm_submissions')->delete();
    }

    private function payload(int $year = null): array
    {
        $answers = [];
        foreach (app(CwisRpmFormService::class)->questions('BD') as $code => $label) {
            $answers[str_replace('.', '_', $code)] = 'No';
        }
        return ['year' => $year ?? now()->year, 'country_code' => 'BD', 'save_mode' => 'submitted', 'answers' => $answers];
    }

    private function source(int $year): int
    {
        $id = DB::table('cwis.rpm_submissions')->insertGetId([
            'reporting_year' => $year, 'country_code' => 'BD', 'status' => 'approved',
            'approved_by' => auth()->id(), 'approved_at' => now(),
        ]);
        foreach (app(CwisRpmFormService::class)->questions('BD') as $code => $label) {
            DB::table('cwis.rpm_answers')->insert(['submission_id' => $id, 'indicator_code' => $code, 'answer' => 'No']);
        }
        return $id;
    }

    private function sqlFails(callable $action, string $message): void
    {
        try {
            DB::transaction($action);
            $this->fail('Expected SQL guard failure');
        } catch (\Illuminate\Database\QueryException $e) {
            $this->assertStringContainsString($message, $e->getMessage());
        }
    }

    public function test_catalogue_picker_and_country_availability(): void
    {
        $this->seed(CwisRpmDataSourceSeeder::class);
        $this->assertSame(43, DB::table('cwis.data_source')->where('outcome', 'rpm')->count());
        foreach (['ALL', 'BD'] as $country) { $this->assertCount(43, app(CwisRpmFormService::class)->questions($country)); }
        $this->assertCount(0, app(CwisRpmFormService::class)->questions('NP'));
        $this->get('/cwis/generator-data')->assertOk()->assertSee('id="add-outcome-rpm"', false);
        $this->get('/cwis/generator-data/create?service_outcome=rpm&year=' . now()->year)
            ->assertRedirect(route('cwis.rpm.create', ['year' => now()->year]));
        $this->get('/cwis/rpm-data/create?year=' . now()->year)->assertOk()->assertSee('RPM-4.14')
            ->assertSee('Save as Draft')->assertSee('Submit for Approval')->assertSee('novalidate', false);
        DB::table('public.site_settings')->where('name', 'active_country')->update(['value' => 'NP']);
        $this->get('/cwis/generator-data')->assertOk()->assertDontSee('id="add-outcome-rpm"', false);
        $this->get('/cwis/rpm-data/create?year=' . now()->year)->assertNotFound();
    }

    public function test_drafts_validation_evidence_reuse_approval_and_read_only_state(): void
    {
        $results = DB::table('cwis.data_cwis')->count();
        $this->post('/cwis/rpm-data', ['year' => now()->year, 'country_code' => 'BD', 'save_mode' => 'draft'])
            ->assertSessionHasNoErrors()->assertSessionHas('cwis_saved');
        $id = DB::table('cwis.rpm_submissions')->where('reporting_year', now()->year)->value('id');
        $review = '/cwis/rpm-data/' . $id . '/review';
        $this->get('/cwis/rpm-data/' . $id)->assertOk()->assertSee('Approve');
        $this->get($review)->assertOk()->assertSee('Save and Approve');
        $this->from($review)->post('/cwis/rpm-data/' . $id . '/approve', ['year' => now()->year, 'country_code' => 'BD'])
            ->assertRedirect($review)->assertSessionHasErrors('answers.RPM-1a_1');
        $payload = $this->payload();
        $payload['answers']['RPM-1a_1'] = 'Yes';
        $this->post('/cwis/rpm-data/' . $id . '/approve', $payload)->assertSessionHasErrors('evidence.RPM-1a_1');
        $payload['evidence']['RPM-1a_1'] = UploadedFile::fake()->create('budget.pdf', 4, 'application/pdf');
        $payload['save_mode'] = 'draft';
        $this->put('/cwis/rpm-data/' . $id, $payload)->assertSessionHasNoErrors();
        $answer = DB::table('cwis.rpm_answers')->where('submission_id', $id)->where('indicator_code', 'RPM-1a.1')->first();
        $this->assertStringStartsWith('cwis/rpm-documents/', $answer->document_path);
        Storage::disk('local')->assertExists($answer->document_path);
        $this->get('/cwis/rpm-data/' . $id . '/documents/' . $answer->id . '?preview=1')->assertOk();
        unset($payload['evidence']);
        $payload['save_mode'] = 'submitted';
        $this->put('/cwis/rpm-data/' . $id, $payload)->assertSessionHasNoErrors();
        $this->assertSame('submitted', DB::table('cwis.rpm_submissions')->find($id)->status);
        $this->post('/cwis/rpm-data/' . $id . '/approve', $payload)->assertSessionHasNoErrors()->assertSessionHas('cwis_saved')
            ->assertRedirect(route('cwis.generator-data.index', ['country_code' => 'BD']));
        $this->assertSame('approved', DB::table('cwis.rpm_submissions')->find($id)->status);
        $this->assertSame($results, DB::table('cwis.data_cwis')->count());
        $this->get($review)->assertForbidden();
        $this->put('/cwis/rpm-data/' . $id, $payload)->assertForbidden();
        $this->get('/cwis/rpm-data/' . $id)->assertOk()->assertSee('Rebuild CWIS Results');
    }

    public function test_year_isolation_unknown_fields_country_and_permissions(): void
    {
        foreach ([now()->year, now()->year - 1] as $year) {
            $this->post('/cwis/rpm-data', ['year' => $year, 'country_code' => 'BD'])->assertSessionHasNoErrors();
        }
        $this->assertSame(2, DB::table('cwis.rpm_submissions')->count());
        $this->post('/cwis/rpm-data', ['year' => now()->year, 'country_code' => 'BD'])->assertSessionHasErrors('year');
        $this->get('/cwis/generator-data')->assertViewHas('inputYearsByOutcome', function ($years) {
            return !in_array(now()->year, $years['rpm']) && !in_array(now()->year - 1, $years['rpm']);
        });
        $this->getJson('/cwis/generator-data/data?service_outcome=rpm&draw=1&start=0&length=10')
            ->assertOk()->assertJsonFragment(['service_outcome' => 'Resource Planning and Management', 'status_label' => 'Draft']);
        $id = DB::table('cwis.rpm_submissions')->where('reporting_year', now()->year)->value('id');
        $payload = $this->payload();
        $payload['answers']['RPM-99'] = 'Yes';
        $this->put('/cwis/rpm-data/' . $id, $payload)->assertSessionHasErrors('answers');
        $payload = $this->payload(now()->year - 1);
        $this->put('/cwis/rpm-data/' . $id, $payload)->assertSessionHasErrors('year');
        DB::table('public.site_settings')->where('name', 'active_country')->update(['value' => 'ALL']);
        $this->get('/cwis/rpm-data/' . $id)->assertNotFound();
        $this->post('/cwis/rpm-data', $this->payload())->assertSessionHasErrors('country_code');
        $user = new User();
        $user->id = -99;
        $user->status = 1;
        $this->actingAs($user)->get('/cwis/rpm-data/create?year=' . now()->year)->assertForbidden();
    }

    public function test_all_43_calculators_updaters_snapshot_and_guards(): void
    {
        $year = 2092;
        $id = $this->source($year);
        DB::table('cwis.generation_runs')->insert(['year' => $year, 'country_code' => 'BD', 'generated_at' => now()]);
        DB::select('SELECT cwis.prepare_rpm_generation(?)', [$year]);
        $map = DB::select('SELECT * FROM cwis.rpm_function_map()');
        $this->assertCount(43, $map);
        foreach ($map as $entry) {
            $this->assertSame('No', DB::selectOne('SELECT cwis.calculate_rpm_' . $entry->suffix . '(?) AS value', [$year])->value);
            DB::select('SELECT public.update_data_into_cwis_table_rpm_' . $entry->suffix . '_newsan(?)', [$year]);
        }
        $this->assertSame(43, DB::table('cwis.data_cwis')->where('year', $year)->where('outcome', 'rpm')->count());
        DB::table('cwis.rpm_answers')->where('submission_id', $id)->where('indicator_code', 'RPM-4.14')->update(['answer' => 'Yes']);
        $this->assertSame('No', DB::selectOne('SELECT cwis.calculate_rpm_4_14(?) AS value', [$year])->value);
        DB::select('SELECT cwis.prepare_rpm_generation(?)', [$year]);
        $this->assertNull(DB::selectOne('SELECT cwis.calculate_rpm_4_14(?) AS value', [$year])->value);
        DB::table('cwis.rpm_answers')->where('submission_id', $id)->where('indicator_code', 'RPM-4.14')->update(['document_path' => 'income.pdf']);
        DB::select('SELECT cwis.prepare_rpm_generation(?)', [$year]);
        $this->assertSame('Yes', DB::selectOne('SELECT cwis.calculate_rpm_4_14(?) AS value', [$year])->value);
        $this->sqlFails(function () use ($year) { DB::select('SELECT cwis.prepare_rpm_generation(?)', [$year + 1]); }, 'Approve RPM data');
        DB::table('public.site_settings')->where('name', 'active_country')->update(['value' => 'ALL']);
        $this->sqlFails(function () use ($year) { DB::select('SELECT cwis.calculate_rpm_4_14(?)', [$year]); }, 'country mismatch');
        DB::table('public.site_settings')->where('name', 'active_country')->update(['value' => 'BD']);
        DB::table('cwis.rpm_submissions')->where('id', $id)->update(['status' => 'submitted']);
        $this->sqlFails(function () use ($year) { DB::select('SELECT cwis.prepare_rpm_generation(?)', [$year]); }, 'Approve RPM data');
    }

    public function test_rebuild_preserves_other_outcomes_and_uses_same_dashboard_and_export_context(): void
    {
        $year = 2092;
        $id = $this->source($year);
        DB::table('cwis.generation_runs')->insert(['year' => $year, 'country_code' => 'BD', 'generated_at' => now()]);
        DB::table('cwis.data_cwis')->insert(['year' => $year, 'indicator_code' => 'AC-1a.1', 'outcome' => 'accountability', 'data_value' => 'Yes']);
        Storage::disk('local')->put('strategy.pdf', 'evidence');
        DB::table('cwis.rpm_answers')->where('submission_id', $id)->where('indicator_code', 'RPM-4.1')
            ->update(['answer' => 'Yes', 'document_path' => 'strategy.pdf']);
        $this->post('/cwis/rpm-data/' . $id . '/calculate')->assertSessionHasNoErrors()->assertSessionHas('cwis_saved');
        $this->assertSame('Yes', DB::table('cwis.data_cwis')->where('year', $year)->where('indicator_code', 'AC-1a.1')->value('data_value'));
        $this->assertSame('Yes', DB::table('cwis.data_cwis')->where('year', $year)->where('indicator_code', 'RPM-4.1')->value('data_value'));
        $this->assertNotEmpty(Storage::disk('local')->files('cwis/result-backups'));
        $this->get('/cwis/cwis/getall?year=' . $year)->assertOk()->assertSee('tab-rpm')->assertSee('#BF4E14', false);
        $this->get('/cwis/cwis/cwis-df-mne?year=' . $year)->assertOk()->assertSee('Resource Planning and Management')
            ->assertSee('data-indicator-code="RPM-4.14"', false);
        $this->assertStringContainsString('Budget line exists for urban sanitation', (new \App\Exports\MneCsvExport($year))->view()->render());
        $view = view('cwis.cwis-dashboard.chart-layout.rpm-pdf-cards', [
            'rpmIndicators' => app(\App\Services\Cwis\CwisIndicatorService::class)->forCountry('BD')->where('outcome', 'rpm'),
            'rpmValues' => app(\App\Services\Cwis\CwisIndicatorService::class)->resultsForCountry($year, 'BD')->pluck('data_value', 'indicator_code'),
            'countryLabel' => 'Bangladesh', 'reportingYear' => $year,
        ])->render();
        $this->assertStringContainsString('#BF4E14', $view);
        $this->assertStringContainsString('Resource Planning and Management', $view);
        $this->assertStringContainsString('Yes', $view);
        Storage::disk('local')->delete('strategy.pdf');
        $this->post('/cwis/rpm-data/' . $id . '/calculate')->assertSessionHasErrors('RPM-4_1');
        $this->assertSame('Yes', DB::table('cwis.data_cwis')->where('year', $year)->where('indicator_code', 'RPM-4.1')->value('data_value'));
        DB::table('cwis.generation_runs')->where('year', $year)->update(['country_code' => 'NP']);
        DB::table('cwis.rpm_answers')->where('submission_id', $id)->where('indicator_code', 'RPM-4.1')->update(['answer' => 'No']);
        $this->post('/cwis/rpm-data/' . $id . '/calculate')->assertSessionHasErrors('calculation');
    }

    public function test_first_generation_requires_approval_and_does_not_overwrite_existing_year(): void
    {
        $year = 2092;
        $id = $this->source($year);
        $ids = DB::table('cwis.data_source')->where('outcome', 'rpm')->pluck('id');
        DB::table('cwis.indicator_country')->where('country_code', 'BD')->whereNotIn('data_source_id', $ids)->delete();
        DB::table('cwis.rpm_submissions')->where('id', $id)->update(['status' => 'draft']);
        $this->sqlFails(function () use ($year) { DB::select('SELECT public.generate_cwis_with_rpm(?)', [$year]); }, 'Approve RPM data');
        $this->assertSame(0, DB::table('cwis.data_cwis')->where('year', $year)->count());
        DB::table('cwis.rpm_submissions')->where('id', $id)->update(['status' => 'approved']);
        $this->post('/cwis/rpm-data/' . $id . '/calculate')->assertSessionHasNoErrors();
        $this->assertSame(43, DB::table('cwis.data_cwis')->where('year', $year)->where('outcome', 'rpm')->count());
        DB::table('cwis.data_cwis')->where('year', $year)->where('indicator_code', 'RPM-1a.1')->update(['data_value' => 'Yes']);
        DB::select('SELECT public.generate_cwis_with_rpm(?)', [$year]);
        $this->assertSame('Yes', DB::table('cwis.data_cwis')->where('year', $year)->where('indicator_code', 'RPM-1a.1')->value('data_value'));
    }
}
