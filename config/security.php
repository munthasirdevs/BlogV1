<?php

return [
    'headers' => [
        'X-Frame-Options' => 'DENY',
        'X-Content-Type-Options' => 'nosniff',
        'Referrer-Policy' => 'strict-origin-when-cross-origin',
        'Permissions-Policy' => 'camera=(), microphone=()',
    ],
    'rate_limiting' => [
        'api' => env('API_RATE_LIMIT', 60),
        'auth' => env('AUTH_RATE_LIMIT', 5),
        'ai' => env('AI_RATE_LIMIT', 10),
    ],
    'content_security_policy' => env('CSP_ENABLED', true),
];
