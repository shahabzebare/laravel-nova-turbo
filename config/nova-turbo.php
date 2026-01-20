<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Auto-refresh in Development
    |--------------------------------------------------------------------------
    |
    | When enabled, lazy loading is disabled in local environment so you can
    | see resource changes immediately without running the cache command.
    |
    */
    'auto_refresh_in_dev' => env('NOVA_TURBO_AUTO_REFRESH', true),

    /*
    |--------------------------------------------------------------------------
    | Resource Paths
    |--------------------------------------------------------------------------
    |
    | Paths to scan for Nova resources. By default, only app/Nova is scanned.
    | Add additional paths if you have resources in modules or other locations.
    |
    */
    'resource_paths' => [
        app_path('Nova'),
    ],
];
