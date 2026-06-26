<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Deployment Configuration
    |--------------------------------------------------------------------------
    |
    | Centralized deployment settings for the XenonBlog platform.
    |
    */

    'environments' => [
        'local' => [
            'url' => env('APP_URL', 'http://localhost:8000'),
            'debug' => true,
            'cache_driver' => 'file',
            'queue_driver' => 'sync',
        ],
        'staging' => [
            'url' => env('APP_URL', 'https://staging.xenonblog.com'),
            'debug' => false,
            'cache_driver' => 'redis',
            'queue_driver' => 'redis',
        ],
        'production' => [
            'url' => env('APP_URL', 'https://xenonblog.com'),
            'debug' => false,
            'cache_driver' => 'redis',
            'queue_driver' => 'redis',
        ],
    ],

    'health_checks' => [
        'database' => env('HEALTH_CHECK_DB', true),
        'redis' => env('HEALTH_CHECK_REDIS', true),
        'queue' => env('HEALTH_CHECK_QUEUE', true),
        'storage' => env('HEALTH_CHECK_STORAGE', true),
    ],

    'maintenance' => [
        'allowed_ips' => explode(',', env('MAINTENANCE_ALLOWED_IPS', '')),
    ],

    'backup' => [
        'database' => env('BACKUP_DATABASE', true),
        'storage' => env('BACKUP_STORAGE', false),
        'retention_days' => env('BACKUP_RETENTION_DAYS', 30),
    ],
];
