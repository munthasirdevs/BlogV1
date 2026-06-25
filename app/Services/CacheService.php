<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class CacheService
{
    public function remember(string $key, int $ttl, callable $callback): mixed
    {
        return Cache::remember($key, $ttl, $callback);
    }

    public function forget(string $key): void
    {
        Cache::forget($key);
    }

    public function forgetByPattern(string $pattern): void
    {
        // Redis supports key scanning by pattern; file cache requires store-level flush
        if (config('cache.default') === 'redis') {
            $keys = Cache::getRedis()->keys($pattern);
            foreach ($keys as $key) {
                Cache::forget($key);
            }
        } else {
            // For file/database cache, clear entire cache as fallback
            // since most file caches lack pattern-based clearing
            $prefix = config('cache.prefix', 'laravel_cache') . ':';
            $pattern = str_replace('*', '', $pattern);
            $pattern = str_replace(':', '', $pattern);

            // Only flush if we can't pattern-match precisely
            Cache::flush();
        }
    }

    public function getTtl(string $context): int
    {
        return match ($context) {
            'homepage' => 3600,
            'post' => 3600,
            'category' => 7200,
            'tag' => 7200,
            'settings' => 86400,
            default => 3600,
        };
    }
}
