<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    /*
     * You can enable CORS for 1 or multiple paths.
     */
    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    /*
     * Allowed origins. Use environment variable for flexibility.
     * Example: http://localhost:3000 or https://yourdomain.com
     */
    'allowed_origins' => explode(',', env('FRONTEND_URL', 'http://localhost:3000')),

    /*
     * You can allow patterns for origins (e.g., *.example.com).
     */
    'allowed_origin_patterns' => [],

    /*
     * Allowed headers (CORS requests).
     */
    'allowed_headers' => ['*'],

    /*
     * Allowed HTTP methods for CORS requests.
     */
    'allowed_methods' => ['*'],

    /*
     * Exposed headers (response headers that browsers can access).
     */
    'exposed_headers' => [
        'Content-Length',
        'X-Request-Id',
        'X-Request-Time',
    ],

    /*
     * Max age of the preflight request cache (in seconds).
     */
    'max_age' => 0,

    /*
     * Whether the browser should include credentials (cookies, authorization headers).
     */
    'supports_credentials' => true,

];
