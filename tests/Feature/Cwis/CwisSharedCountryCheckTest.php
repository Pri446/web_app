<?php

namespace Tests\Feature\Cwis;

use App\Services\Cwis\CwisIndicatorService;
use Database\Seeders\CwisIndicatorCountryExampleSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CwisSharedCountryCheckTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        DB::table('cwis.indicator_country')->delete();
        $this->seed(CwisIndicatorCountryExampleSeeder::class);
    }

    public function test_all_registered_indicators_share_the_country_rules(): void
    {
        $codes = DB::table('cwis.data_source')->pluck('indicator_code');
        foreach (['ALL', 'NP', 'BD'] as $country) {
            $expected = $country === 'ALL' ? $codes : DB::table('cwis.data_source as ds')
                ->join('cwis.indicator_country as m', 'm.data_source_id', '=', 'ds.id')
                ->where('m.country_code', $country)->pluck('ds.indicator_code');
            $this->assertEqualsCanonicalizing($expected->all(), app(CwisIndicatorService::class)->forCountry($country)->pluck('indicator_code')->all());
            foreach ($codes as $code) {
                $this->assertSame($expected->contains($code), DB::selectOne(
                    'SELECT cwis.indicator_applies_to_country(?, ?) AS value', [$country, $code]
                )->value, $country . ' / ' . $code);
            }
        }
        $this->assertFalse(DB::selectOne("SELECT cwis.indicator_applies_to_country('ALL', 'UNKNOWN-CODE') AS value")->value);
    }

    public function test_safety_and_future_outcomes_do_not_require_equity_approval(): void
    {
        $before = DB::table('cwis.equity_submissions')->count();
        $this->assertSame('SF-1d', DB::selectOne("SELECT cwis.require_indicator_country('NP', 'SF-1d') AS value")->value);
        $id = ((int) DB::table('cwis.data_source')->max('id')) + 1;
        DB::table('cwis.data_source')->insert(['id' => $id, 'indicator_code' => 'TEST-SS', 'outcome' => 'sustainability', 'label' => 'Test indicator']);
        DB::table('cwis.indicator_country')->insert(['data_source_id' => $id, 'country_code' => 'BD']);
        $this->assertSame('TEST-SS', DB::selectOne("SELECT cwis.require_indicator_country('BD', 'TEST-SS') AS value")->value);
        $this->assertFalse(DB::selectOne("SELECT cwis.indicator_applies_to_country('NP', 'TEST-SS') AS value")->value);
        $this->assertSame($before, DB::table('cwis.equity_submissions')->count());
    }

    public function test_invalid_country_and_excluded_indicators_are_rejected(): void
    {
        foreach ([[null, 'SF-1a', 'Invalid country'], ['', 'SF-1a', 'Invalid country'],
            ['XX', 'SF-1a', 'Invalid country'], ['BD', 'SF-1d', 'is unavailable'],
            ['ALL', 'UNKNOWN-CODE', 'is unavailable']] as [$country, $code, $message]) {
            try {
                DB::transaction(function () use ($country, $code) {
                    DB::selectOne('SELECT cwis.require_indicator_country(?, ?)', [$country, $code]);
                });
                $this->fail('Expected the country check to reject the request.');
            } catch (\Illuminate\Database\QueryException $error) {
                $this->assertStringContainsString($message, $error->getMessage());
            }
        }
    }

    public function test_equity_approval_guard_cannot_be_used_for_safety(): void
    {
        $id = DB::table('cwis.equity_submissions')->insertGetId([
            'reporting_year' => now()->year, 'country_code' => 'ALL', 'status' => 'approved', 'approved_at' => now(),
        ]);
        try {
            DB::transaction(function () use ($id) {
                DB::selectOne("SELECT cwis.require_approved_equity_submission(?, 'ALL', 'SF-1a')", [$id]);
            });
            $this->fail('An Equity submission was accepted for Safety.');
        } catch (\Illuminate\Database\QueryException $error) {
            $this->assertStringContainsString('not an Equity indicator', $error->getMessage());
        }
    }

    public function test_shared_check_migration_can_be_rolled_back_and_reinstalled(): void
    {
        require_once database_path('migrations/cwis/equity/2026_10_13_000002_share_country_checks_across_cwis_indicators.php');
        $migration = new \ShareCountryChecksAcrossCwisIndicators();
        $migration->down();
        $this->assertNull(DB::selectOne("SELECT to_regprocedure('cwis.require_indicator_country(character varying, character varying)') AS value")->value);
        $migration->up();
        $this->assertSame('SF-1a', DB::selectOne("SELECT cwis.require_indicator_country('ALL', 'SF-1a') AS value")->value);
    }
}
