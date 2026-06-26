<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class MeasureRequestTime
{
    public function handle(Request $request, Closure $next): Response
    {
        $start = microtime(true);
        $response = $next($request);
        $duration = (microtime(true) - $start) * 1000;

        if ($duration > config('performance.database.slow_query_threshold', 500)) {
            Log::warning('Slow request detected', [
                'method' => $request->method(),
                'url' => $request->fullUrl(),
                'duration_ms' => round($duration, 2),
            ]);
        }

        if ($request->expectsJson() || $request->ajax()) {
            $response->headers->set('X-Response-Time', round($duration, 2) . 'ms');
        }

        return $response;
    }
}
