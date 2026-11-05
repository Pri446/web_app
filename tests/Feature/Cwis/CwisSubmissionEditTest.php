<?php

namespace Tests\Feature\Cwis;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CwisSubmissionEditTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::whereHas('roles',function ($query) { $query->where('name','Super Admin'); })->firstOrFail());
        Storage::fake('public');
        DB::table('public.site_settings')->where('name','active_country')->update(['value' => 'ALL']);
        DB::table('cwis.equity_submissions')->whereNull('deleted_at')->update(['deleted_at' => now()]);
        $this->seed(\Database\Seeders\Cwis\Equity\CwisFormFieldsSeeder::class);
    }

    private function payload(): array
    {
        return ['year' => now()->year, 'country_code' => 'ALL', 'save_mode' => 'draft',
            'inputs' => ['total_subsidies_amount_nss' => 100, 'total_subsidies_amount_ss' => 50],
            'policies' => ['EQ_6_1' => 'Yes']];
    }

    private function draft(): int
    {
        $this->post('/cwis/generator-data',$this->payload() + [
            'evidence' => ['EQ_6_1' => UploadedFile::fake()->create('training.pdf',1)],
        ])->assertSessionHasNoErrors();
        return DB::table('cwis.equity_submissions')->whereNull('deleted_at')->max('id');
    }

    public function test_edit_prefills_saved_values_and_existing_evidence(): void
    {
        $id = $this->draft();
        $this->get('/cwis/generator-data/' . $id . '?country_code=ALL')->assertOk()
            ->assertDontSee('/' . $id . '/edit',false)->assertSee('/' . $id . '/review',false);
        $this->get('/cwis/generator-data/' . $id . '/edit')->assertOk()
            ->assertSee('value="100.00"',false)->assertSee('training.pdf')
            ->assertSee('name="_method" value="PUT"',false);
    }

    public function test_equity_document_links_are_downloadable_and_country_scoped(): void
    {
        $id = $this->draft();
        $document = DB::table('cwis.sanitation_worker_policy')->where('submission_id', $id)->value('training_requirement_document_id');
        $url = route('cwis.generator-data.document', ['submission' => $id, 'document' => $document]);
        foreach (['', '/edit', '/review'] as $suffix) {
            $this->get('/cwis/generator-data/' . $id . $suffix)->assertOk()
                ->assertSee('training.pdf')->assertSee($url, false)->assertSee('preview=1', false);
        }
        $download = $this->get($url)->assertOk();
        $this->assertStringContainsString('attachment', $download->headers->get('Content-Disposition'));
        $this->get(route('cwis.generator-data.document', ['submission' => $id, 'document' => $document + 100000]))->assertNotFound();
        DB::table('public.site_settings')->where('name', 'active_country')->update(['value' => 'BD']);
        $this->get($url)->assertNotFound();
    }

    public function test_equity_submission_requires_each_yes_document_and_reuses_draft_evidence(): void
    {
        $id = $this->draft();
        $payload = $this->payload();
        $payload['save_mode'] = 'submitted';
        $payload['inputs'] += ['women_employee_count' => 10, 'total_employee_count' => 20,
            'women_leadership_count' => 1, 'total_leadership_count' => 2,
            'average_salary_of_women_in_sanitation' => 100, 'average_salary_of_men_in_sanitation' => 100];
        foreach (app(\App\Services\Cwis\CwisFormService::class)->definitions() as $field) {
            if ($field->kind === 'policy' && !$field->parent_code && $field->input_name !== 'EQ_6_1') {
                $payload['policies'][$field->input_name] = 'No';
            }
        }
        $payload['policies']['EQ_6_1a'] = 'Yes';
        $payload['policies']['EQ_6_1b'] = 'No';
        $url = '/cwis/generator-data/' . $id;
        $this->put($url, $payload, ['Accept' => 'application/json'])->assertStatus(422)
            ->assertJsonValidationErrors('evidence.EQ_6_1a')->assertJsonMissingValidationErrors('evidence.EQ_6_1');
        $this->assertDatabaseHas('cwis.equity_submissions', ['id' => $id, 'status' => 'draft']);
        $payload['evidence']['EQ_6_1a'] = UploadedFile::fake()->create('rights.pdf', 1);
        $this->put($url, $payload, ['Accept' => 'application/json'])->assertOk()->assertJsonStructure(['redirect']);
        $this->assertDatabaseHas('cwis.equity_submissions', ['id' => $id, 'status' => 'submitted']);
    }

    public function test_form_uses_laravel_errors_instead_of_browser_validation(): void
    {
        $id = $this->draft();
        $url = '/cwis/generator-data/' . $id . '/review';
        $this->get($url)->assertOk()->assertSee('id="indicator-data-form" novalidate', false)
            ->assertDontSee('reportValidity', false)->assertDontSee('setCustomValidity', false);
        $this->from($url)->post('/cwis/generator-data/' . $id . '/approve', [
            'review_form' => 1, 'year' => now()->year, 'country_code' => 'ALL',
            'save_mode' => 'submitted', 'policies' => ['EQ_6_1' => ''],
        ])->assertRedirect($url)->assertSessionHasErrors(['policies.EQ_6_1', 'inputs.total_subsidies_amount_nss']);
        $this->get($url)->assertOk()->assertSee('is-invalid', false)
            ->assertSee('invalid-feedback', false)->assertSee('Training / Certification Required')
            ->assertSee('Error!')->assertSee('fa-exclamation-circle', false)
            ->assertSee('alert alert-danger', false);
        $this->assertDatabaseHas('cwis.equity_submissions', ['id' => $id, 'status' => 'draft']);
    }

    public function test_update_preserves_submission_and_source_identity_and_existing_documents(): void
    {
        $id = $this->draft();
        $header = DB::table('cwis.equity_submissions')->where('id',$id)->first();
        $row = DB::table('cwis.equity_subsidies')->where('submission_id',$id)->first();
        $docId = DB::table('cwis.sanitation_worker_policy')->where('submission_id',$id)->value('training_requirement_document_id');
        $count = DB::table('cwis.equity_submissions')->count();
        $payload = $this->payload();
        $payload['inputs']['total_subsidies_amount_nss'] = 60000;
        $this->put('/cwis/generator-data/' . $id,$payload)->assertSessionHasNoErrors()
            ->assertRedirect(route('cwis.generator-data.show',['submission' => $id,'country_code' => 'ALL']))
            ->assertSessionHas('cwis_saved');
        $updated = DB::table('cwis.equity_subsidies')->where('submission_id',$id)->first();
        $this->assertSame($row->id,$updated->id);
        $this->assertEquals(60000,$updated->total_subsidies_amount_nss);
        $this->assertSame($row->created_at,$updated->created_at);
        $this->assertSame($header->created_at,DB::table('cwis.equity_submissions')->where('id',$id)->value('created_at'));
        $this->assertSame($count,DB::table('cwis.equity_submissions')->count());
        $this->assertEquals($docId,DB::table('cwis.sanitation_worker_policy')->where('submission_id',$id)->value('training_requirement_document_id'));
    }

    public function test_evidence_can_be_replaced_without_deleting_the_previous_file(): void
    {
        $id = $this->draft();
        $oldId = DB::table('cwis.sanitation_worker_policy')->where('submission_id',$id)->value('training_requirement_document_id');
        $oldPath = DB::table('cwis.equity_documents')->where('id',$oldId)->value('path');
        $payload = $this->payload();
        $payload['evidence'] = ['EQ_6_1' => UploadedFile::fake()->create('replacement.pdf',1)];
        $this->put('/cwis/generator-data/' . $id,$payload)->assertSessionHasNoErrors();
        $newId = DB::table('cwis.sanitation_worker_policy')->where('submission_id',$id)->value('training_requirement_document_id');
        $this->assertNotEquals($oldId,$newId);
        Storage::disk('public')->assertExists($oldPath);
        $this->assertSame('replacement.pdf',DB::table('cwis.equity_documents')->where('id',$newId)->value('original_name'));
    }

    public function test_approved_records_are_read_only_and_cannot_be_updated(): void
    {
        $id = $this->draft();
        DB::table('cwis.equity_submissions')->where('id',$id)->update(['status' => 'approved','approved_at' => now()]);
        $this->get('/cwis/generator-data/' . $id . '?country_code=ALL')->assertOk()->assertDontSee('/' . $id . '/edit',false);
        $this->get('/cwis/generator-data/' . $id . '/edit')->assertForbidden();
        $this->put('/cwis/generator-data/' . $id,$this->payload())->assertForbidden();
        $this->assertEquals(100,DB::table('cwis.equity_subsidies')->where('submission_id',$id)->value('total_subsidies_amount_nss'));
    }

    public function test_update_cannot_change_year_country_or_submit_incomplete_data(): void
    {
        $id = $this->draft();
        $payload = $this->payload();
        $this->put('/cwis/generator-data/' . $id,array_replace($payload,['year' => now()->year - 1]))->assertSessionHasErrors('year');
        $this->put('/cwis/generator-data/' . $id,array_replace($payload,['country_code' => 'BD']))->assertSessionHasErrors('country_code');
        $this->put('/cwis/generator-data/' . $id,array_replace($payload,['save_mode' => 'submitted']))->assertSessionHasErrors('inputs.women_employee_count');
        $this->assertSame('draft',DB::table('cwis.equity_submissions')->where('id',$id)->value('status'));
    }

    public function test_submitted_record_can_be_corrected_without_approving_it(): void
    {
        $id = $this->draft();
        DB::table('cwis.equity_submissions')->where('id',$id)->update(['status' => 'submitted']);
        $payload = $this->payload();
        $payload['policies']['EQ_6_1'] = 'No';
        $this->get('/cwis/generator-data/' . $id . '/edit')->assertOk();
        $this->put('/cwis/generator-data/' . $id,$payload)->assertSessionHasNoErrors();
        $this->assertSame('draft',DB::table('cwis.equity_submissions')->where('id',$id)->value('status'));
        $this->assertNull(DB::table('cwis.sanitation_worker_policy')->where('submission_id',$id)->value('training_requirement_document_id'));
    }
}
