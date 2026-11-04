<?php

namespace Tests\Feature\Cwis;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CwisSharedListingTest extends TestCase
{
    use DatabaseTransactions;

    private function users(): array
    {
        return [
            User::whereHas('roles', function ($query) { $query->where('name','Super Admin'); })->firstOrFail(),
            User::whereHas('roles', function ($query) { $query->where('name','Municipality - Sanitation Department'); })->firstOrFail(),
        ];
    }

    public function test_admin_and_sanitation_see_the_same_records_regardless_of_creator(): void
    {
        [$admin, $sanitation] = $this->users();
        DB::table('public.site_settings')->where('name','active_country')->update(['value' => 'ALL']);
        DB::table('cwis.equity_submissions')->whereNull('deleted_at')->update(['deleted_at' => now()]);
        $expected = [];
        foreach ([$admin, $sanitation] as $creator) {
            $expected[] = DB::table('cwis.equity_submissions')->insertGetId([
                'reporting_year' => now()->year, 'country_code' => 'ALL', 'status' => 'draft', 'created_by' => $creator->id,
            ]);
        }
        DB::table('cwis.equity_submissions')->insert([
            ['reporting_year' => now()->year, 'country_code' => 'BD', 'status' => 'approved'],
            ['reporting_year' => now()->year - 1, 'country_code' => 'ALL', 'status' => 'submitted'],
        ]);
        sort($expected);
        foreach ([$admin, $sanitation] as $viewer) {
            $this->actingAs($viewer);
            $this->get('/cwis/generator-data')->assertOk()->assertViewHas('country','ALL')
                ->assertViewHas('year', function ($year) { return $year === null; })->assertSee('#data-table th', false)
                ->assertViewHas('page_title', 'CWIS Indicator Generation Data')
                ->assertSee('CWIS Indicator Generation Data')
                ->assertDontSee('Submission ID')
                ->assertDontSee("{ data: 'id', name: 'id' }", false)
                ->assertSee("order: [[0, 'desc'], [3, 'desc']]", false);
            $this->get('/cwis/generator-data/' . $expected[0] . '?country_code=ALL')
                ->assertOk()->assertSee('#indicator-detail-back:hover', false)
                ->assertSee('color: #fff !important;', false);
            $response = $this->getJson('/cwis/generator-data/data?year=' . now()->year);
            $response->assertOk()->assertJsonPath('recordsTotal',2);
            $response->assertJsonPath('data.0.status_label','Draft');
            $actual = array_column($response->json('data'),'id');
            sort($actual);
            $this->assertSame($expected, $actual);
        }
    }

    public function test_stale_country_links_and_ajax_requests_follow_shared_settings_for_both_roles(): void
    {
        DB::table('public.site_settings')->where('name','active_country')->update(['value' => 'ALL']);
        foreach ($this->users() as $viewer) {
            $this->actingAs($viewer);
            $url = action('Cwis\\CwisGeneratorDataController@index', ['country_code' => 'ALL', 'year' => now()->year]);
            $this->get('/cwis/generator-data?country_code=BD&year=' . now()->year)->assertRedirect($url);
            $this->getJson('/cwis/generator-data/data?country_code=BD&year=' . now()->year)
                ->assertStatus(409)->assertJsonPath('country_changed',true)->assertJsonPath('redirect',$url);
        }
    }
}
