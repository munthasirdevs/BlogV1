<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PageView;
use App\Models\Post;
use App\Models\PostView;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AnalyticsController extends Controller
{
    public function index(): View
    {
        $now = Carbon::now();
        $thirtyDaysAgo = $now->copy()->subDays(30);
        $todayStart = $now->copy()->startOfDay();

        // Total page views (last 30 days)
        $totalViews = PageView::where('visited_at', '>=', $thirtyDaysAgo)->count();

        // Views per day (chart data)
        $viewsPerDay = PageView::where('visited_at', '>=', $thirtyDaysAgo)
            ->selectRaw('DATE(visited_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date')
            ->toArray();

        // Fill missing dates with zero
        $chartData = [];
        $period = new \DatePeriod($thirtyDaysAgo, new \DateInterval('P1D'), $now);
        foreach ($period as $date) {
            $key = $date->format('Y-m-d');
            $chartData[$key] = $viewsPerDay[$key] ?? 0;
        }

        // Today's views
        $todayViews = PageView::where('visited_at', '>=', $todayStart)->count();

        // Avg daily views
        $avgDailyViews = $totalViews > 0 ? round($totalViews / 30, 1) : 0;

        // Top posts by views
        $topPosts = Post::published()
            ->with('category')
            ->orderBy('views_count', 'desc')
            ->take(10)
            ->get(['id', 'title', 'slug', 'views_count', 'shares_count']);

        // Top categories by views (via post views_count)
        $topCategories = DB::table('posts')
            ->join('categories', 'posts.category_id', '=', 'categories.id')
            ->select('categories.name', DB::raw('SUM(posts.views_count) as total_views'))
            ->whereNotNull('posts.category_id')
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc('total_views')
            ->take(10)
            ->get();

        // Device breakdown
        $deviceBreakdown = PageView::where('visited_at', '>=', $thirtyDaysAgo)
            ->selectRaw('COALESCE(device_type, "desktop") as device, COUNT(*) as count')
            ->groupBy('device')
            ->pluck('count', 'device')
            ->toArray();

        // Traffic sources (group by URL prefix)
        $trafficSources = PageView::where('visited_at', '>=', $thirtyDaysAgo)
            ->selectRaw("
                CASE
                    WHEN page_url LIKE 'blog/%' THEN 'blog'
                    WHEN page_url LIKE 'category/%' THEN 'category'
                    WHEN page_url LIKE 'tag/%' THEN 'tag'
                    WHEN page_url = '/' OR page_url = '' THEN 'homepage'
                    ELSE 'other'
                END as source,
                COUNT(*) as count
            ")
            ->groupBy('source')
            ->orderByDesc('count')
            ->get();

        $data = compact(
            'totalViews',
            'todayViews',
            'avgDailyViews',
            'chartData',
            'topPosts',
            'topCategories',
            'deviceBreakdown',
            'trafficSources'
        );

        return view('admin.analytics.index', compact('data'));
    }
}
