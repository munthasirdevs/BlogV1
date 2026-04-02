<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Interaction\ShareRequest;
use App\Models\Post;
use App\Services\ShareService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Class ShareController
 *
 * Controller for managing share operations and tracking.
 */
class ShareController extends Controller
{
    /**
     * The share service instance.
     *
     * @var ShareService
     */
    protected ShareService $shareService;

    /**
     * ShareController constructor.
     *
     * @param ShareService $shareService
     */
    public function __construct(ShareService $shareService)
    {
        $this->shareService = $shareService;
    }

    /**
     * Track a share.
     *
     * @param Post $post
     * @param ShareRequest $request
     * @return JsonResponse
     */
    public function trackShare(Post $post, ShareRequest $request): JsonResponse
    {
        $provider = $request->getProvider();
        $userId = $request->user()?->id;

        // Generate share URL
        $shareUrl = $this->shareService->generateShareUrl($post, $provider);

        // Record the share
        $share = $this->shareService->recordShare($post, $provider, $userId, $shareUrl);

        return response()->json([
            'success' => true,
            'data' => [
                'share_id' => $share->id,
                'provider' => $share->provider,
                'share_count' => $this->shareService->getCount($post),
                'share_url' => $shareUrl,
            ],
            'message' => 'Share tracked successfully',
        ]);
    }

    /**
     * Get share count for a post.
     *
     * @param Post $post
     * @return JsonResponse
     */
    public function getShareCount(Post $post): JsonResponse
    {
        $count = $this->shareService->getCount($post);
        $byProvider = $this->shareService->getCountByProvider($post);

        return response()->json([
            'success' => true,
            'data' => [
                'total' => $count,
                'by_provider' => $byProvider,
            ],
        ]);
    }

    /**
     * Generate share URL for a post.
     *
     * @param Post $post
     * @param Request $request
     * @return JsonResponse
     */
    public function generateShareUrl(Post $post, Request $request): JsonResponse
    {
        $provider = $request->get('provider', 'copy');
        $additionalParams = $request->get('params', []);

        if (!$this->shareService->isValidProvider($provider)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid share provider',
            ], 400);
        }

        // Generate provider-specific URL
        $shareUrl = $this->shareService->generateProviderShareUrl($post, $provider);

        return response()->json([
            'success' => true,
            'data' => [
                'provider' => $provider,
                'share_url' => $shareUrl,
                'post_url' => $this->shareService->generateShareUrl($post, $provider, $additionalParams),
            ],
        ]);
    }

    /**
     * Get share statistics for a post.
     *
     * @param Post $post
     * @return JsonResponse
     */
    public function getStatistics(Post $post): JsonResponse
    {
        $stats = $this->shareService->getStatistics($post);

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Get shares for a post.
     *
     * @param Post $post
     * @param Request $request
     * @return JsonResponse
     */
    public function getShares(Post $post, Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 15);

        $shares = $this->shareService->getForPost($post, $perPage);

        return response()->json([
            'success' => true,
            'data' => $shares->getCollection()->map(function ($share) {
                return [
                    'id' => $share->id,
                    'provider' => $share->provider,
                    'provider_display' => ucfirst($share->provider),
                    'user' => $share->user ? [
                        'id' => $share->user->id,
                        'name' => $share->user->name,
                        'avatar' => $share->user->avatar,
                    ] : null,
                    'share_url' => $share->share_url,
                    'shared_at' => $share->created_at->toIso8601String(),
                ];
            }),
            'meta' => [
                'current_page' => $shares->currentPage(),
                'per_page' => $shares->perPage(),
                'total' => $shares->total(),
                'total_pages' => $shares->lastPage(),
            ],
        ]);
    }

    /**
     * Get available share providers.
     *
     * @return JsonResponse
     */
    public function getProviders(): JsonResponse
    {
        $providers = $this->shareService->getProviders();

        return response()->json([
            'success' => true,
            'data' => $providers,
        ]);
    }

    /**
     * Get trending posts by shares.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getTrendingPosts(Request $request): JsonResponse
    {
        $days = $request->get('days', 7);
        $limit = $request->get('limit', 10);

        $trendingPosts = $this->shareService->getTrendingPosts($days, $limit);

        return response()->json([
            'success' => true,
            'data' => $trendingPosts,
        ]);
    }

    /**
     * Get share analytics for a post.
     *
     * @param Post $post
     * @param Request $request
     * @return JsonResponse
     */
    public function getAnalytics(Post $post, Request $request): JsonResponse
    {
        $days = $request->get('days', 30);

        $analytics = $this->shareService->getAnalytics($post, $days);

        return response()->json([
            'success' => true,
            'data' => $analytics,
        ]);
    }

    /**
     * Get user's shares.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getUserShares(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $perPage = $request->get('per_page', 15);

        $shares = $this->shareService->getByUser($userId, $perPage);

        return response()->json([
            'success' => true,
            'data' => $shares->getCollection()->map(function ($share) {
                return [
                    'id' => $share->id,
                    'provider' => $share->provider,
                    'post' => [
                        'id' => $share->post->id,
                        'title' => $share->post->title,
                        'slug' => $share->post->slug,
                    ],
                    'shared_at' => $share->created_at->toIso8601String(),
                ];
            }),
            'meta' => [
                'current_page' => $shares->currentPage(),
                'per_page' => $shares->perPage(),
                'total' => $shares->total(),
                'total_pages' => $shares->lastPage(),
            ],
        ]);
    }
}
