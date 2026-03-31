<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Models\Post;
use App\Models\Comment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Get dashboard statistics.
     */
    public function index(): JsonResponse
    {
        $stats = [
            'users' => [
                'total' => User::count(),
                'new_today' => User::whereDate('created_at', today())->count(),
                'new_this_week' => User::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
                'new_this_month' => User::whereMonth('created_at', now()->month)->count(),
            ],
            'posts' => [
                'total' => Post::count(),
                'published' => Post::published()->count(),
                'draft' => Post::draft()->count(),
                'pending_review' => 0,
            ],
            'comments' => [
                'total' => Comment::count(),
                'pending' => Comment::pending()->count(),
                'approved' => Comment::approved()->count(),
                'rejected' => Comment::where('status', 'rejected')->count(),
            ],
            'views' => [
                'today' => Post::whereDate('updated_at', today())->sum('views_count'),
                'this_week' => Post::whereBetween('updated_at', [now()->startOfWeek(), now()->endOfWeek()])->sum('views_count'),
                'this_month' => Post::whereMonth('updated_at', now()->month)->sum('views_count'),
            ],
        ];

        // Popular posts
        $popularPosts = Post::published()
            ->orderBy('views_count', 'desc')
            ->limit(5)
            ->get(['id', 'title', 'slug', 'views_count']);

        // Recent activity
        $recentActivity = collect();
        
        $recentUsers = User::latest()->limit(5)->get();
        foreach ($recentUsers as $user) {
            $recentActivity->push([
                'type' => 'user_registered',
                'user' => ['id' => $user->id, 'name' => $user->name],
                'timestamp' => $user->created_at->toIso8601String(),
            ]);
        }

        $recentPosts = Post::latest()->limit(5)->get();
        foreach ($recentPosts as $post) {
            $recentActivity->push([
                'type' => 'post_created',
                'user' => ['id' => $post->author->id, 'name' => $post->author->name],
                'post' => ['id' => $post->id, 'title' => $post->title],
                'timestamp' => $post->created_at->toIso8601String(),
            ]);
        }

        $recentActivity = $recentActivity->sortByDesc('timestamp')->values();

        return response()->json([
            'success' => true,
            'data' => [
                'stats' => $stats,
                'popular_posts' => $popularPosts,
                'recent_activity' => $recentActivity,
            ],
        ]);
    }

    /**
     * Get analytics data.
     */
    public function analytics(Request $request): JsonResponse
    {
        $period = $request->get('period', 'week');
        $dateRange = $this->getDateRange($period);

        // Views over time
        $viewsData = Post::selectRaw('DATE(created_at) as date, SUM(views_count) as total')
            ->whereBetween('created_at', $dateRange)
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Users over time
        $usersData = User::selectRaw('DATE(created_at) as date, COUNT(*) as total')
            ->whereBetween('created_at', $dateRange)
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Top posts
        $topPosts = Post::published()
            ->whereBetween('created_at', $dateRange)
            ->orderBy('views_count', 'desc')
            ->limit(10)
            ->get(['id', 'title', 'slug', 'views_count']);

        // Top categories
        $topCategories = DB::table('categories')
            ->join('posts', 'categories.id', '=', 'posts.category_id')
            ->whereBetween('posts.created_at', $dateRange)
            ->groupBy('categories.id', 'categories.name', 'categories.slug')
            ->select('categories.id', 'categories.name', 'categories.slug', DB::raw('COUNT(*) as posts_count'))
            ->orderByDesc('posts_count')
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'views' => [
                    'labels' => $viewsData->pluck('date'),
                    'data' => $viewsData->pluck('total'),
                ],
                'users' => [
                    'labels' => $usersData->pluck('date'),
                    'data' => $usersData->pluck('total'),
                ],
                'top_posts' => $topPosts,
                'top_categories' => $topCategories,
            ],
        ]);
    }

    /**
     * Get date range based on period.
     */
    private function getDateRange(string $period): array
    {
        return match ($period) {
            'today' => [now()->startOfDay(), now()->endOfDay()],
            'week' => [now()->startOfWeek(), now()->endOfWeek()],
            'month' => [now()->startOfMonth(), now()->endOfMonth()],
            'year' => [now()->startOfYear(), now()->endOfYear()],
            default => [now()->startOfWeek(), now()->endOfWeek()],
        };
    }
}
