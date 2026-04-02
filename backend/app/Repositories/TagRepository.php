<?php

namespace App\Repositories;

use App\Models\Tag;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Class TagRepository
 *
 * Repository for Tag model operations.
 *
 * @extends BaseRepository<Tag>
 */
class TagRepository extends BaseRepository
{
    /**
     * Cache key prefix.
     */
    const CACHE_PREFIX = 'tags.';

    /**
     * Cache TTL in seconds (1 hour).
     */
    const CACHE_TTL = 3600;

    /**
     * Default limit for suggestions.
     */
    const SUGGESTION_LIMIT = 10;

    /**
     * Default limit for popular tags.
     */
    const POPULAR_LIMIT = 20;

    /**
     * Specify Model class name.
     *
     * @return string
     */
    protected function model(): string
    {
        return Tag::class;
    }

    /**
     * Get popular tags (most used) with caching.
     *
     * @param int $limit
     * @param array $columns
     * @return Collection
     */
    public function getPopular(int $limit = self::POPULAR_LIMIT, array $columns = ['*']): Collection
    {
        return Cache::remember(
            self::CACHE_PREFIX . 'popular.' . $limit,
            self::CACHE_TTL,
            function () use ($limit, $columns) {
                return $this->model->withCount('posts')
                    ->orderBy('posts_count', 'desc')
                    ->limit($limit)
                    ->get($columns);
            }
        );
    }

    /**
     * Get featured tags.
     *
     * @param array $columns
     * @return Collection
     */
    public function findFeatured(array $columns = ['*']): Collection
    {
        return $this->model->where('is_featured', true)->get($columns);
    }

    /**
     * Get tag by slug.
     *
     * @param string $slug
     * @param array $columns
     * @return Tag|null
     */
    public function findBySlug(string $slug, array $columns = ['*']): ?Tag
    {
        return $this->model->where('slug', $slug)->first($columns);
    }

    /**
     * Get tags by multiple slugs.
     *
     * @param array $slugs
     * @param array $columns
     * @return Collection
     */
    public function findBySlugs(array $slugs, array $columns = ['*']): Collection
    {
        return $this->model->whereIn('slug', $slugs)->get($columns);
    }

    /**
     * Search tags.
     *
     * @param string $search
     * @param array $columns
     * @return Collection
     */
    public function search(string $search, array $columns = ['*']): Collection
    {
        return $this->model->where(function ($q) use ($search) {
            $q->where('name', 'LIKE', "%{$search}%")
                ->orWhere('description', 'LIKE', "%{$search}%");
        })->get($columns);
    }

    /**
     * Get tags with post count.
     *
     * @param array $columns
     * @return Collection
     */
    public function withPostsCount(array $columns = ['*']): Collection
    {
        return $this->model->withCount('posts')->get($columns);
    }

    /**
     * Get trending tags (most used in recent period).
     *
     * @param int $days
     * @param int $limit
     * @param array $columns
     * @return Collection
     */
    public function getTrending(int $days = 30, int $limit = 10, array $columns = ['*']): Collection
    {
        return $this->model->whereHas('posts', function ($q) use ($days) {
            $q->where('published_at', '>=', now()->subDays($days));
        })
        ->withCount(['posts as recent_posts_count' => function ($q) use ($days) {
            $q->where('published_at', '>=', now()->subDays($days));
        }])
        ->orderBy('recent_posts_count', 'desc')
        ->limit($limit)
        ->get($columns);
    }

    /**
     * Get or create tag by name.
     *
     * @param string $name
     * @param array $attributes
     * @return Tag
     */
    public function firstOrCreateByName(string $name, array $attributes = []): Tag
    {
        $slug = \Illuminate\Support\Str::slug($name);

        return $this->model->firstOrCreate(
            ['slug' => $slug],
            array_merge(['name' => $name], $attributes)
        );
    }

    /**
     * Sync tags by names.
     *
     * @param array $names
     * @return array
     */
    public function syncByNames(array $names): array
    {
        $tagIds = [];

        foreach ($names as $name) {
            $tag = $this->firstOrCreateByName($name);
            $tagIds[] = $tag->id;
        }

        return $tagIds;
    }

    /**
     * Get tag suggestions based on search term with caching.
     *
     * @param string $search
     * @param int $limit
     * @return Collection
     */
    public function getSuggestions(string $search, int $limit = self::SUGGESTION_LIMIT): Collection
    {
        $cacheKey = self::CACHE_PREFIX . 'suggestions.' . md5($search) . '.' . $limit;

        return Cache::remember(
            $cacheKey,
            self::CACHE_TTL,
            function () use ($search, $limit) {
                return $this->model->where('name', 'LIKE', "%{$search}%")
                    ->withCount('posts')
                    ->orderBy('posts_count', 'desc')
                    ->orderBy('name', 'asc')
                    ->limit($limit)
                    ->get();
            }
        );
    }

    /**
     * Delete unused tags.
     *
     * @return int Number of deleted tags
     */
    public function deleteUnused(): int
    {
        return $this->model->whereDoesntHave('posts')->delete();
    }

    /**
     * Get tags alphabetically.
     *
     * @param array $columns
     * @return Collection
     */
    public function getAlphabetical(array $columns = ['*']): Collection
    {
        return $this->model->orderBy('name')->get($columns);
    }

    /**
     * Get tags for autocomplete with caching.
     *
     * @param string $query
     * @param int $limit
     * @return array
     */
    public function getForAutocomplete(string $query, int $limit = 10): array
    {
        $cacheKey = self::CACHE_PREFIX . 'autocomplete.' . md5($query) . '.' . $limit;

        $tags = Cache::remember(
            $cacheKey,
            self::CACHE_TTL,
            function () use ($query, $limit) {
                return $this->model->where('name', 'LIKE', "%{$query}%")
                    ->withCount('posts')
                    ->orderBy('posts_count', 'desc')
                    ->limit($limit)
                    ->get();
            }
        );

        return $tags->map(function ($tag) {
            return [
                'id' => $tag->id,
                'name' => $tag->name,
                'slug' => $tag->slug,
                'posts_count' => $tag->posts_count,
            ];
        })->toArray();
    }

    /**
     * Update tag post count.
     *
     * @param int $tagId
     * @return int New post count
     */
    public function updatePostCount(int $tagId): int
    {
        $tag = $this->findOrFail($tagId);
        $count = $tag->posts()->published()->count();
        $tag->update(['posts_count' => $count]);
        
        // Clear popular tags cache
        $this->clearPopularCache();
        
        return $count;
    }

    /**
     * Update post counts for all tags.
     *
     * @return void
     */
    public function updateAllPostCounts(): void
    {
        $tags = $this->model->get();
        
        foreach ($tags as $tag) {
            $tag->update(['posts_count' => $tag->posts()->published()->count()]);
        }
        
        $this->clearPopularCache();
    }

    /**
     * Clear popular tags cache.
     *
     * @return void
     */
    public function clearPopularCache(): void
    {
        Cache::forget(self::CACHE_PREFIX . 'popular.' . self::POPULAR_LIMIT);
        Cache::forget(self::CACHE_PREFIX . 'popular.10');
        Cache::forget(self::CACHE_PREFIX . 'popular.20');
    }

    /**
     * Clear suggestion cache for a specific query.
     *
     * @param string $search
     * @return void
     */
    public function clearSuggestionCache(string $search): void
    {
        $cacheKey = self::CACHE_PREFIX . 'suggestions.' . md5($search) . '.' . self::SUGGESTION_LIMIT;
        Cache::forget($cacheKey);
    }

    /**
     * Get tag with posts.
     *
     * @param int $id
     * @param int $postsLimit
     * @return Tag|null
     */
    public function getWithTagPosts(int $id, int $postsLimit = 10): ?Tag
    {
        return $this->model->with(['posts' => function ($q) use ($postsLimit) {
            $q->published()->latest()->limit($postsLimit);
        }])->find($id);
    }

    /**
     * Get tag by slug with posts.
     *
     * @param string $slug
     * @param int $postsLimit
     * @return Tag|null
     */
    public function getBySlugWithPosts(string $slug, int $postsLimit = 10): ?Tag
    {
        return $this->model->with(['posts' => function ($q) use ($postsLimit) {
            $q->published()->latest()->limit($postsLimit);
        }])->where('slug', $slug)->first();
    }

    /**
     * Get paginated posts for a tag.
     *
     * @param int $tagId
     * @param int $perPage
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function getPaginatedPosts(int $tagId, int $perPage = 15): \Illuminate\Pagination\LengthAwarePaginator
    {
        $tag = $this->findOrFail($tagId);
        
        return $tag->posts()
            ->published()
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Get paginated posts for a tag by slug.
     *
     * @param string $slug
     * @param int $perPage
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function getPaginatedPostsBySlug(string $slug, int $perPage = 15): \Illuminate\Pagination\LengthAwarePaginator
    {
        $tag = $this->findBySlug($slug);
        
        if (!$tag) {
            throw new \Illuminate\Database\Eloquent\ModelNotFoundException("Tag not found: {$slug}");
        }
        
        return $tag->posts()
            ->published()
            ->latest()
            ->paginate($perPage);
    }
}
