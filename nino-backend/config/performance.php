<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Dashboard Cache TTL
    |--------------------------------------------------------------------------
    |
    | The dashboard payload is read-heavy and safe to cache briefly because
    | it is summary data for the admin shell rather than transaction-critical
    | write paths.
    |
    */
    'dashboard_cache_ttl_seconds' => (int) env('PERF_DASHBOARD_CACHE_TTL_SECONDS', 600),

    /*
    |--------------------------------------------------------------------------
    | Settings Cache TTL
    |--------------------------------------------------------------------------
    |
    | Settings are reference data. They are invalidated on write, so a longer
    | read TTL is safe and keeps repeated settings lookups off the database.
    |
    */
    'settings_cache_ttl_seconds' => (int) env('PERF_SETTINGS_CACHE_TTL_SECONDS', 3600),

    /*
    |--------------------------------------------------------------------------
    | Queue Names
    |--------------------------------------------------------------------------
    |
    | These names keep async workloads explicit. Notification delivery stays
    | isolated from default jobs so queue health is easier to reason about.
    |
    */
    'queues' => [
        'notifications' => 'notifications',
        'media' => 'media',
    ],
];
