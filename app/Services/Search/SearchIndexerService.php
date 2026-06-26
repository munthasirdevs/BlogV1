<?php

namespace App\Services\Search;

use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Services\CacheService;

class SearchIndexerService
{
    public function __construct(
        protected CacheService $cacheService
    ) {}

    public function indexPost(Post $post): array
    {
        $data = [
            'id' => $post->id,
            'title' => $post->title,
            'slug' => $post->slug,
            'excerpt' => $post->excerpt ?? strip_tags(mb_substr($post->content ?? '', 0, 200)),
            'content' => strip_tags($post->content ?? ''),
            'status' => $post->status,
            'visibility' => $post->visibility,
            'category_id' => $post->category_id,
            'category_name' => $post->category?->name,
            'tags' => $post->tags->pluck('name')->toArray(),
            'author_name' => $post->author?->name,
            'views_count' => $post->views_count,
            'seo_score' => $post->seo_score,
            'published_at' => $post->published_at?->toIso8601String(),
            'created_at' => $post->created_at->toIso8601String(),
            'tenant_id' => $post->tenant_id,
        ];

        $cacheKey = "search:post:{$post->id}";
        $this->cacheService->put($cacheKey, $data, 3600);

        return $data;
    }

    public function removePost(int $postId): void
    {
        $this->cacheService->forget("search:post:{$postId}");
    }

    public function rebuildIndex(?int $tenantId = null): int
    {
        $count = 0;

        Post::query()
            ->when($tenantId, fn($q) => $q->where('tenant_id', $tenantId))
            ->with('category', 'tags', 'author')
            ->chunk(100, function ($posts) use (&$count) {
                foreach ($posts as $post) {
                    $this->indexPost($post);
                    $count++;
                }
            });

        $this->cacheService->forgetByPattern('search:*');

        return $count;
    }

    public function getFacets(): array
    {
        return [
            'categories' => Category::published()
                ->withCount('posts')
                ->orderByDesc('posts_count')
                ->take(20)
                ->get(['id', 'name', 'slug']),
            'tags' => Tag::active()
                ->orderByDesc('usage_count')
                ->take(20)
                ->get(['id', 'name', 'slug']),
        ];
    }

    public function searchWithFilters(string $keyword, array $filters = [], int $perPage = 12)
    {
        $query = Post::published()
            ->where(function ($q) use ($keyword) {
                $q->whereRaw('MATCH(title, content) AGAINST(? IN BOOLEAN MODE)', [$keyword])
                  ->orWhere('title', 'like', "%{$keyword}%");
            });

        if (!empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (!empty($filters['tag_id'])) {
            $query->whereHas('tags', fn($t) => $t->where('tags.id', $filters['tag_id']));
        }

        if (!empty($filters['author_id'])) {
            $query->where('author_id', $filters['author_id']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('published_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('published_at', '<=', $filters['date_to']);
        }

        $sort = $filters['sort'] ?? 'relevance';
        match ($sort) {
            'latest' => $query->orderBy('published_at', 'desc'),
            'popular' => $query->orderBy('views_count', 'desc'),
            default => $query->orderBy('published_at', 'desc'),
        };

        return $query->with('category', 'author', 'tags')->paginate($perPage);
    }
}
