<?php

namespace Tests\Unit;

use App\Http\Middleware\PreventPiiResponseCaching;
use Illuminate\Http\Request;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class PreventPiiResponseCachingTest extends TestCase
{
    public function test_it_prevents_browser_and_proxy_caching(): void
    {
        $middleware = new PreventPiiResponseCaching();
        $request = Request::create('/building-info/buildings/example', 'GET');

        $response = $middleware->handle($request, function () {
            return new Response('ok');
        });

        $this->assertStringContainsString(
            'no-store',
            (string) $response->headers->get('Cache-Control')
        );
        $this->assertSame('no-cache', $response->headers->get('Pragma'));
        $this->assertSame('0', $response->headers->get('Expires'));
    }

    public function test_it_removes_pii_step_up_passwords_after_the_request(): void
    {
        $middleware = new PreventPiiResponseCaching();
        $request = Request::create('/security/pii-key-backup', 'POST', [
            'current_password' => 'must-not-survive',
            'application_export_password' => 'must-not-survive',
        ]);

        $middleware->handle($request, function () {
            return new Response('ok');
        });

        $this->assertNull($request->input('current_password'));
        $this->assertNull($request->input('application_export_password'));
    }

    public function test_it_removes_pii_step_up_passwords_when_the_action_fails(): void
    {
        $middleware = new PreventPiiResponseCaching();
        $request = Request::create('/building-info/owner-pii/export', 'POST', [
            'export_password' => 'must-not-reach-exception-reporting',
        ]);

        try {
            $middleware->handle($request, function () {
                throw new RuntimeException('simulated failure');
            });
            $this->fail('The simulated exception was not thrown.');
        } catch (RuntimeException $exception) {
            $this->assertSame('simulated failure', $exception->getMessage());
        }

        $this->assertNull($request->input('export_password'));
    }
}
