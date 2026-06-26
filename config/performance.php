<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Performance Optimization Configuration
    |--------------------------------------------------------------------------
    |
    | Centralized performance settings for the entire application.
    | Tune these values based on your server capacity and traffic.
    |
    */

    'cache' => [
        'ttl' => [
            'homepage' => env('CACHE_TTL_HOMEPAGE', 3600),
            'post' => env('CACHE_TTL_POST', 3600),
            'category' => env('CACHE_TTL_CATEGORY', 7200),
            'tag' => env('CACHE_TTL_TAG', 7200),
            'settings' => env('CACHE_TTL_SETTINGS', 86400),
            'seo' => env('CACHE_TTL_SEO', 7200),
            'analytics' => env('CACHE_TTL_ANALYTICS', 1800),
        ],
        'prefix' => env('CACHE_PREFIX', 'xenonblog_cache'),
        'driver' => env('CACHE_DRIVER', 'redis'),
    ],

    'database' => [
        'slow_query_threshold' => env('DB_SLOW_QUERY_MS', 200),
        'max_connections' => env('DB_MAX_CONNECTIONS', 100),
        'recommended_engine' => 'InnoDB',
        'recommended_charset' => 'utf8mb4',
        'recommended_collation' => 'utf8mb4_unicode_ci',
    ],

    'queue' => [
        'default' => env('QUEUE_CONNECTION', 'redis'),
        'retry_after' => env('QUEUE_RETRY_AFTER', 90),
        'worker_memory' => env('QUEUE_WORKER_MEMORY', 128),
        'tries' => [
            'default' => 3,
            'ai_processing' => 2,
            'notifications' => 5,
            'media_optimization' => 3,
        ],
    ],

    'session' => [
        'driver' => env('SESSION_DRIVER', 'redis'),
        'lifetime' => env('SESSION_LIFETIME', 120),
        'lottery' => [2, 100],
    ],

    'ai' => [
        'timeout' => env('AI_API_TIMEOUT', 30),
        'retry_attempts' => env('AI_RETRY_ATTEMPTS', 2),
        'cache_ttl' => env('AI_CACHE_TTL', 3600),
        'batch_size' => env('AI_BATCH_SIZE', 5),
    ],

    'pagination' => [
        'posts_per_page' => env('POSTS_PER_PAGE', 12),
        'admin_per_page' => env('ADMIN_PER_PAGE', 20),
    ],

    'opcache' => [
        'enable' => true,
        'memory_consumption' => 256,
        'interned_strings_buffer' => 16,
        'max_accelerated_files' => 20000,
        'revalidate_freq' => 60,
        'fast_shutdown' => true,
    ],

    'cdn' => [
        'enabled' => env('CDN_ENABLED', false),
        'url' => env('CDN_URL', ''),
        'media_prefix' => env('CDN_MEDIA_PREFIX', 'media'),
    ],
];
