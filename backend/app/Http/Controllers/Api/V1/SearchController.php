<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\PostResource;
use App\Models\Post;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    /**
     * Search posts.
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'q' => ['required', 'string', 'min:2'],
        ]);

        $query = Post::published()
            ->with(['author', 'category', 'tags']);

        // Search in title, content, excerpt
        $searchTerm = $request->q;
        $query->where(function ($q) use ($searchTerm) {
            $q->where('title', 'LIKE', "%{$searchTerm}%")
                ->orWhere('content', 'LIKE', "%{$searchTerm}%")
                ->orWhere('excerpt', 'LIKE', "%{$searchTerm}%");
        });

        // Filter by category
        if ($request->has('category')) {
            $query->whereHas('category', function ($q) use ($request) {
                $q->where('slug', $request->category);
            });
        }

        // Filter by tag
        if ($request->has('tag')) {
            $query->whereHas('tags', function ($q) use ($request) {
                $q->where('slug', $request->tag);
            });
        }

        // Filter by author
        if ($request->has('author')) {
            $query->where('user_id', $request->author);
        }

        // Date range filter
        if ($request->has('date_from')) {
            $query->where('published_at', '>=', $request->date_from);
        }
        if ($request->has('date_to')) {
            $query->where('published_at', '<=', $request->date_to);
        }

        // Sorting
        $sortField = match ($request->sort) {
            'date' => 'published_at',
            'views' => 'views_count',
            default => 'published_at',
        };
        $sortOrder = $request->order === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sortField, $sortOrder);

        $posts = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => PostResource::collection($posts),
            'meta' => [
                'query' => $searchTerm,
                'total' => $posts->total(),
                'current_page' => $posts->currentPage(),
                'per_page' => $posts->perPage(),
            ],
        ]);
    }

    /**
     * Get search suggestions.
     */
    public function suggest(Request $request): JsonResponse
    {
        $request->validate([
            'q' => ['required', 'string', 'min:2'],
        ]);

        $searchTerm = $request->q;
        $limit = $request->get('limit', 5);

        // Get post suggestions
        $posts = Post::published()
            ->where('title', 'LIKE', "%{$searchTerm}%")
            ->limit($limit)
            ->get(['id', 'title', 'slug']);

        // Get category suggestions
        $categories = \App\Models\Category::active()
            ->where('name', 'LIKE', "%{$searchTerm}%")
            ->limit($limit)
            ->get(['id', 'name', 'slug']);

        // Get tag suggestions
        $tags = \App\Models\Tag::where('name', 'LIKE', "%{$searchTerm}%")
            ->limit($limit)
            ->get(['id', 'name', 'slug']);

        return response()->json([
            'success' => true,
            'data' => [
                'posts' => $posts->map(fn($p) => [
                    'id' => $p->id,
                    'title' => $p->title,
                    'slug' => $p->slug,
                ]),
                'categories' => $categories->map(fn($c) => [
                    'id' => $c->id,
                    'name' => $c->name,
                    'slug' => $c->slug,
                ]),
                'tags' => $tags->map(fn($t) => [
                    'id' => $t->id,
                    'name' => $t->name,
                    'slug' => $t->slug,
                ]),
            ],
        ]);
    }
}
