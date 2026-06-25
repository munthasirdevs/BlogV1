<?php

return [
    'enabled' => env('CDN_ENABLED', false),
    'url' => env('CDN_URL', ''),
    'media_prefix' => env('CDN_MEDIA_PREFIX', 'media'),
    'cache_control' => 'public, max-age=31536000, immutable',
];
