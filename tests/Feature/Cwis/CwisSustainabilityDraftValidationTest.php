<?php

namespace Tests\Feature\Cwis;

use App\Http\Requests\Cwis\CwisSustainabilityDraftRequest;
use App\Services\Cwis\CwisIndicatorService;
use App\Services\Cwis\CwisSustainabilityFormService;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class CwisSustainabilityDraftValidationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->mock(CwisIndicatorService::class, function ($mock) {
            $mock->shouldReceive('activeCountry')->andReturn('ALL');
            $mock->shouldReceive('forCountry')->with('ALL')->andReturn(collect(array_map(function ($code) {
                return (object) ['indicator_code' => $code];
            }, ['SS-2', 'SS-3', 'SS-4.1', 'SS-4.1a', 'SS-4.1b', 'SS-4.1c', 'SS-4.1d'])));
        });
    }

    private function validator(array $data)
    {
        $request = CwisSustainabilityDraftRequest::create('/', 'POST', $data);
        return Validator::make($request->all(), $request->rules());
    }

    public function test_incomplete_drafts_are_allowed(): void
    {
        $this->assertTrue($this->validator(['year' => now()->year, 'country_code' => 'ALL'])->passes());
    }

    public function test_supplied_amounts_require_nonnegative_two_decimal_values(): void
    {
        foreach (['-1', '1.123', '10000000000000000', 'abc'] as $value) {
            $this->assertTrue($this->validator(['year' => now()->year, 'country_code' => 'ALL',
                'inputs' => ['gov_funded_treatment_capex' => $value]])->fails(), $value);
        }
        $this->assertTrue($this->validator(['year' => now()->year, 'country_code' => 'ALL',
            'inputs' => ['gov_funded_treatment_capex' => '0.00']])->passes());
    }

    public function test_country_tampering_and_unmapped_inputs_are_rejected(): void
    {
        $this->assertTrue($this->validator(['year' => now()->year, 'country_code' => 'BD'])->fails());
        $this->assertTrue($this->validator(['year' => now()->year, 'country_code' => 'ALL',
            'inputs' => ['treated_effluent_reused_volume' => '20']])->fails());
    }

    public function test_campaigns_do_not_inherit_equity_parent_rules(): void
    {
        $this->assertTrue($this->validator(['year' => now()->year, 'country_code' => 'ALL',
            'campaigns' => ['SS-4_1' => 'No', 'SS-4_1a' => 'Yes']])->passes());
        $this->assertTrue($this->validator(['year' => now()->year, 'country_code' => 'ALL',
            'campaigns' => ['SS-4_1' => 'invalid']])->fails());
    }

    public function test_form_contains_financial_and_campaign_sections_only(): void
    {
        $fields = app(CwisSustainabilityFormService::class)->fields('ALL');
        $this->assertSame(['SS-2', 'SS-3'], array_keys($fields['sections']));
        $this->assertCount(5, $fields['campaigns']);
        $this->assertCount(6, $fields['sections']['SS-3']['fields']);
    }

    public function test_sustainability_routes_are_registered_separately(): void
    {
        foreach (['create', 'store', 'edit', 'update', 'document'] as $action) {
            $route = app('router')->getRoutes()->getByName('cwis.sustainability.' . $action);
            $this->assertNotNull($route);
            $this->assertStringContainsString('CwisSustainabilityDataController', $route->getActionName());
        }
    }
}
