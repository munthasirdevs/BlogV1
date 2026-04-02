<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class PostViewSummary
 *
 * Aggregated daily statistics for post views.
 * Populated by the AggregatePostViews scheduled job.
 *
 * @property int $id
 * @property int $post_id
 * @property \Carbon\Carbon $view_date
 * @property int $total_views
 * @property int $unique_views
 * @property int $new_visitors
 * @property int $returning_visitors
 * @property array|null $referrer_breakdown
 * @property array|null $device_breakdown
 * @property array|null $country_breakdown
 * @property int $avg_time_on_page
 * @property int $bounce_count
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class PostViewSummary extends Model
{
    use HasFactory;

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
        'post_id',
        'view_date',
        'total_views',
        'unique_views',
        'new_visitors',
        'returning_visitors',
        'referrer_breakdown',
        'device_breakdown',
        'country_breakdown',
        'avg_time_on_page',
        'bounce_count',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'view_date' => 'date',
            'total_views' => 'integer',
            'unique_views' => 'integer',
            'new_visitors' => 'integer',
            'returning_visitors' => 'integer',
            'referrer_breakdown' => 'array',
            'device_breakdown' => 'array',
            'country_breakdown' => 'array',
            'avg_time_on_page' => 'integer',
            'bounce_count' => 'integer',
        ];
    }

    /**
     * Get the post that this summary belongs to.
     */
    public function post()
    {
        return $this->belongsTo(Post::class, 'post_id');
    }

    /**
     * Scope for summaries on or after a date.
     */
    public function scopeFrom($query, $date)
    {
        return $query->where('view_date', '>=', $date);
    }

    /**
     * Scope for summaries on or before a date.
     */
    public function scopeTo($query, $date)
    {
        return $query->where('view_date', '<=', $date);
    }

    /**
     * Scope for summaries in a date range.
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('view_date', [$startDate, $endDate]);
    }

    /**
     * Scope for specific post.
     */
    public function scopeForPost($query, int $postId)
    {
        return $query->where('post_id', $postId);
    }

    /**
     * Scope to order by date descending.
     */
    public function scopeLatest($query)
    {
        return $query->orderByDesc('view_date');
    }

    /**
     * Get total views for a post in date range.
     */
    public static function getTotalViewsForPost(int $postId, $startDate, $endDate): int
    {
        return static::forPost($postId)
            ->dateRange($startDate, $endDate)
            ->sum('total_views');
    }

    /**
     * Get unique views for a post in date range.
     */
    public static function getUniqueViewsForPost(int $postId, $startDate, $endDate): int
    {
        return static::forPost($postId)
            ->dateRange($startDate, $endDate)
            ->sum('unique_views');
    }

    /**
     * Create or update summary for a post on a specific date.
     */
    public static function upsertSummary(
        int $postId,
        $viewDate,
        int $totalViews,
        int $uniqueViews,
        int $newVisitors,
        int $returningVisitors,
        array $referrerBreakdown = [],
        array $deviceBreakdown = [],
        array $countryBreakdown = [],
        int $avgTimeOnPage = 0,
        int $bounceCount = 0
    ): static {
        return static::updateOrCreate(
            [
                'post_id' => $postId,
                'view_date' => $viewDate,
            ],
            [
                'total_views' => $totalViews,
                'unique_views' => $uniqueViews,
                'new_visitors' => $newVisitors,
                'returning_visitors' => $returningVisitors,
                'referrer_breakdown' => $referrerBreakdown,
                'device_breakdown' => $deviceBreakdown,
                'country_breakdown' => $countryBreakdown,
                'avg_time_on_page' => $avgTimeOnPage,
                'bounce_count' => $bounceCount,
            ]
        );
    }

    /**
     * Get summary with post data.
     */
    public function getSummaryWithDataAttribute(): array
    {
        return [
            'id' => $this->id,
            'post_id' => $this->post_id,
            'post_title' => $this->post->title ?? null,
            'post_slug' => $this->post->slug ?? null,
            'view_date' => $this->view_date->toDateString(),
            'total_views' => $this->total_views,
            'unique_views' => $this->unique_views,
            'new_visitors' => $this->new_visitors,
            'returning_visitors' => $this->returning_visitors,
            'referrer_breakdown' => $this->referrer_breakdown,
            'device_breakdown' => $this->device_breakdown,
            'country_breakdown' => $this->country_breakdown,
            'avg_time_on_page' => $this->avg_time_on_page,
            'bounce_count' => $this->bounce_count,
        ];
    }
}
