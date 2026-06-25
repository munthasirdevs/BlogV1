<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function show(string $slug): View
    {
        $category = Category::where('slug', $slug)
            ->published()
            ->firstOrFail();

        $posts = $category->posts()
            ->published()
            ->with('categories', 'tags', 'author')
            ->orderBy('published_at', 'desc')
            ->paginate(12);

        return view('pages.blog.index', compact('category', 'posts'));
    }
}
