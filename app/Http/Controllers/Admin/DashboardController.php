<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Comment;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $totalPosts = Post::count();
        $publishedPosts = Post::published()->count();
        $draftPosts = Post::draft()->count();

        $totalCategories = Category::count();
        $totalTags = Tag::count();

        $totalComments = Comment::count();
        $pendingComments = Comment::pending()->count();

        $totalUsers = User::count();

        $totalViews = Post::sum('views_count');

        $recentPosts = Post::with('author', 'category')
            ->published()
            ->orderBy('published_at', 'desc')
            ->limit(5)
            ->get();

        $stats = compact(
            'totalPosts',
            'publishedPosts',
            'draftPosts',
            'totalCategories',
            'totalTags',
            'totalComments',
            'pendingComments',
            'totalUsers',
            'totalViews'
        );

        return view('admin.dashboard.index', compact('stats', 'recentPosts'));
    }
}
