<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Comment;
use App\Models\Post;
use App\Models\PostRevision;
use App\Models\ScheduledJob;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $totalPosts = Post::count();
        $publishedPosts = Post::published()->count();
        $draftPosts = Post::draft()->count();
        $scheduledPosts = Post::where('status', 'scheduled')->count();

        $totalCategories = Category::count();
        $totalTags = Tag::count();
        $totalComments = Comment::count();
        $pendingComments = Comment::pending()->count();
        $totalUsers = User::count();
        $totalViews = Post::sum('views_count');
        $totalRevisions = PostRevision::count();
        $aiRevisions = PostRevision::where('ai_generated', true)->count();

        $pendingJobs = ScheduledJob::where('status', 'pending')->count();
        $failedJobs = ScheduledJob::where('status', 'failed')->count();

        $avgSeoScore = Post::published()->avg('seo_score') ?? 0;

        $postsInReview = Post::whereIn('status', ['review', 'seo_review'])->count();
        $approvedPosts = Post::where('status', 'approved')->count();

        $recentPosts = Post::with('author', 'category')
            ->published()
            ->orderBy('published_at', 'desc')
            ->limit(5)
            ->get();

        $pendingQueue = Comment::pending()->with('post', 'user')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        $failedJobsDetail = ScheduledJob::where('status', 'failed')
            ->with('post')
            ->orderBy('updated_at', 'desc')
            ->limit(5)
            ->get();

        $stats = compact(
            'totalPosts', 'publishedPosts', 'draftPosts', 'scheduledPosts',
            'totalCategories', 'totalTags',
            'totalComments', 'pendingComments',
            'totalUsers', 'totalViews',
            'totalRevisions', 'aiRevisions',
            'pendingJobs', 'failedJobs',
            'avgSeoScore', 'postsInReview', 'approvedPosts',
        );

        return view('admin.dashboard.index', compact(
            'stats', 'recentPosts', 'pendingQueue', 'failedJobsDetail'
        ));
    }
}
