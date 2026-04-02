<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class AnalyticsDailyStat
 *
 * Daily aggregated analytics statistics.
 * Provides quick access to dashboard metrics without querying raw events.
 *
 * @property int $id
 * @property \Carbon\Carbon $stat_date
 * @property int $total_page_views
 * @property int $unique_visitors
 * @property int $new_visitors
 * @property int $returning_visitors
 * @property int $total_sessions
 * @property int $avg_session_duration
 * @property int $avg_pages_per_session
 * @property int $bounce_count
 * @property float $bounce_rate
 * @property array|null $event_counts
 * @property array|null $traffic_sources
 * @property array|null $top_referrers
 * @property array|null $device_breakdown
 * @property array|null $browser_breakdown
 * @property array|null $os_breakdown
 * @property array|null $top_countries
 * @property int $peak_concurrent_users
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class AnalyticsDailyStat extends Model
{
    use HasFactory;

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = true;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'analytics_daily_stats';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'stat_date',
        'total_page_views',
        'unique_visitors',
        'new_visitors',
        'returning_visitors',
        'total_sessions',
        'avg_session_duration',
        'avg_pages_per_session',
        'bounce_count',
        'bounce_rate',
        'event_counts',
        'traffic_sources',
        'top_referrers',
        'device_breakdown',
        'browser_breakdown',
        'os_breakdown',
        'top_countries',
        'peak_concurrent_users',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'stat_date' => 'date',
            'total_page_views' => 'integer',
            'unique_visitors' => 'integer',
            'new_visitors' => 'integer',
            'returning_visitors' => 'integer',
            'total_sessions' => 'integer',
            'avg_session_duration' => 'integer',
            'avg_pages_per_session' => 'integer',
            'bounce_count' => 'integer',
            'bounce_rate' => 'decimal:2',
            'event_counts' => 'array',
            'traffic_sources' => 'array',
            'top_referrers' => 'array',
            'device_breakdown' => 'array',
            'browser_breakdown' => 'array',
            'os_breakdown' => 'array',
            'top_countries' => 'array',
            'peak_concurrent_users' => 'integer',
        ];
    }

    /**
     * Scope for stats on or after a date.
     */
    public function scopeFrom($query, $date)
    {
        return $query->where('stat_date', '>=', $date);
    }

    /**
     * Scope for stats on or before a date.
     */
    public function scopeTo($query, $date)
    {
        return $query->where('stat_date', '<=', $date);
    }

    /**
     * Scope for stats in a date range.
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('stat_date', [$startDate, $endDate]);
    }

    /**
     * Scope to order by date descending.
     */
    public function scopeLatest($query)
    {
        return $query->orderByDesc('stat_date');
    }

    /**
     * Scope to order by date ascending.
     */
    public function scopeOldest($query)
    {
        return $query->orderBy('stat_date');
    }

    /**
     * Get stats for a specific date.
     */
    public static function getForDate($date): ?static
    {
        return static::where('stat_date', $date)->first();
    }

    /**
     * Get stats for date range.
     */
    public static function getForDateRange($startDate, $endDate): \Illuminate\Support\Collection
    {
        return static::dateRange($startDate, $endDate)
            ->orderBy('stat_date')
            ->get();
    }

    /**
     * Calculate totals for a date range.
     */
    public static function getTotalsForDateRange($startDate, $endDate): array
    {
        $stats = static::dateRange($startDate, $endDate)->get();

        return [
            'total_page_views' => $stats->sum('total_page_views'),
            'total_unique_visitors' => $stats->sum('unique_visitors'),
            'total_new_visitors' => $stats->sum('new_visitors'),
            'total_returning_visitors' => $stats->sum('returning_visitors'),
            'total_sessions' => $stats->sum('total_sessions'),
            'avg_session_duration' => $stats->avg('avg_session_duration'),
            'avg_pages_per_session' => $stats->avg('avg_pages_per_session'),
            'avg_bounce_rate' => $stats->avg('bounce_rate'),
        ];
    }

    /**
     * Create or update daily stat.
     */
    public static function upsertDailyStat(
        $statDate,
        array $data
    ): static {
        return static::updateOrCreate(
            ['stat_date' => $statDate],
            $data
        );
    }

    /**
     * Get formatted stat data for API response.
     */
    public function getFormattedDataAttribute(): array
    {
        return [
            'date' => $this->stat_date->toDateString(),
            'traffic' => [
                'page_views' => $this->total_page_views,
                'unique_visitors' => $this->unique_visitors,
                'new_visitors' => $this->new_visitors,
                'returning_visitors' => $this->returning_visitors,
                'sessions' => $this->total_sessions,
            ],
            'engagement' => [
                'avg_session_duration' => $this->avg_session_duration,
                'avg_pages_per_session' => $this->avg_pages_per_session,
                'bounce_rate' => $this->bounce_rate,
                'bounce_count' => $this->bounce_count,
            ],
            'sources' => [
                'traffic_sources' => $this->traffic_sources,
                'top_referrers' => $this->top_referrers,
            ],
            'devices' => [
                'device_breakdown' => $this->device_breakdown,
                'browser_breakdown' => $this->browser_breakdown,
                'os_breakdown' => $this->os_breakdown,
            ],
            'geography' => [
                'top_countries' => $this->top_countries,
            ],
            'peak_concurrent_users' => $this->peak_concurrent_users,
        ];
    }
}
