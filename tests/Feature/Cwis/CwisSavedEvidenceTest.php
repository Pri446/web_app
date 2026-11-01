<?php

namespace Tests\Feature\Cwis;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CwisSavedEvidenceTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::whereHas('roles', function ($q) { $q->where('name', 'Super Admin'); })->firstOrFail());
        DB::table('public.site_settings')->where('name', 'active_country')->update(['value' => 'BD']);
        Storage::fake('local');
    }

    public static function outcomes(): array
    {
        return [['sustainability', 'campaigns', 'SS-4_1', 'sustainability_campaigns'],
            ['responsibility', 'answers', 'RS-1a_1', 'responsibility_answers']];
    }

    private function payload(string $outcome, string $key): array
    {
        $year = collect(range(now()->year, now()->year - 5))->diff(DB::table('cwis.' . $outcome . '_submissions')
            ->where('country_code', 'BD')->pluck('reporting_year'))->first();
        $this->assertNotNull($year);
        $payload = ['year' => $year, 'country_code' => 'BD', 'save_mode' => 'draft'];
        $questions = $outcome === 'responsibility'
            ? app(\App\Services\Cwis\CwisResponsibilityFormService::class)->questions('BD') : config('cwis_sustainability.campaigns');
        foreach ($questions as $code => $label) { $payload[$key][str_replace('.', '_', $code)] = 'No'; }
        if ($outcome === 'sustainability') {
            foreach (config('cwis_sustainability.financial_sections') as $section) {
                foreach ($section['fields'] as $name => $label) { $payload['inputs'][$name] = 100; }
            }
        }
        return $payload;
    }

    private function pdf(string $name = 'saved-mandate.pdf')
    {
        return UploadedFile::fake()->createWithContent($name, "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\n%%EOF");
    }

    /** @dataProvider outcomes */
    public function test_draft_attachment_is_visible_and_approval_reuses_it_without_another_upload(string $outcome, string $key, string $code, string $table): void
    {
        $payload = $this->payload($outcome, $key);
        $payload[$key][$code] = 'Yes';
        $payload['evidence'][$code] = $this->pdf();
        $this->post('/cwis/' . $outcome . '-data', $payload)->assertSessionHasNoErrors();
        $id = DB::table('cwis.' . $outcome . '_submissions')->where('country_code', 'BD')->where('reporting_year', $payload['year'])->value('id');
        $saved = DB::table('cwis.' . $table)->where('submission_id', $id)->whereNotNull('document_path')->first();
        Storage::disk('local')->assertExists($saved->document_path);
        $this->assertStringStartsWith('cwis/' . $outcome . '-documents/', $saved->document_path);
        foreach (['/edit', '/review', ''] as $suffix) {
            $this->get('/cwis/' . $outcome . '-data/' . $id . $suffix)->assertOk()->assertSee('saved-mandate.pdf')->assertSee('preview=1', false);
        }
        $this->get('/cwis/' . $outcome . '-data/' . $id . '/review')->assertSee('Replace Evidence (Optional)');
        $url = '/cwis/' . $outcome . '-data/' . $id . '/documents/' . $saved->id;
        $preview = $this->get($url . '?preview=1')->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringContainsString('inline', $preview->headers->get('Content-Disposition'));
        $download = $this->get($url)->assertOk();
        $this->assertStringContainsString('attachment', $download->headers->get('Content-Disposition'));
        unset($payload['evidence']);
        $payload['save_mode'] = 'submitted';
        $this->post('/cwis/' . $outcome . '-data/' . $id . '/approve', $payload)->assertSessionHasNoErrors()->assertSessionHas('cwis_saved');
        $this->assertSame('approved', DB::table('cwis.' . $outcome . '_submissions')->where('id', $id)->value('status'));
        $this->assertSame($saved->document_path, DB::table('cwis.' . $table)->where('id', $saved->id)->value('document_path'));
        Storage::disk('local')->assertExists($saved->document_path);
        DB::table('public.site_settings')->where('name', 'active_country')->update(['value' => 'ALL']);
        $this->get($url . '?preview=1')->assertNotFound();
    }

    /** @dataProvider outcomes */
    public function test_invalid_replacement_keeps_saved_document_and_valid_replacement_updates_it(string $outcome, string $key, string $code, string $table): void
    {
        $payload = $this->payload($outcome, $key);
        $payload[$key][$code] = 'Yes';
        $payload['evidence'][$code] = $this->pdf();
        $this->post('/cwis/' . $outcome . '-data', $payload)->assertSessionHasNoErrors();
        $id = DB::table('cwis.' . $outcome . '_submissions')->where('country_code', 'BD')->where('reporting_year', $payload['year'])->value('id');
        $saved = DB::table('cwis.' . $table)->where('submission_id', $id)->whereNotNull('document_path')->first();
        $payload['evidence'][$code] = UploadedFile::fake()->createWithContent('invalid.html', '<html>not evidence</html>');
        $this->put('/cwis/' . $outcome . '-data/' . $id, $payload)->assertSessionHasErrors('evidence.' . $code);
        Storage::disk('local')->assertExists($saved->document_path);
        $payload['evidence'][$code] = $this->pdf('replacement.pdf');
        $this->put('/cwis/' . $outcome . '-data/' . $id, $payload)->assertSessionHasNoErrors();
        $updated = DB::table('cwis.' . $table)->where('id', $saved->id)->first();
        $this->assertSame('replacement.pdf', $updated->document_name);
        $this->assertNotSame($saved->document_path, $updated->document_path);
        Storage::disk('local')->assertExists($updated->document_path);
        Storage::disk('local')->assertMissing($saved->document_path);
    }
}
