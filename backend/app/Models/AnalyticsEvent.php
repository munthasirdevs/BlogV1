<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class AnalyticsEvent
 * 
 * Represents an analytics event for tracking user interactions
 * and application metrics.
 * 
 * @property int $id
 * @property string $event_type
 * @property int|null $user_id
 * @property int|null $post_id
 * @property string|null $session_id
 * @property array|null $metadata
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property string|null $referrer
 * @property string|null $url
 * @property \Illuminate\Support\Carbon $occurred_at
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class AnalyticsEvent extends Model
{
    use HasFactory;

    /**
     * Event type constants.
     */
    const TYPE_PAGE_VIEW = 'page_view';
    const TYPE_POST_VIEW = 'post_view';
    const TYPE_POST_CLICK = 'post_click';
    const TYPE_SEARCH = 'search';
    const TYPE_COMMENT_CREATED = 'comment_created';
    const TYPE_COMMENT_LIKED = 'comment_liked';
    const TYPE_POST_LIKED = 'post_liked';
    const TYPE_POST_BOOKMARKED = 'post_bookmarked';
    const TYPE_CATEGORY_VIEW = 'category_view';
    const TYPE_TAG_VIEW = 'tag_view';
    const TYPE_AUTHOR_VIEW = 'author_view';
    const TYPE_SUBSCRIPTION = 'subscription';
    const TYPE_NEWSLETTER_OPEN = 'newsletter_open';
    const TYPE_NEWSLETTER_CLICK = 'newsletter_click';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'event_type',
        'user_id',
        'post_id',
        'session_id',
        'metadata',
        'ip_address',
        'hashed_ip_address',
        'user_agent',
        'referrer',
        'referrer_domain',
        'url',
        'landing_page',
        'response_time_ms',
        'visitor_fingerprint',
        'is_new_visitor',
        'country',
        'city',
        'device_type',
        'browser',
        'os',
        'traffic_source',
        'occurred_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'occurred_at' => 'datetime',
            'is_new_visitor' => 'boolean',
            'response_time_ms' => 'integer',
        ];
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($event) {
            if (empty($event->occurred_at)) {
                $event->occurred_at = now();
            }
        });
    }

    /**
     * Get the user associated with the event.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the post associated with the event.
     */
    public function post()
    {
        return $this->belongsTo(Post::class, 'post_id');
    }

    /**
     * Get metadata value.
     */
    public function getMetadataValue(string $key, $default = null)
    {
        return $this->metadata[$key] ?? $default;
    }

    /**
     * Check if event is a page view.
     */
    public function isPageView(): bool
    {
        return $this->event_type === self::TYPE_PAGE_VIEW;
    }

    /**
     * Check if event is a post view.
     */
    public function isPostView(): bool
    {
        return $this->event_type === self::TYPE_POST_VIEW;
    }

    /**
     * Check if event is user-generated.
     */
    public function isUserEvent(): bool
    {
        return $this->user_id !== null;
    }

    /**
     * Scope for specific event type.
     */
    public function scopeType($query, string $eventType)
    {
        return $query->where('event_type', $eventType);
    }

    /**
     * Scope for page view events.
     */
    public function scopePageViews($query)
    {
        return $query->where('event_type', self::TYPE_PAGE_VIEW);
    }

    /**
     * Scope for post view events.
     */
    public function scopePostViews($query)
    {
        return $query->where('event_type', self::TYPE_POST_VIEW);
    }

    /**
     * Scope for events by a user.
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope for events on a post.
     */
    public function scopeForPost($query, int $postId)
    {
        return $query->where('post_id', $postId);
    }

    /**
     * Scope for events in a session.
     */
    public function scopeInSession($query, string $sessionId)
    {
        return $query->where('session_id', $sessionId);
    }

    /**
     * Scope for events in date range.
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('occurred_at', [$startDate, $endDate]);
    }

    /**
     * Scope for events today.
     */
    public function scopeToday($query)
    {
        return $query->whereDate('occurred_at', today());
    }

    /**
     * Scope for events this week.
     */
    public function scopeThisWeek($query)
    {
        return $query->whereBetween('occurred_at', [
            now()->startOfWeek(),
            now()->endOfWeek(),
        ]);
    }

    /**
     * Scope for events this month.
     */
    public function scopeThisMonth($query)
    {
        return $query->whereMonth('occurred_at', now()->month)
            ->whereYear('occurred_at', now()->year);
    }

    /**
     * Scope to order by most recent.
     */
    public function scopeLatest($query)
    {
        return $query->orderBy('occurred_at', 'desc');
    }

    /**
     * Scope to order by oldest.
     */
    public function scopeOldest($query)
    {
        return $query->orderBy('occurred_at', 'asc');
    }

    /**
     * Record a page view event.
     */
    public static function recordPageView(
        string $url,
        ?string $sessionId = null,
        ?int $userId = null,
        ?string $referrer = null
    ): static {
        return static::create([
            'event_type' => self::TYPE_PAGE_VIEW,
            'url' => $url,
            'session_id' => $sessionId,
            'user_id' => $userId,
            'referrer' => $referrer,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Record a post view event.
     */
    public static function recordPostView(
        Post $post,
        ?string $sessionId = null,
        ?int $userId = null,
        ?string $referrer = null
    ): static {
        return static::create([
            'event_type' => self::TYPE_POST_VIEW,
            'post_id' => $post->id,
            'session_id' => $sessionId,
            'user_id' => $userId,
            'referrer' => $referrer,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'metadata' => [
                'post_title' => $post->title,
                'post_slug' => $post->slug,
                'category_id' => $post->category_id,
            ],
        ]);
    }

    /**
     * Record a search event.
     */
    public static function recordSearch(
        string $query,
        int $resultsCount,
        ?string $sessionId = null,
        ?int $userId = null
    ): static {
        return static::create([
            'event_type' => self::TYPE_SEARCH,
            'session_id' => $sessionId,
            'user_id' => $userId,
            'metadata' => [
                'search_query' => $query,
                'results_count' => $resultsCount,
            ],
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Get unique visitors count for date range.
     */
    public static function getUniqueVisitorsCount($startDate, $endDate): int
    {
        return static::whereBetween('occurred_at', [$startDate, $endDate])
            ->distinct()
            ->count('session_id');
    }

    /**
     * Get page views count for date range.
     */
    public static function getPageViewsCount($startDate, $endDate): int
    {
        return static::pageViews()
            ->dateRange($startDate, $endDate)
            ->count();
    }

    /**
     * Get events grouped by type.
     */
    public static function getEventsByType($startDate, $endDate): \Illuminate\Support\Collection
    {
        return static::dateRange($startDate, $endDate)
            ->selectRaw('event_type, COUNT(*) as count')
            ->groupBy('event_type')
            ->get();
    }

    /**
     * Scope for new visitors only.
     */
    public function scopeNewVisitors($query)
    {
        return $query->where('is_new_visitor', true);
    }

    /**
     * Scope for returning visitors only.
     */
    public function scopeReturningVisitors($query)
    {
        return $query->where('is_new_visitor', false);
    }

    /**
     * Scope for specific traffic source.
     */
    public function scopeTrafficSource($query, string $source)
    {
        return $query->where('traffic_source', $source);
    }

    /**
     * Scope for specific device type.
     */
    public function scopeDeviceType($query, string $deviceType)
    {
        return $query->where('device_type', $deviceType);
    }

    /**
     * Scope for specific country.
     */
    public function scopeCountry($query, string $country)
    {
        return $query->where('country', $country);
    }

    /**
     * Scope for events with response time.
     */
    public function scopeWithResponseTime($query)
    {
        return $query->whereNotNull('response_time_ms');
    }

    /**
     * Scope for average response time.
     */
    public function scopeAvgResponseTime($query)
    {
        return $query->selectRaw('AVG(response_time_ms) as avg_response_time');
    }

    /**
     * Get visitor fingerprint from session data.
     */
    public static function generateVisitorFingerprint(?string $ipAddress, ?string $userAgent): string
    {
        return hash('sha256', ($ipAddress ?? '') . '|' . ($userAgent ?? ''));
    }

    /**
     * Hash IP address for privacy compliance.
     */
    public static function hashIpAddress(?string $ipAddress): ?string
    {
        if (!$ipAddress) {
            return null;
        }
        return hash('sha256', config('app.key', '') . '|' . $ipAddress);
    }

    /**
     * Get traffic source category from referrer.
     */
    public static function categorizeTrafficSource(?string $referrer, ?string $url): string
    {
        if (!$referrer) {
            return 'direct';
        }

        $referrerDomain = strtolower(parse_url($referrer, PHP_URL_HOST) ?? '');
        $urlDomain = strtolower(parse_url($url ?? '', PHP_URL_HOST) ?? '');

        // Check if same domain (direct)
        if ($referrerDomain === $urlDomain) {
            return 'direct';
        }

        // Search engines
        $searchEngines = ['google', 'bing', 'yahoo', 'duckduckgo', 'baidu', 'yandex'];
        foreach ($searchEngines as $engine) {
            if (str_contains($referrerDomain, $engine)) {
                return 'organic';
            }
        }

        // Social media
        $socialMedia = ['facebook', 'twitter', 'linkedin', 'reddit', 'pinterest', 'instagram', 'tiktok', 'youtube'];
        foreach ($socialMedia as $platform) {
            if (str_contains($referrerDomain, $platform)) {
                return 'social';
            }
        }

        // Paid traffic (common UTM parameters would be in URL)
        if (str_contains($referrer, 'utm_medium=cpc') || str_contains($referrer, 'utm_medium=paid')) {
            return 'paid';
        }

        // Email
        if (str_contains($referrerDomain, 'mail') || str_contains($referrer, 'utm_medium=email')) {
            return 'email';
        }

        // Everything else is referral
        return 'referral';
    }

    /**
     * Get referrer domain from URL.
     */
    public static function getReferrerDomain(?string $referrer): ?string
    {
        if (!$referrer) {
            return null;
        }
        return parse_url($referrer, PHP_URL_HOST);
    }

    /**
     * Record event with full tracking data.
     */
    public static function recordWithFullTracking(
        string $eventType,
        ?int $userId = null,
        ?int $postId = null,
        ?string $sessionId = null,
        ?string $url = null,
        ?string $referrer = null,
        ?array $metadata = null,
        ?int $responseTimeMs = null
    ): static {
        $ipAddress = request()->ip();
        $userAgent = request()->userAgent();
        $landingPage = session('analytics_landing_page');

        return static::create([
            'event_type' => $eventType,
            'user_id' => $userId,
            'post_id' => $postId,
            'session_id' => $sessionId,
            'url' => $url ?? request()->fullUrl(),
            'landing_page' => $landingPage,
            'referrer' => $referrer ?? request()->headers->get('referer'),
            'referrer_domain' => static::getReferrerDomain($referrer ?? request()->headers->get('referer')),
            'traffic_source' => static::categorizeTrafficSource(
                $referrer ?? request()->headers->get('referer'),
                $url ?? request()->fullUrl()
            ),
            'ip_address' => $ipAddress,
            'hashed_ip_address' => static::hashIpAddress($ipAddress),
            'user_agent' => $userAgent,
            'visitor_fingerprint' => static::generateVisitorFingerprint($ipAddress, $userAgent),
            'response_time_ms' => $responseTimeMs,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Get daily views with unique count.
     */
    public static function getDailyViews($startDate, $endDate): \Illuminate\Support\Collection
    {
        return static::pageViews()
            ->dateRange($startDate, $endDate)
            ->selectRaw('DATE(occurred_at) as date, COUNT(*) as views, COUNT(DISTINCT session_id) as unique_views')
            ->groupBy('date')
            ->orderBy('date')
            ->get();
    }

    /**
     * Get top posts by views.
     */
    public static function getTopPostsByViews(int $limit = 10, $startDate = null, $endDate = null): \Illuminate\Support\Collection
    {
        $query = static::postViews()
            ->selectRaw('post_id, COUNT(*) as views, COUNT(DISTINCT session_id) as unique_views')
            ->groupBy('post_id')
            ->orderByDesc('views')
            ->limit($limit);

        if ($startDate && $endDate) {
            $query->dateRange($startDate, $endDate);
        }

        return $query->get();
    }

    /**
     * Get bounce rate for date range.
     * A bounce is a session with only one page view.
     */
    public static function getBounceRate($startDate, $endDate): float
    {
        $sessionsWithSingleView = static::pageViews()
            ->dateRange($startDate, $endDate)
            ->selectRaw('session_id, COUNT(*) as view_count')
            ->groupBy('session_id')
            ->havingRaw('COUNT(*) = 1')
            ->count();

        $totalSessions = static::pageViews()
            ->dateRange($startDate, $endDate)
            ->distinct('session_id')
            ->count('session_id');

        if ($totalSessions === 0) {
            return 0.0;
        }

        return round(($sessionsWithSingleView / $totalSessions) * 100, 2);
    }

    /**
     * Get average session duration.
     */
    public static function getAverageSessionDuration($startDate, $endDate): float
    {
        // Calculate based on time between first and last event in each session
        $sessionDurations = static::dateRange($startDate, $endDate)
            ->selectRaw('session_id, 
                TIMESTAMPDIFF(SECOND, MIN(occurred_at), MAX(occurred_at)) as duration')
            ->groupBy('session_id')
            ->get();

        if ($sessionDurations->isEmpty()) {
            return 0.0;
        }

        $totalDuration = $sessionDurations->sum('duration');
        return round($totalDuration / $sessionDurations->count(), 2);
    }

    /**
     * Get average pages per session.
     */
    public static function getAveragePagesPerSession($startDate, $endDate): float
    {
        $totalViews = static::pageViews()
            ->dateRange($startDate, $endDate)
            ->count();

        $totalSessions = static::pageViews()
            ->dateRange($startDate, $endDate)
            ->distinct('session_id')
            ->count('session_id');

        if ($totalSessions === 0) {
            return 0.0;
        }

        return round($totalViews / $totalSessions, 2);
    }

    /**
     * Get traffic sources breakdown.
     */
    public static function getTrafficSourcesBreakdown($startDate, $endDate): \Illuminate\Support\Collection
    {
        return static::dateRange($startDate, $endDate)
            ->selectRaw('traffic_source, COUNT(*) as count')
            ->groupBy('traffic_source')
            ->orderByDesc('count')
            ->get();
    }

    /**
     * Get device breakdown.
     */
    public static function getDeviceBreakdown($startDate, $endDate): \Illuminate\Support\Collection
    {
        return static::dateRange($startDate, $endDate)
            ->selectRaw('device_type, COUNT(*) as count')
            ->groupBy('device_type')
            ->orderByDesc('count')
            ->get();
    }

    /**
     * Get browser breakdown.
     */
    public static function getBrowserBreakdown($startDate, $endDate): \Illuminate\Support\Collection
    {
        return static::dateRange($startDate, $endDate)
            ->selectRaw('browser, COUNT(*) as count')
            ->groupBy('browser')
            ->orderByDesc('count')
            ->get();
    }

    /**
     * Get OS breakdown.
     */
    public static function getOsBreakdown($startDate, $endDate): \Illuminate\Support\Collection
    {
        return static::dateRange($startDate, $endDate)
            ->selectRaw('os, COUNT(*) as count')
            ->groupBy('os')
            ->orderByDesc('count')
            ->get();
    }

    /**
     * Get geographic breakdown.
     */
    public static function getGeographicBreakdown($startDate, $endDate, int $limit = 20): \Illuminate\Support\Collection
    {
        return static::dateRange($startDate, $endDate)
            ->whereNotNull('country')
            ->selectRaw('country, COUNT(*) as count')
            ->groupBy('country')
            ->orderByDesc('count')
            ->limit($limit)
            ->get();
    }

    /**
     * Get top referrers.
     */
    public static function getTopReferrers($startDate, $endDate, int $limit = 20): \Illuminate\Support\Collection
    {
        return static::dateRange($startDate, $endDate)
            ->whereNotNull('referrer_domain')
            ->where('traffic_source', '!=', 'direct')
            ->selectRaw('referrer_domain, COUNT(*) as count')
            ->groupBy('referrer_domain')
            ->orderByDesc('count')
            ->limit($limit)
            ->get();
    }

    /**
     * Check if visitor is new based on fingerprint.
     */
    public static function isNewVisitor(string $fingerprint, ?string $sessionId = null): bool
    {
        $query = static::where('visitor_fingerprint', $fingerprint);
        
        if ($sessionId) {
            $query->where('session_id', '!=', $sessionId);
        }

        return !$query->exists();
    }

    /**
     * Delete events older than specified date.
     */
    public static function deleteOlderThan(\DateTimeInterface $date): int
    {
        return static::where('occurred_at', '<', $date)->delete();
    }
}
