<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Analytics\AnalyticsDateRangeRequest;
use App\Http\Requests\Analytics\ExportAnalyticsRequest;
use App\Http\Requests\Analytics\TopPostsRequest;
use App\Services\AnalyticsService;
use App\Models\Post;
use App\Models\Like;
use App\Models\Comment;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;

/**
 * Class AnalyticsController
 *
 * Controller for analytics and tracking endpoints.
 * Provides dashboard metrics, traffic analysis, and reporting.
 * 
 * Access: Admin and Editor roles only
 */
class AnalyticsController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(
        private AnalyticsService $analyticsService
    ) {
        // Authorize admin/editor access for all methods
        $this->middleware(function ($request, $next) {
            $user = $request->user();
            
            if (!$user || !$user->hasAnyRole(['admin', 'editor'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. Admin or Editor role required.',
                ], 403);
            }

            return $next($request);
        });
    }

    /**
     * Get dashboard overview metrics.
     *
     * @param Request $request
     * @return JsonResponse
     *
     * @OA\Get(
     *     path="/api/v1/analytics/overview",
     *     summary="Get dashboard overview metrics",
     *     tags={"Analytics"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="start_date", in="query", @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="end_date", in="query", @OA\Schema(type="string", format="date")),
     *     @OA\Response(response=200, description="Successful response")
     * )
     */
    public function overview(Request $request): JsonResponse
    {
        $startDate = $request->get('start_date') 
            ? Carbon::parse($request->get('start_date'))
            : now()->subDays(30);
        
        $endDate = $request->get('end_date')
            ? Carbon::parse($request->get('end_date'))
            : now();

        $overview = $this->analyticsService->getDashboardOverview($startDate, $endDate);

        return response()->json([
            'success' => true,
            'data' => [
                'overview' => $overview,
                'period' => [
                    'start_date' => $startDate->toDateString(),
                    'end_date' => $endDate->toDateString(),
                    'days' => $startDate->diffInDays($endDate),
                ],
            ],
        ]);
    }

    /**
     * Get views over time.
     *
     * @param Request $request
     * @return JsonResponse
     *
     * @OA\Get(
     *     path="/api/v1/analytics/views",
     *     summary="Get views over time",
     *     tags={"Analytics"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="start_date", in="query", @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="end_date", in="query", @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="group_by", in="query", @OA\Schema(type="string", enum={"daily", "weekly", "monthly"}, default="daily")),
     *     @OA\Response(response=200, description="Successful response")
     * )
     */
    public function views(Request $request): JsonResponse
    {
        $startDate = $request->get('start_date') 
            ? Carbon::parse($request->get('start_date'))
            : now()->subDays(30);
        
        $endDate = $request->get('end_date')
            ? Carbon::parse($request->get('end_date'))
            : now();

        $groupBy = $request->get('group_by', 'daily');
        if (!in_array($groupBy, ['daily', 'weekly', 'monthly'])) {
            $groupBy = 'daily';
        }

        $views = $this->analyticsService->getViewsOverTime($startDate, $endDate, $groupBy);

        return response()->json([
            'success' => true,
            'data' => [
                'views' => $views,
                'group_by' => $groupBy,
                'period' => [
                    'start_date' => $startDate->toDateString(),
                    'end_date' => $endDate->toDateString(),
                ],
            ],
        ]);
    }

    /**
     * Get traffic data.
     *
     * @param Request $request
     * @return JsonResponse
     *
     * @OA\Get(
     *     path="/api/v1/analytics/traffic",
     *     summary="Get traffic data",
     *     tags={"Analytics"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="start_date", in="query", @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="end_date", in="query", @OA\Schema(type="string", format="date")),
     *     @OA\Response(response=200, description="Successful response")
     * )
     */
    public function traffic(Request $request): JsonResponse
    {
        $startDate = $request->get('start_date') 
            ? Carbon::parse($request->get('start_date'))
            : now()->subDays(30);
        
        $endDate = $request->get('end_date')
            ? Carbon::parse($request->get('end_date'))
            : now();

        $trafficData = $this->analyticsService->getTrafficData($startDate, $endDate);

        return response()->json([
            'success' => true,
            'data' => $trafficData,
        ]);
    }

    /**
     * Get post performance data.
     *
     * @param Request $request
     * @return JsonResponse
     *
     * @OA\Get(
     *     path="/api/v1/analytics/posts",
     *     summary="Get post performance data",
     *     tags={"Analytics"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="start_date", in="query", @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="end_date", in="query", @OA\Schema(type="string", format="date")),
     *     @OA\Response(response=200, description="Successful response")
     * )
     */
    public function posts(Request $request): JsonResponse
    {
        $startDate = $request->get('start_date') 
            ? Carbon::parse($request->get('start_date'))
            : now()->subDays(30);
        
        $endDate = $request->get('end_date')
            ? Carbon::parse($request->get('end_date'))
            : now();

        $postPerformance = $this->analyticsService->getPostPerformance($startDate, $endDate, 20);

        return response()->json([
            'success' => true,
            'data' => $postPerformance,
        ]);
    }

    /**
     * Get top posts.
     *
     * @param Request $request
     * @return JsonResponse
     *
     * @OA\Get(
     *     path="/api/v1/analytics/posts/top",
     *     summary="Get top posts",
     *     tags={"Analytics"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="start_date", in="query", @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="end_date", in="query", @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="sort_by", in="query", @OA\Schema(type="string", enum={"views", "unique_views", "engagement"}, default="views")),
     *     @OA\Parameter(name="limit", in="query", @OA\Schema(type="integer", enum={10, 20, 50}, default=10)),
     *     @OA\Response(response=200, description="Successful response")
     * )
     */
    public function topPosts(Request $request): JsonResponse
    {
        $startDate = $request->get('start_date') 
            ? Carbon::parse($request->get('start_date'))
            : now()->subDays(30);
        
        $endDate = $request->get('end_date')
            ? Carbon::parse($request->get('end_date'))
            : now();

        $sortBy = $request->get('sort_by', 'views');
        if (!in_array($sortBy, ['views', 'unique_views', 'engagement'])) {
            $sortBy = 'views';
        }

        $limit = (int) $request->get('limit', 10);
        if (!in_array($limit, [10, 20, 50])) {
            $limit = 10;
        }

        $topPosts = $this->analyticsService->getTopPosts($startDate, $endDate, $limit, $sortBy);

        // Enrich with post data
        $enrichedPosts = collect($topPosts)->map(function ($post) {
            $postModel = Post::find($post['post_id']);
            return array_merge($post, [
                'post' => $postModel ? [
                    'title' => $postModel->title,
                    'slug' => $postModel->slug,
                    'status' => $postModel->status,
                    'published_at' => $postModel->published_at?->toIso8601String(),
                    'thumbnail_url' => $postModel->thumbnail_url,
                ] : null,
            ]);
        });

        return response()->json([
            'success' => true,
            'data' => [
                'posts' => $enrichedPosts,
                'sort_by' => $sortBy,
                'limit' => $limit,
                'period' => [
                    'start_date' => $startDate->toDateString(),
                    'end_date' => $endDate->toDateString(),
                ],
            ],
        ]);
    }

    /**
     * Get engagement metrics.
     *
     * @param Request $request
     * @return JsonResponse
     *
     * @OA\Get(
     *     path="/api/v1/analytics/engagement",
     *     summary="Get engagement metrics",
     *     tags={"Analytics"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="start_date", in="query", @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="end_date", in="query", @OA\Schema(type="string", format="date")),
     *     @OA\Response(response=200, description="Successful response")
     * )
     */
    public function engagement(Request $request): JsonResponse
    {
        $startDate = $request->get('start_date') 
            ? Carbon::parse($request->get('start_date'))
            : now()->subDays(30);
        
        $endDate = $request->get('end_date')
            ? Carbon::parse($request->get('end_date'))
            : now();

        $engagement = $this->analyticsService->getEngagementMetrics($startDate, $endDate);

        // Format duration for readability
        if (isset($engagement['avg_session_duration'])) {
            $engagement['avg_session_duration_formatted'] = $this->formatDuration($engagement['avg_session_duration']);
        }

        return response()->json([
            'success' => true,
            'data' => $engagement,
        ]);
    }

    /**
     * Get traffic sources.
     *
     * @param Request $request
     * @return JsonResponse
     *
     * @OA\Get(
     *     path="/api/v1/analytics/sources",
     *     summary="Get traffic sources",
     *     tags={"Analytics"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="start_date", in="query", @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="end_date", in="query", @OA\Schema(type="string", format="date")),
     *     @OA\Response(response=200, description="Successful response")
     * )
     */
    public function sources(Request $request): JsonResponse
    {
        $startDate = $request->get('start_date') 
            ? Carbon::parse($request->get('start_date'))
            : now()->subDays(30);
        
        $endDate = $request->get('end_date')
            ? Carbon::parse($request->get('end_date'))
            : now();

        $sources = $this->analyticsService->getTrafficSources($startDate, $endDate);
        $topReferrers = $this->analyticsService->getTopReferrers($startDate, $endDate, 20);

        // Calculate percentages
        $total = array_sum($sources);
        $sourcesWithPercentage = collect($sources)->map(function ($count, $source) use ($total) {
            return [
                'source' => $source,
                'count' => $count,
                'percentage' => $total > 0 ? round(($count / $total) * 100, 2) : 0,
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data' => [
                'sources' => $sourcesWithPercentage,
                'top_referrers' => $topReferrers,
                'total' => $total,
                'period' => [
                    'start_date' => $startDate->toDateString(),
                    'end_date' => $endDate->toDateString(),
                ],
            ],
        ]);
    }

    /**
     * Get geographic data.
     *
     * @param Request $request
     * @return JsonResponse
     *
     * @OA\Get(
     *     path="/api/v1/analytics/geo",
     *     summary="Get geographic data",
     *     tags={"Analytics"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="start_date", in="query", @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="end_date", in="query", @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="limit", in="query", @OA\Schema(type="integer", default=20)),
     *     @OA\Response(response=200, description="Successful response")
     * )
     */
    public function geo(Request $request): JsonResponse
    {
        $startDate = $request->get('start_date') 
            ? Carbon::parse($request->get('start_date'))
            : now()->subDays(30);
        
        $endDate = $request->get('end_date')
            ? Carbon::parse($request->get('end_date'))
            : now();

        $limit = min((int) $request->get('limit', 20), 100);

        $geoData = $this->analyticsService->getGeographicBreakdown($startDate, $endDate, $limit);

        // Calculate percentages
        $total = collect($geoData)->sum('count');
        $geoWithPercentage = collect($geoData)->map(function ($item) use ($total) {
            return [
                'country' => $item['country'],
                'count' => $item['count'],
                'percentage' => $total > 0 ? round(($item['count'] / $total) * 100, 2) : 0,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'countries' => $geoWithPercentage,
                'total' => $total,
                'period' => [
                    'start_date' => $startDate->toDateString(),
                    'end_date' => $endDate->toDateString(),
                ],
            ],
        ]);
    }

    /**
     * Get device breakdown.
     *
     * @param Request $request
     * @return JsonResponse
     *
     * @OA\Get(
     *     path="/api/v1/analytics/devices",
     *     summary="Get device breakdown",
     *     tags={"Analytics"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="start_date", in="query", @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="end_date", in="query", @OA\Schema(type="string", format="date")),
     *     @OA\Response(response=200, description="Successful response")
     * )
     */
    public function devices(Request $request): JsonResponse
    {
        $startDate = $request->get('start_date') 
            ? Carbon::parse($request->get('start_date'))
            : now()->subDays(30);
        
        $endDate = $request->get('end_date')
            ? Carbon::parse($request->get('end_date'))
            : now();

        $devices = $this->analyticsService->getDeviceBreakdown($startDate, $endDate);
        $browsers = $this->analyticsService->getBrowserBreakdown($startDate, $endDate);
        $osData = $this->analyticsService->getOsBreakdown($startDate, $endDate);

        // Calculate percentages for devices
        $deviceTotal = array_sum($devices);
        $devicesWithPercentage = collect($devices)->map(function ($count, $type) use ($deviceTotal) {
            return [
                'type' => $type,
                'count' => $count,
                'percentage' => $deviceTotal > 0 ? round(($count / $deviceTotal) * 100, 2) : 0,
            ];
        })->values();

        // Calculate percentages for browsers
        $browserTotal = array_sum($browsers);
        $browsersWithPercentage = collect($browsers)->map(function ($count, $name) use ($browserTotal) {
            return [
                'name' => $name,
                'count' => $count,
                'percentage' => $browserTotal > 0 ? round(($count / $browserTotal) * 100, 2) : 0,
            ];
        })->values();

        // Calculate percentages for OS
        $osTotal = array_sum($osData);
        $osWithPercentage = collect($osData)->map(function ($count, $name) use ($osTotal) {
            return [
                'name' => $name,
                'count' => $count,
                'percentage' => $osTotal > 0 ? round(($count / $osTotal) * 100, 2) : 0,
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data' => [
                'devices' => $devicesWithPercentage,
                'browsers' => $browsersWithPercentage,
                'operating_systems' => $osWithPercentage,
                'period' => [
                    'start_date' => $startDate->toDateString(),
                    'end_date' => $endDate->toDateString(),
                ],
            ],
        ]);
    }

    /**
     * Get real-time active users.
     *
     * @return JsonResponse
     *
     * @OA\Get(
     *     path="/api/v1/analytics/realtime",
     *     summary="Get real-time active users",
     *     tags={"Analytics"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, description="Successful response")
     * )
     */
    public function realtime(): JsonResponse
    {
        $realtimeData = $this->analyticsService->getRealTimeActiveUsers(5);

        return response()->json([
            'success' => true,
            'data' => $realtimeData,
            'cached_until' => now()->addSeconds(30)->toIso8601String(),
        ]);
    }

    /**
     * Get audience insights.
     *
     * @param Request $request
     * @return JsonResponse
     *
     * @OA\Get(
     *     path="/api/v1/analytics/audience",
     *     summary="Get audience insights",
     *     tags={"Analytics"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="start_date", in="query", @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="end_date", in="query", @OA\Schema(type="string", format="date")),
     *     @OA\Response(response=200, description="Successful response")
     * )
     */
    public function audience(Request $request): JsonResponse
    {
        $startDate = $request->get('start_date') 
            ? Carbon::parse($request->get('start_date'))
            : now()->subDays(30);
        
        $endDate = $request->get('end_date')
            ? Carbon::parse($request->get('end_date'))
            : now();

        $audience = $this->analyticsService->getAudienceInsights($startDate, $endDate);

        return response()->json([
            'success' => true,
            'data' => $audience,
        ]);
    }

    /**
     * Export analytics data.
     *
     * @param Request $request
     * @return JsonResponse
     *
     * @OA\Get(
     *     path="/api/v1/analytics/export",
     *     summary="Export analytics data",
     *     tags={"Analytics"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="start_date", in="query", @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="end_date", in="query", @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="format", in="query", @OA\Schema(type="string", enum={"json", "csv"}, default="json")),
     *     @OA\Response(response=200, description="Successful response")
     * )
     */
    public function export(Request $request): JsonResponse
    {
        $startDate = $request->get('start_date') 
            ? Carbon::parse($request->get('start_date'))
            : now()->subDays(30);
        
        $endDate = $request->get('end_date')
            ? Carbon::parse($request->get('end_date'))
            : now();

        $format = $request->get('format', 'json');
        if (!in_array($format, ['json', 'csv'])) {
            $format = 'json';
        }

        // Check if date range is too large for immediate export
        $days = $startDate->diffInDays($endDate);
        if ($days > 90 && $format === 'csv') {
            // For large exports, queue the job and notify when ready
            // This is a simplified version - in production you'd use a queue
            return response()->json([
                'success' => true,
                'data' => [
                    'message' => 'Large export queued. You will be notified when ready.',
                    'estimated_time' => '5-10 minutes',
                ],
            ], 202);
        }

        $exportData = $this->analyticsService->exportData($startDate, $endDate, $format);

        if ($format === 'csv') {
            return $this->exportAsCsv($exportData['data'], $startDate, $endDate);
        }

        return response()->json([
            'success' => true,
            'data' => $exportData['data'],
        ]);
    }

    /**
     * Export data as CSV.
     *
     * @param array $data
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return JsonResponse
     */
    protected function exportAsCsv(array $data, Carbon $startDate, Carbon $endDate): JsonResponse
    {
        // Simplified CSV export - in production you'd use proper CSV generation
        $csvData = [];
        
        // Overview
        if (isset($data['overview'])) {
            $csvData[] = ['Metric', 'Value'];
            foreach ($data['overview'] as $key => $value) {
                $csvData[] = [$key, is_numeric($value) ? $value : json_encode($value)];
            }
            $csvData[] = [];
        }

        $csvString = collect($csvData)->map(fn($row) => implode(',', $row))->join("\n");

        return response()->json([
            'success' => true,
            'data' => [
                'csv' => $csvString,
                'filename' => "analytics_{$startDate->toDateString()}_to_{$endDate->toDateString()}.csv",
            ],
        ]);
    }

    /**
     * Format duration in seconds to human readable format.
     *
     * @param float $seconds
     * @return string
     */
    protected function formatDuration(float $seconds): string
    {
        if ($seconds < 60) {
            return round($seconds) . 's';
        }

        $minutes = floor($seconds / 60);
        $remainingSeconds = $seconds % 60;

        if ($minutes >= 60) {
            $hours = floor($minutes / 60);
            $remainingMinutes = $minutes % 60;
            return "{$hours}h {$remainingMinutes}m";
        }

        return "{$minutes}m " . round($remainingSeconds) . "s";
    }

    /**
     * Clear analytics cache.
     *
     * @return JsonResponse
     */
    public function clearCache(): JsonResponse
    {
        $this->analyticsService->clearCache();
        Cache::tags(['analytics'])->flush();

        return response()->json([
            'success' => true,
            'message' => 'Analytics cache cleared successfully',
        ]);
    }

    /**
     * Warm analytics cache.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function warmCache(Request $request): JsonResponse
    {
        $startDate = $request->get('start_date') 
            ? Carbon::parse($request->get('start_date'))
            : now()->subDays(30);
        
        $endDate = $request->get('end_date')
            ? Carbon::parse($request->get('end_date'))
            : now();

        $this->analyticsService->warmCache($startDate, $endDate);

        return response()->json([
            'success' => true,
            'message' => 'Analytics cache warmed successfully',
        ]);
    }
}
