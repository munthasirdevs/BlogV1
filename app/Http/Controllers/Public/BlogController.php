<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\View\View;

class BlogController extends Controller
{
    public function index(): View
    {
        $posts = Post::published()
            ->with('author', 'category')
            ->orderBy('published_at', 'desc')
            ->paginate(12);

        return view('pages.blog.index', compact('posts'));
    }

    public function show(string $slug): View
    {
        $post = Post::published()
            ->where('slug', $slug)
            ->with('author', 'category', 'tags', 'seo', 'metrics')
            ->firstOrFail();

        $post->increment('views_count');

        $relatedPosts = Post::published()
            ->where('category_id', $post->category_id)
            ->where('id', '!=', $post->id)
            ->with('author', 'category')
            ->orderBy('published_at', 'desc')
            ->take(3)
            ->get();

        return view('pages.blog.show', compact('post', 'relatedPosts'));
    }
}
