<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use Illuminate\View\View;

class TagController extends Controller
{
    public function show(string $slug): View
    {
        $tag = Tag::where('slug', $slug)
            ->active()
            ->firstOrFail();

        $posts = $tag->posts()
            ->published()
            ->with('categories', 'tags', 'author')
            ->orderBy('published_at', 'desc')
            ->paginate(12);

        return view('pages.blog.index', compact('tag', 'posts'));
    }
}
