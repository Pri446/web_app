<?php

namespace Tests\Feature\Cwis;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CwisSustainabilityActivationTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::whereHas('roles', function ($query) { $query->where('name', 'Super Admin'); })->firstOrFail());
        DB::table('public.site_settings')->where('name', 'active_country')->update(['value' => 'BD']);
    }

    public function test_bangladesh_picker_and_form_are_available(): void
    {
        $year = collect(range(now()->year, now()->year - 5))->diff(
            DB::table('cwis.sustainability_submissions')->where('country_code', 'BD')->pluck('reporting_year')
        )->first();
        $this->assertNotNull($year, 'An unused year is required to test the create form.');
        $this->get('/cwis/generator-data')->assertOk()->assertSee('id="add-outcome-equity"', false)
            ->assertSee('id="add-outcome-sustainability"', false)
            ->assertDontSee('id="add-indicator-outcome"', false)->assertViewHas('inputOutcomes', function ($outcomes) {
            return isset($outcomes['sustainability']);
        });
        $this->get('/cwis/sustainability-data/create?country_code=BD&year=' . $year)
            ->assertOk()->assertViewHas('page_title', 'Add Indicator Data')
            ->assertSee('class="card card-info"', false)
            ->assertSee('class="table table-bordered mb-0"', false)
            ->assertSee('Government Funded Treatment CAPEX')->assertSee('Save as Draft');
    }

    public function test_drafts_for_different_years_are_preserved(): void
    {
        $year = now()->year;
        foreach ([$year, $year - 1] as $value) {
            $this->post('/cwis/sustainability-data', ['country_code' => 'BD', 'year' => $value,
                'currency_code' => 'BDT', 'inputs' => ['gov_funded_treatment_capex' => '100.25']])
                ->assertSessionHasNoErrors()->assertRedirect(route('cwis.generator-data.index', ['country_code' => 'BD']));
        }
        $this->assertSame(2, DB::table('cwis.sustainability_submissions')->where('country_code', 'BD')
            ->whereIn('reporting_year', [$year, $year - 1])->count());
        $this->post('/cwis/sustainability-data', ['country_code' => 'BD', 'year' => $year])
            ->assertSessionHasErrors('year');
    }

    public function test_existing_view_and_review_approval_flow(): void
    {
        // Isolate test sources inside the outer rollback transaction; never touch evidence files.
        DB::table('cwis.sustainability_submissions')->where('country_code', 'BD')->delete();
        $resultCount = DB::table('cwis.data_cwis')->count();
        $payload = ['country_code' => 'BD', 'year' => now()->year, 'save_mode' => 'draft'];
        $this->post('/cwis/sustainability-data', $payload)->assertSessionHasNoErrors();
        $id = DB::table('cwis.sustainability_submissions')->where('country_code', 'BD')->value('id');
        $this->get('/cwis/sustainability-data/' . $id)->assertOk()->assertSee('Status')
            ->assertSee('Approve')->assertDontSee('Save as Draft');
        $review = '/cwis/sustainability-data/' . $id . '/review';
        $this->get($review)->assertOk()->assertViewHas('approvalReview', true)->assertSee('Approve');
        $this->from($review)->post('/cwis/sustainability-data/' . $id . '/approve', $payload)
            ->assertRedirect($review)->assertSessionHasErrors('inputs.gov_funded_treatment_capex');
        foreach (config('cwis_sustainability.financial_sections') as $section) {
            foreach ($section['fields'] as $name => $label) { $payload['inputs'][$name] = '100.00'; }
        }
        foreach (config('cwis_sustainability.campaigns') as $code => $label) {
            $payload['campaigns'][str_replace('.', '_', $code)] = 'No';
        }
        $payload['campaigns']['SS-4_1'] = 'Yes';
        $this->from($review)->post('/cwis/sustainability-data/' . $id . '/approve', $payload)
            ->assertRedirect($review)->assertSessionHasErrors('evidence.SS-4_1');
        $payload['campaigns']['SS-4_1'] = 'No';
        $payload['save_mode'] = 'submitted';
        $this->put('/cwis/sustainability-data/' . $id, $payload)->assertSessionHasNoErrors();
        $this->assertSame('submitted', DB::table('cwis.sustainability_submissions')->where('id', $id)->value('status'));
        $this->post('/cwis/sustainability-data/' . $id . '/approve', $payload)
            ->assertSessionHasNoErrors()->assertSessionHas('cwis_saved');
        $record = DB::table('cwis.sustainability_submissions')->where('id', $id)->first();
        $this->assertSame('approved', $record->status);
        $this->assertNotNull($record->approved_at);
        $this->assertSame($resultCount, DB::table('cwis.data_cwis')->count());
        $this->get($review)->assertForbidden();
        $approvedView = $this->get('/cwis/sustainability-data/' . $id)->assertOk()->assertDontSee('Save as Draft')
            ->assertViewIs('cwis.sustainability.show')->assertSee('CWIS Results')
            ->assertDontSee('id="sustainability-rebuild-status"', false)
            ->assertDontSee('name="currency_code"', false)->assertDontSee('name="inputs[', false);
        $dom = new \DOMDocument();
        @$dom->loadHTML($approvedView->getContent());
        $this->assertSame(1, (new \DOMXPath($dom))->query('//button[@id="sustainability-rebuild-button"][not(@disabled)]')->length);
    }
}
