<?php

return [
    /* Three minutes keeps operational data reasonably fresh while protecting the database. */
    'cache_ttl_seconds' => (int) env('DASHBOARD_CACHE_TTL_SECONDS', 180),

    /*
     * Keep the last authorized response available while it is refreshed after
     * the HTTP response. Data-changing model events still invalidate immediately.
     */
    'cache_stale_ttl_seconds' => (int) env('DASHBOARD_CACHE_STALE_TTL_SECONDS', 1800),
];
