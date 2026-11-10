<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * This namespace is applied to your controller routes.
     *
     * In addition, it is set as the URL generator's root namespace.
     *
     * @var string
     */
    protected $namespace = 'App\Http\Controllers';
    /**
     * The path to the "home" route for your application.
     *
     * This is used by Laravel authentication to redirect users after login.
     *
     * @var string
     */
    public const HOME = '/';

    /**
     * The controller namespace for the application.
     *
     * When present, controller route declarations will automatically be prefixed with this namespace.
     *
     * @var string|null
     */
    // protected $namespace = 'App\\Http\\Controllers';

    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @return void
     */
    public function boot()
    {
        $this->configureRateLimiting();

        $this->routes(function () {
            Route::prefix('api')
                ->middleware('api')
                ->namespace($this->namespace)
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->namespace($this->namespace)
                ->group(base_path('routes/web.php'));
        });
    }

    /**
     * Configure the rate limiters for the application.
     *
     * @return void
     */
    protected function configureRateLimiting()
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by(optional($request->user())->id ?: $request->ip());
        });

        $webLoginLimitResponse = function (Request $request, array $headers) {
            $retryAfter = (int) ($headers['Retry-After'] ?? 60);
            $message = trans('auth.throttle', ['seconds' => $retryAfter]);

            return redirect()->back()
                ->withInput($request->only('username'))
                ->withErrors(['username' => $message])
                ->withHeaders($headers);
        };

        $apiLoginLimitResponse = function (Request $request, array $headers) {
            $retryAfter = (int) ($headers['Retry-After'] ?? 60);

            return response()->json([
                'status' => false,
                'message' => trans('auth.throttle', ['seconds' => $retryAfter]),
            ], 429, $headers);
        };

        $requestLimits = function (
            Request $request,
            string $identifierField,
            string $keyPrefix,
            callable $response,
            string $configuration = 'login_rate_limit'
        ) {
            $input = $request->input($identifierField);
            $identifier = Str::lower(trim(is_string($input) ? $input : ''));
            $identifierHash = hash('sha256', $identifier);
            $ip = $request->ip();

            $identityIp = config('security.'.$configuration.'.identity_ip');
            $ipOnly = config('security.'.$configuration.'.ip');
            $identityOnly = config('security.'.$configuration.'.identity');

            return [
                Limit::perMinutes(
                    max(1, (int) $identityIp['decay_minutes']),
                    max(1, (int) $identityIp['max_attempts'])
                )->by($keyPrefix.'-identity-ip:'.$identifierHash.'|'.$ip)->response($response),
                Limit::perMinutes(
                    max(1, (int) $ipOnly['decay_minutes']),
                    max(1, (int) $ipOnly['max_attempts'])
                )->by($keyPrefix.'-ip:'.$ip)->response($response),
                Limit::perMinutes(
                    max(1, (int) $identityOnly['decay_minutes']),
                    max(1, (int) $identityOnly['max_attempts'])
                )->by($keyPrefix.'-identity:'.$identifierHash)->response($response),
            ];
        };

        RateLimiter::for('web-login', function (Request $request) use ($requestLimits, $webLoginLimitResponse) {
            return $requestLimits($request, 'username', 'web-login', $webLoginLimitResponse);
        });

        RateLimiter::for('api-login', function (Request $request) use ($requestLimits, $apiLoginLimitResponse) {
            return $requestLimits($request, 'email', 'api-login', $apiLoginLimitResponse);
        });

        $formLimitResponse = function (string $messageKey, string $errorKey, array $fields, bool $contact) {
            return function (Request $request, array $headers) use ($messageKey, $errorKey, $fields, $contact) {
                $message = trans($messageKey, ['seconds' => (int) $headers['Retry-After']]);

                if ($request->expectsJson()) {
                    return response()->json(['status' => false, 'message' => $message], 429, $headers);
                }

                return redirect($contact ? url('/').'#contact' : route('password.request'))
                    ->withErrors([$errorKey => $message])
                    ->withInput($request->only($fields))
                    ->withHeaders($headers);
            };
        };

        $passwordResetResponse = $formLimitResponse('forms.password_reset_throttle', 'email', ['email'], false);
        RateLimiter::for('password-reset', function (Request $request) use ($requestLimits, $passwordResetResponse) {
            return $requestLimits($request, 'email', 'password-reset', $passwordResetResponse, 'password_reset_rate_limit');
        });

        $contactResponse = $formLimitResponse('forms.contact_throttle', 'contact_rate_limit', ['name', 'email', 'subject', 'message'], true);
        RateLimiter::for('contact', function (Request $request) use ($requestLimits, $contactResponse) {
            return $requestLimits($request, 'email', 'contact', $contactResponse, 'contact_rate_limit');
        });

    }
}
