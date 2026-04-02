<?php

namespace App\Repositories;

use App\Models\AnalyticsEvent;
use App\Models\PostViewSummary;
use App\Models\AnalyticsDailyStat;
use App\Models\ActiveSession;
use App\Models\Post;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Class AnalyticsRepository
 *
 * Repository for analytics data access and complex queries.
 * Handles all database operations related to analytics.
 */
class AnalyticsRepository extends BaseRepository
{
    /**
     * Specify Model class name.
     *
     * @return string
     */
    protected function model(): string
    {
        return AnalyticsEvent::class;
    }

    /**
     * Get dashboard overview metrics.
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return array
     */
    public function getDashboardOverview(Carbon $startDate, Carbon $endDate): array
    {
        // Try to get from daily stats first (faster)
        $dailyStats = AnalyticsDailyStat::dateRange($startDate, $endDate)->get();

        if ($dailyStats->isNotEmpty()) {
            return [
                'total_page_views' => $dailyStats->sum('total_page_views'),
                'unique_visitors' => $dailyStats->sum('unique_visitors'),
                'new_visitors' => $dailyStats->sum('new_visitors'),
                'returning_visitors' => $dailyStats->sum('returning_visitors'),
                'total_sessions' => $dailyStats->sum('total_sessions'),
                'avg_session_duration' => round($dailyStats->avg('avg_session_duration'), 2),
                'avg_pages_per_session' => round($dailyStats->avg('avg_pages_per_session'), 2),
                'bounce_rate' => round($dailyStats->avg('bounce_rate'), 2),
                'total_posts_viewed' => $this->getPostsViewedCount($startDate, $endDate),
            ];
        }

        // Fallback to raw events
        return [
            'total_page_views' => $this->getPageViewsCount($startDate, $endDate),
            'unique_visitors' => $this->getUniqueVisitorsCount($startDate, $endDate),
            'new_visitors' => $this->getNewVisitorsCount($startDate, $endDate),
            'returning_visitors' => $this->getReturningVisitorsCount($startDate, $endDate),
            'total_sessions' => $this->getSessionsCount($startDate, $endDate),
            'avg_session_duration' => $this->getAverageSessionDuration($startDate, $endDate),
            'avg_pages_per_session' => $this->getAveragePagesPerSession($startDate, $endDate),
            'bounce_rate' => $this->getBounceRate($startDate, $endDate),
            'total_posts_viewed' => $this->getPostsViewedCount($startDate, $endDate),
        ];
    }

    /**
     * Get page views over time grouped by period.
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @param string $groupBy (daily, weekly, monthly)
     * @return Collection
     */
    public function getViewsOverTime(Carbon $startDate, Carbon $endDate, string $groupBy = 'daily'): Collection
    {
        $dateFormats = [
            'daily' => '%Y-%m-%d',
            'weekly' => '%Y-%u',
            'monthly' => '%Y-%m',
        ];

        $format = $dateFormats[$groupBy] ?? $dateFormats['daily'];

        return AnalyticsEvent::pageViews()
            ->whereBetween('occurred_at', [$startDate, $endDate])
            ->selectRaw("DATE_FORMAT(occurred_at, '{$format}') as period")
            ->selectRaw('COUNT(*) as views')
            ->selectRaw('COUNT(DISTINCT session_id) as unique_views')
            ->selectRaw('COUNT(DISTINCT visitor_fingerprint) as unique_visitors')
            ->groupBy('period')
            ->orderBy('period')
            ->get()
            ->map(function ($item) use ($groupBy) {
                return [
                    'period' => $item->period,
                    'views' => (int) $item->views,
                    'unique_views' => (int) $item->unique_views,
                    'unique_visitors' => (int) $item->unique_visitors,
                ];
            });
    }

    /**
     * Get unique visitors count for date range.
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return int
     */
    public function getUniqueVisitorsCount(Carbon $startDate, Carbon $endDate): int
    {
        return AnalyticsEvent::whereBetween('occurred_at', [$startDate, $endDate])
            ->distinct('visitor_fingerprint')
            ->count('visitor_fingerprint');
    }

    /**
     * Get new visitors count for date range.
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return int
     */
    public function getNewVisitorsCount(Carbon $startDate, Carbon $endDate): int
    {
        return AnalyticsEvent::whereBetween('occurred_at', [$startDate, $endDate])
            ->where('is_new_visitor', true)
            ->distinct('visitor_fingerprint')
            ->count('visitor_fingerprint');
    }

    /**
     * Get returning visitors count for date range.
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return int
     */
    public function getReturningVisitorsCount(Carbon $startDate, Carbon $endDate): int
    {
        return AnalyticsEvent::whereBetween('occurred_at', [$startDate, $endDate])
            ->where('is_new_visitor', false)
            ->distinct('visitor_fingerprint')
            ->count('visitor_fingerprint');
    }

    /**
     * Get sessions count for date range.
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return int
     */
    public function getSessionsCount(Carbon $startDate, Carbon $endDate): int
    {
        return AnalyticsEvent::whereBetween('occurred_at', [$startDate, $endDate])
            ->distinct('session_id')
            ->count('session_id');
    }

    /**
     * Get page views count for date range.
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return int
     */
    public function getPageViewsCount(Carbon $startDate, Carbon $endDate): int
    {
        return AnalyticsEvent::pageViews()
            ->whereBetween('occurred_at', [$startDate, $endDate])
            ->count();
    }

    /**
     * Get bounce rate for date range.
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return float
     */
    public function getBounceRate(Carbon $startDate, Carbon $endDate): float
    {
        $sessionsWithSingleView = AnalyticsEvent::pageViews()
            ->whereBetween('occurred_at', [$startDate, $endDate])
            ->selectRaw('session_id, COUNT(*) as view_count')
            ->groupBy('session_id')
            ->havingRaw('COUNT(*) = 1')
            ->count();

        $totalSessions = $this->getSessionsCount($startDate, $endDate);

        if ($totalSessions === 0) {
            return 0.0;
        }

        return round(($sessionsWithSingleView / $totalSessions) * 100, 2);
    }

    /**
     * Get average session duration in seconds.
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return float
     */
    public function getAverageSessionDuration(Carbon $startDate, Carbon $endDate): float
    {
        $sessionDurations = AnalyticsEvent::whereBetween('occurred_at', [$startDate, $endDate])
            ->selectRaw('session_id, 
                TIMESTAMPDIFF(SECOND, MIN(occurred_at), MAX(occurred_at)) as duration')
            ->groupBy('session_id')
            ->get();

        if ($sessionDurations->isEmpty()) {
            return 0.0;
        }

        return round($sessionDurations->avg('duration'), 2);
    }

    /**
     * Get average pages per session.
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return float
     */
    public function getAveragePagesPerSession(Carbon $startDate, Carbon $endDate): float
    {
        $totalViews = $this->getPageViewsCount($startDate, $endDate);
        $totalSessions = $this->getSessionsCount($startDate, $endDate);

        if ($totalSessions === 0) {
            return 0.0;
        }

        return round($totalViews / $totalSessions, 2);
    }

    /**
     * Get posts viewed count.
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return int
     */
    public function getPostsViewedCount(Carbon $startDate, Carbon $endDate): int
    {
        return AnalyticsEvent::postViews()
            ->whereBetween('occurred_at', [$startDate, $endDate])
            ->distinct('post_id')
            ->count('post_id');
    }

    /**
     * Get top posts by views.
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @param int $limit
     * @param string $sortBy (views, unique_views, engagement)
     * @return Collection
     */
    public function getTopPosts(Carbon $startDate, Carbon $endDate, int $limit = 10, string $sortBy = 'views'): Collection
    {
        $query = AnalyticsEvent::postViews()
            ->whereBetween('occurred_at', [$startDate, $endDate])
            ->selectRaw('post_id')
            ->selectRaw('COUNT(*) as views')
            ->selectRaw('COUNT(DISTINCT session_id) as unique_views')
            ->selectRaw('COUNT(DISTINCT user_id) as logged_in_views')
            ->groupBy('post_id');

        if ($sortBy === 'unique_views') {
            $query->orderByDesc('unique_views');
        } elseif ($sortBy === 'engagement') {
            // Engagement score: views + (unique_views * 0.5) + (logged_in_views * 2)
            $query->selectRaw('(COUNT(*) + (COUNT(DISTINCT session_id) * 0.5) + (COUNT(DISTINCT user_id) * 2)) as engagement_score')
                ->orderByDesc('engagement_score');
        } else {
            $query->orderByDesc('views');
        }

        return $query->limit($limit)
            ->get()
            ->map(function ($item) {
                $post = Post::find($item->post_id);
                return [
                    'post_id' => $item->post_id,
                    'post_title' => $post->title ?? 'Unknown',
                    'post_slug' => $post->slug ?? null,
                    'views' => (int) $item->views,
                    'unique_views' => (int) $item->unique_views,
                    'logged_in_views' => (int) $item->logged_in_views,
                    'engagement_score' => isset($item->engagement_score) ? round($item->engagement_score, 2) : null,
                ];
            });
    }

    /**
     * Get traffic sources breakdown.
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return Collection
     */
    public function getTrafficSources(Carbon $startDate, Carbon $endDate): Collection
    {
        return AnalyticsEvent::whereBetween('occurred_at', [$startDate, $endDate])
            ->selectRaw('traffic_source, COUNT(*) as count')
            ->groupBy('traffic_source')
            ->orderByDesc('count')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->traffic_source => (int) $item->count];
            });
    }

    /**
     * Get top referrers.
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @param int $limit
     * @return Collection
     */
    public function getTopReferrers(Carbon $startDate, Carbon $endDate, int $limit = 20): Collection
    {
        return AnalyticsEvent::whereBetween('occurred_at', [$startDate, $endDate])
            ->whereNotNull('referrer_domain')
            ->where('traffic_source', '!=', 'direct')
            ->selectRaw('referrer_domain, COUNT(*) as count')
            ->groupBy('referrer_domain')
            ->orderByDesc('count')
            ->limit($limit)
            ->get()
            ->map(function ($item) {
                return [
                    'domain' => $item->referrer_domain,
                    'count' => (int) $item->count,
                ];
            });
    }

    /**
     * Get device breakdown.
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return Collection
     */
    public function getDeviceBreakdown(Carbon $startDate, Carbon $endDate): Collection
    {
        return AnalyticsEvent::whereBetween('occurred_at', [$startDate, $endDate])
            ->whereNotNull('device_type')
            ->selectRaw('device_type, COUNT(*) as count')
            ->groupBy('device_type')
            ->orderByDesc('count')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->device_type => (int) $item->count];
            });
    }

    /**
     * Get browser breakdown.
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return Collection
     */
    public function getBrowserBreakdown(Carbon $startDate, Carbon $endDate): Collection
    {
        return AnalyticsEvent::whereBetween('occurred_at', [$startDate, $endDate])
            ->whereNotNull('browser')
            ->selectRaw('browser, COUNT(*) as count')
            ->groupBy('browser')
            ->orderByDesc('count')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->browser => (int) $item->count];
            });
    }

    /**
     * Get OS breakdown.
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return Collection
     */
    public function getOsBreakdown(Carbon $startDate, Carbon $endDate): Collection
    {
        return AnalyticsEvent::whereBetween('occurred_at', [$startDate, $endDate])
            ->whereNotNull('os')
            ->selectRaw('os, COUNT(*) as count')
            ->groupBy('os')
            ->orderByDesc('count')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->os => (int) $item->count];
            });
    }

    /**
     * Get geographic breakdown.
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @param int $limit
     * @return Collection
     */
    public function getGeographicBreakdown(Carbon $startDate, Carbon $endDate, int $limit = 20): Collection
    {
        return AnalyticsEvent::whereBetween('occurred_at', [$startDate, $endDate])
            ->whereNotNull('country')
            ->selectRaw('country, COUNT(*) as count')
            ->groupBy('country')
            ->orderByDesc('count')
            ->limit($limit)
            ->get()
            ->map(function ($item) {
                return [
                    'country' => $item->country,
                    'count' => (int) $item->count,
                ];
            });
    }

    /**
     * Get real-time active users.
     *
     * @param int $thresholdMinutes
     * @return array
     */
    public function getRealTimeActiveUsers(int $thresholdMinutes = 5): array
    {
        $activeCount = ActiveSession::active($thresholdMinutes)->count();

        $activeSessions = ActiveSession::active($thresholdMinutes)
            ->latest()
            ->limit(50)
            ->get(['session_id', 'user_id', 'current_url', 'current_page_title', 'last_seen_at', 'country'])
            ->map(function ($session) {
                return [
                    'session_id' => $session->session_id,
                    'user_id' => $session->user_id,
                    'current_url' => $session->current_url,
                    'current_page_title' => $session->current_page_title,
                    'last_seen_at' => $session->last_seen_at->toIso8601String(),
                    'country' => $session->country,
                ];
            });

        $byCountry = ActiveSession::active($thresholdMinutes)
            ->whereNotNull('country')
            ->selectRaw('country, COUNT(*) as count')
            ->groupBy('country')
            ->orderByDesc('count')
            ->limit(10)
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->country => (int) $item->count];
            });

        return [
            'active_users' => $activeCount,
            'sessions' => $activeSessions,
            'by_country' => $byCountry,
        ];
    }

    /**
     * Get audience insights.
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return array
     */
    public function getAudienceInsights(Carbon $startDate, Carbon $endDate): array
    {
        $totalVisitors = $this->getUniqueVisitorsCount($startDate, $endDate);
        $newVisitors = $this->getNewVisitorsCount($startDate, $endDate);
        $returningVisitors = $this->getReturningVisitorsCount($startDate, $endDate);

        return [
            'total_visitors' => $totalVisitors,
            'new_visitors' => $newVisitors,
            'returning_visitors' => $returningVisitors,
            'new_visitor_percentage' => $totalVisitors > 0 ? round(($newVisitors / $totalVisitors) * 100, 2) : 0,
            'returning_visitor_percentage' => $totalVisitors > 0 ? round(($returningVisitors / $totalVisitors) * 100, 2) : 0,
            'device_breakdown' => $this->getDeviceBreakdown($startDate, $endDate),
            'browser_breakdown' => $this->getBrowserBreakdown($startDate, $endDate),
            'os_breakdown' => $this->getOsBreakdown($startDate, $endDate),
            'geographic_breakdown' => $this->getGeographicBreakdown($startDate, $endDate),
        ];
    }

    /**
     * Get engagement metrics.
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return array
     */
    public function getEngagementMetrics(Carbon $startDate, Carbon $endDate): array
    {
        return [
            'avg_session_duration' => $this->getAverageSessionDuration($startDate, $endDate),
            'avg_pages_per_session' => $this->getAveragePagesPerSession($startDate, $endDate),
            'bounce_rate' => $this->getBounceRate($startDate, $endDate),
            'total_sessions' => $this->getSessionsCount($startDate, $endDate),
            'total_page_views' => $this->getPageViewsCount($startDate, $endDate),
        ];
    }

    /**
     * Aggregate post views for a specific date.
     *
     * @param Carbon $date
     * @return int Number of posts aggregated
     */
    public function aggregatePostViewsForDate(Carbon $date): int
    {
        $startDate = $date->copy()->startOfDay();
        $endDate = $date->copy()->endOfDay();

        // Get all posts that were viewed on this date
        $postViews = AnalyticsEvent::postViews()
            ->whereBetween('occurred_at', [$startDate, $endDate])
            ->selectRaw('post_id')
            ->selectRaw('COUNT(*) as total_views')
            ->selectRaw('COUNT(DISTINCT session_id) as unique_views')
            ->selectRaw('COUNT(DISTINCT CASE WHEN is_new_visitor = 1 THEN visitor_fingerprint END) as new_visitors')
            ->selectRaw('COUNT(DISTINCT CASE WHEN is_new_visitor = 0 THEN visitor_fingerprint END) as returning_visitors')
            ->selectRaw('traffic_source')
            ->selectRaw('device_type')
            ->selectRaw('country')
            ->groupBy('post_id', 'traffic_source', 'device_type', 'country')
            ->get();

        // Group by post_id and aggregate
        $aggregated = [];
        foreach ($postViews as $view) {
            $postId = $view->post_id;

            if (!isset($aggregated[$postId])) {
                $aggregated[$postId] = [
                    'total_views' => 0,
                    'unique_views' => 0,
                    'new_visitors' => 0,
                    'returning_visitors' => 0,
                    'referrer_breakdown' => [],
                    'device_breakdown' => [],
                    'country_breakdown' => [],
                ];
            }

            $aggregated[$postId]['total_views'] += $view->total_views;
            $aggregated[$postId]['unique_views'] += $view->unique_views;
            $aggregated[$postId]['new_visitors'] += $view->new_visitors;
            $aggregated[$postId]['returning_visitors'] += $view->returning_visitors;

            if ($view->traffic_source) {
                $aggregated[$postId]['referrer_breakdown'][$view->traffic_source] = 
                    ($aggregated[$postId]['referrer_breakdown'][$view->traffic_source] ?? 0) + $view->total_views;
            }

            if ($view->device_type) {
                $aggregated[$postId]['device_breakdown'][$view->device_type] = 
                    ($aggregated[$postId]['device_breakdown'][$view->device_type] ?? 0) + $view->total_views;
            }

            if ($view->country) {
                $aggregated[$postId]['country_breakdown'][$view->country] = 
                    ($aggregated[$postId]['country_breakdown'][$view->country] ?? 0) + $view->total_views;
            }
        }

        // Save aggregated data
        foreach ($aggregated as $postId => $data) {
            PostViewSummary::upsertSummary(
                $postId,
                $date->toDateString(),
                $data['total_views'],
                $data['unique_views'],
                $data['new_visitors'],
                $data['returning_visitors'],
                $data['referrer_breakdown'],
                $data['device_breakdown'],
                $data['country_breakdown']
            );
        }

        return count($aggregated);
    }

    /**
     * Delete events older than specified date.
     *
     * @param Carbon $cutoffDate
     * @return int Number of deleted records
     */
    public function deleteOldEvents(Carbon $cutoffDate): int
    {
        return AnalyticsEvent::where('occurred_at', '<', $cutoffDate)->delete();
    }

    /**
     * Clean up expired active sessions.
     *
     * @param int $thresholdMinutes
     * @return int Number of deleted sessions
     */
    public function cleanupActiveSessions(int $thresholdMinutes = 5): int
    {
        return ActiveSession::where('last_seen_at', '<', now()->subMinutes($thresholdMinutes))->delete();
    }

    /**
     * Get events count by type.
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return Collection
     */
    public function getEventCountsByType(Carbon $startDate, Carbon $endDate): Collection
    {
        return AnalyticsEvent::whereBetween('occurred_at', [$startDate, $endDate])
            ->selectRaw('event_type, COUNT(*) as count')
            ->groupBy('event_type')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->event_type => (int) $item->count];
            });
    }

    /**
     * Update active session.
     *
     * @param string $sessionId
     * @param array $data
     * @return ActiveSession
     */
    public function updateActiveSession(string $sessionId, array $data): ActiveSession
    {
        return ActiveSession::updateOrCreate(
            ['session_id' => $sessionId],
            array_merge([
                'last_seen_at' => now(),
            ], $data)
        );
    }

    /**
     * Get or create active session.
     *
     * @param string $sessionId
     * @param int|null $userId
     * @param string|null $ipAddress
     * @param string|null $userAgent
     * @param string|null $fingerprint
     * @return ActiveSession
     */
    public function getOrCreateActiveSession(
        string $sessionId,
        ?int $userId = null,
        ?string $ipAddress = null,
        ?string $userAgent = null,
        ?string $fingerprint = null
    ): ActiveSession {
        return ActiveSession::findOrCreate($sessionId, $userId, $ipAddress, $userAgent, $fingerprint);
    }
}
