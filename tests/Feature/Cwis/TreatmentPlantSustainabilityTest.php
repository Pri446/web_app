<?php

namespace Tests\Feature\Cwis;

use App\Http\Requests\Fsm\TreatmentPlantRequest;
use App\Models\Fsm\TreatmentPlant;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class TreatmentPlantSustainabilityTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::whereHas('roles', function ($q) { $q->where('name', 'Super Admin'); })->firstOrFail());
    }

    private function payload(): array
    {
        return ['name' => 'CWIS annual plant test', 'location' => 'Test Location', 'type' => 1,
            'capacity_per_day' => '100', 'caretaker_name' => 'Test Caretaker', 'caretaker_gender' => 'Female',
            'caretaker_number' => '9800000000', 'status' => 1, 'longitude' => '85.3', 'latitude' => '27.6'];
    }

    private function annual(int $year, string $generated = '100.125', string $reused = '25.000'): array
    {
        return ['data_year' => $year, 'treated_effluent_generated_volume' => $generated,
            'treated_effluent_reused_volume' => $reused, 'treated_biosolids_generated_volume' => '20.000',
            'treated_biosolids_reused_volume' => '0'];
    }

    public function test_add_edit_show_and_preservation_of_other_years(): void
    {
        $year = now()->year - 1;
        $data = $this->payload();
        $this->get('/fsm/treatment-plants/create')->assertOk()->assertSee('Treated Effluent Generated (KLD)')
            ->assertDontSee('CWIS Sustainability')
            ->assertSee('name="cwis_annual[0][data_year]"', false);
        $data['cwis_annual'] = [$this->annual($year)];
        $this->post('/fsm/treatment-plants', $data)->assertSessionHasNoErrors()->assertRedirect('/fsm/treatment-plants');
        $plant = TreatmentPlant::where('name', $data['name'])->latest('id')->firstOrFail();
        $this->assertSame('0.000', $plant->annualSustainability()->first()->treated_biosolids_reused_volume);
        $data['cwis_annual'] = [$this->annual($year - 1, '70.000', '10.000')];
        $this->patch('/fsm/treatment-plants/' . $plant->id, $data)->assertSessionHasNoErrors();
        $this->assertSame(2, $plant->annualSustainability()->count());
        $this->assertSame('100.125', $plant->annualSustainability()->where('data_year', $year)->first()->treated_effluent_generated_volume);
        $this->get('/fsm/treatment-plants/' . $plant->id . '/edit')->assertOk()->assertSee('100.125')->assertSee('70.000');
        $this->get('/fsm/treatment-plants/' . $plant->id)->assertOk()->assertSee('100.125')->assertSee('70.000');
    }

    public function test_validation_rejects_bad_volumes_duplicate_years_and_missing_generated(): void
    {
        foreach ([
            [$this->annual(now()->year - 1, '10', '11')],
            [$this->annual(now()->year - 1, '-1', '0')],
            [$this->annual(now()->year - 1, '1.1234', '0')],
            [$this->annual(now()->year + 1)],
            [$this->annual(now()->year - 1), $this->annual(now()->year - 1)],
            [$this->annual(now()->year - 1, '', '0')],
        ] as $rows) {
            $request = TreatmentPlantRequest::create('/', 'PATCH', $this->payload() + ['cwis_annual' => $rows]);
            $validator = Validator::make($request->all(), $request->rules());
            $request->withValidator($validator);
            $this->assertTrue($validator->fails());
        }
    }

    public function test_csv_contains_all_years_and_values_without_dropping_plant_columns(): void
    {
        $data = $this->payload();
        $data['cwis_annual'] = [$this->annual(now()->year - 1), $this->annual(now()->year - 2, '50.000', '5.000')];
        $this->post('/fsm/treatment-plants', $data)->assertSessionHasNoErrors();
        $response = app(\App\Services\Fsm\TreatmentPlantService::class)->download(['name' => $data['name']]);
        ob_start();
        $response->sendContent();
        $csv = ob_get_clean();
        $rows = array_values(array_filter(preg_split('/\r?\n/', trim($csv))));
        $header = str_getcsv($rows[0]);
        $this->assertCount(11, $header);
        $this->assertSame('Data Year', $header[6]);
        $this->assertStringContainsString('100.125', $csv);
        $this->assertStringContainsString('50.000', $csv);
        $this->assertCount(3, $rows);
        $this->assertSame(11, count(str_getcsv($rows[1])));
    }

    public function test_unrelated_plant_updates_preserve_annual_sources(): void
    {
        $data = $this->payload();
        $data['cwis_annual'] = [$this->annual(now()->year - 1)];
        $this->post('/fsm/treatment-plants', $data)->assertSessionHasNoErrors();
        $plant = TreatmentPlant::where('name', $data['name'])->latest('id')->firstOrFail();
        unset($data['cwis_annual']);
        $this->patch('/fsm/treatment-plants/' . $plant->id, $data)->assertSessionHasNoErrors();
        $this->assertSame(1, $plant->annualSustainability()->count());
    }

    public function test_editing_saved_data_year_updates_same_record_and_show_uses_new_year(): void
    {
        $data = $this->payload();
        $data['cwis_annual'] = [$this->annual(now()->year - 1)];
        $this->post('/fsm/treatment-plants', $data)->assertSessionHasNoErrors();
        $plant = TreatmentPlant::where('name', $data['name'])->latest('id')->firstOrFail();
        $annual = $plant->annualSustainability()->first();
        $data['cwis_annual'] = [$this->annual(now()->year - 2, '40.000', '10.000') + ['id' => $annual->id]];
        $this->patch('/fsm/treatment-plants/' . $plant->id, $data)->assertSessionHasNoErrors();
        $this->assertSame(1, $plant->annualSustainability()->count());
        $updated = $plant->annualSustainability()->first();
        $this->assertSame($annual->id, $updated->id);
        $this->assertSame(now()->year - 2, $updated->data_year);
        $this->get('/fsm/treatment-plants/' . $plant->id)->assertOk()->assertSee('40.000')
            ->assertViewHas('treatmentPlant', function ($model) { return $model->annualSustainability->first()->data_year === now()->year - 2; });
        $this->get('/fsm/treatment-plants/' . $plant->id . '/edit')->assertOk()->assertSee('name="cwis_annual[0][id]"', false)
            ->assertSee('value="' . (now()->year - 2) . '"', false);
    }

    public function test_explicit_removal_deletes_only_selected_saved_extra_year(): void
    {
        $data = $this->payload();
        $data['cwis_annual'] = [$this->annual(now()->year - 1), $this->annual(now()->year - 2)];
        $this->post('/fsm/treatment-plants', $data)->assertSessionHasNoErrors();
        $plant = TreatmentPlant::where('name', $data['name'])->latest('id')->firstOrFail();
        $rows = $plant->annualSustainability()->get();
        $this->get('/fsm/treatment-plants/' . $plant->id . '/edit')->assertOk()->assertSee('aria-label="Remove Data Year"', false);
        $data['cwis_annual'] = [$this->annual(now()->year - 1) + ['id' => $rows[0]->id]];
        $data['cwis_removed_annual'] = [$rows[1]->id];
        $this->patch('/fsm/treatment-plants/' . $plant->id, $data)->assertSessionHasNoErrors();
        $this->assertSame(1, $plant->annualSustainability()->count());
        $this->assertSame($rows[0]->id, $plant->annualSustainability()->first()->id);
        $this->assertFalse(DB::table('cwis.treatment_plant_annual_sustainability')->where('id', $rows[1]->id)->exists());
    }

    public function test_conflicting_year_rename_and_foreign_record_ids_are_rejected(): void
    {
        $data = $this->payload();
        $data['cwis_annual'] = [$this->annual(now()->year - 1), $this->annual(now()->year - 2)];
        $this->post('/fsm/treatment-plants', $data)->assertSessionHasNoErrors();
        $plant = TreatmentPlant::where('name', $data['name'])->latest('id')->firstOrFail();
        $rows = $plant->annualSustainability()->get();
        $data['cwis_annual'] = [$this->annual(now()->year - 2) + ['id' => $rows[0]->id]];
        $this->patch('/fsm/treatment-plants/' . $plant->id, $data)->assertSessionHasErrors('cwis_annual.0.data_year');
        $other = $this->payload();
        $other['name'] = 'Other plant for ownership test';
        $this->post('/fsm/treatment-plants', $other)->assertSessionHasNoErrors();
        $otherPlant = TreatmentPlant::where('name', $other['name'])->latest('id')->firstOrFail();
        $this->patch('/fsm/treatment-plants/' . $otherPlant->id, $data)->assertSessionHasErrors('cwis_annual.0.id');
        unset($data['cwis_annual']);
        $data['cwis_removed_annual'] = [$rows[0]->id];
        $this->patch('/fsm/treatment-plants/' . $otherPlant->id, $data)->assertSessionHasErrors('cwis_removed_annual.0');
        $this->assertSame(2, $plant->annualSustainability()->count());
    }
}
