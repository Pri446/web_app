<?php

namespace Tests\Feature\Cwis;

use App\Models\User;
use Database\Seeders\Cwis\Equity\CwisFormFieldsSeeder;
use Database\Seeders\CwisIndicatorCountryExampleSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CwisSubmissionYearTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::query()->firstOrFail());
        DB::table('public.site_settings')->where('name','active_country')->update(['value' => 'BD']);
        DB::table('cwis.equity_submissions')->whereNull('deleted_at')->update(['deleted_at' => now()]);
        DB::table('cwis.indicator_country')->delete();
        $this->seed(CwisIndicatorCountryExampleSeeder::class);
        $this->seed(CwisFormFieldsSeeder::class);
    }

    private function submission(int $year, string $country, string $status = 'draft', bool $deleted = false): int
    {
        return DB::table('cwis.equity_submissions')->insertGetId([
            'reporting_year' => $year, 'country_code' => $country, 'status' => $status,
            'deleted_at' => $deleted ? now() : null,
        ]);
    }

    public function test_add_years_exclude_saved_submissions_but_filters_keep_every_year(): void
    {
        $year = now()->year;
        foreach (['draft','submitted','approved'] as $offset => $status) {
            $this->submission($year - $offset, 'BD', $status);
        }
        $this->submission($year - 3, 'NP');
        $this->submission($year - 4, 'BD', 'draft', true);
        $response = $this->get('/cwis/generator-data?country_code=BD&year=' . $year);
        $response->assertOk()->assertViewHas('availableInputYears', function ($years) use ($year) {
            return $years->all() === [$year - 3, $year - 4, $year - 5];
        })->assertViewHas('years', function ($years) { return $years->count() === 6; });
        $dom = new \DOMDocument();
        @$dom->loadHTML($response->getContent());
        $xpath = new \DOMXPath($dom);
        $this->assertSame(0, $xpath->query('//select[@id="add-indicator-year"]/option')->length);
        $this->assertSame(1, $xpath->query('//*[@id="add-indicator-year-group"][@hidden]')->length);
        $this->assertSame(7, $xpath->query('//select[@id="generator-data-year"]/option')->length);
    }

    public function test_direct_create_url_redirects_to_existing_records(): void
    {
        foreach (['draft','submitted','approved'] as $offset => $status) {
            $year = now()->year - $offset;
            $this->submission($year, 'BD', $status);
            $this->get('/cwis/generator-data/create?country_code=BD&year=' . $year)
                ->assertRedirect(action('Cwis\\CwisGeneratorDataController@index', [
                    'country_code' => 'BD', 'year' => $year, 'service_outcome' => 'equity',
                ]))->assertSessionHas('cwis_input_notice');
        }
    }

    public function test_duplicate_save_is_rejected_without_creating_source_rows(): void
    {
        $payload = ['country_code' => 'BD', 'year' => now()->year, 'save_mode' => 'draft'];
        $this->post('/cwis/generator-data', $payload)->assertSessionHasNoErrors()
            ->assertRedirect(action('Cwis\\CwisGeneratorDataController@index', [
                'country_code' => 'BD',
            ]))->assertSessionHas('cwis_saved');
        $counts = [];
        foreach (['equity_submissions','equity_subsidies','sanitation_personnel_snapshot','sanitation_worker_policy','equity_documents'] as $table) {
            $counts[$table] = DB::table('cwis.' . $table)->count();
        }
        $this->post('/cwis/generator-data', $payload)->assertSessionHasErrors('year');
        foreach ($counts as $table => $count) {
            $this->assertSame($count, DB::table('cwis.' . $table)->count(), $table);
        }
    }

    public function test_country_scoping_and_soft_deleted_submissions_do_not_block_new_records(): void
    {
        $this->submission(now()->year, 'NP');
        $this->submission(now()->year, 'BD', 'draft', true);
        $this->get('/cwis/generator-data/create?country_code=BD&year=' . now()->year)->assertOk();
        $this->post('/cwis/generator-data', [
            'country_code' => 'BD', 'year' => now()->year, 'save_mode' => 'draft',
        ])->assertSessionHasNoErrors()->assertSessionHas('cwis_saved');
    }

    public function test_add_is_disabled_when_every_year_is_used(): void
    {
        foreach (range(now()->year, now()->year - 5) as $year) {
            $this->submission($year, 'BD');
        }
        $response = $this->get('/cwis/generator-data?country_code=BD');
        $response->assertOk()->assertViewHas('availableInputYears', function ($years) { return $years->isEmpty(); });
        $dom = new \DOMDocument();
        @$dom->loadHTML($response->getContent());
        $xpath = new \DOMXPath($dom);
        $this->assertSame(1, $xpath->query('//button[@id="add-indicator-data"][@disabled]')->length);
        $this->assertSame(1, $xpath->query('//form[@id="add-indicator-form"]//button[@type="submit"][@disabled]')->length);
    }

    public function test_saving_another_year_preserves_old_data_and_default_listing_shows_both(): void
    {
        $oldYear = now()->year - 3;
        $newYear = now()->year - 4;
        $oldId = $this->submission($oldYear,'BD');
        DB::table('cwis.equity_subsidies')->insert(['submission_id' => $oldId,'total_subsidies_amount_nss' => 777]);
        $this->post('/cwis/generator-data', [
            'country_code' => 'BD', 'year' => $newYear, 'save_mode' => 'draft',
            'inputs' => ['total_subsidies_amount_nss' => 1000],
        ])->assertSessionHasNoErrors()->assertRedirect(action('Cwis\\CwisGeneratorDataController@index',['country_code' => 'BD']));
        $this->assertEquals($oldYear,DB::table('cwis.equity_submissions')->where('id',$oldId)->value('reporting_year'));
        $this->assertEquals(777,DB::table('cwis.equity_subsidies')->where('submission_id',$oldId)->value('total_subsidies_amount_nss'));
        $response = $this->getJson('/cwis/generator-data/data?country_code=BD');
        $response->assertOk()->assertJsonPath('recordsTotal',2);
        $actualYears = array_column($response->json('data'),'year');
        sort($actualYears);
        $this->assertSame([$newYear,$oldYear],$actualYears);
        $this->getJson('/cwis/generator-data/data?country_code=BD&year=' . $oldYear)->assertJsonPath('recordsTotal',1);
        $this->withSession(['cwis_saved' => 'Saved successfully'])->get('/cwis/generator-data?country_code=BD')->assertOk()->assertSee('All Years')
            ->assertSee('toastr.success(',false)->assertDontSee('class="alert alert-success"',false);
    }
}
