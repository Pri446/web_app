<?php

namespace App\Http\Middleware;

use Closure;

class PreventPiiResponseCaching
{
    /**
     * Password fields used only for short-lived PII step-up authentication.
     *
     * These values must remain available while the controller verifies the
     * password. They are removed in a finally block before the request leaves
     * this middleware, including when the controller throws an exception.
     */
    private const SENSITIVE_CREDENTIAL_FIELDS = [
        'current_password',
        'export_password',
        'application_export_password',
        'property_tax_export_password',
    ];

    public function handle($request, Closure $next)
    {
        try {
            $response = $next($request);
        } finally {
            // Do not leave the submitted password in Laravel's parsed request
            // bag where exception reporters or later middleware could read it.
            foreach (self::SENSITIVE_CREDENTIAL_FIELDS as $field) {
                $request->request->remove($field);
            }
        }

        $response->headers->set(
            'Cache-Control',
            'no-store, no-cache, must-revalidate, private, max-age=0'
        );
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');

        return $response;
    }
}
