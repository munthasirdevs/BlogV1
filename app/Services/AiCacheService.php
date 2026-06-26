<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class AiCacheService
{
    public function __construct(
        protected CacheService $cacheService
    ) {}

    public function remember(string $prompt, string $type, callable $callback): mixed
    {
        $hash = $this->normalizePrompt($prompt);
        $key = "ai:{$type}:{$hash}";
        $ttl = $this->cacheService->getTtl('ai_result');

        return $this->cacheService->remember($key, $ttl, function () use ($callback, $prompt, $type, $hash) {
            $start = microtime(true);
            $result = $callback();
            $duration = (microtime(true) - $start) * 1000;

            Log::info('AI cache miss', [
                'type' => $type,
                'hash' => substr($hash, 0, 8),
                'duration_ms' => round($duration, 1),
            ]);

            return $result;
        });
    }

    public function forget(string $prompt, string $type): void
    {
        $hash = $this->normalizePrompt($prompt);
        $this->cacheService->forget("ai:{$type}:{$hash}");
    }

    public function forgetByType(string $type): void
    {
        $this->cacheService->forgetByPattern("ai:{$type}:*");
    }

    public function flushAll(): void
    {
        $this->cacheService->forgetByPattern('ai:*');
    }

    protected function normalizePrompt(string $prompt): string
    {
        $normalized = preg_replace('/\s+/', ' ', trim($prompt));
        $normalized = mb_strtolower($normalized);
        return md5($normalized);
    }
}
