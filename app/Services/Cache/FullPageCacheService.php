<?php

namespace App\Services\Cache;

use Illuminate\Support\Facades\Cache;

class FullPageCacheService
{
    public function getKey(string $url, ?int $tenantId = null): string
    {
        $prefix = $tenantId ? "fpc:{$tenantId}" : 'fpc:global';
        return $prefix . ':' . md5($url);
    }

    public function get(string $url, ?int $tenantId = null): ?string
    {
        return Cache::get($this->getKey($url, $tenantId));
    }

    public function put(string $url, string $content, int $ttl, ?int $tenantId = null): void
    {
        Cache::put($this->getKey($url, $tenantId), $content, $ttl);
    }

    public function forget(string $url, ?int $tenantId = null): void
    {
        Cache::forget($this->getKey($url, $tenantId));
    }

    public function invalidateByPrefix(string $prefix, ?int $tenantId = null): void
    {
        $versionKey = ($tenantId ? "fpcv:{$tenantId}" : 'fpcv:global') . ":{$prefix}";
        Cache::increment($versionKey);
    }

    public function getVersion(string $prefix, ?int $tenantId = null): int
    {
        $versionKey = ($tenantId ? "fpcv:{$tenantId}" : 'fpcv:global') . ":{$prefix}";
        return (int) Cache::get($versionKey, 1);
    }
}
