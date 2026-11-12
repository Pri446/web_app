<?php

namespace Tests\Unit;

use App\Http\Requests\Cwis\CwisAccountabilityDataRequest;
use App\Http\Requests\Cwis\CwisIndicatorDataRequest;
use App\Http\Requests\Cwis\CwisResponsibilityDataRequest;
use App\Http\Requests\Cwis\CwisRpmDataRequest;
use App\Http\Requests\Cwis\CwisSustainabilityDraftRequest;
use App\Services\Cwis\CwisAccountabilityFormService;
use App\Services\Cwis\CwisFormService;
use App\Services\Cwis\CwisIndicatorService;
use App\Services\Cwis\CwisResponsibilityFormService;
use App\Services\Cwis\CwisRpmFormService;
use App\Services\Cwis\CwisSustainabilityFormService;
use App\Support\CwisEvidence;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class CwisEvidenceUploadValidationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->mock(CwisIndicatorService::class, function ($mock) {
            $mock->shouldReceive('activeCountry')->andReturn('BD');
            $mock->shouldReceive('forCountry')->andReturn(collect([(object) ['indicator_code' => 'EQ-6.1']]));
        });
        $policy = (object) ['kind' => 'policy', 'input_name' => 'training_certification_required',
            'indicator_code' => 'EQ-6.1', 'parent_code' => null, 'required_on_submit' => true];
        $this->mock(CwisFormService::class, function ($mock) use ($policy) {
            $mock->shouldReceive('definitions')->andReturn([$policy]);
            $mock->shouldReceive('applicable')->andReturn([$policy]);
        });
        foreach ([CwisResponsibilityFormService::class, CwisAccountabilityFormService::class, CwisRpmFormService::class] as $class) {
            $this->mock($class, function ($mock) {
                $mock->shouldReceive('questions')->andReturn(['question' => 'Question']);
            });
        }
        $this->mock(CwisSustainabilityFormService::class, function ($mock) {
            $mock->shouldReceive('fields')->andReturn(['sections' => [], 'campaigns' => ['question' => 'Question']]);
        });
    }

    public static function requestsAndModes(): array
    {
        $cases = [];
        foreach ([
            'equity' => [CwisIndicatorDataRequest::class, 'policies', 'training_certification_required'],
            'sustainability' => [CwisSustainabilityDraftRequest::class, 'campaigns', 'question'],
            'responsibility' => [CwisResponsibilityDataRequest::class, 'answers', 'question'],
            'accountability' => [CwisAccountabilityDataRequest::class, 'answers', 'question'],
            'rpm' => [CwisRpmDataRequest::class, 'answers', 'question'],
        ] as $name => $request) {
            foreach (['draft', 'submitted', 'approve'] as $mode) {
                $cases[$name . '-' . $mode] = array_merge($request, [$mode]);
            }
        }
        return $cases;
    }

    /** @dataProvider requestsAndModes */
    public function test_document_types_size_and_conditional_uploads(string $class, string $group, string $key, string $mode): void
    {
        $request = $class::create('/', 'POST', ['country_code' => 'BD', 'year' => now()->year,
            'save_mode' => $mode === 'approve' ? 'submitted' : $mode, 'review_form' => true,
            $group => [$key => 'Yes']]);
        $route = new Route(['POST'], 'cwis/upload', ['uses' => 'TestController@' . ($mode === 'approve' ? 'approve' : 'store')]);
        $route->bind($request);
        $request->setRouteResolver(function () use ($route) { return $route; });
        $rules = $request->rules()['evidence.' . $key];
        $this->assertSame(CwisEvidence::uploadRules(), $rules);
        foreach ([
            ['evidence.pdf', 'application/pdf', 10, true],
            ['evidence.doc', 'application/msword', 10, true],
            ['evidence.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 10, true],
            ['boundary.pdf', 'application/pdf', 5120, true],
            ['oversize.pdf', 'application/pdf', 5121, false],
            ['image.png', 'image/png', 10, false],
            ['archive.zip', 'application/zip', 10, false],
            ['notes.txt', 'text/plain', 10, false],
            ['renamed.pdf', 'image/png', 10, false],
        ] as [$name, $mime, $size, $accepted]) {
            $validator = Validator::make(['upload' => UploadedFile::fake()->create($name, $size, $mime)], ['upload' => $rules]);
            $this->assertSame($accepted, $validator->passes(), $name);
        }
        $this->assertTrue(Validator::make(['upload' => null], ['upload' => $rules])->passes());
        $request->merge([$group => [$key => 'No']]);
        $this->assertSame(['prohibited'], $request->rules()['evidence.' . $key]);
    }

    public function test_all_outcome_forms_use_the_same_file_picker_types(): void
    {
        $this->assertSame('.pdf,.doc,.docx', CwisEvidence::UPLOAD_ACCEPT);
        foreach (['generator-data/create', 'sustainability/form', 'responsibility/form', 'accountability/form', 'rpm/form'] as $view) {
            $this->assertStringContainsString('CwisEvidence::UPLOAD_ACCEPT', file_get_contents(resource_path('views/cwis/' . $view . '.blade.php')));
        }
    }
}
