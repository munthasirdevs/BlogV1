<?php

namespace App\Services;

use App\Models\SystemLog;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CacheService
{
    public function remember(string $key, int $ttl, callable $callback): mixed
    {
        $tenantKey = $this->tenantKey($key);

        $start = microtime(true);
        $result = Cache::remember($tenantKey, $ttl, $callback);
        $duration = (microtime(true) - $start) * 1000;

        if ($duration > 100) {
            Log::warning('Slow cache miss', ['key' => $tenantKey, 'duration_ms' => round($duration, 2)]);
        }

        return $result;
    }

    public function rememberForever(string $key, callable $callback): mixed
    {
        return Cache::rememberForever($this->tenantKey($key), $callback);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return Cache::get($this->tenantKey($key), $default);
    }

    public function put(string $key, mixed $value, int $ttl = 3600): void
    {
        Cache::put($this->tenantKey($key), $value, $ttl);
    }

    public function forget(string $key): void
    {
        Cache::forget($this->tenantKey($key));
    }

    public function forgetByPattern(string $pattern): void
    {
        $driver = config('cache.default');

        if (in_array($driver, ['redis', 'memcached'])) {
            try {
                $store = Cache::getStore();
                if (method_exists($store, 'connection')) {
                    $connection = $store->connection();
                    if ($driver === 'redis') {
                        $prefix = config('cache.prefix', 'laravel') . ':';
                        $keys = $connection->keys($prefix . $pattern);
                        foreach ($keys as $key) {
                            $connection->del($key);
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::warning('Cache pattern flush fallback', ['pattern' => $pattern]);
                Cache::flush();
            }
        } else {
            $prefix = config('cache.prefix', 'laravel_cache') . ':';
            $cleanPattern = str_replace(['*', ':'], ['', ''], $pattern);
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
            'categories' => 7200,
            'category_tree' => 7200,
            'ai_result' => config('performance.ai.cache_ttl', 3600),
            'search' => 300,
            'autocomplete' => 300,
            'analytics' => 1800,
            'billing' => 3600,
            'user_permissions' => 600,
            'tenant_config' => 3600,
            default => 3600,
        };
    }

    public function getCategories(): \Illuminate\Support\Collection
    {
        return $this->remember('categories:all', $this->getTtl('categories'), function () {
            return \App\Models\Category::published()
                ->withCount('posts')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get();
        });
    }

    public function getCategoryTree(): \Illuminate\Support\Collection
    {
        return $this->remember('categories:tree', $this->getTtl('category_tree'), function () {
            return \App\Models\Category::whereNull('parent_id')
                ->with(['children' => fn($q) => $q->withCount('posts')->orderBy('sort_order')])
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get();
        });
    }

    public function getCategoryBySlug(string $slug): ?\App\Models\Category
    {
        return $this->remember("category:slug:{$slug}", $this->getTtl('category'), function () use ($slug) {
            return \App\Models\Category::published()->where('slug', $slug)->first();
        });
    }

    public function invalidateCategories(): void
    {
        $this->forgetByPattern('categories:*');
        $this->forgetByPattern('category:*');
    }

    public function getRedisInfo(): array
    {
        try {
            if (config('cache.default') === 'redis') {
                $store = Cache::getStore();
                $connection = $store->connection();
                $info = $connection->info();
                return [
                    'used_memory' => $info['used_memory_human'] ?? 'N/A',
                    'hit_ratio' => $this->calculateHitRatio($connection),
                    'keys' => $connection->dbsize(),
                    'uptime' => $info['uptime_in_days'] ?? 0,
                ];
            }
        } catch (\Exception $e) {
        }
        return ['status' => 'not_available'];
    }

    protected function tenantKey(string $key): string
    {
        $tenantId = tenant_id();
        if ($tenantId) {
            return "tenant:{$tenantId}:{$key}";
        }
        return $key;
    }

    protected function calculateHitRatio($connection): float
    {
        try {
            $hits = $connection->get('keyspace_hits') ?? 0;
            $misses = $connection->get('keyspace_misses') ?? 1;
            $total = $hits + $misses;
            return $total > 0 ? round($hits / $total * 100, 2) : 0;
        } catch (\Exception $e) {
            return 0;
        }
    }
}
