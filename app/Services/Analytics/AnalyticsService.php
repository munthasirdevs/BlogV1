<?php

namespace App\Services\Analytics;

use App\Models\PageView;
use App\Models\Post;
use App\Services\CacheService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AnalyticsService
{
    public function __construct(
        protected CacheService $cacheService
    ) {}

    public function getDashboardData(): array
    {
        $now = Carbon::now();
        $thirtyDaysAgo = $now->copy()->subDays(30);
        $todayStart = $now->copy()->startOfDay();

        $totalViews = PageView::where('visited_at', '>=', $thirtyDaysAgo)->count();
        $todayViews = PageView::where('visited_at', '>=', $todayStart)->count();

        $viewsPerDay = PageView::where('visited_at', '>=', $thirtyDaysAgo)
            ->selectRaw('DATE(visited_at) as date, COUNT(*) as count')
            ->groupBy('date')->orderBy('date')
            ->pluck('count', 'date')->toArray();

        $chartData = [];
        $period = new \DatePeriod($thirtyDaysAgo, new \DateInterval('P1D'), $now);
        foreach ($period as $date) {
            $chartData[$date->format('Y-m-d')] = $viewsPerDay[$date->format('Y-m-d')] ?? 0;
        }

        $topPosts = Post::published()->with('category')
            ->orderBy('views_count', 'desc')->take(10)
            ->get(['id', 'title', 'slug', 'views_count', 'shares_count']);

        $topCategories = DB::table('posts')
            ->join('categories', 'posts.category_id', '=', 'categories.id')
            ->select('categories.name', DB::raw('SUM(posts.views_count) as total_views'))
            ->whereNotNull('posts.category_id')
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc('total_views')->take(10)->get();

        $deviceBreakdown = PageView::where('visited_at', '>=', $thirtyDaysAgo)
            ->selectRaw('COALESCE(device_type, "desktop") as device, COUNT(*) as count')
            ->groupBy('device')->pluck('count', 'device')->toArray();

        return compact('totalViews', 'todayViews', 'chartData', 'topPosts', 'topCategories', 'deviceBreakdown');
    }

    public function getPostAnalytics(int $postId): array
    {
        $post = Post::findOrFail($postId);
        $thirtyDaysAgo = now()->subDays(30);

        $dailyViews = \App\Models\PostView::where('post_id', $postId)
            ->where('visited_at', '>=', $thirtyDaysAgo)
            ->selectRaw('DATE(visited_at) as date, COUNT(*) as count')
            ->groupBy('date')->orderBy('date')
            ->pluck('count', 'date')->toArray();

        $chartData = [];
        $period = new \DatePeriod($thirtyDaysAgo, new \DateInterval('P1D'), now());
        foreach ($period as $date) {
            $chartData[$date->format('Y-m-d')] = $dailyViews[$date->format('Y-m-d')] ?? 0;
        }

        return [
            'total_views' => $post->views_count,
            'total_shares' => $post->shares_count,
            'daily_views' => $chartData,
            'avg_daily' => $post->views_count / max(now()->diffInDays($post->created_at), 1),
        ];
    }
}
