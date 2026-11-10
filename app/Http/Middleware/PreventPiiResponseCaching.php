<?php

namespace App\Http\Middleware;

use Closure;

class PreventPiiResponseCaching
{
    public function handle($request, Closure $next)
    {
        $response = $next($request);

        $response->headers->set(
            'Cache-Control',
            'no-store, no-cache, must-revalidate, private, max-age=0'
        );
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');

        return $response;
    }
}
