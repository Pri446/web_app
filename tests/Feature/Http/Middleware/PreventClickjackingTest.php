<?php

namespace Tests\Feature\Http\Middleware;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\User;
use App\Services\Fsm\ApplicationService;
use Illuminate\Support\Facades\Route;
use Illuminate\Testing\TestResponse;
use Mockery;
use Tests\Feature\Http\Concerns\InputBehaviorTestHelpers;
use Tests\TestCase;

class PreventClickjackingTest extends TestCase
{
    use InputBehaviorTestHelpers;

    private const CSP = "frame-ancestors 'none'";

    private const X_FRAME_OPTIONS = 'DENY';

    protected function setUp(): void
    {
        parent::setUp();

        // These tests exercise route authorization and response headers. The
        // real service queries PostgreSQL during construction, before auth runs.
        // A strict mock also fails if a guest reaches a business service method.
        $this->app->instance(ApplicationService::class, Mockery::mock(ApplicationService::class));

        Route::middleware('web')->get('/_clickjacking-test/html', function () {
            return response('<!doctype html><title>Protected</title>')
                ->header('Content-Type', 'text/html; charset=UTF-8');
        });

        Route::middleware('web')->get('/_clickjacking-test/redirect', function () {
            return redirect('/_clickjacking-test/html');
        });

        Route::middleware('web')->get('/_clickjacking-test/forbidden', function () {
            return response('<!doctype html><title>Forbidden</title>', 403)
                ->header('Content-Type', 'text/html; charset=UTF-8');
        });

        Route::middleware('web')->get('/_clickjacking-test/aborted', function () {
            abort(403);
        });

        Route::middleware('web')->get('/_clickjacking-test/existing-csp', function () {
            return response('<!doctype html><title>Protected</title>')
                ->header('Content-Type', 'text/html; charset=UTF-8')
                ->header('Content-Security-Policy', "default-src 'self'; img-src data:");
        });

        Route::middleware('web')->get('/_clickjacking-test/conflicting-headers', function () {
            return response('<!doctype html><title>Protected</title>')
                ->header('Content-Type', 'text/html; charset=UTF-8')
                ->header(
                    'Content-Security-Policy',
                    "frame-ancestors 'self'; default-src 'self'; FRAME-ANCESTORS https://unapproved.example"
                )
                ->header('X-Frame-Options', 'SAMEORIGIN');
        });

        Route::middleware('web')->get('/_clickjacking-test/json', function () {
            return response()->json(['status' => 'ok']);
        });

        Route::middleware(['web', 'auth'])->get('/_clickjacking-test/authenticated', function () {
            return response('<!doctype html><title>Authenticated</title>')
                ->header('Content-Type', 'text/html; charset=UTF-8');
        });
    }

    public function test_clickjacking_fixture_has_the_required_case_structure(): void
    {
        foreach ($this->loadJsonFixture('Clickjacking/clickjacking.json') as $case) {
            $this->assertArrayHasKey('id', $case);
            $this->assertArrayHasKey('test-data-description', $case);
            $this->assertArrayHasKey('payload', $case);
            $this->assertArrayHasKey('expected', $case);
            $this->assertArrayHasKey('validation_errors', $case);
        }
    }

    public function test_configuration_defines_the_approved_no_framing_baseline(): void
    {
        $case = $this->fixtureCase(
            'Clickjacking/clickjacking.json',
            'HTML response without existing framing headers'
        );

        $this->assertTrue($case['expected']['headers_apply']);
        $this->assertSame(
            $case['expected']['content_security_policy'],
            config('security_headers.content_security_policy')
        );
        $this->assertSame(
            $case['expected']['x_frame_options'],
            config('security_headers.x_frame_options')
        );
    }

    public function test_real_public_landing_page_has_one_copy_of_each_header(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $this->assertProtectedHtmlResponse($response);
    }

    public function test_guest_is_redirected_from_the_real_protected_application_page_with_headers(): void
    {
        $response = $this->get('/fsm/application');

        $response->assertRedirect(route('login.show'));
        $this->assertProtectedHtmlResponse($response);
    }

    public function test_controlled_authenticated_user_can_receive_a_protected_html_page(): void
    {
        $user = new User();
        $user->forceFill(['id' => 9001, 'status' => 1]);

        $response = $this->actingAs($user)->get('/_clickjacking-test/authenticated');

        $response->assertOk();
        $this->assertProtectedHtmlResponse($response);
    }

    /**
     * @dataProvider protectedApplicationRouteProvider
     */
    public function test_guest_cannot_call_real_protected_application_routes_directly(
        string $method,
        string $uri
    ): void {
        $this->withoutMiddleware(VerifyCsrfToken::class);

        $response = $this->call($method, $uri);

        $response->assertRedirect(route('login.show'));
        $this->assertProtectedHtmlResponse($response);
    }

    public function protectedApplicationRouteProvider(): array
    {
        return [
            'index' => ['GET', '/fsm/application'],
            'create' => ['GET', '/fsm/application/create'],
            'view' => ['GET', '/fsm/application/1'],
            'edit' => ['GET', '/fsm/application/1/edit'],
            'delete' => ['DELETE', '/fsm/application/1'],
            'data' => ['GET', '/fsm/application/getData'],
            'export' => ['GET', '/fsm/application/export'],
            'history' => ['GET', '/fsm/application/1/history'],
            'report' => ['GET', '/fsm/application/1/application-report'],
        ];
    }

    public function test_controlled_html_response_has_one_copy_of_each_header(): void
    {
        $response = $this->get('/_clickjacking-test/html');

        $response->assertOk();
        $this->assertProtectedHtmlResponse($response);
    }

    public function test_html_redirect_is_protected(): void
    {
        $response = $this->get('/_clickjacking-test/redirect');

        $response->assertRedirect('/_clickjacking-test/html');
        $this->assertProtectedHtmlResponse($response);
    }

    public function test_handled_html_error_response_is_protected(): void
    {
        $response = $this->get('/_clickjacking-test/forbidden');

        $response->assertForbidden();
        $this->assertProtectedHtmlResponse($response);
    }

    public function test_framework_rendered_forbidden_response_is_protected(): void
    {
        $response = $this->get('/_clickjacking-test/aborted');

        $response->assertForbidden();
        $this->assertProtectedHtmlResponse($response);
    }

    public function test_framework_rendered_not_found_response_is_protected(): void
    {
        $response = $this->get('/_definitely-missing-clickjacking-test');

        $response->assertNotFound();
        $this->assertProtectedHtmlResponse($response);
    }

    public function test_existing_non_framing_csp_directives_are_preserved(): void
    {
        $case = $this->fixtureCase(
            'Clickjacking/clickjacking.json',
            'HTML response with unrelated CSP directives'
        );
        $response = $this->get('/_clickjacking-test/existing-csp');

        $response->assertOk();
        $response->assertHeader(
            'Content-Security-Policy',
            $case['expected']['content_security_policy']
        );
        $response->assertHeader(
            'X-Frame-Options',
            $case['expected']['x_frame_options']
        );
    }

    public function test_conflicting_framing_headers_are_replaced_without_duplicates(): void
    {
        $case = $this->fixtureCase(
            'Clickjacking/clickjacking.json',
            'HTML response with conflicting framing headers'
        );
        $response = $this->get('/_clickjacking-test/conflicting-headers');

        $response->assertOk();
        $response->assertHeader(
            'Content-Security-Policy',
            $case['expected']['content_security_policy']
        );
        $response->assertHeader(
            'X-Frame-Options',
            $case['expected']['x_frame_options']
        );
        $this->assertSame(
            1,
            substr_count(
                strtolower($response->headers->get('Content-Security-Policy')),
                'frame-ancestors'
            )
        );
        $this->assertStringNotContainsString(
            $case['expected']['rejected_value'],
            $response->headers->get('Content-Security-Policy')
        );
    }

    public function test_non_html_response_is_not_given_document_framing_headers(): void
    {
        $case = $this->fixtureCase(
            'Clickjacking/clickjacking.json',
            'JSON response is outside document framing scope'
        );

        $this->assertFalse($case['expected']['headers_apply']);
        $this->getJson('/_clickjacking-test/json')
            ->assertOk()
            ->assertHeaderMissing('Content-Security-Policy')
            ->assertHeaderMissing('X-Frame-Options');
    }

    private function assertProtectedHtmlResponse(TestResponse $response): void
    {
        $response->assertHeader('Content-Security-Policy', self::CSP);
        $response->assertHeader('X-Frame-Options', self::X_FRAME_OPTIONS);
        $this->assertSame([self::CSP], $response->headers->all('Content-Security-Policy'));
        $this->assertSame(
            [self::X_FRAME_OPTIONS],
            $response->headers->all('X-Frame-Options')
        );
    }
}
