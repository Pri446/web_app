<?php

namespace Tests\Feature\Cwis;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CwisSustainabilityCalculationTest extends TestCase
{
    use DatabaseTransactions;

    private int $year = 2090;
    private int $submissionId;
    private array $plants = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::whereHas('roles', function ($q) { $q->where('name', 'Super Admin'); })->firstOrFail());
        Storage::fake('local');
        DB::table('public.site_settings')->where('name', 'active_country')->update(['value' => 'BD']);
        DB::table('fsm.treatment_plants')->update(['status' => false]);
        $this->submissionId = DB::table('cwis.sustainability_submissions')->insertGetId([
            'reporting_year' => $this->year, 'country_code' => 'BD', 'status' => 'approved',
            'currency_code' => 'BDT', 'approved_by' => auth()->id(), 'approved_at' => now(),
        ]);
        DB::table('cwis.sustainability_financials')->insert([
            'submission_id' => $this->submissionId,
            'ct_pt_user_fee_revenue' => 100, 'ct_pt_om_expenditure' => 200,
            'fsm_chain_revenue' => 150, 'sewerage_charges_revenue' => 50, 'sanitation_chain_om_expenditure' => 400,
            'gov_funded_treatment_capex' => 10, 'gov_funded_ct_pt_capex' => 20, 'gov_funded_desludging_capex' => 30,
            'annualized_treatment_capex' => 100, 'annualized_ct_pt_capex' => 200, 'annualized_desludging_capex' => 300,
        ]);
        Storage::disk('local')->put('campaign-test.pdf', 'test evidence');
        foreach (array_keys(config('cwis_sustainability.campaigns')) as $code) {
            DB::table('cwis.sustainability_campaigns')->insert([
                'submission_id' => $this->submissionId, 'indicator_code' => $code,
                'answer' => $code === 'SS-4.1' ? 'Yes' : 'No',
                'document_path' => $code === 'SS-4.1' ? 'campaign-test.pdf' : null,
            ]);
        }
        $this->plants[] = $this->plant(1, true, 100, 25, 10, 5);
        $this->plants[] = $this->plant(2, true, 300, 75, 30, 15);
        $this->plant(3, true, 1000, 1000, 1000, 1000);
        $this->plant(1, false, 1000, 1000, 1000, 1000);
    }

    private function plant(int $type, bool $status, $generated, $reused, $biosolids, $bioReused): int
    {
        $id = DB::table('fsm.treatment_plants')->insertGetId([
            'name' => 'Sustainability calculation fixture', 'location' => 'Test Location',
            'capacity_per_day' => 100, 'caretaker_name' => 'Test', 'caretaker_number' => '9800000000',
            'type' => $type, 'status' => $status,
        ]);
        DB::table('cwis.treatment_plant_annual_sustainability')->insert([
            'treatment_plant_id' => $id, 'data_year' => $this->year - 1,
            'treated_effluent_generated_volume' => $generated, 'treated_effluent_reused_volume' => $reused,
            'treated_biosolids_generated_volume' => $biosolids, 'treated_biosolids_reused_volume' => $bioReused,
        ]);
        return $id;
    }

    private function prepare(): void
    {
        DB::select('SELECT cwis.prepare_sustainability_generation(?)', [$this->year]);
    }

    public function test_all_individual_functions_follow_document_and_previous_year(): void
    {
        DB::table('cwis.treatment_plant_annual_sustainability')->insert([
            'treatment_plant_id' => $this->plants[0], 'data_year' => $this->year,
            'treated_effluent_generated_volume' => 1000, 'treated_effluent_reused_volume' => 1000,
        ]);
        $this->prepare();
        $result = DB::selectOne('SELECT cwis.calculate_ss_1a(?) AS a, cwis.calculate_ss_1b(?) AS b,
            cwis.calculate_ss_2(?) AS c, cwis.calculate_ss_3(?) AS d, cwis.calculate_ss_4_1(?) AS e,
            cwis.calculate_ss_4_1a(?) AS f, cwis.calculate_ss_4_1b(?) AS g,
            cwis.calculate_ss_4_1c(?) AS h, cwis.calculate_ss_4_1d(?) AS i', array_fill(0, 9, $this->year));
        $this->assertSame(25.0, (float) $result->a);
        $this->assertSame(50.0, (float) $result->b);
        $this->assertSame(1.0, (float) $result->c);
        $this->assertSame(0.1, (float) $result->d);
        $this->assertSame('Yes', $result->e);
        foreach (['f','g','h','i'] as $key) { $this->assertSame('No', $result->$key); }
        $this->assertSame(2, count(json_decode(DB::table('cwis.sustainability_generation_runs')->where('year', $this->year)->value('plant_snapshot'), true)));
    }

    public function test_missing_plant_data_produces_no_data_instead_of_partial_total(): void
    {
        DB::table('cwis.treatment_plant_annual_sustainability')->where('treatment_plant_id', $this->plants[1])->delete();
        $this->prepare();
        $this->assertNull(DB::selectOne('SELECT cwis.calculate_ss_1a(?) AS value', [$this->year])->value);
    }

    public function test_zero_denominators_and_three_decimal_precision(): void
    {
        DB::table('cwis.sustainability_financials')->where('submission_id', $this->submissionId)->update([
            'ct_pt_om_expenditure' => 0, 'annualized_treatment_capex' => 0,
            'annualized_ct_pt_capex' => 0, 'annualized_desludging_capex' => 0,
        ]);
        DB::table('cwis.treatment_plant_annual_sustainability')->whereIn('treatment_plant_id', $this->plants)
            ->update(['treated_effluent_generated_volume' => 0, 'treated_effluent_reused_volume' => 0]);
        DB::table('cwis.treatment_plant_annual_sustainability')->where('treatment_plant_id', $this->plants[0])
            ->update(['treated_effluent_generated_volume' => 3, 'treated_effluent_reused_volume' => 1]);
        $this->prepare();
        $result = DB::selectOne('SELECT cwis.calculate_ss_1a(?) AS a, cwis.calculate_ss_2(?) AS b, cwis.calculate_ss_3(?) AS c', array_fill(0, 3, $this->year));
        $this->assertSame('33.333', (string) $result->a);
        $this->assertNull($result->b);
        $this->assertNull($result->c);
    }

    public function test_rebuild_preserves_equity_results_and_uses_confirmed_source(): void
    {
        DB::table('cwis.generation_runs')->insert(['year' => $this->year, 'country_code' => 'BD', 'generated_at' => now()]);
        DB::table('cwis.data_cwis')->insert(['year' => $this->year, 'indicator_code' => 'EQ-1', 'outcome' => 'equity', 'data_value' => '777.000']);
        $this->post('/cwis/sustainability-data/' . $this->submissionId . '/calculate')
            ->assertSessionHasNoErrors()->assertRedirect(route('cwis.generator-data.index', ['country_code' => 'BD']))->assertSessionHas('cwis_saved');
        $this->assertSame('777.000', DB::table('cwis.data_cwis')->where('year', $this->year)->where('indicator_code', 'EQ-1')->value('data_value'));
        $this->assertSame('25.000', DB::table('cwis.data_cwis')->where('year', $this->year)->where('indicator_code', 'SS-1a')->value('data_value'));
        $this->assertSame(9, DB::table('cwis.data_cwis')->where('year', $this->year)->where('outcome', 'sustainability')->count());
        $this->assertNotEmpty(Storage::disk('local')->files('cwis/result-backups'));
        $this->get('/cwis/cwis/getall?year=' . $this->year)->assertOk()->assertSee('tab-sustainability')->assertSee('25');
    }

    public function test_wrong_country_and_unapproved_sources_cannot_overwrite_results(): void
    {
        DB::table('cwis.generation_runs')->insert(['year' => $this->year, 'country_code' => 'NP', 'generated_at' => now()]);
        DB::table('cwis.data_cwis')->insert(['year' => $this->year, 'indicator_code' => 'EQ-1', 'outcome' => 'equity', 'data_value' => '777.000']);
        $this->post('/cwis/sustainability-data/' . $this->submissionId . '/calculate')->assertSessionHasErrors('calculation');
        $this->assertSame(1, DB::table('cwis.data_cwis')->where('year', $this->year)->count());
        DB::table('cwis.sustainability_submissions')->where('id', $this->submissionId)->update(['status' => 'submitted']);
        $this->post('/cwis/sustainability-data/' . $this->submissionId . '/calculate')->assertSessionHasErrors('submission');
    }

    public function test_new_generation_runs_sustainability_dispatch_without_replacing_existing_generator(): void
    {
        $ssIds = DB::table('cwis.data_source')->where('outcome', 'sustainability')->pluck('id');
        DB::table('cwis.indicator_country')->where('country_code', 'BD')->whereNotIn('data_source_id', $ssIds)->delete();
        DB::select('SELECT public.generate_cwis_with_sustainability(?)', [$this->year]);
        $this->assertSame('1.000', DB::table('cwis.data_cwis')->where('year', $this->year)->where('indicator_code', 'SS-2')->value('data_value'));
        $this->assertSame($this->submissionId, (int) DB::table('cwis.sustainability_generation_runs')->where('year', $this->year)->value('submission_id'));
        $this->assertNotNull(DB::selectOne("SELECT to_regprocedure('public.generate_cwis_with_equity(integer)') AS existing")->existing);
    }

    public function test_new_generation_rejects_unapproved_sources_before_writing_results(): void
    {
        DB::table('cwis.sustainability_submissions')->where('id', $this->submissionId)->update(['status' => 'submitted']);
        try {
            DB::transaction(function () { DB::select('SELECT public.generate_cwis_with_sustainability(?)', [$this->year]); });
            $this->fail('Unapproved source should be rejected.');
        } catch (\Illuminate\Database\QueryException $exception) {
            $this->assertStringContainsString('Approve Sustainability data', $exception->getMessage());
        }
        $this->assertSame(0, DB::table('cwis.data_cwis')->where('year', $this->year)->count());
    }

    public function test_missing_evidence_file_blocks_rebuild(): void
    {
        Storage::disk('local')->delete('campaign-test.pdf');
        $this->post('/cwis/sustainability-data/' . $this->submissionId . '/calculate')->assertSessionHasErrors('SS-4_1');
        $this->assertSame(0, DB::table('cwis.data_cwis')->where('year', $this->year)->count());
    }

    public function test_campaign_subindicator_is_evaluated_independently(): void
    {
        DB::table('cwis.sustainability_campaigns')->where('submission_id', $this->submissionId)->where('indicator_code', 'SS-4.1')->update(['answer' => 'No']);
        DB::table('cwis.sustainability_campaigns')->where('submission_id', $this->submissionId)->where('indicator_code', 'SS-4.1a')
            ->update(['answer' => 'Yes', 'document_path' => 'campaign-test.pdf']);
        $this->prepare();
        $this->assertSame('Yes', DB::selectOne('SELECT cwis.calculate_ss_4_1a(?) AS value', [$this->year])->value);
    }
}
