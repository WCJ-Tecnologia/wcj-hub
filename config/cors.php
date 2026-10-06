<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS)
    |--------------------------------------------------------------------------
    |
    | The app is same-origin (Inertia), so nothing needs CORS by default. Only
    | the public health-check is listed, and only for origins declared in
    | CORS_ALLOWED_ORIGINS (comma separated). Never "*" on a route with data.
    |
    */

    'paths' => ['api/health'],

    'allowed_methods' => ['GET'],

    'allowed_origins' => array_values(array_filter(array_map('trim', explode(',', (string) env('CORS_ALLOWED_ORIGINS', ''))))),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['Accept'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];
