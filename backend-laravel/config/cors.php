<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Configure which origins, methods, and headers are allowed.
    | Adjust 'allowed_origins' per environment via .env:
    |   CORS_ALLOWED_ORIGINS=http://localhost:4200,https://app.meetlyai.com
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => explode(',', env('CORS_ALLOWED_ORIGINS', 'http://localhost:63301,http://localhost:4200,http://127.0.0.1:4200')),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => ['X-Total-Count', 'X-Page-Count'],

    'max_age' => 86400, // 24 hours preflight cache

    'supports_credentials' => true,

];