<?php

namespace App\Services;

use App\Repositories\AnalyticsRepository;
use App\Models\AnalyticsEvent;
use App\Models\AnalyticsDailyStat;
use App\Models\ActiveSession;
use App\Helpers\UserAgentParser;
use App\Helpers\GeoLocation;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Class AnalyticsService
 *
 * Service for analytics business logic and data aggregation.
 * Handles caching, complex calculations, and data transformation.
 */
class AnalyticsService extends BaseService
{
    /**
     * The analytics repository instance.
     *
     * @var AnalyticsRepository
     */
    protected $repository;

    /**
     * Cache TTL for dashboard metrics (1 hour).
     */
    const DASHBOARD_CACHE_TTL = 3600;

    /**
     * Cache TTL for aggregations (6 hours).
     */
    const AGGREGATION_CACHE_TTL = 21600;

    /**
     * Cache TTL for real-time data (30 seconds).
     */
    const REALTIME_CACHE_TTL = 30;

    /**
     * Initialize the repository.
     */
    protected function initializeRepository(): void
    {
        $this->repository = new AnalyticsRepository();
    }

    /**
     * Get dashboard overview metrics.
     *
     * @param Carbon|null $startDate
     * @param Carbon|null $endDate
     * @param bool $forceRefresh
     * @return array
     */
    public function getDashboardOverview(
        ?Carbon $startDate = null,
        ?Carbon $endDate = null,
        bool $forceRefresh = false
    ): array {
        $startDate = $startDate ?? now()->subDays(30);
        $endDate = $endDate ?? now();

        $cacheKey = $this->getCacheKey('dashboard_overview', $startDate, $endDate);

        if (!$forceRefresh) {
            return Cache::remember($cacheKey, self::DASHBOARD_CACHE_TTL, function () use ($startDate, $endDate) {
                return $this->repository->getDashboardOverview($startDate, $endDate);
            });
        }

        return $this->repository->getDashboardOverview($startDate, $endDate);
    }

    /**
     * Get views over time.
     *
     * @param Carbon|null $startDate
     * @param Carbon|null $endDate
     * @param string $groupBy
     * @param bool $forceRefresh
     * @return array
     */
    public function getViewsOverTime(
        ?Carbon $startDate = null,
        ?Carbon $endDate = null,
        string $groupBy = 'daily',
        bool $forceRefresh = false
    ): array {
        $startDate = $startDate ?? now()->subDays(30);
        $endDate = $endDate ?? now();

        $cacheKey = $this->getCacheKey("views_over_time_{$groupBy}", $startDate, $endDate);

        if (!$forceRefresh) {
            return Cache::remember($cacheKey, self::AGGREGATION_CACHE_TTL, function () use ($startDate, $endDate, $groupBy) {
                return $this->repository->getViewsOverTime($startDate, $endDate, $groupBy)->toArray();
            });
        }

        return $this->repository->getViewsOverTime($startDate, $endDate, $groupBy)->toArray();
    }

    /**
     * Get top posts.
     *
     * @param Carbon|null $startDate
     * @param Carbon|null $endDate
     * @param int $limit
     * @param string $sortBy
     * @param bool $forceRefresh
     * @return array
     */
    public function getTopPosts(
        ?Carbon $startDate = null,
        ?Carbon $endDate = null,
        int $limit = 10,
        string $sortBy = 'views',
        bool $forceRefresh = false
    ): array {
        $startDate = $startDate ?? now()->subDays(30);
        $endDate = $endDate ?? now();

        $cacheKey = $this->getCacheKey("top_posts_{$sortBy}_{$limit}", $startDate, $endDate);

        if (!$forceRefresh) {
            return Cache::remember($cacheKey, self::AGGREGATION_CACHE_TTL, function () use ($startDate, $endDate, $limit, $sortBy) {
                return $this->repository->getTopPosts($startDate, $endDate, $limit, $sortBy)->toArray();
            });
        }

        return $this->repository->getTopPosts($startDate, $endDate, $limit, $sortBy)->toArray();
    }

    /**
     * Get engagement metrics.
     *
     * @param Carbon|null $startDate
     * @param Carbon|null $endDate
     * @param bool $forceRefresh
     * @return array
     */
    public function getEngagementMetrics(
        ?Carbon $startDate = null,
        ?Carbon $endDate = null,
        bool $forceRefresh = false
    ): array {
        $startDate = $startDate ?? now()->subDays(30);
        $endDate = $endDate ?? now();

        $cacheKey = $this->getCacheKey('engagement_metrics', $startDate, $endDate);

        if (!$forceRefresh) {
            return Cache::remember($cacheKey, self::DASHBOARD_CACHE_TTL, function () use ($startDate, $endDate) {
                return $this->repository->getEngagementMetrics($startDate, $endDate);
            });
        }

        return $this->repository->getEngagementMetrics($startDate, $endDate);
    }

    /**
     * Get traffic sources.
     *
     * @param Carbon|null $startDate
     * @param Carbon|null $endDate
     * @param bool $forceRefresh
     * @return array
     */
    public function getTrafficSources(
        ?Carbon $startDate = null,
        ?Carbon $endDate = null,
        bool $forceRefresh = false
    ): array {
        $startDate = $startDate ?? now()->subDays(30);
        $endDate = $endDate ?? now();

        $cacheKey = $this->getCacheKey('traffic_sources', $startDate, $endDate);

        if (!$forceRefresh) {
            return Cache::remember($cacheKey, self::AGGREGATION_CACHE_TTL, function () use ($startDate, $endDate) {
                return $this->repository->getTrafficSources($startDate, $endDate)->toArray();
            });
        }

        return $this->repository->getTrafficSources($startDate, $endDate)->toArray();
    }

    /**
     * Get top referrers.
     *
     * @param Carbon|null $startDate
     * @param Carbon|null $endDate
     * @param int $limit
     * @param bool $forceRefresh
     * @return array
     */
    public function getTopReferrers(
        ?Carbon $startDate = null,
        ?Carbon $endDate = null,
        int $limit = 20,
        bool $forceRefresh = false
    ): array {
        $startDate = $startDate ?? now()->subDays(30);
        $endDate = $endDate ?? now();

        $cacheKey = $this->getCacheKey("top_referrers_{$limit}", $startDate, $endDate);

        if (!$forceRefresh) {
            return Cache::remember($cacheKey, self::AGGREGATION_CACHE_TTL, function () use ($startDate, $endDate, $limit) {
                return $this->repository->getTopReferrers($startDate, $endDate, $limit)->toArray();
            });
        }

        return $this->repository->getTopReferrers($startDate, $endDate, $limit)->toArray();
    }

    /**
     * Get geographic breakdown.
     *
     * @param Carbon|null $startDate
     * @param Carbon|null $endDate
     * @param int $limit
     * @param bool $forceRefresh
     * @return array
     */
    public function getGeographicBreakdown(
        ?Carbon $startDate = null,
        ?Carbon $endDate = null,
        int $limit = 20,
        bool $forceRefresh = false
    ): array {
        $startDate = $startDate ?? now()->subDays(30);
        $endDate = $endDate ?? now();

        $cacheKey = $this->getCacheKey("geo_breakdown_{$limit}", $startDate, $endDate);

        if (!$forceRefresh) {
            return Cache::remember($cacheKey, self::AGGREGATION_CACHE_TTL, function () use ($startDate, $endDate, $limit) {
                return $this->repository->getGeographicBreakdown($startDate, $endDate, $limit)->toArray();
            });
        }

        return $this->repository->getGeographicBreakdown($startDate, $endDate, $limit)->toArray();
    }

    /**
     * Get device breakdown.
     *
     * @param Carbon|null $startDate
     * @param Carbon|null $endDate
     * @param bool $forceRefresh
     * @return array
     */
    public function getDeviceBreakdown(
        ?Carbon $startDate = null,
        ?Carbon $endDate = null,
        bool $forceRefresh = false
    ): array {
        $startDate = $startDate ?? now()->subDays(30);
        $endDate = $endDate ?? now();

        $cacheKey = $this->getCacheKey('device_breakdown', $startDate, $endDate);

        if (!$forceRefresh) {
            return Cache::remember($cacheKey, self::AGGREGATION_CACHE_TTL, function () use ($startDate, $endDate) {
                return $this->repository->getDeviceBreakdown($startDate, $endDate)->toArray();
            });
        }

        return $this->repository->getDeviceBreakdown($startDate, $endDate)->toArray();
    }

    /**
     * Get browser breakdown.
     *
     * @param Carbon|null $startDate
     * @param Carbon|null $endDate
     * @param bool $forceRefresh
     * @return array
     */
    public function getBrowserBreakdown(
        ?Carbon $startDate = null,
        ?Carbon $endDate = null,
        bool $forceRefresh = false
    ): array {
        $startDate = $startDate ?? now()->subDays(30);
        $endDate = $endDate ?? now();

        $cacheKey = $this->getCacheKey('browser_breakdown', $startDate, $endDate);

        if (!$forceRefresh) {
            return Cache::remember($cacheKey, self::AGGREGATION_CACHE_TTL, function () use ($startDate, $endDate) {
                return $this->repository->getBrowserBreakdown($startDate, $endDate)->toArray();
            });
        }

        return $this->repository->getBrowserBreakdown($startDate, $endDate)->toArray();
    }

    /**
     * Get OS breakdown.
     *
     * @param Carbon|null $startDate
     * @param Carbon|null $endDate
     * @param bool $forceRefresh
     * @return array
     */
    public function getOsBreakdown(
        ?Carbon $startDate = null,
        ?Carbon $endDate = null,
        bool $forceRefresh = false
    ): array {
        $startDate = $startDate ?? now()->subDays(30);
        $endDate = $endDate ?? now();

        $cacheKey = $this->getCacheKey('os_breakdown', $startDate, $endDate);

        if (!$forceRefresh) {
            return Cache::remember($cacheKey, self::AGGREGATION_CACHE_TTL, function () use ($startDate, $endDate) {
                return $this->repository->getOsBreakdown($startDate, $endDate)->toArray();
            });
        }

        return $this->repository->getOsBreakdown($startDate, $endDate)->toArray();
    }

    /**
     * Get real-time active users.
     *
     * @param int $thresholdMinutes
     * @param bool $forceRefresh
     * @return array
     */
    public function getRealTimeActiveUsers(int $thresholdMinutes = 5, bool $forceRefresh = false): array
    {
        $cacheKey = "realtime_active_users_{$thresholdMinutes}";

        if (!$forceRefresh) {
            return Cache::remember($cacheKey, self::REALTIME_CACHE_TTL, function () use ($thresholdMinutes) {
                return $this->repository->getRealTimeActiveUsers($thresholdMinutes);
            });
        }

        return $this->repository->getRealTimeActiveUsers($thresholdMinutes);
    }

    /**
     * Get audience insights.
     *
     * @param Carbon|null $startDate
     * @param Carbon|null $endDate
     * @param bool $forceRefresh
     * @return array
     */
    public function getAudienceInsights(
        ?Carbon $startDate = null,
        ?Carbon $endDate = null,
        bool $forceRefresh = false
    ): array {
        $startDate = $startDate ?? now()->subDays(30);
        $endDate = $endDate ?? now();

        $cacheKey = $this->getCacheKey('audience_insights', $startDate, $endDate);

        if (!$forceRefresh) {
            return Cache::remember($cacheKey, self::AGGREGATION_CACHE_TTL, function () use ($startDate, $endDate) {
                return $this->repository->getAudienceInsights($startDate, $endDate);
            });
        }

        return $this->repository->getAudienceInsights($startDate, $endDate);
    }

    /**
     * Get traffic data for dashboard.
     *
     * @param Carbon|null $startDate
     * @param Carbon|null $endDate
     * @param bool $forceRefresh
     * @return array
     */
    public function getTrafficData(
        ?Carbon $startDate = null,
        ?Carbon $endDate = null,
        bool $forceRefresh = false
    ): array {
        $startDate = $startDate ?? now()->subDays(30);
        $endDate = $endDate ?? now();

        return [
            'overview' => $this->getDashboardOverview($startDate, $endDate, $forceRefresh),
            'views_over_time' => $this->getViewsOverTime($startDate, $endDate, 'daily', $forceRefresh),
            'traffic_sources' => $this->getTrafficSources($startDate, $endDate, $forceRefresh),
            'top_referrers' => $this->getTopReferrers($startDate, $endDate, 10, $forceRefresh),
        ];
    }

    /**
     * Get post performance data.
     *
     * @param Carbon|null $startDate
     * @param Carbon|null $endDate
     * @param int $limit
     * @param bool $forceRefresh
     * @return array
     */
    public function getPostPerformance(
        ?Carbon $startDate = null,
        ?Carbon $endDate = null,
        int $limit = 20,
        bool $forceRefresh = false
    ): array {
        $startDate = $startDate ?? now()->subDays(30);
        $endDate = $endDate ?? now();

        return [
            'top_by_views' => $this->getTopPosts($startDate, $endDate, $limit, 'views', $forceRefresh),
            'top_by_unique' => $this->getTopPosts($startDate, $endDate, $limit, 'unique_views', $forceRefresh),
            'top_by_engagement' => $this->getTopPosts($startDate, $endDate, $limit, 'engagement', $forceRefresh),
        ];
    }

    /**
     * Record a page view event.
     *
     * @param string $url
     * @param string|null $sessionId
     * @param int|null $userId
     * @param int|null $responseTimeMs
     * @return AnalyticsEvent
     */
    public function recordPageView(
        string $url,
        ?string $sessionId = null,
        ?int $userId = null,
        ?int $responseTimeMs = null
    ): AnalyticsEvent {
        return $this->recordEvent(
            AnalyticsEvent::TYPE_PAGE_VIEW,
            $userId,
            null,
            $sessionId,
            $url,
            null,
            null,
            $responseTimeMs
        );
    }

    /**
     * Record a post view event.
     *
     * @param int $postId
     * @param string $url
     * @param string|null $sessionId
     * @param int|null $userId
     * @param string|null $postTitle
     * @return AnalyticsEvent
     */
    public function recordPostView(
        int $postId,
        string $url,
        ?string $sessionId = null,
        ?int $userId = null,
        ?string $postTitle = null
    ): AnalyticsEvent {
        return $this->recordEvent(
            AnalyticsEvent::TYPE_POST_VIEW,
            $userId,
            $postId,
            $sessionId,
            $url,
            null,
            ['post_title' => $postTitle],
            null
        );
    }

    /**
     * Record a generic analytics event.
     *
     * @param string $eventType
     * @param int|null $userId
     * @param int|null $postId
     * @param string|null $sessionId
     * @param string|null $url
     * @param string|null $referrer
     * @param array|null $metadata
     * @param int|null $responseTimeMs
     * @return AnalyticsEvent
     */
    public function recordEvent(
        string $eventType,
        ?int $userId = null,
        ?int $postId = null,
        ?string $sessionId = null,
        ?string $url = null,
        ?string $referrer = null,
        ?array $metadata = null,
        ?int $responseTimeMs = null
    ): AnalyticsEvent {
        $ipAddress = request()->ip();
        $userAgent = request()->userAgent() ?? '';
        $fingerprint = AnalyticsEvent::generateVisitorFingerprint($ipAddress, $userAgent);

        // Parse user agent
        $uaData = UserAgentParser::parse($userAgent);

        // Get geo location
        $geoData = GeoLocation::getLocation($ipAddress);

        // Check if new visitor
        $isNewVisitor = AnalyticsEvent::isNewVisitor($fingerprint, $sessionId);

        // Get referrer domain and traffic source
        $referrerDomain = AnalyticsEvent::getReferrerDomain($referrer ?? request()->headers->get('referer'));
        $trafficSource = AnalyticsEvent::categorizeTrafficSource(
            $referrer ?? request()->headers->get('referer'),
            $url ?? request()->fullUrl()
        );

        return AnalyticsEvent::create([
            'event_type' => $eventType,
            'user_id' => $userId,
            'post_id' => $postId,
            'session_id' => $sessionId,
            'url' => $url ?? request()->fullUrl(),
            'landing_page' => session('analytics_landing_page'),
            'referrer' => $referrer ?? request()->headers->get('referer'),
            'referrer_domain' => $referrerDomain,
            'traffic_source' => $trafficSource,
            'ip_address' => $ipAddress,
            'hashed_ip_address' => AnalyticsEvent::hashIpAddress($ipAddress),
            'user_agent' => $userAgent,
            'visitor_fingerprint' => $fingerprint,
            'is_new_visitor' => $isNewVisitor,
            'country' => $geoData['country'],
            'city' => $geoData['city'],
            'device_type' => $uaData['device_type'],
            'browser' => $uaData['browser'],
            'os' => $uaData['os'],
            'response_time_ms' => $responseTimeMs,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Update active session.
     *
     * @param string $sessionId
     * @param int|null $userId
     * @param string|null $currentUrl
     * @param string|null $currentPageTitle
     * @return ActiveSession
     */
    public function updateActiveSession(
        string $sessionId,
        ?int $userId = null,
        ?string $currentUrl = null,
        ?string $currentPageTitle = null
    ): ActiveSession {
        $ipAddress = request()->ip();
        $userAgent = request()->userAgent() ?? '';
        $fingerprint = AnalyticsEvent::generateVisitorFingerprint($ipAddress, $userAgent);
        $uaData = UserAgentParser::parse($userAgent);
        $geoData = GeoLocation::getLocation($ipAddress);

        // Check if this is a new visitor
        $isNewVisitor = AnalyticsEvent::isNewVisitor($fingerprint, $sessionId);

        return $this->repository->updateActiveSession($sessionId, [
            'user_id' => $userId,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'current_url' => $currentUrl,
            'current_page_title' => $currentPageTitle,
            'visitor_fingerprint' => $fingerprint,
            'is_new_visitor' => $isNewVisitor,
            'country' => $geoData['country'],
            'city' => $geoData['city'],
            'device_type' => $uaData['device_type'],
            'browser' => $uaData['browser'],
            'os' => $uaData['os'],
            'referrer' => request()->headers->get('referer'),
        ]);
    }

    /**
     * Aggregate daily stats for a specific date.
     *
     * @param Carbon $date
     * @return array
     */
    public function aggregateDailyStats(Carbon $date): array
    {
        $startDate = $date->copy()->startOfDay();
        $endDate = $date->copy()->endOfDay();

        $overview = $this->repository->getDashboardOverview($startDate, $endDate);
        $trafficSources = $this->repository->getTrafficSources($startDate, $endDate);
        $topReferrers = $this->repository->getTopReferrers($startDate, $endDate, 10);
        $deviceBreakdown = $this->repository->getDeviceBreakdown($startDate, $endDate);
        $browserBreakdown = $this->repository->getBrowserBreakdown($startDate, $endDate);
        $osBreakdown = $this->repository->getOsBreakdown($startDate, $endDate);
        $geoBreakdown = $this->repository->getGeographicBreakdown($startDate, $endDate, 10);
        $eventCounts = $this->repository->getEventCountsByType($startDate, $endDate);

        $data = [
            'total_page_views' => $overview['total_page_views'] ?? 0,
            'unique_visitors' => $overview['unique_visitors'] ?? 0,
            'new_visitors' => $overview['new_visitors'] ?? 0,
            'returning_visitors' => $overview['returning_visitors'] ?? 0,
            'total_sessions' => $overview['total_sessions'] ?? 0,
            'avg_session_duration' => (int) ($overview['avg_session_duration'] ?? 0),
            'avg_pages_per_session' => (int) ($overview['avg_pages_per_session'] ?? 0),
            'bounce_count' => $overview['bounce_rate'] * ($overview['total_sessions'] ?? 0) / 100,
            'bounce_rate' => $overview['bounce_rate'] ?? 0,
            'event_counts' => $eventCounts->toArray(),
            'traffic_sources' => $trafficSources->toArray(),
            'top_referrers' => $topReferrers->toArray(),
            'device_breakdown' => $deviceBreakdown->toArray(),
            'browser_breakdown' => $browserBreakdown->toArray(),
            'os_breakdown' => $osBreakdown->toArray(),
            'top_countries' => $geoBreakdown->toArray(),
            'peak_concurrent_users' => $this->getRealTimeActiveUsers()['active_users'] ?? 0,
        ];

        AnalyticsDailyStat::upsertDailyStat($date->toDateString(), $data);

        return $data;
    }

    /**
     * Delete old analytics events.
     *
     * @param Carbon $cutoffDate
     * @return int
     */
    public function deleteOldEvents(Carbon $cutoffDate): int
    {
        Log::info('Starting analytics cleanup', ['cutoff_date' => $cutoffDate->toDateString()]);

        $deleted = $this->repository->deleteOldEvents($cutoffDate);

        Log::info('Analytics cleanup completed', ['deleted_count' => $deleted]);

        return $deleted;
    }

    /**
     * Clear analytics cache.
     *
     * @return void
     */
    public function clearCache(): void
    {
        Cache::tags(['analytics'])->flush();
        
        // Also clear specific cache keys
        $pattern = 'analytics_*';
        // Note: This requires cache tags or a different approach depending on cache driver
    }

    /**
     * Warm analytics cache.
     *
     * @param Carbon|null $startDate
     * @param Carbon|null $endDate
     * @return void
     */
    public function warmCache(?Carbon $startDate = null, ?Carbon $endDate = null): void
    {
        $startDate = $startDate ?? now()->subDays(30);
        $endDate = $endDate ?? now();

        // Pre-populate cache for common queries
        $this->getDashboardOverview($startDate, $endDate, true);
        $this->getViewsOverTime($startDate, $endDate, 'daily', true);
        $this->getEngagementMetrics($startDate, $endDate, true);
        $this->getTrafficSources($startDate, $endDate, true);
        $this->getDeviceBreakdown($startDate, $endDate, true);
        $this->getBrowserBreakdown($startDate, $endDate, true);
        $this->getGeographicBreakdown($startDate, $endDate, 20, true);
    }

    /**
     * Get cache key.
     *
     * @param string $type
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return string
     */
    protected function getCacheKey(string $type, Carbon $startDate, Carbon $endDate): string
    {
        return "analytics:{$type}:{$startDate->timestamp}:{$endDate->timestamp}";
    }

    /**
     * Export analytics data.
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @param string $format (csv, json)
     * @return mixed
     */
    public function exportData(Carbon $startDate, Carbon $endDate, string $format = 'json')
    {
        $data = [
            'export_date' => now()->toIso8601String(),
            'date_range' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
            ],
            'overview' => $this->getDashboardOverview($startDate, $endDate, true),
            'daily_stats' => AnalyticsDailyStat::dateRange($startDate, $endDate)
                ->orderBy('stat_date')
                ->get()
                ->map(fn($stat) => $stat->formatted_data)
                ->toArray(),
            'top_posts' => $this->getTopPosts($startDate, $endDate, 50, 'views', true),
            'traffic_sources' => $this->getTrafficSources($startDate, $endDate, true),
            'geographic' => $this->getGeographicBreakdown($startDate, $endDate, 50, true),
        ];

        if ($format === 'json') {
            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        }

        // CSV export would require additional formatting
        return $data;
    }
}
