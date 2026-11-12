<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;

class CwisFormResponse
{
    public function handle($request, Closure $next)
    {
        $response = $next($request);
        // Keep flash messages for the actual browser navigation, not a fetch-followed redirect.
        if ($request->expectsJson() && !$request->isMethod('GET') && $response instanceof RedirectResponse) {
            if (session()->has('errors')) {
                $errors = session('errors')->getBag('default')->messages();
                session()->forget('errors');
                return response()->json(['errors' => $errors], 422);
            }
            return response()->json(['redirect' => $response->getTargetUrl()]);
        }
        return $response;
    }
}
