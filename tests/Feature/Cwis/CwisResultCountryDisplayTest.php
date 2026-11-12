<?php

namespace Tests\Feature\Cwis;

use App\Models\User;
use App\Services\Cwis\CwisIndicatorService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CwisResultCountryDisplayTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::whereHas('roles', function ($query) { $query->where('name', 'Super Admin'); })->firstOrFail());
        DB::table('cwis.equity_submissions')->whereNull('deleted_at')->update(['deleted_at' => now()]);
        DB::table('public.site_settings')->where('name', 'active_country')->update(['value' => 'BD']);
        DB::table('cwis.indicator_country')->delete();
        foreach (['EQ-3' => 'BD', 'EQ-1' => 'NP', 'SF-1a' => 'NP'] as $code => $country) {
            DB::table('cwis.indicator_country')->insert([
                'data_source_id' => DB::table('cwis.data_source')->where('indicator_code', $code)->value('id'),
                'country_code' => $country,
            ]);
        }
        foreach ([2024, 2025] as $year) {
            DB::table('cwis.data_cwis')->where('year', $year)->delete();
            foreach (['EQ-1', 'EQ-3', 'SF-1a'] as $code) {
                DB::table('cwis.data_cwis')->insert([
                    'year' => $year, 'indicator_code' => $code, 'data_value' => '12.345',
                ]);
            }
            DB::table('cwis.generation_runs')->updateOrInsert(['year' => $year], [
                'country_code' => 'BD', 'equity_submission_id' => null, 'generated_at' => now(),
            ]);
        }
    }

    public function test_both_pages_apply_selected_country_to_every_year(): void
    {
        foreach ([2024, 2025] as $year) {
            $this->get('/cwis/cwis/cwis-df-mne?year=' . $year)->assertOk()
                ->assertViewHas('countryLabel', 'Bangladesh')
                ->assertSee('Bangladesh')->assertSee('Equity of Subsidies')
                ->assertDontSee('data-indicator-code="EQ-1"', false)
                ->assertDontSee('data-indicator-code="SF-1a"', false);
            $this->get('/cwis/cwis/getall?year=' . $year)->assertOk()
                ->assertViewHas('countryLabel', 'Bangladesh')->assertSee('Bangladesh')
                ->assertSee('Equity of Subsidies')->assertSee('12.345')
                ->assertDontSee('id="sf1aCanvas"', false)->assertDontSee('for="tab-4"', false);
        }
    }

    public function test_results_from_another_country_are_not_relabelled(): void
    {
        DB::table('cwis.generation_runs')->where('year', 2025)->update(['country_code' => 'NP']);
        $this->assertTrue(app(CwisIndicatorService::class)->resultsForCountry(2025, 'BD')->isEmpty());
        foreach (['/cwis/cwis/cwis-df-mne?year=2025', '/cwis/cwis/getall?year=2025'] as $url) {
            $this->get($url)->assertOk()->assertViewHas('resultCountryMismatch', true)
                ->assertSee('Saved results for this year belong to another country selection.')
                ->assertDontSee('12.345');
        }
    }

    public function test_warning_link_is_readable_and_nepal_only_shows_mapped_safety(): void
    {
        DB::table('cwis.data_cwis')->where('year', '>=', 2026)->delete();
        DB::table('cwis.equity_submissions')->insert(['reporting_year' => 2026, 'country_code' => 'BD', 'status' => 'submitted']);
        $this->get('/cwis/cwis/cwis-df-mne?year=2025')->assertOk()
            ->assertSee('id="cwis-equity-review-link"', false)->assertSee('color: #0056b3 !important', false);
        DB::table('public.site_settings')->where('name', 'active_country')->update(['value' => 'NP']);
        DB::table('cwis.generation_runs')->where('year', 2025)->update(['country_code' => 'NP']);
        $this->get('/cwis/cwis/getall?year=2025')->assertOk()->assertSee('Nepal')
            ->assertSee('id="sf1aCanvas"', false)->assertDontSee('id="sf1bCanvas"', false)
            ->assertDontSee('data-indicator-code="EQ-3"', false);
    }

    public function test_approved_generation_year_does_not_warn_about_draft_display_year(): void
    {
        DB::table('cwis.data_cwis')->where('year', '>=', 2026)->delete();
        DB::table('cwis.equity_submissions')->insert(['reporting_year' => 2025, 'country_code' => 'BD', 'status' => 'draft']);
        $id = DB::table('cwis.equity_submissions')->insertGetId([
            'reporting_year' => 2026, 'country_code' => 'BD', 'status' => 'approved',
            'approved_at' => now(), 'approved_by' => auth()->id(),
        ]);
        foreach ([2024, 2025] as $year) {
            $this->get('/cwis/cwis/cwis-df-mne?year=' . $year)->assertOk()
                ->assertViewHas('generationYear', 2026)->assertViewHas('equityApprovalMissing', false)
                ->assertSee('>Add CWIS Data</a>', false)->assertDontSee('Add CWIS Data (2026)')
                ->assertDontSee('id="cwis-equity-review-link"', false);
        }
        $this->assertDatabaseHas('cwis.equity_submissions', ['id' => $id, 'status' => 'approved']);
    }

    public function test_missing_approval_warning_names_and_links_the_generation_year(): void
    {
        DB::table('cwis.data_cwis')->where('year', '>=', 2026)->delete();
        DB::table('cwis.equity_submissions')->insert([
            'reporting_year' => 2025, 'country_code' => 'BD', 'status' => 'approved', 'approved_at' => now(),
        ]);
        $id = DB::table('cwis.equity_submissions')->insertGetId(['reporting_year' => 2026, 'country_code' => 'BD', 'status' => 'submitted']);
        $this->get('/cwis/cwis/cwis-df-mne?year=2025')->assertOk()
            ->assertViewHas('generationYear', 2026)->assertViewHas('equityApprovalMissing', true)
            ->assertViewHas('equitySource', function ($source) use ($id) { return $source->id === $id; })
            ->assertSee('Equity inputs for 2026 (Bangladesh) must be approved');
    }

    public function test_newsurvey_page_only_displays_the_selected_country_indicators(): void
    {
        DB::table('cwis.equity_submissions')->insert([
            'reporting_year' => 2025, 'country_code' => 'BD', 'status' => 'approved',
            'approved_at' => now(), 'approved_by' => auth()->id(),
        ]);
        $url = '/cwis/cwis-df-mne/newsurvey?year=2025&displayText=quantitative';
        $this->get($url)->assertOk()->assertViewHas('countryLabel', 'Bangladesh')
            ->assertSee('Bangladesh')->assertSee('data-indicator-code="EQ-3"', false)
            ->assertDontSee('data-indicator-code="EQ-1"', false)
            ->assertDontSee('data-indicator-code="SF-1a"', false)->assertDontSee('>Safety<', false);
        DB::table('public.site_settings')->where('name', 'active_country')->update(['value' => 'NP']);
        DB::table('cwis.generation_runs')->where('year', 2025)->update(['country_code' => 'NP']);
        $this->get($url)->assertOk()->assertViewHas('countryLabel', 'Nepal')->assertSee('Nepal')
            ->assertSee('data-indicator-code="EQ-1"', false)
            ->assertSee('data-indicator-code="SF-1a"', false)
            ->assertDontSee('data-indicator-code="EQ-3"', false);
    }

    public function test_repeated_generation_reuses_approval_without_changing_it(): void
    {
        $id = DB::table('cwis.equity_submissions')->insertGetId([
            'reporting_year' => 2026, 'country_code' => 'BD', 'status' => 'approved',
            'approved_at' => now(), 'approved_by' => auth()->id(),
        ]);
        DB::table('cwis.data_cwis')->insert(['year' => 2026, 'indicator_code' => 'EQ-3', 'data_value' => '2.000']);
        $before = DB::table('cwis.equity_submissions')->where('id', $id)->first();
        $count = DB::table('cwis.data_cwis')->count();
        $controller = app(\App\Http\Controllers\Cwis\CwisMneController::class);
        $this->assertEquals(200, $controller->cwis(2026)->getStatusCode());
        $this->assertEquals(200, $controller->cwis(2026)->getStatusCode());
        $this->assertEquals($before, DB::table('cwis.equity_submissions')->where('id', $id)->first());
        $this->assertSame($count, DB::table('cwis.data_cwis')->count());
    }
}
