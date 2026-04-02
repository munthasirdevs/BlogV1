<?php

namespace App\Jobs;

use App\Models\AnalyticsEvent;
use App\Models\PostViewSummary;
use App\Models\AnalyticsDailyStat;
use App\Repositories\AnalyticsRepository;
use App\Services\AnalyticsService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Class AggregatePostViews
 *
 * Scheduled job to aggregate post views data daily.
 * Runs at midnight to process the previous day's data.
 * 
 * Features:
 * - Aggregates views by post
 * - Calculates unique views
 * - Tracks referrer breakdown
 * - Tracks device breakdown
 * - Tracks geographic breakdown
 */
class AggregatePostViews implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The date to aggregate (defaults to yesterday).
     */
    public Carbon $aggregateDate;

    /**
     * Number of attempts before failing.
     */
    public int $tries = 3;

    /**
     * Backoff in seconds between attempts.
     */
    public array $backoff = [60, 300, 900];

    /**
     * Timeout in seconds.
     */
    public int $timeout = 300;

    /**
     * Create a new job instance.
     *
     * @param Carbon|null $date Date to aggregate (defaults to yesterday)
     */
    public function __construct(?Carbon $date = null)
    {
        $this->aggregateDate = $date ?? now()->subDay()->startOfDay();
    }

    /**
     * Execute the job.
     *
     * @param AnalyticsRepository $repository
     * @return void
     */
    public function handle(AnalyticsRepository $repository): void
    {
        $date = $this->aggregateDate;
        $startDate = $date->copy()->startOfDay();
        $endDate = $date->copy()->endOfDay();

        Log::info('Starting post views aggregation', [
            'date' => $date->toDateString(),
            'started_at' => now()->toIso8601String(),
        ]);

        try {
            DB::transaction(function () use ($repository, $date, $startDate, $endDate) {
                // Get all post views for the date
                $postViews = AnalyticsEvent::postViews()
                    ->whereBetween('occurred_at', [$startDate, $endDate])
                    ->get();

                if ($postViews->isEmpty()) {
                    Log::info('No post views to aggregate', ['date' => $date->toDateString()]);
                    return;
                }

                // Group by post_id
                $groupedByPost = $postViews->groupBy('post_id');

                $aggregatedCount = 0;

                foreach ($groupedByPost as $postId => $views) {
                    $this->aggregatePostData($postId, $views, $date);
                    $aggregatedCount++;
                }

                Log::info('Post views aggregation completed', [
                    'date' => $date->toDateString(),
                    'posts_aggregated' => $aggregatedCount,
                    'total_views' => $postViews->count(),
                    'completed_at' => now()->toIso8601String(),
                ]);
            });

            // Also aggregate daily stats
            $this->aggregateDailyStats($date, $repository);

        } catch (\Exception $e) {
            Log::error('Post views aggregation failed', [
                'date' => $date->toDateString(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Aggregate data for a specific post.
     *
     * @param int $postId
     * @param \Illuminate\Support\Collection $views
     * @param Carbon $date
     * @return void
     */
    protected function aggregatePostData(int $postId, $views, Carbon $date): void
    {
        $totalViews = $views->count();
        $uniqueViews = $views->unique('session_id')->count();
        
        // Count new vs returning visitors
        $newVisitors = $views->where('is_new_visitor', true)
            ->unique('visitor_fingerprint')
            ->count();
        $returningVisitors = $views->where('is_new_visitor', false)
            ->unique('visitor_fingerprint')
            ->count();

        // Referrer breakdown (by traffic source)
        $referrerBreakdown = $views->groupBy('traffic_source')
            ->map->count()
            ->toArray();

        // Device breakdown
        $deviceBreakdown = $views->groupBy('device_type')
            ->map->count()
            ->toArray();

        // Country breakdown
        $countryBreakdown = $views->filter(fn($v) => $v->country)
            ->groupBy('country')
            ->map->count()
            ->toArray();

        // Calculate average time on page (if response time is tracked)
        $avgTimeOnPage = $views->avg('response_time_ms') ?? 0;

        // Calculate bounce count (sessions with only 1 view)
        $sessionCounts = $views->groupBy('session_id')->map->count();
        $bounceCount = $sessionCounts->filter(fn($count) => $count === 1)->count();

        // Upsert the summary
        PostViewSummary::upsertSummary(
            $postId,
            $date->toDateString(),
            $totalViews,
            $uniqueViews,
            $newVisitors,
            $returningVisitors,
            $referrerBreakdown,
            $deviceBreakdown,
            $countryBreakdown,
            (int) $avgTimeOnPage,
            $bounceCount
        );
    }

    /**
     * Aggregate daily statistics.
     *
     * @param Carbon $date
     * @param AnalyticsRepository $repository
     * @return void
     */
    protected function aggregateDailyStats(Carbon $date, AnalyticsRepository $repository): void
    {
        $startDate = $date->copy()->startOfDay();
        $endDate = $date->copy()->endOfDay();

        // Get overview metrics
        $overview = $repository->getDashboardOverview($startDate, $endDate);

        // Get traffic sources
        $trafficSources = $repository->getTrafficSources($startDate, $endDate)
            ->toArray();

        // Get top referrers
        $topReferrers = $repository->getTopReferrers($startDate, $endDate, 10)
            ->toArray();

        // Get device breakdown
        $deviceBreakdown = $repository->getDeviceBreakdown($startDate, $endDate)
            ->toArray();

        // Get browser breakdown
        $browserBreakdown = $repository->getBrowserBreakdown($startDate, $endDate)
            ->toArray();

        // Get OS breakdown
        $osBreakdown = $repository->getOsBreakdown($startDate, $endDate)
            ->toArray();

        // Get geographic breakdown
        $topCountries = $repository->getGeographicBreakdown($startDate, $endDate, 10)
            ->toArray();

        // Get event counts by type
        $eventCounts = $repository->getEventCountsByType($startDate, $endDate)
            ->toArray();

        // Get real-time active users for peak
        $realtimeData = $repository->getRealTimeActiveUsers();

        // Upsert daily stat
        AnalyticsDailyStat::upsertDailyStat($date->toDateString(), [
            'total_page_views' => $overview['total_page_views'] ?? 0,
            'unique_visitors' => $overview['unique_visitors'] ?? 0,
            'new_visitors' => $overview['new_visitors'] ?? 0,
            'returning_visitors' => $overview['returning_visitors'] ?? 0,
            'total_sessions' => $overview['total_sessions'] ?? 0,
            'avg_session_duration' => (int) ($overview['avg_session_duration'] ?? 0),
            'avg_pages_per_session' => (int) ($overview['avg_pages_per_session'] ?? 0),
            'bounce_count' => (int) (($overview['bounce_rate'] ?? 0) * ($overview['total_sessions'] ?? 0) / 100),
            'bounce_rate' => $overview['bounce_rate'] ?? 0,
            'event_counts' => $eventCounts,
            'traffic_sources' => $trafficSources,
            'top_referrers' => $topReferrers,
            'device_breakdown' => $deviceBreakdown,
            'browser_breakdown' => $browserBreakdown,
            'os_breakdown' => $osBreakdown,
            'top_countries' => $topCountries,
            'peak_concurrent_users' => $realtimeData['active_users'] ?? 0,
        ]);

        Log::info('Daily stats aggregation completed', [
            'date' => $date->toDateString(),
        ]);
    }

    /**
     * Handle a job failure.
     *
     * @param \Throwable $exception
     * @return void
     */
    public function failed(\Throwable $exception): void
    {
        Log::critical('Post views aggregation job failed permanently', [
            'date' => $this->aggregateDate->toDateString(),
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

        // Optionally notify admins
        // Notification::send(new AnalyticsJobFailed($this->aggregateDate, $exception));
    }

    /**
     * Get the tags that should be assigned to the job.
     *
     * @return array
     */
    public function tags(): array
    {
        return ['analytics', 'aggregation', 'post-views'];
    }
}
