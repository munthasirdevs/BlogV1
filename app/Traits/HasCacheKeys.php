<?php

namespace App\Traits;

use App\Services\CacheService;
use Illuminate\Support\Facades\Cache;

trait HasCacheKeys
{
    protected static ?CacheService $cacheService = null;

    protected static function getCacheService(): CacheService
    {
        if (self::$cacheService === null) {
            self::$cacheService = app(CacheService::class);
        }
        return self::$cacheService;
    }

    public function getCacheKey(string $suffix = ''): string
    {
        $key = $this->cachePrefix() . ':' . $this->getKey();

        if ($suffix) {
            $key .= ':' . $suffix;
        }

        return $key;
    }

    public function cachePrefix(): string
    {
        return defined('static::CACHE_PREFIX') ? static::CACHE_PREFIX : strtolower(class_basename(static::class));
    }

    public function invalidateOnUpdate(): void
    {
        self::getCacheService()->forget($this->getCacheKey());
    }

    public function invalidateOnDelete(): void
    {
        self::getCacheService()->forget($this->getCacheKey());
    }

    public static function bootHasCacheKeys(): void
    {
        static::saved(function ($model) {
            $model->invalidateOnUpdate();
        });

        static::deleted(function ($model) {
            $model->invalidateOnDelete();
        });
    }

    protected function flushCacheTags(array $tags): void
    {
        try {
            if (config('cache.default') === 'redis' || config('cache.default') === 'memcached') {
                Cache::tags($tags)->flush();
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('Cache tag flush not supported', [
                'driver' => config('cache.default'),
                'tags' => $tags,
            ]);
        }
    }
}
