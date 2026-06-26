<?php

namespace App\Http\Middleware;

use App\Models\AnalyticsEvent;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class DetectSuspiciousActivity
{
    public function handle(Request $request, Closure $next): Response
    {
        $ip = $request->ip() ?? 'unknown';
        $key = 'suspicious:' . $ip;
        $maxAttempts = 100;

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            Log::warning('Suspicious activity detected: IP blocked', ['ip' => $ip, 'path' => $request->path()]);

            AnalyticsEvent::create([
                'event_type' => 'suspicious_activity',
                'ip_hash' => hash('sha256', $ip),
                'url' => $request->fullUrl(),
                'metadata' => ['reason' => 'rate_limit_exceeded'],
                'created_at' => now(),
            ]);

            return response()->json(['error' => 'Too many requests'], 429);
        }

        RateLimiter::hit($key, 60);

        return $next($request);
    }
}
