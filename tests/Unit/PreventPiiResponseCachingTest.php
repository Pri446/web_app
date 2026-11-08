<?php

namespace Tests\Unit;

use App\Http\Middleware\PreventPiiResponseCaching;
use Illuminate\Http\Request;
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
}
