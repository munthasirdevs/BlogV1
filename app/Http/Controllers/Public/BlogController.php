<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use Illuminate\View\View;

class BlogController extends Controller
{
    public function index(): View
    {
        $featuredPosts = Post::published()
            ->where('is_featured', true)
            ->with('author', 'category', 'tags')
            ->orderBy('published_at', 'desc')
            ->take(3)
            ->get();

        $posts = Post::published()
            ->with('author', 'category', 'tags')
            ->orderBy('published_at', 'desc')
            ->paginate(12);

        $categories = Category::query()
            ->where('status', 'published')
            ->orderBy('sort_order')
            ->get();

        $trendingPosts = Post::published()
            ->with('author', 'category', 'tags')
            ->orderBy('views_count', 'desc')
            ->take(4)
            ->get();

        $recentPosts = Post::published()
            ->with('author', 'category', 'tags')
            ->orderBy('published_at', 'desc')
            ->take(3)
            ->get();

        $tags = Tag::query()
            ->where('status', 'active')
            ->orderBy('usage_count', 'desc')
            ->take(10)
            ->get();

        return view('pages.blog.index', compact('featuredPosts', 'posts', 'categories', 'trendingPosts', 'recentPosts', 'tags'));
    }

    public function show(string $slug): View
    {
        $post = Post::published()
            ->where('slug', $slug)
            ->with('author', 'category', 'tags', 'seo', 'metrics', 'media')
            ->firstOrFail();

        $post->increment('views_count');

        $relatedPosts = Post::published()
            ->where('category_id', $post->category_id)
            ->where('id', '!=', $post->id)
            ->with('author', 'category', 'tags')
            ->orderBy('published_at', 'desc')
            ->take(3)
            ->get();

        $categories = Category::query()
            ->where('status', 'published')
            ->orderBy('sort_order')
            ->get();

        $recentPosts = Post::published()
            ->with('author', 'category', 'tags')
            ->orderBy('published_at', 'desc')
            ->take(3)
            ->get();

        $tags = Tag::query()
            ->where('status', 'active')
            ->orderBy('usage_count', 'desc')
            ->take(10)
            ->get();

        return view('pages.blog.show', compact('post', 'relatedPosts', 'categories', 'recentPosts', 'tags'));
    }
}
