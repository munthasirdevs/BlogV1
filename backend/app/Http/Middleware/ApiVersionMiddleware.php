<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class ApiVersionMiddleware
 *
 * Adds API version headers to all responses.
 */
class ApiVersionMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Add API version headers
        $response->headers->set('X-API-Version', 'v1');
        $response->headers->set('X-API-Vendor', 'application/vnd.blog.v1+json');

        // Add deprecation warning if using old version
        if (str_contains($request->header('Accept', ''), 'application/vnd.blog.v0')) {
            $response->headers->set('Deprecation', 'true');
            $response->headers->set('Sunset', now()->addMonths(6)->format(\DateTimeInterface::RFC7231));
        }

        return $response;
    }
}
