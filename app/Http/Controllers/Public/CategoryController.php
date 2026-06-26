<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Services\CacheService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function __construct(
        protected CacheService $cacheService
    ) {}

    public function show(Request $request, string $slug): View
    {
        $category = Category::where('slug', $slug)
            ->published()
            ->with('children', 'seo', 'parent')
            ->firstOrFail();

        $query = $category->posts()->published()->with('tags', 'author', 'media');

        if ($author = $request->get('author')) {
            $query->whereHas('author', fn($q) => $q->where('name', 'like', "%{$author}%"));
        }

        if ($from = $request->get('from')) {
            $query->whereDate('published_at', '>=', $from);
        }

        if ($to = $request->get('to')) {
            $query->whereDate('published_at', '<=', $to);
        }

        switch ($request->get('sort', 'latest')) {
            case 'popular':
                $query->orderBy('views_count', 'desc');
                break;
            case 'oldest':
                $query->orderBy('published_at', 'asc');
                break;
            default:
                $query->orderBy('published_at', 'desc');
        }

        $posts = $query->paginate(12)->withQueryString();

        $featuredPosts = $category->posts()
            ->published()
            ->where('is_featured', true)
            ->with('tags', 'media')
            ->orderBy('published_at', 'desc')
            ->take(3)
            ->get();

        $siblingCategories = collect();
        if ($category->parent) {
            $siblingCategories = $category->parent->children()
                ->published()
                ->orderBy('sort_order')
                ->get();
        }

        $categories = $this->cacheService->getCategories();

        return view('pages.category.show', compact('category', 'posts', 'featuredPosts', 'siblingCategories', 'categories'));
    }
}
