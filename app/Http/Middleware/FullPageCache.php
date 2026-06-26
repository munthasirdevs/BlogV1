<?php

namespace App\Http\Middleware;

use App\Services\Cache\FullPageCacheService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class FullPageCache
{
    public function __construct(
        private readonly FullPageCacheService $cacheService
    ) {}

    public function handle(Request $request, Closure $next, int $ttl = 3600): Response
    {
        if ($request->isMethod('GET') === false || $request->ajax() || $request->expectsJson()) {
            return $next($request);
        }

        $tenantId = function_exists('tenant_id') ? tenant_id() : null;
        $version = $this->cacheService->getVersion('global', $tenantId);
        $key = $this->cacheService->getKey($request->fullUrl() . ":v{$version}", $tenantId);

        $cached = Cache::get($key);
        if ($cached !== null) {
            return response($cached)->header('X-Cache', 'HIT');
        }

        $response = $next($request);

        if ($response->getStatusCode() === 200) {
            Cache::put($key, $response->getContent(), $ttl);
        }

        return $response->header('X-Cache', 'MISS');
    }
}
