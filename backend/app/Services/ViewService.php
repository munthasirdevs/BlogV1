<?php

namespace App\Services;

use App\Models\Post;
use App\Models\PostView;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Class ViewService
 *
 * Service for managing post view tracking with unique view detection,
 * 24-hour window, and view analytics.
 *
 * @package App\Services
 */
class ViewService
{
    /**
     * Unique view window in hours.
     */
    const UNIQUE_VIEW_WINDOW = 24;

    /**
     * Cache TTL for view counts in seconds.
     */
    protected int $countCacheTtl = 300; // 5 minutes

    /**
     * Cache key prefix.
     */
    protected string $cachePrefix = 'views:';

    /**
     * ViewService constructor.
     */
    public function __construct()
    {
    }

    /**
     * Record a post view with uniqueness check.
     *
     * @param Post $post
     * @param string $sessionId
     * @param int|null $userId
     * @param string|null $referrer
     * @param string|null $userAgent
     * @param string|null $ipAddress
     * @return array ['view' => PostView, 'is_unique' => bool]
     */
    public function recordView(
        Post $post,
        string $sessionId,
        ?int $userId = null,
        ?string $referrer = null,
        ?string $userAgent = null,
        ?string $ipAddress = null
    ): array {
        // Check for existing view in the unique window
        $existingView = $this->findExistingView(
            $post->id,
            $sessionId,
            $userId
        );

        if ($existingView) {
            // Not a unique view, update time on page if provided
            return [
                'view' => $existingView,
                'is_unique' => false,
            ];
        }

        // Create new unique view
        $view = PostView::create([
            'post_id' => $post->id,
            'user_id' => $userId,
            'session_id' => $sessionId,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'referrer' => $referrer,
            'is_unique' => true,
            'viewed_at' => now(),
        ]);

        // Increment post view count
        $post->increment('views_count');

        // Invalidate cache
        $this->invalidateCountCache($post->id);

        return [
            'view' => $view,
            'is_unique' => true,
        ];
    }

    /**
     * Find existing view within unique window.
     *
     * @param int $postId
     * @param string $sessionId
     * @param int|null $userId
     * @return PostView|null
     */
    public function findExistingView(
        int $postId,
        string $sessionId,
        ?int $userId = null
    ): ?PostView {
        $query = PostView::where('post_id', $postId)
            ->where('session_id', $sessionId)
            ->where('viewed_at', '>=', now()->subHours(self::UNIQUE_VIEW_WINDOW));

        // If user is logged in, also check by user_id
        if ($userId) {
            $query->orWhere(function ($q) use ($postId, $userId) {
                $q->where('post_id', $postId)
                    ->where('user_id', $userId)
                    ->where('viewed_at', '>=', now()->subHours(self::UNIQUE_VIEW_WINDOW));
            });
        }

        return $query->first();
    }

    /**
     * Check if view should be tracked (not bot, not author).
     *
     * @param Post $post
     * @param int|null $userId
     * @param string $userAgent
     * @return bool
     */
    public function shouldTrackView(Post $post, ?int $userId, string $userAgent): bool
    {
        // Don't track if user is the author
        if ($userId && $userId === $post->user_id) {
            return false;
        }

        // Don't track bots
        if ($this->isBot($userAgent)) {
            return false;
        }

        return true;
    }

    /**
     * Check if user agent is a bot.
     *
     * @param string $userAgent
     * @return bool
     */
    public function isBot(string $userAgent): bool
    {
        $botPatterns = [
            'bot',
            'crawler',
            'spider',
            'scraper',
            'curl',
            'wget',
            'googlebot',
            'bingbot',
            'yandex',
            'baiduspider',
            'facebookexternalhit',
            'twitterbot',
            'linkedinbot',
        ];

        $userAgent = strtolower($userAgent);

        foreach ($botPatterns as $pattern) {
            if (str_contains($userAgent, $pattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get view count for a post.
     *
     * @param Post $post
     * @param bool $unique
     * @param bool $useCache
     * @return int
     */
    public function getCount(Post $post, bool $unique = false, bool $useCache = true): int
    {
        if ($useCache) {
            return $this->getCachedCount($post->id, $unique);
        }

        if ($unique) {
            return PostView::where('post_id', $post->id)
                ->where('is_unique', true)
                ->count();
        }

        return $post->views_count;
    }

    /**
     * Get view count for a date range.
     *
     * @param Post $post
     * @param \DateTimeInterface $startDate
     * @param \DateTimeInterface $endDate
     * @param bool $unique
     * @return int
     */
    public function getCountForDateRange(
        Post $post,
        \DateTimeInterface $startDate,
        \DateTimeInterface $endDate,
        bool $unique = false
    ): int {
        $query = PostView::where('post_id', $post->id)
            ->whereBetween('viewed_at', [$startDate, $endDate]);

        if ($unique) {
            $query->where('is_unique', true);
        }

        return $query->count();
    }

    /**
     * Get views for a post.
     *
     * @param Post $post
     * @param int $perPage
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function getForPost(Post $post, int $perPage = 15): \Illuminate\Pagination\LengthAwarePaginator
    {
        return PostView::where('post_id', $post->id)
            ->latest('viewed_at')
            ->paginate($perPage);
    }

    /**
     * Get unique views for a post.
     *
     * @param Post $post
     * @param int $perPage
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function getUniqueViewsForPost(Post $post, int $perPage = 15): \Illuminate\Pagination\LengthAwarePaginator
    {
        return PostView::where('post_id', $post->id)
            ->where('is_unique', true)
            ->latest('viewed_at')
            ->paginate($perPage);
    }

    /**
     * Get view statistics for a post.
     *
     * @param Post $post
     * @param int $days
     * @return array
     */
    public function getStatistics(Post $post, int $days = 30): array
    {
        $startDate = now()->subDays($days);

        $totalViews = PostView::where('post_id', $post->id)
            ->where('viewed_at', '>=', $startDate)
            ->count();

        $uniqueViews = PostView::where('post_id', $post->id)
            ->where('is_unique', true)
            ->where('viewed_at', '>=', $startDate)
            ->count();

        $viewsByDate = PostView::where('post_id', $post->id)
            ->where('viewed_at', '>=', $startDate)
            ->selectRaw('DATE(viewed_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->pluck('count', 'date')
            ->toArray();

        $uniqueByDate = PostView::where('post_id', $post->id)
            ->where('is_unique', true)
            ->where('viewed_at', '>=', $startDate)
            ->selectRaw('DATE(viewed_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->pluck('count', 'date')
            ->toArray();

        return [
            'total' => $totalViews,
            'unique' => $uniqueViews,
            'by_date' => $viewsByDate,
            'unique_by_date' => $uniqueByDate,
            'period' => [
                'start' => $startDate->toDateString(),
                'end' => now()->toDateString(),
                'days' => $days,
            ],
        ];
    }

    /**
     * Get trending posts by views.
     *
     * @param int $days
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getTrendingPosts(int $days = 7, int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return PostView::selectRaw('post_id, COUNT(*) as view_count')
            ->where('viewed_at', '>=', now()->subDays($days))
            ->groupBy('post_id')
            ->orderByDesc('view_count')
            ->limit($limit)
            ->with(['post' => function ($q) {
                $q->published()->with(['author', 'category']);
            }])
            ->get()
            ->map(function ($item) {
                return [
                    'post' => $item->post,
                    'view_count' => $item->view_count,
                ];
            });
    }

    /**
     * Get views by referrer.
     *
     * @param Post $post
     * @return array
     */
    public function getViewsByReferrer(Post $post): array
    {
        return PostView::where('post_id', $post->id)
            ->whereNotNull('referrer')
            ->selectRaw('referrer, COUNT(*) as count')
            ->groupBy('referrer')
            ->orderByDesc('count')
            ->limit(20)
            ->get()
            ->map(function ($item) {
                return [
                    'referrer' => $item->referrer,
                    'domain' => parse_url($item->referrer, PHP_URL_HOST),
                    'count' => $item->count,
                ];
            })
            ->toArray();
    }

    /**
     * Get views by browser.
     *
     * @param Post $post
     * @return array
     */
    public function getViewsByBrowser(Post $post): array
    {
        return PostView::where('post_id', $post->id)
            ->whereNotNull('user_agent')
            ->get()
            ->groupBy(function ($view) {
                return $view->browser;
            })
            ->map(function ($views) {
                return $views->count();
            })
            ->toArray();
    }

    /**
     * Get views by OS.
     *
     * @param Post $post
     * @return array
     */
    public function getViewsByOS(Post $post): array
    {
        return PostView::where('post_id', $post->id)
            ->whereNotNull('user_agent')
            ->get()
            ->groupBy(function ($view) {
                return $view->os;
            })
            ->map(function ($views) {
                return $views->count();
            })
            ->toArray();
    }

    /**
     * Get views by device type.
     *
     * @param Post $post
     * @return array
     */
    public function getViewsByDevice(Post $post): array
    {
        $views = PostView::where('post_id', $post->id)
            ->whereNotNull('user_agent')
            ->get();

        return [
            'mobile' => $views->filter(function ($view) {
                return $view->isFromMobile();
            })->count(),
            'desktop' => $views->filter(function ($view) {
                return !$view->isFromMobile();
            })->count(),
        ];
    }

    /**
     * Get cached count for a post.
     *
     * @param int $postId
     * @param bool $unique
     * @return int
     */
    protected function getCachedCount(int $postId, bool $unique = false): int
    {
        $cacheKey = $this->getCountCacheKey($postId, $unique);

        return Cache::remember($cacheKey, $this->countCacheTtl, function () use ($postId, $unique) {
            if ($unique) {
                return PostView::where('post_id', $postId)
                    ->where('is_unique', true)
                    ->count();
            }

            // Return the cached views_count from post
            $post = Post::find($postId);
            return $post?->views_count ?? 0;
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
        Cache::forget($this->getCountCacheKey($postId, false));
        Cache::forget($this->getCountCacheKey($postId, true));
    }

    /**
     * Get cache key for count.
     *
     * @param int $postId
     * @param bool $unique
     * @return string
     */
    protected function getCountCacheKey(int $postId, bool $unique = false): string
    {
        return $this->cachePrefix . "post:{$postId}:" . ($unique ? 'unique:' : '') . 'count';
    }

    /**
     * Get user's viewed posts.
     *
     * @param int $userId
     * @param int $perPage
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function getUserViewedPosts(int $userId, int $perPage = 15): \Illuminate\Pagination\LengthAwarePaginator
    {
        return PostView::where('user_id', $userId)
            ->with(['post' => function ($q) {
                $q->published()->with(['author', 'category']);
            }])
            ->latest('viewed_at')
            ->paginate($perPage)
            ->unique('post_id'); // Only unique posts
    }

    /**
     * Check if user has viewed a post.
     *
     * @param int $userId
     * @param int $postId
     * @return bool
     */
    public function hasUserViewedPost(int $userId, int $postId): bool
    {
        return PostView::where('user_id', $userId)
            ->where('post_id', $postId)
            ->exists();
    }

    /**
     * Get total time spent on post by user.
     *
     * @param int $userId
     * @param int $postId
     * @return int Time in seconds
     */
    public function getUserTimeOnPost(int $userId, int $postId): int
    {
        return PostView::where('user_id', $userId)
            ->where('post_id', $postId)
            ->sum('time_on_page') ?? 0;
    }

    /**
     * Update time on page for a view.
     *
     * @param int $viewId
     * @param int $timeOnPage Time in seconds
     * @return PostView|null
     */
    public function updateTimeOnPage(int $viewId, int $timeOnPage): ?PostView
    {
        $view = PostView::find($viewId);

        if (!$view) {
            return null;
        }

        $view->update(['time_on_page' => $timeOnPage]);

        return $view;
    }

    /**
     * Delete all views for a post.
     *
     * @param Post $post
     * @return int Number of deleted views
     */
    public function deleteForPost(Post $post): int
    {
        $count = PostView::where('post_id', $post->id)->delete();

        // Update post count
        $post->update(['views_count' => 0]);
        $this->invalidateCountCache($post->id);

        return $count;
    }

    /**
     * Get views count grouped by day.
     *
     * @param Post $post
     * @param int $days
     * @return array
     */
    public function getViewsByDay(Post $post, int $days = 30): array
    {
        $startDate = now()->subDays($days);

        return PostView::where('post_id', $post->id)
            ->where('viewed_at', '>=', $startDate)
            ->selectRaw('DATE(viewed_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->pluck('count', 'date')
            ->toArray();
    }

    /**
     * Get peak viewing hours.
     *
     * @param Post $post
     * @param int $days
     * @return array
     */
    public function getPeakHours(Post $post, int $days = 30): array
    {
        $startDate = now()->subDays($days);

        return PostView::where('post_id', $post->id)
            ->where('viewed_at', '>=', $startDate)
            ->selectRaw('HOUR(viewed_at) as hour, COUNT(*) as count')
            ->groupBy('hour')
            ->orderByDesc('count')
            ->get()
            ->map(function ($item) {
                return [
                    'hour' => $item->hour,
                    'count' => $item->count,
                    'label' => sprintf('%02d:00', $item->hour),
                ];
            })
            ->toArray();
    }
}
