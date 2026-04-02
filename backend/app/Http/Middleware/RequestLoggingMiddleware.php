<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class RequestLoggingMiddleware
 *
 * Middleware for logging all API requests with method, path, IP, user ID,
 * response status, and duration. Excludes sensitive endpoints.
 */
class RequestLoggingMiddleware
{
    /**
     * Endpoints to exclude from logging.
     */
    protected array $except = [
        'api/v1/auth/login',
        'api/v1/auth/register',
        'api/v1/auth/forgot-password',
        'api/v1/auth/reset-password',
        'api/v1/auth/verify*',
        'api/v1/auth/resend*',
        'health',
        'health/*',
        'debug',
        'debug/*',
    ];

    /**
     * Log levels for different status codes.
     */
    protected array $logLevels = [
        '2' => 'info',      // 2xx Success
        '3' => 'info',      // 3xx Redirect
        '4' => 'warning',   // 4xx Client Error
        '5' => 'error',     // 5xx Server Error
    ];

    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next): mixed
    {
        // Skip if logging is disabled
        if (!config('blog.request_logging.enabled', true)) {
            return $next($request);
        }

        // Skip excluded endpoints
        if ($this->isExcluded($request)) {
            return $next($request);
        }

        // Record start time
        $startTime = microtime(true);
        $startMemory = memory_get_usage();

        // Generate or get request ID
        $requestId = $request->header('X-Request-ID') ?? uniqid('req_');

        // Store request ID in header for response
        $request->headers->set('X-Request-ID', $requestId);

        // Process request
        $response = $next($request);

        // Calculate duration
        $duration = round((microtime(true) - $startTime) * 1000, 2); // milliseconds
        $memoryUsed = memory_get_usage() - $startMemory;

        // Log the request
        $this->logRequest($request, $response, $duration, $memoryUsed, $requestId);

        // Add timing header to response
        $response->headers->set('X-Response-Time', "{$duration}ms");
        $response->headers->set('X-Request-ID', $requestId);

        return $response;
    }

    /**
     * Log the request details.
     *
     * @param Request $request
     * @param Response $response
     * @param float $duration
     * @param int $memoryUsed
     * @param string $requestId
     * @return void
     */
    protected function logRequest(
        Request $request,
        Response $response,
        float $duration,
        int $memoryUsed,
        string $requestId
    ): void {
        // Determine log level based on status code
        $statusCode = $response->getStatusCode();
        $level = $this->logLevels[substr((string) $statusCode, 0, 1)] ?? 'info';

        // Build log context
        $context = [
            'request_id' => $requestId,
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'path' => $request->path(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'status' => $statusCode,
            'duration_ms' => $duration,
            'memory_used' => $this->formatMemory($memoryUsed),
            'user_id' => Auth::id(),
            'user_email' => Auth::user()?->email,
            'referer' => $request->headers->get('referer'),
        ];

        // Add request body for non-GET requests (excluding sensitive data)
        if ($request->method() !== 'GET' && !empty($request->all())) {
            $context['request_body'] = $this->sanitizeRequestBody($request->all());
        }

        // Add response size
        $context['response_size'] = strlen($response->getContent());

        // Log slow requests with higher priority
        if ($duration > config('blog.request_logging.slow_threshold_ms', 1000)) {
            $context['slow_request'] = true;
            $level = 'warning';
        }

        Log::channel(config('blog.request_logging.channel', 'api'))->{$level}(
            "{$request->method()} {$request->path()}",
            $context
        );
    }

    /**
     * Check if the request should be excluded from logging.
     *
     * @param Request $request
     * @return bool
     */
    protected function isExcluded(Request $request): bool
    {
        $path = $request->path();

        foreach ($this->except as $except) {
            if (str_ends_with($except, '*')) {
                $pattern = rtrim($except, '*');
                if (str_starts_with($path, $pattern)) {
                    return true;
                }
            } elseif ($path === $except) {
                return true;
            }
        }

        return false;
    }

    /**
     * Sanitize request body by removing sensitive fields.
     *
     * @param array $body
     * @return array
     */
    protected function sanitizeRequestBody(array $body): array
    {
        $sensitive = [
            'password',
            'password_confirmation',
            'current_password',
            'token',
            'api_key',
            'apikey',
            'secret',
            'credit_card',
            'card_number',
            'cvv',
        ];

        foreach ($sensitive as $field) {
            if (isset($body[$field])) {
                $body[$field] = '***REDACTED***';
            }
        }

        return $body;
    }

    /**
     * Format memory usage.
     *
     * @param int $bytes
     * @return string
     */
    protected function formatMemory(int $bytes): string
    {
        $units = ['B', 'KB', 'MB'];
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Terminate method for any cleanup.
     *
     * @param Request $request
     * @param Response $response
     * @return void
     */
    public function terminate(Request $request, Response $response): void
    {
        // Any post-response logging or cleanup can go here
    }
}
