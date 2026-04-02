<?php

namespace App\Services;

use App\Models\Post;
use App\Models\PostShare;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;

/**
 * Class ShareService
 *
 * Service for managing share operations, URL generation with UTM parameters,
 * and share count tracking.
 *
 * @package App\Services
 */
class ShareService
{
    /**
     * Cache TTL for share counts in seconds.
     */
    protected int $countCacheTtl = 300; // 5 minutes

    /**
     * UTM campaign name.
     */
    protected string $utmCampaign = 'blog_share';

    /**
     * ShareService constructor.
     */
    public function __construct()
    {
        $this->utmCampaign = config('app.name', 'Blog') . '_share';
    }

    /**
     * Record a share.
     *
     * @param Post $post
     * @param string $provider
     * @param int|null $userId
     * @param string|null $shareUrl
     * @return PostShare
     */
    public function recordShare(Post $post, string $provider, ?int $userId = null, ?string $shareUrl = null): PostShare
    {
        // Validate provider
        if (!in_array($provider, PostShare::AVAILABLE_PROVIDERS)) {
            throw new \InvalidArgumentException("Invalid share provider: {$provider}");
        }

        // Record the share
        $share = PostShare::recordShare($post->id, $provider, $userId, $shareUrl);

        // Invalidate share count cache
        $this->invalidateCountCache($post->id);

        return $share;
    }

    /**
     * Generate share URL with UTM parameters.
     *
     * @param Post $post
     * @param string $provider
     * @param array $additionalParams Additional query parameters
     * @return string
     */
    public function generateShareUrl(Post $post, string $provider, array $additionalParams = []): string
    {
        $postUrl = $this->getPostUrl($post);

        // Build UTM parameters
        $utmParams = [
            'utm_source' => $provider,
            'utm_medium' => 'social',
            'utm_content' => "post_{$post->id}",
            'utm_campaign' => $this->utmCampaign,
        ];

        // Merge with additional params
        $queryParams = array_merge($utmParams, $additionalParams);

        // Build URL with query parameters
        return $this->buildUrlWithParams($postUrl, $queryParams);
    }

    /**
     * Generate provider-specific share URL.
     *
     * @param Post $post
     * @param string $provider
     * @return string|null
     */
    public function generateProviderShareUrl(Post $post, string $provider): ?string
    {
        $postUrl = urlencode($this->generateShareUrl($post, $provider));
        $title = urlencode($post->title);

        return match ($provider) {
            PostShare::PROVIDER_TWITTER => "https://twitter.com/intent/tweet?url={$postUrl}&text={$title}",
            PostShare::PROVIDER_FACEBOOK => "https://www.facebook.com/sharer/sharer.php?u={$postUrl}",
            PostShare::PROVIDER_LINKEDIN => "https://www.linkedin.com/sharing/share-offsite/?url={$postUrl}",
            PostShare::PROVIDER_REDDIT => "https://reddit.com/submit?url={$postUrl}&title={$title}",
            PostShare::PROVIDER_WHATSAPP => "https://api.whatsapp.com/send?text={$title}%20{$postUrl}",
            PostShare::PROVIDER_EMAIL => "mailto:?subject={$title}&body=Check out this post: {$postUrl}",
            PostShare::PROVIDER_COPY => $this->generateShareUrl($post, $provider),
            default => $this->generateShareUrl($post, $provider),
        };
    }

    /**
     * Get share count for a post.
     *
     * @param Post $post
     * @param bool $useCache
     * @return int
     */
    public function getCount(Post $post, bool $useCache = true): int
    {
        if ($useCache) {
            return $this->getCachedCount($post->id);
        }

        return PostShare::getTotalCount($post->id);
    }

    /**
     * Get share count by provider for a post.
     *
     * @param Post $post
     * @return array [provider => count]
     */
    public function getCountByProvider(Post $post): array
    {
        return PostShare::getCountByProvider($post->id);
    }

    /**
     * Get total share count across multiple posts.
     *
     * @param array $postIds
     * @return array [postId => count]
     */
    public function getBulkCounts(array $postIds): array
    {
        return PostShare::whereIn('post_id', $postIds)
            ->selectRaw('post_id, COUNT(*) as count')
            ->groupBy('post_id')
            ->get()
            ->pluck('count', 'post_id')
            ->toArray();
    }

    /**
     * Get shares for a post.
     *
     * @param Post $post
     * @param int $perPage
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function getForPost(Post $post, int $perPage = 15): \Illuminate\Pagination\LengthAwarePaginator
    {
        return PostShare::where('post_id', $post->id)
            ->with('user')
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Get recent shares across all posts.
     *
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getRecentShares(int $limit = 20): \Illuminate\Database\Eloquent\Collection
    {
        return PostShare::with(['post', 'user'])
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * Get shares by provider.
     *
     * @param string $provider
     * @param int $perPage
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function getByProvider(string $provider, int $perPage = 15): \Illuminate\Pagination\LengthAwarePaginator
    {
        return PostShare::where('provider', $provider)
            ->with(['post', 'user'])
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Get user's shares.
     *
     * @param int $userId
     * @param int $perPage
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function getByUser(int $userId, int $perPage = 15): \Illuminate\Pagination\LengthAwarePaginator
    {
        return PostShare::where('user_id', $userId)
            ->with('post')
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Get share statistics.
     *
     * @param Post $post
     * @return array
     */
    public function getStatistics(Post $post): array
    {
        $countByProvider = $this->getCountByProvider($post);
        $totalCount = array_sum($countByProvider);

        return [
            'total' => $totalCount,
            'by_provider' => $countByProvider,
            'most_popular' => !empty($countByProvider) ? array_keys($countByProvider, max($countByProvider))[0] : null,
        ];
    }

    /**
     * Get trending posts by shares.
     *
     * @param int $days
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getTrendingPosts(int $days = 7, int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return PostShare::selectRaw('post_id, COUNT(*) as share_count')
            ->where('created_at', '>=', now()->subDays($days))
            ->groupBy('post_id')
            ->orderByDesc('share_count')
            ->limit($limit)
            ->with(['post' => function ($q) {
                $q->published()->with(['author', 'category']);
            }])
            ->get()
            ->map(function ($item) {
                return [
                    'post' => $item->post,
                    'share_count' => $item->share_count,
                ];
            });
    }

    /**
     * Refresh share count from database.
     *
     * @param Post $post
     * @return int New count
     */
    public function refreshCount(Post $post): int
    {
        $count = PostShare::getTotalCount($post->id);

        // Update the post's count
        $post->update(['shares_count' => $count]);

        // Invalidate cache
        $this->invalidateCountCache($post->id);

        return $count;
    }

    /**
     * Sync share count with actual shares.
     *
     * @param Post $post
     * @return int Corrected count
     */
    public function syncCount(Post $post): int
    {
        $actualCount = PostShare::getTotalCount($post->id);

        if ($actualCount !== $post->shares_count) {
            $post->update(['shares_count' => $actualCount]);
            $this->invalidateCountCache($post->id);
        }

        return $actualCount;
    }

    /**
     * Get cached count for a post.
     *
     * @param int $postId
     * @return int
     */
    protected function getCachedCount(int $postId): int
    {
        $cacheKey = $this->getCountCacheKey($postId);

        return Cache::remember($cacheKey, $this->countCacheTtl, function () use ($postId) {
            return PostShare::getTotalCount($postId);
        });
    }

    /**
     * Invalidate count cache for a post.
     *
     * @param int $postId
     * @return void
     */
    protected function invalidateCountCache(int $postId): void
    {
        $cacheKey = $this->getCountCacheKey($postId);
        Cache::forget($cacheKey);
    }

    /**
     * Get cache key for count.
     *
     * @param int $postId
     * @return string
     */
    protected function getCountCacheKey(int $postId): string
    {
        return "shares:post:{$postId}:count";
    }

    /**
     * Get the public URL for a post.
     *
     * @param Post $post
     * @return string
     */
    protected function getPostUrl(Post $post): string
    {
        // Use config or generate based on your URL structure
        $frontendUrl = config('app.frontend_url', config('app.url'));

        return rtrim($frontendUrl, '/') . '/posts/' . $post->slug;
    }

    /**
     * Build URL with query parameters.
     *
     * @param string $url
     * @param array $params
     * @return string
     */
    protected function buildUrlWithParams(string $url, array $params): string
    {
        $parsedUrl = parse_url($url);
        $path = ($parsedUrl['path'] ?? '/') . ($parsedUrl['fragment'] ?? '');

        // Parse existing query string
        $existingParams = [];
        if (isset($parsedUrl['query'])) {
            parse_url($url, PHP_URL_QUERY);
            parse_str($parsedUrl['query'], $existingParams);
        }

        // Merge with new params
        $allParams = array_merge($existingParams, $params);

        // Build query string
        $queryString = http_build_query($allParams);

        // Rebuild URL
        $result = ($parsedUrl['scheme'] ?? 'https') . '://' . ($parsedUrl['host'] ?? '');

        if (isset($parsedUrl['port'])) {
            $result .= ':' . $parsedUrl['port'];
        }

        $result .= $path;

        if (!empty($queryString)) {
            $result .= '?' . $queryString;
        }

        return $result;
    }

    /**
     * Get share providers with labels and icons.
     *
     * @return array
     */
    public function getProviders(): array
    {
        return [
            PostShare::PROVIDER_TWITTER => [
                'label' => 'Twitter',
                'icon' => 'fa-twitter',
                'color' => '#1DA1F2',
            ],
            PostShare::PROVIDER_FACEBOOK => [
                'label' => 'Facebook',
                'icon' => 'fa-facebook',
                'color' => '#4267B2',
            ],
            PostShare::PROVIDER_LINKEDIN => [
                'label' => 'LinkedIn',
                'icon' => 'fa-linkedin',
                'color' => '#0077B5',
            ],
            PostShare::PROVIDER_REDDIT => [
                'label' => 'Reddit',
                'icon' => 'fa-reddit',
                'color' => '#FF4500',
            ],
            PostShare::PROVIDER_WHATSAPP => [
                'label' => 'WhatsApp',
                'icon' => 'fa-whatsapp',
                'color' => '#25D366',
            ],
            PostShare::PROVIDER_EMAIL => [
                'label' => 'Email',
                'icon' => 'fa-envelope',
                'color' => '#666666',
            ],
            PostShare::PROVIDER_COPY => [
                'label' => 'Copy Link',
                'icon' => 'fa-copy',
                'color' => '#666666',
            ],
        ];
    }

    /**
     * Check if provider is valid.
     *
     * @param string $provider
     * @return bool
     */
    public function isValidProvider(string $provider): bool
    {
        return in_array($provider, PostShare::AVAILABLE_PROVIDERS);
    }

    /**
     * Delete all shares for a post.
     *
     * @param Post $post
     * @return int Number of deleted shares
     */
    public function deleteForPost(Post $post): int
    {
        $count = PostShare::where('post_id', $post->id)->delete();

        // Update post count
        $post->update(['shares_count' => 0]);
        $this->invalidateCountCache($post->id);

        return $count;
    }

    /**
     * Get share analytics for a post.
     *
     * @param Post $post
     * @param int $days
     * @return array
     */
    public function getAnalytics(Post $post, int $days = 30): array
    {
        $startDate = now()->subDays($days);

        $totalShares = PostShare::where('post_id', $post->id)
            ->where('created_at', '>=', $startDate)
            ->count();

        $sharesByProvider = PostShare::where('post_id', $post->id)
            ->where('created_at', '>=', $startDate)
            ->selectRaw('provider, COUNT(*) as count')
            ->groupBy('provider')
            ->get()
            ->pluck('count', 'provider')
            ->toArray();

        $sharesByDate = PostShare::where('post_id', $post->id)
            ->where('created_at', '>=', $startDate)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->pluck('count', 'date')
            ->toArray();

        return [
            'total' => $totalShares,
            'by_provider' => $sharesByProvider,
            'by_date' => $sharesByDate,
            'period' => [
                'start' => $startDate->toDateString(),
                'end' => now()->toDateString(),
                'days' => $days,
            ],
        ];
    }
}
