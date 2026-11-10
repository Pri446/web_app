<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class PreventClickjacking
{
    /**
     * Apply the centrally configured anti-framing policy to HTML responses.
     */
    public function handle(Request $request, Closure $next)
    {
        return $this->applyHeaders($next($request));
    }

    /**
     * Apply the policy to an already-rendered response, including exception responses.
     */
    public function applyHeaders($response)
    {

        if (! $this->isHtmlResponse($response)) {
            return $response;
        }

        $contentSecurityPolicy = $response->headers->get('Content-Security-Policy');
        $frameAncestors = config('security_headers.content_security_policy');

        if ($contentSecurityPolicy) {
            $directives = array_filter(array_map('trim', explode(';', $contentSecurityPolicy)));
            $directives = array_values(array_filter($directives, function ($directive) {
                return ! preg_match('/^frame-ancestors\b/i', $directive);
            }));
            $directives[] = $frameAncestors;
            $contentSecurityPolicy = implode('; ', $directives);
        } else {
            $contentSecurityPolicy = $frameAncestors;
        }

        $response->headers->set('Content-Security-Policy', $contentSecurityPolicy);
        $response->headers->set(
            'X-Frame-Options',
            config('security_headers.x_frame_options')
        );

        return $response;
    }

    private function isHtmlResponse($response): bool
    {
        return stripos((string) $response->headers->get('Content-Type'), 'text/html') === 0;
    }
}
