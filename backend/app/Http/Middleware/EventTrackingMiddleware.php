<?php

namespace App\Http\Middleware;

use App\Models\AnalyticsEvent;
use App\Models\ActiveSession;
use App\Services\AnalyticsService;
use App\Helpers\UserAgentParser;
use App\Helpers\GeoLocation;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class EventTrackingMiddleware
 *
 * Middleware for automatically tracking analytics events.
 * Tracks page views, captures request metadata, and updates active sessions.
 * Uses async logging to avoid blocking responses.
 */
class EventTrackingMiddleware
{
    /**
     * Paths to exclude from tracking.
     */
    protected array $exceptPaths = [
        // Static assets
        'css/*',
        'js/*',
        'images/*',
        'img/*',
        'fonts/*',
        'assets/*',
        'static/*',
        // API endpoints that shouldn't be tracked as page views
        'api/*/analytics/*',
        'api/*/health',
        'api/*/docs*',
        // Common static file extensions
        '*.css',
        '*.js',
        '*.png',
        '*.jpg',
        '*.jpeg',
        '*.gif',
        '*.svg',
        '*.ico',
        '*.woff',
        '*.woff2',
        '*.ttf',
        '*.eot',
        '*.pdf',
        '*.zip',
        '*.mp4',
        '*.mp3',
        '*.webm',
        '*.webp',
        // Health and monitoring
        'health*',
        'ready',
        'live',
        'favicon.ico',
        'robots.txt',
        'sitemap.xml',
        // Admin and system
        '_debugbar/*',
        '_tt/*',
        'telescope/*',
        'horizon/*',
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
        // Store landing page for first request in session
        if (!$request->session()->has('analytics_landing_page')) {
            $request->session()->put('analytics_landing_page', $request->fullUrl());
        }

        // Generate or get session ID
        if (!$request->session()->has('analytics_session_id')) {
            $request->session()->put('analytics_session_id', $this->generateSessionId());
        }

        return $next($request);
    }

    /**
     * Handle tasks after response is sent.
     *
     * @param Request $request
     * @param Response $response
     * @return void
     */
    public function terminate(Request $request, Response $response): void
    {
        // Skip if path should be excluded
        if ($this->shouldSkipTracking($request)) {
            return;
        }

        // Skip non-GET requests for page view tracking
        if ($request->method() !== 'GET') {
            return;
        }

        // Skip if response is not successful
        if ($response->getStatusCode() >= 400) {
            return;
        }

        // Track asynchronously using Laravel's deferred tasks
        // This ensures tracking doesn't block the response
        if (function_exists('fastcgi_finish_request')) {
            // If FastCGI, we can finish the request and continue processing
            fastcgi_finish_request();
        }

        try {
            $this->trackPageView($request, $response);
            $this->updateActiveSession($request);
        } catch (\Exception $e) {
            // Log error but don't affect the response
            Log::error('Analytics tracking failed', [
                'error' => $e->getMessage(),
                'url' => $request->fullUrl(),
            ]);
        }
    }

    /**
     * Track a page view event.
     *
     * @param Request $request
     * @param Response $response
     * @return void
     */
    protected function trackPageView(Request $request, Response $response): void
    {
        $sessionId = $request->session()->get('analytics_session_id');
        $userId = $request->user()?->id;
        $url = $request->fullUrl();
        $referrer = $request->headers->get('referer');

        // Calculate response time
        $startTime = $request->server->get('REQUEST_TIME_FLOAT');
        $responseTimeMs = $startTime ? (int) ((microtime(true) - $startTime) * 1000) : null;

        // Determine event type based on URL pattern
        $eventType = $this->determineEventType($request, $url);

        // Get additional metadata
        $metadata = $this->getEventMetadata($request, $response);

        // Record the event
        AnalyticsEvent::recordWithFullTracking(
            $eventType,
            $userId,
            $metadata['post_id'] ?? null,
            $sessionId,
            $url,
            $referrer,
            $metadata,
            $responseTimeMs
        );
    }

    /**
     * Update active session.
     *
     * @param Request $request
     * @return void
     */
    protected function updateActiveSession(Request $request): void
    {
        $sessionId = $request->session()->get('analytics_session_id');
        $userId = $request->user()?->id;
        $currentUrl = $request->fullUrl();

        // Get page title from response content (for HTML responses)
        $pageTitle = $this->extractPageTitle($request);

        // Update or create active session
        ActiveSession::findOrCreate(
            $sessionId,
            $userId,
            $request->ip(),
            $request->userAgent(),
            AnalyticsEvent::generateVisitorFingerprint($request->ip(), $request->userAgent())
        );

        // Touch the session activity
        $session = ActiveSession::where('session_id', $sessionId)->first();
        if ($session) {
            $session->touchActivity($currentUrl, $pageTitle);
        }
    }

    /**
     * Determine event type based on URL pattern.
     *
     * @param Request $request
     * @param string $url
     * @return string
     */
    protected function determineEventType(Request $request, string $url): string
    {
        // Check for post view
        if (preg_match('/\/posts\/([^\/\?]+)/', $url, $matches)) {
            return AnalyticsEvent::TYPE_POST_VIEW;
        }

        // Check for category view
        if (preg_match('/\/categories\/([^\/\?]+)/', $url, $matches)) {
            return AnalyticsEvent::TYPE_CATEGORY_VIEW;
        }

        // Check for tag view
        if (preg_match('/\/tags\/([^\/\?]+)/', $url, $matches)) {
            return AnalyticsEvent::TYPE_TAG_VIEW;
        }

        // Check for author view
        if (preg_match('/\/users\/(\d+)/', $url, $matches) || preg_match('/\/authors\/([^\/\?]+)/', $url, $matches)) {
            return AnalyticsEvent::TYPE_AUTHOR_VIEW;
        }

        // Check for search
        if (str_contains($url, '/search') || $request->has('q') || $request->has('query')) {
            return AnalyticsEvent::TYPE_SEARCH;
        }

        // Default to page view
        return AnalyticsEvent::TYPE_PAGE_VIEW;
    }

    /**
     * Get event metadata.
     *
     * @param Request $request
     * @param Response $response
     * @return array
     */
    protected function getEventMetadata(Request $request, Response $response): array
    {
        $metadata = [
            'method' => $request->method(),
            'path' => $request->path(),
            'query_params' => $request->query->all(),
            'response_status' => $response->getStatusCode(),
        ];

        // Extract post ID if viewing a post
        if (preg_match('/\/posts\/(\d+)/', $request->path(), $matches)) {
            $metadata['post_id'] = (int) $matches[1];
        }

        // Extract search query if searching
        if ($request->has('q')) {
            $metadata['search_query'] = $request->get('q');
        }

        return $metadata;
    }

    /**
     * Extract page title from response.
     *
     * @param Request $request
     * @return string|null
     */
    protected function extractPageTitle(Request $request): ?string
    {
        // For API requests, use the path as title
        if ($request->expectsJson()) {
            return 'API: ' . $request->path();
        }

        // For HTML responses, try to extract title
        // This is a simplified approach - in production you might want to parse the HTML
        $segments = explode('/', trim($request->path(), '/'));
        $lastSegment = end($segments);

        if ($lastSegment) {
            return ucwords(str_replace(['-', '_'], ' ', $lastSegment));
        }

        return 'Home';
    }

    /**
     * Check if tracking should be skipped for this request.
     *
     * @param Request $request
     * @return bool
     */
    protected function shouldSkipTracking(Request $request): bool
    {
        $path = $request->path();
        $url = $request->fullUrl();

        // Skip if path is empty (root)
        if (empty($path)) {
            return false;
        }

        foreach ($this->exceptPaths as $except) {
            if ($this->pathMatches($path, $except)) {
                return true;
            }

            if ($this->pathMatches($url, $except)) {
                return true;
            }
        }

        // Skip bot traffic
        if (UserAgentParser::isBot($request->userAgent() ?? '')) {
            return true;
        }

        return false;
    }

    /**
     * Check if path matches pattern.
     *
     * @param string $path
     * @param string $pattern
     * @return bool
     */
    protected function pathMatches(string $path, string $pattern): bool
    {
        // Convert glob pattern to regex
        $regex = str_replace(
            ['*', '/'],
            ['.*', '\/'],
            $pattern
        );

        return (bool) preg_match('/^' . $regex . '$/i', $path);
    }

    /**
     * Generate a unique session ID.
     *
     * @return string
     */
    protected function generateSessionId(): string
    {
        return bin2hex(random_bytes(16));
    }
}
