<?php

namespace App\Services;

use App\Models\Post;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Class EngagementScoreService
 *
 * Service for calculating and caching engagement scores for posts.
 * Engagement is calculated based on views, likes, comments, bookmarks, and shares.
 *
 * @package App\Services
 */
class EngagementScoreService
{
    /**
     * Weights for each engagement type.
     */
    const WEIGHT_VIEW = 1;
    const WEIGHT_LIKE = 5;
    const WEIGHT_COMMENT = 10;
    const WEIGHT_BOOKMARK = 8;
    const WEIGHT_SHARE = 15;

    /**
     * Cache TTL for engagement scores in seconds.
     */
    protected int $scoreCacheTtl = 600; // 10 minutes

    /**
     * Cache key prefix.
     */
    protected string $cachePrefix = 'engagement:';

    /**
     * Calculate engagement score for a post.
     *
     * @param Post $post
     * @param bool $recalculate Force recalculation
     * @return float
     */
    public function calculateScore(Post $post, bool $recalculate = false): float
    {
        $cacheKey = $this->getScoreCacheKey($post->id);

        if (!$recalculate) {
            return Cache::remember($cacheKey, $this->scoreCacheTtl, function () use ($post) {
                return $this->computeScore($post);
            });
        }

        $score = $this->computeScore($post);
        Cache::put($cacheKey, $score, $this->scoreCacheTtl);

        return $score;
    }

    /**
     * Compute the actual engagement score.
     *
     * @param Post $post
     * @return float
     */
    protected function computeScore(Post $post): float
    {
        $views = $post->views_count ?? 0;
        $likes = $post->likes_count ?? 0;
        $comments = $post->comments_count ?? 0;
        $shares = $post->shares_count ?? 0;

        // Get bookmark count
        $bookmarks = $post->bookmarks()->count();

        // Calculate weighted score
        $score = (
            ($views * self::WEIGHT_VIEW) +
            ($likes * self::WEIGHT_LIKE) +
            ($comments * self::WEIGHT_COMMENT) +
            ($bookmarks * self::WEIGHT_BOOKMARK) +
            ($shares * self::WEIGHT_SHARE)
        );

        // Apply time decay factor (newer posts get a boost)
        $ageInDays = max(1, $post->published_at?->diffInDays(now()) ?? 1);
        $decayFactor = 1 / log($ageInDays + 2, 2); // Logarithmic decay

        return round($score * $decayFactor, 2);
    }

    /**
     * Get engagement score breakdown for a post.
     *
     * @param Post $post
     * @return array
     */
    public function getScoreBreakdown(Post $post): array
    {
        $views = $post->views_count ?? 0;
        $likes = $post->likes_count ?? 0;
        $comments = $post->comments_count ?? 0;
        $shares = $post->shares_count ?? 0;
        $bookmarks = $post->bookmarks()->count();

        return [
            'views' => [
                'count' => $views,
                'weight' => self::WEIGHT_VIEW,
                'score' => $views * self::WEIGHT_VIEW,
            ],
            'likes' => [
                'count' => $likes,
                'weight' => self::WEIGHT_LIKE,
                'score' => $likes * self::WEIGHT_LIKE,
            ],
            'comments' => [
                'count' => $comments,
                'weight' => self::WEIGHT_COMMENT,
                'score' => $comments * self::WEIGHT_COMMENT,
            ],
            'bookmarks' => [
                'count' => $bookmarks,
                'weight' => self::WEIGHT_BOOKMARK,
                'score' => $bookmarks * self::WEIGHT_BOOKMARK,
            ],
            'shares' => [
                'count' => $shares,
                'weight' => self::WEIGHT_SHARE,
                'score' => $shares * self::WEIGHT_SHARE,
            ],
            'raw_score' => ($views * self::WEIGHT_VIEW) +
                ($likes * self::WEIGHT_LIKE) +
                ($comments * self::WEIGHT_COMMENT) +
                ($bookmarks * self::WEIGHT_BOOKMARK) +
                ($shares * self::WEIGHT_SHARE),
            'final_score' => $this->calculateScore($post),
        ];
    }

    /**
     * Get trending posts by engagement score.
     *
     * @param int $limit
     * @param int $days Only consider posts from last N days
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getTrendingPosts(int $limit = 10, int $days = 30): \Illuminate\Database\Eloquent\Collection
    {
        $startDate = now()->subDays($days);

        // Get published posts with their engagement metrics
        $posts = Post::published()
            ->where('published_at', '>=', $startDate)
            ->with(['author', 'category'])
            ->get();

        // Calculate scores and sort
        $postsWithScores = $posts->map(function ($post) {
            return [
                'post' => $post,
                'score' => $this->calculateScore($post, true),
            ];
        })->sortByDesc('score')
            ->take($limit)
            ->values();

        return $postsWithScores;
    }

    /**
     * Get hot posts (high engagement in short time).
     *
     * @param int $limit
     * @param int $hours Time window in hours
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getHotPosts(int $limit = 10, int $hours = 24): \Illuminate\Database\Eloquent\Collection
    {
        $startDate = now()->subHours($hours);

        // Calculate engagement velocity (engagement per hour)
        $posts = Post::published()
            ->where('published_at', '>=', $startDate)
            ->with(['author', 'category'])
            ->get();

        $postsWithVelocity = $posts->map(function ($post) use ($startDate) {
            $hoursSincePublished = max(1, $post->published_at->diffInHours($startDate));

            $rawScore = (
                ($post->views_count * self::WEIGHT_VIEW) +
                ($post->likes_count * self::WEIGHT_LIKE) +
                ($post->comments_count * self::WEIGHT_COMMENT) +
                ($post->shares_count * self::WEIGHT_SHARE)
            );

            return [
                'post' => $post,
                'velocity' => round($rawScore / $hoursSincePublished, 2),
            ];
        })->sortByDesc('velocity')
            ->take($limit)
            ->values();

        return $postsWithVelocity;
    }

    /**
     * Get posts by engagement level.
     *
     * @param string $level low, medium, high, viral
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getPostsByEngagementLevel(string $level, int $limit = 20): \Illuminate\Database\Eloquent\Collection
    {
        $thresholds = [
            'low' => 0,
            'medium' => 100,
            'high' => 500,
            'viral' => 1000,
        ];

        $minScore = $thresholds[$level] ?? 0;

        $posts = Post::published()
            ->with(['author', 'category'])
            ->get()
            ->filter(function ($post) use ($minScore) {
                return $this->calculateScore($post) >= $minScore;
            })
            ->sortByDesc(function ($post) {
                return $this->calculateScore($post);
            })
            ->take($limit)
            ->values();

        return $posts;
    }

    /**
     * Get engagement score for multiple posts.
     *
     * @param array $postIds
     * @return array [postId => score]
     */
    public function getBulkScores(array $postIds): array
    {
        $scores = [];

        foreach ($postIds as $postId) {
            $scores[$postId] = $this->getScoreCacheKey($postId);
        }

        // Get all from cache
        $cachedScores = Cache::getMultiple(array_values($scores));

        // Calculate missing scores
        foreach ($scores as $postId => $cacheKey) {
            if (!isset($cachedScores[$cacheKey])) {
                $post = Post::find($postId);
                if ($post) {
                    $cachedScores[$cacheKey] = $this->calculateScore($post);
                }
            }
        }

        // Flip back to postId => score
        $result = [];
        foreach ($scores as $postId => $cacheKey) {
            if (isset($cachedScores[$cacheKey])) {
                $result[$postId] = $cachedScores[$cacheKey];
            }
        }

        return $result;
    }

    /**
     * Invalidate engagement score cache for a post.
     *
     * @param int $postId
     * @return void
     */
    public function invalidateScoreCache(int $postId): void
    {
        Cache::forget($this->getScoreCacheKey($postId));
    }

    /**
     * Invalidate engagement score cache for multiple posts.
     *
     * @param array $postIds
     * @return void
     */
    public function invalidateBulkScoreCache(array $postIds): void
    {
        foreach ($postIds as $postId) {
            $this->invalidateScoreCache($postId);
        }
    }

    /**
     * Get cache key for score.
     *
     * @param int $postId
     * @return string
     */
    protected function getScoreCacheKey(int $postId): string
    {
        return $this->cachePrefix . "post:{$postId}:score";
    }

    /**
     * Get engagement statistics across all posts.
     *
     * @return array
     */
    public function getGlobalStatistics(): array
    {
        $totalPosts = Post::published()->count();
        $totalViews = Post::published()->sum('views_count');
        $totalLikes = Post::published()->sum('likes_count');
        $totalComments = Post::published()->sum('comments_count');
        $totalShares = Post::published()->sum('shares_count');

        $avgEngagement = $totalPosts > 0 ? (
            ($totalViews + $totalLikes * self::WEIGHT_LIKE + $totalComments * self::WEIGHT_COMMENT + $totalShares * self::WEIGHT_SHARE) / $totalPosts
        ) : 0;

        return [
            'total_posts' => $totalPosts,
            'total_engagement' => [
                'views' => $totalViews,
                'likes' => $totalLikes,
                'comments' => $totalComments,
                'shares' => $totalShares,
            ],
            'average_engagement_score' => round($avgEngagement, 2),
            'weights' => [
                'view' => self::WEIGHT_VIEW,
                'like' => self::WEIGHT_LIKE,
                'comment' => self::WEIGHT_COMMENT,
                'bookmark' => self::WEIGHT_BOOKMARK,
                'share' => self::WEIGHT_SHARE,
            ],
        ];
    }

    /**
     * Get top engaged posts by time period.
     *
     * @param int $days
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getTopEngagedPosts(int $days = 7, int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        $startDate = now()->subDays($days);

        return Post::published()
            ->where('published_at', '>=', $startDate)
            ->orderByRaw('
                (views_count * ?) +
                (likes_count * ?) +
                (comments_count * ?) +
                (shares_count * ?) DESC
            ', [
                self::WEIGHT_VIEW,
                self::WEIGHT_LIKE,
                self::WEIGHT_COMMENT,
                self::WEIGHT_SHARE,
            ])
            ->limit($limit)
            ->with(['author', 'category'])
            ->get();
    }

    /**
     * Update weights (for A/B testing or tuning).
     *
     * @param array $weights
     * @return void
     */
    public function updateWeights(array $weights): void
    {
        if (isset($weights['view'])) {
            // Note: In production, these should be constants or config values
            // This is for demonstration purposes
        }
    }

    /**
     * Get engagement growth over time.
     *
     * @param int $days
     * @return array
     */
    public function getEngagementGrowth(int $days = 30): array
    {
        $startDate = now()->subDays($days);

        $growth = Post::published()
            ->where('created_at', '>=', $startDate)
            ->selectRaw('
                DATE(created_at) as date,
                SUM(views_count) as total_views,
                SUM(likes_count) as total_likes,
                SUM(comments_count) as total_comments,
                SUM(shares_count) as total_shares
            ')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return $growth->map(function ($day) {
            return [
                'date' => $day->date,
                'total_engagement' => (
                    ($day->total_views * self::WEIGHT_VIEW) +
                    ($day->total_likes * self::WEIGHT_LIKE) +
                    ($day->total_comments * self::WEIGHT_COMMENT) +
                    ($day->total_shares * self::WEIGHT_SHARE)
                ),
                'views' => $day->total_views,
                'likes' => $day->total_likes,
                'comments' => $day->total_comments,
                'shares' => $day->total_shares,
            ];
        })->toArray();
    }
}
