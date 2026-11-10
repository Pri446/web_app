<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustProxies as Middleware;
use Illuminate\Http\Request;

class TrustProxies extends Middleware
{
    /**
     * The trusted proxies for this application.
     *
     * @var array<int, string>|string|null
     */
    protected $proxies;

    /**
     * The headers that should be used to detect proxies.
     *
     * @var int
     */
    protected $headers =
        Request::HEADER_X_FORWARDED_FOR |
        Request::HEADER_X_FORWARDED_HOST |
        Request::HEADER_X_FORWARDED_PORT |
        Request::HEADER_X_FORWARDED_PROTO |
        Request::HEADER_X_FORWARDED_AWS_ELB;

    /**
     * Return only deployment-approved proxy addresses.
     *
     * @return array|string|null
     */
    protected function proxies()
    {
        $proxies = config('security.trusted_proxies');

        if (!is_string($proxies)) {
            return $proxies;
        }

        return array_values(array_filter(
            array_map('trim', explode(',', $proxies)),
            fn ($proxy) => $proxy !== '' && $proxy !== '*' && $proxy !== '**'
        ));
    }
}
