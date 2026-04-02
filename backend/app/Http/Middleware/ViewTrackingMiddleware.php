<?php

namespace App\Http\Middleware;

use App\Models\Post;
use App\Services\ViewService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class ViewTrackingMiddleware
 *
 * Automatically tracks post views when viewing a post detail page.
 * Implements unique view detection with 24-hour window.
 *
 * @package App\Http\Middleware
 */
class ViewTrackingMiddleware
{
    /**
     * The view service instance.
     *
     * @var ViewService
     */
    protected ViewService $viewService;

    /**
     * Routes to exclude from tracking.
     *
     * @var array
     */
    protected array $except = [
        'api/*/posts', // List endpoints
        'api/*/posts/*/edit',
        'api/*/posts/*/preview',
    ];

    /**
     * Constructor.
     *
     * @param ViewService $viewService
     */
    public function __construct(ViewService $viewService)
    {
        $this->viewService = $viewService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $response = $next($request);

        // Only track on successful GET/HEAD requests
        if (!$this->shouldTrack($request, $response)) {
            return $response;
        }

        // Extract post from route
        $post = $request->route('post');

        if (!$post instanceof Post) {
            return $response;
        }

        // Track the view
        $this->trackView($post, $request);

        return $response;
    }

    /**
     * Determine if the request should be tracked.
     *
     * @param Request $request
     * @param Response $response
     * @return bool
     */
    protected function shouldTrack(Request $request, Response $response): bool
    {
        // Only track successful responses
        if (!$response->isSuccessful()) {
            return false;
        }

        // Only track GET and HEAD requests
        if (!in_array($request->method(), ['GET', 'HEAD'])) {
            return false;
        }

        // Check excluded routes
        foreach ($this->except as $except) {
            if ($request->is($except)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Track a post view.
     *
     * @param Post $post
     * @param Request $request
     * @return void
     */
    protected function trackView(Post $post, Request $request): void
    {
        $userId = $request->user()?->id;
        $sessionId = $request->session()->getId();
        $userAgent = $request->userAgent() ?? '';
        $ipAddress = $request->ip();
        $referrer = $request->headers->get('referer');

        // Check if we should track this view
        if (!$this->viewService->shouldTrackView($post, $userId, $userAgent)) {
            return;
        }

        // Record the view
        $this->viewService->recordView(
            $post,
            $sessionId,
            $userId,
            $referrer,
            $userAgent,
            $ipAddress
        );
    }

    /**
     * Determine if the middleware should run for a given route.
     *
     * @param Request $request
     * @return bool
     */
    protected function shouldRunForRoute(Request $request): bool
    {
        // Only run for post show routes
        return $request->routeIs('api.*.posts.show') ||
               $request->routeIs('api.*.posts.*') && $request->method() === 'GET';
    }
}
