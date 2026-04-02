<?php

namespace App\Repositories;

use App\Models\Post;
use App\Models\Category;
use App\Models\Tag;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Class PostRepository
 *
 * Repository for Post model with advanced query methods.
 * Extends BaseRepository with post-specific operations.
 *
 * @package App\Repositories
 */
class PostRepository extends BaseRepository
{
    /**
     * Specify Model class name.
     */
    protected function model(): string
    {
        return Post::class;
    }

    /**
     * Get paginated posts with advanced filtering.
     *
     * @param array $filters
     * @param int $perPage
     * @param array $columns
     * @return LengthAwarePaginator
     */
    public function getPaginated(
        array $filters = [],
        int $perPage = 15,
        array $columns = ['*']
    ): LengthAwarePaginator {
        $query = $this->model->newQuery();

        // Apply filters
        $this->applyFilters($query, $filters);

        // Apply sorting
        $this->applySorting($query, $filters);

        // Eager load relationships
        $this->applyEagerLoading($query, $filters);

        return $query->paginate($perPage, $columns);
    }

    /**
     * Get published posts with filters.
     *
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getPublishedPosts(
        array $filters = [],
        int $perPage = 15
    ): LengthAwarePaginator {
        $query = $this->model->published()
            ->with(['author', 'category', 'tags']);

        $this->applyFilters($query, $filters);
        $this->applySorting($query, $filters);

        return $query->paginate($perPage);
    }

    /**
     * Get user's posts with all statuses.
     *
     * @param int $userId
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getUserPosts(
        int $userId,
        array $filters = [],
        int $perPage = 15
    ): LengthAwarePaginator {
        $query = $this->model->where('user_id', $userId);

        $this->applyFilters($query, $filters);
        $this->applySorting($query, $filters);

        return $query->with(['category', 'tags'])->paginate($perPage);
    }

    /**
     * Get posts by category.
     *
     * @param int|string $category Category ID or slug
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getByCategory(
        int|string $category,
        array $filters = [],
        int $perPage = 15
    ): LengthAwarePaginator {
        $query = $this->model->published();

        if (is_numeric($category)) {
            $query->where('category_id', $category);
        } else {
            $query->whereHas('category', function ($q) use ($category) {
                $q->where('slug', $category);
            });
        }

        $this->applyFilters($query, $filters);
        $this->applySorting($query, $filters);

        return $query->with(['author', 'tags'])->paginate($perPage);
    }

    /**
     * Get posts by tag.
     *
     * @param int|string $tag Tag ID or slug
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getByTag(
        int|string $tag,
        array $filters = [],
        int $perPage = 15
    ): LengthAwarePaginator {
        $query = $this->model->published();

        if (is_numeric($tag)) {
            $query->whereHas('tags', function ($q) use ($tag) {
                $q->where('tags.id', $tag);
            });
        } else {
            $query->whereHas('tags', function ($q) use ($tag) {
                $q->where('slug', $tag);
            });
        }

        $this->applyFilters($query, $filters);
        $this->applySorting($query, $filters);

        return $query->with(['author', 'category'])->paginate($perPage);
    }

    /**
     * Get posts by author.
     *
     * @param int $authorId
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getByAuthor(
        int $authorId,
        array $filters = [],
        int $perPage = 15
    ): LengthAwarePaginator {
        $query = $this->model->published()
            ->where('user_id', $authorId);

        $this->applyFilters($query, $filters);
        $this->applySorting($query, $filters);

        return $query->with(['category', 'tags'])->paginate($perPage);
    }

    /**
     * Search posts with full-text search.
     *
     * @param string $query
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function search(
        string $query,
        array $filters = [],
        int $perPage = 15
    ): LengthAwarePaginator {
        $searchQuery = $this->model->published();

        // Full-text search in title, content, and excerpt
        $searchQuery->where(function ($q) use ($query) {
            $q->where('title', 'LIKE', "%{$query}%")
                ->orWhere('content', 'LIKE', "%{$query}%")
                ->orWhere('excerpt', 'LIKE', "%{$query}%")
                ->orWhere('meta_title', 'LIKE', "%{$query}%")
                ->orWhere('meta_description', 'LIKE', "%{$query}%");
        });

        // Add relevance scoring
        $searchQuery->selectRaw(
            '*, 
            CASE 
                WHEN title LIKE ? THEN 3
                WHEN excerpt LIKE ? THEN 2
                ELSE 1
            END as relevance',
            ["%{$query}%", "%{$query}%"]
        )
        ->orderBy('relevance', 'desc')
        ->orderBy('published_at', 'desc');

        $this->applyFilters($searchQuery, $filters);

        return $searchQuery->with(['author', 'category', 'tags'])->paginate($perPage);
    }

    /**
     * Boolean mode search.
     *
     * @param string $query
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function searchBoolean(
        string $query,
        array $filters = [],
        int $perPage = 15
    ): LengthAwarePaginator {
        $searchQuery = $this->model->published();

        // Parse boolean operators
        $mustHave = [];
        $mustNot = [];
        $anyOf = [];

        $words = explode(' ', $query);
        foreach ($words as $word) {
            $word = trim($word);
            if (str_starts_with($word, '+')) {
                $mustHave[] = substr($word, 1);
            } elseif (str_starts_with($word, '-')) {
                $mustNot[] = substr($word, 1);
            } elseif (str_starts_with($word, '|')) {
                $anyOf[] = substr($word, 1);
            } else {
                $anyOf[] = $word;
            }
        }

        $searchQuery->where(function ($q) use ($mustHave, $mustNot, $anyOf) {
            // Must have words
            foreach ($mustHave as $word) {
                $q->where(function ($sub) use ($word) {
                    $sub->where('title', 'LIKE', "%{$word}%")
                        ->orWhere('content', 'LIKE', "%{$word}%");
                });
            }

            // Must not have words
            foreach ($mustNot as $word) {
                $q->where(function ($sub) use ($word) {
                    $sub->where('title', 'NOT LIKE', "%{$word}%")
                        ->where('content', 'NOT LIKE', "%{$word}%");
                });
            }

            // Any of words
            if (!empty($anyOf)) {
                $q->where(function ($sub) use ($anyOf) {
                    foreach ($anyOf as $word) {
                        $sub->orWhere('title', 'LIKE', "%{$word}%")
                            ->orWhere('content', 'LIKE', "%{$word}%");
                    }
                });
            }
        });

        $searchQuery->orderBy('published_at', 'desc');
        $this->applyFilters($searchQuery, $filters);

        return $searchQuery->with(['author', 'category', 'tags'])->paginate($perPage);
    }

    /**
     * Get trending posts.
     *
     * @param int $days Number of days to look back
     * @param int $limit Maximum number of posts to return
     * @return Collection
     */
    public function getTrending(
        int $days = 7,
        int $limit = 10
    ): Collection {
        $cacheKey = "trending_posts_{$days}_{$limit}";

        return Cache::remember($cacheKey, 3600, function () use ($days, $limit) {
            return $this->model->published()
                ->where('published_at', '>=', now()->subDays($days))
                ->selectRaw('*, 
                    (views_count + (likes_count * 2) + (comments_count * 3)) as trending_score
                ')
                ->orderBy('trending_score', 'desc')
                ->limit($limit)
                ->get(['*', DB::raw('(views_count + (likes_count * 2) + (comments_count * 3)) as trending_score')]);
        });
    }

    /**
     * Clear trending cache.
     *
     * @return void
     */
    public function clearTrendingCache(): void
    {
        Cache::forget("trending_posts_7_10");
        Cache::forget("trending_posts_30_10");
    }

    /**
     * Get featured posts.
     *
     * @param int $limit
     * @return Collection
     */
    public function getFeatured(int $limit = 5): Collection
    {
        return $this->model->published()
            ->featured()
            ->orderBy('featured_at', 'desc')
            ->limit($limit)
            ->with(['author', 'category', 'tags'])
            ->get();
    }

    /**
     * Get related posts.
     *
     * @param Post $post
     * @param int $limit
     * @return Collection
     */
    public function getRelated(Post $post, int $limit = 4): Collection
    {
        return $this->model->published()
            ->where('id', '!=', $post->id)
            ->where(function ($q) use ($post) {
                // Same category
                $q->where('category_id', $post->category_id)
                    // Or shared tags
                    ->orWhereHas('tags', function ($tagQuery) use ($post) {
                        $tagQuery->whereIn('tags.id', $post->tags->pluck('id'));
                    });
            })
            ->orderBy('published_at', 'desc')
            ->limit($limit)
            ->with(['author', 'category', 'tags'])
            ->get();
    }

    /**
     * Get posts by status.
     *
     * @param string $status
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getByStatus(
        string $status,
        array $filters = [],
        int $perPage = 15
    ): LengthAwarePaginator {
        $query = $this->model->where('status', $status);

        $this->applyFilters($query, $filters);
        $this->applySorting($query, $filters);

        return $query->with(['author', 'category', 'tags'])->paginate($perPage);
    }

    /**
     * Get draft posts for a user.
     *
     * @param int $userId
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getDrafts(int $userId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->where('user_id', $userId)
            ->draft()
            ->latest('updated_at')
            ->with(['category', 'tags'])
            ->paginate($perPage);
    }

    /**
     * Get scheduled posts.
     *
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getScheduled(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->scheduled()
            ->where('scheduled_for', '>', now())
            ->orderBy('scheduled_for')
            ->with(['author', 'category', 'tags'])
            ->paginate($perPage);
    }

    /**
     * Get posts ready for publishing (scheduled time passed).
     *
     * @return Collection
     */
    public function getReadyForPublishing(): Collection
    {
        return $this->model->scheduled()
            ->where('scheduled_for', '<=', now())
            ->with(['author', 'category', 'tags'])
            ->get();
    }

    /**
     * Find post by slug.
     *
     * @param string $slug
     * @param array $with
     * @return Post|null
     */
    public function findBySlug(string $slug, array $with = []): ?Post
    {
        $query = $this->model->where('slug', $slug);

        if (!empty($with)) {
            $query->with($with);
        }

        return $query->first();
    }

    /**
     * Find post by slug including trashed.
     *
     * @param string $slug
     * @param array $with
     * @return Post|null
     */
    public function findBySlugWithTrashed(string $slug, array $with = []): ?Post
    {
        $query = $this->model->withTrashed()->where('slug', $slug);

        if (!empty($with)) {
            $query->with($with);
        }

        return $query->first();
    }

    /**
     * Generate unique slug.
     *
     * @param string $title
     * @param int|null $excludeId Post ID to exclude (for updates)
     * @return string
     */
    public function generateUniqueSlug(string $title, ?int $excludeId = null): string
    {
        $slug = Str::slug($title);
        $originalSlug = $slug;
        $counter = 1;

        while ($this->slugExists($slug, $excludeId)) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Check if slug exists.
     *
     * @param string $slug
     * @param int|null $excludeId
     * @return bool
     */
    public function slugExists(string $slug, ?int $excludeId = null): bool
    {
        $query = $this->model->where('slug', $slug);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    /**
     * Create a new post.
     *
     * @param array $data
     * @return Post
     */
    public function createPost(array $data): Post
    {
        // Generate unique slug if not provided
        if (empty($data['slug']) && !empty($data['title'])) {
            $data['slug'] = $this->generateUniqueSlug($data['title']);
        }

        // Auto-calculate reading time
        if (empty($data['reading_time']) && !empty($data['content'])) {
            $data['reading_time'] = $this->calculateReadingTime($data['content']);
        }

        $tags = $data['tags'] ?? [];
        unset($data['tags']);

        $post = $this->model->create($data);

        if (!empty($tags)) {
            $post->tags()->attach($tags);
        }

        return $post->fresh(['author', 'category', 'tags']);
    }

    /**
     * Update a post.
     *
     * @param Post $post
     * @param array $data
     * @return Post
     */
    public function updatePost(Post $post, array $data): Post
    {
        // Regenerate slug if title changes and slug wasn't manually set
        if (isset($data['title']) && $post->getOriginal('slug') === Str::slug($post->getOriginal('title'))) {
            $data['slug'] = $this->generateUniqueSlug($data['title'], $post->id);
        }

        // Recalculate reading time if content changes
        if (isset($data['content'])) {
            $data['reading_time'] = $this->calculateReadingTime($data['content']);
        }

        $tags = $data['tags'] ?? null;
        unset($data['tags']);

        $post->update($data);

        if ($tags !== null) {
            $post->tags()->sync($tags);
        }

        return $post->fresh(['author', 'category', 'tags']);
    }

    /**
     * Soft delete a post.
     *
     * @param Post $post
     * @return bool|null
     */
    public function deletePost(Post $post): ?bool
    {
        $this->clearTrendingCache();
        return $post->delete();
    }

    /**
     * Restore a soft-deleted post.
     *
     * @param Post $post
     * @return bool|null
     */
    public function restore(Post $post): ?bool
    {
        return $post->restore();
    }

    /**
     * Publish a post.
     *
     * @param Post $post
     * @return Post
     */
    public function publish(Post $post): Post
    {
        $post->update([
            'status' => Post::STATUS_PUBLISHED,
            'published_at' => $post->published_at ?? now(),
        ]);

        $this->clearTrendingCache();

        return $post->fresh();
    }

    /**
     * Unpublish a post.
     *
     * @param Post $post
     * @return Post
     */
    public function unpublish(Post $post): Post
    {
        $post->update([
            'status' => Post::STATUS_DRAFT,
        ]);

        $this->clearTrendingCache();

        return $post->fresh();
    }

    /**
     * Archive a post.
     *
     * @param Post $post
     * @return Post
     */
    public function archive(Post $post): Post
    {
        $post->update([
            'status' => Post::STATUS_ARCHIVED,
        ]);

        return $post->fresh();
    }

    /**
     * Feature a post.
     *
     * @param Post $post
     * @return Post
     */
    public function feature(Post $post): Post
    {
        $post->update([
            'is_featured' => true,
            'featured_at' => now(),
        ]);

        return $post->fresh();
    }

    /**
     * Unfeature a post.
     *
     * @param Post $post
     * @return Post
     */
    public function unfeature(Post $post): Post
    {
        $post->update([
            'is_featured' => false,
            'featured_at' => null,
        ]);

        return $post->fresh();
    }

    /**
     * Autosave a post draft.
     *
     * @param Post $post
     * @param array $data
     * @return Post
     */
    public function autosave(Post $post, array $data): Post
    {
        // Only update content-related fields for autosave
        $allowedFields = ['title', 'content', 'excerpt', 'meta_title', 'meta_description'];
        $autosaveData = [];

        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $autosaveData[$field] = $data[$field];
            }
        }

        // Don't change status or published_at
        $post->update($autosaveData);

        return $post->fresh();
    }

    /**
     * Increment view count.
     *
     * @param Post $post
     * @param int $amount
     * @return int
     */
    public function incrementViews(Post $post, int $amount = 1): int
    {
        return $post->increment('views_count', $amount);
    }

    /**
     * Get posts count by status.
     *
     * @param int|null $userId
     * @return array
     */
    public function getCountByStatus(?int $userId = null): array
    {
        $query = $this->model->query();

        if ($userId) {
            $query->where('user_id', $userId);
        }

        return [
            'all' => $query->count(),
            'draft' => (clone $query)->where('status', Post::STATUS_DRAFT)->count(),
            'published' => (clone $query)->where('status', Post::STATUS_PUBLISHED)->count(),
            'scheduled' => (clone $query)->where('status', Post::STATUS_SCHEDULED)->count(),
            'archived' => (clone $query)->where('status', Post::STATUS_ARCHIVED)->count(),
        ];
    }

    /**
     * Bulk update posts.
     *
     * @param array $ids
     * @param array $data
     * @return int Number of updated posts
     */
    public function bulkUpdate(array $ids, array $data): int
    {
        $this->clearTrendingCache();
        return $this->model->whereIn('id', $ids)->update($data);
    }

    /**
     * Bulk delete posts.
     *
     * @param array $ids
     * @return int Number of deleted posts
     */
    public function bulkDelete(array $ids): int
    {
        $this->clearTrendingCache();
        return $this->model->whereIn('id', $ids)->delete();
    }

    /**
     * Calculate reading time from content.
     *
     * @param string $content
     * @return int
     */
    private function calculateReadingTime(string $content): int
    {
        $words = str_word_count(strip_tags($content));
        return max(1, (int) ceil($words / 200));
    }

    /**
     * Apply filters to query.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $filters
     * @return void
     */
    private function applyFilters($query, array $filters): void
    {
        // Status filter
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Category filter (supports multiple categories - OR logic)
        if (isset($filters['category'])) {
            $categories = is_array($filters['category']) ? $filters['category'] : [$filters['category']];
            
            if (!empty($categories)) {
                $query->whereHas('category', function ($q) use ($categories) {
                    // Check if any of the categories match
                    $q->where(function ($subQ) use ($categories) {
                        foreach ($categories as $index => $category) {
                            if (is_numeric($category)) {
                                if ($index === 0) {
                                    $subQ->where('category_id', $category);
                                } else {
                                    $subQ->orWhere('category_id', $category);
                                }
                            } else {
                                if ($index === 0) {
                                    $subQ->where('slug', $category);
                                } else {
                                    $subQ->orWhere('slug', $category);
                                }
                            }
                        }
                    });
                });
            }
        }

        // Tag filter (supports multiple tags - AND logic)
        if (isset($filters['tag'])) {
            $tags = is_array($filters['tag']) ? $filters['tag'] : [$filters['tag']];
            
            if (!empty($tags)) {
                foreach ($tags as $tag) {
                    $query->whereHas('tags', function ($q) use ($tag) {
                        if (is_numeric($tag)) {
                            $q->where('tags.id', $tag);
                        } else {
                            $q->where('slug', $tag);
                        }
                    });
                }
            }
        }

        // Author filter
        if (isset($filters['author'])) {
            $query->where('user_id', $filters['author']);
        }

        // Featured filter
        if (isset($filters['featured']) && $filters['featured']) {
            $query->where('is_featured', true);
        }

        // Date range filter
        if (isset($filters['from_date'])) {
            $query->where('published_at', '>=', $filters['from_date']);
        }
        if (isset($filters['to_date'])) {
            $query->where('published_at', '<=', $filters['to_date']);
        }

        // Search filter
        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('title', 'LIKE', "%{$search}%")
                    ->orWhere('excerpt', 'LIKE', "%{$search}%")
                    ->orWhere('content', 'LIKE', "%{$search}%");
            });
        }

        // User ID filter (for admin views)
        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }
    }

    /**
     * Apply sorting to query.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $filters
     * @return void
     */
    private function applySorting($query, array $filters): void
    {
        $sortField = $filters['sort'] ?? 'published_at';
        $sortOrder = ($filters['order'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        $allowedSortFields = ['id', 'title', 'published_at', 'created_at', 'updated_at', 'views_count', 'likes_count', 'comments_count'];

        if (in_array($sortField, $allowedSortFields)) {
            $query->orderBy($sortField, $sortOrder);
        } else {
            $query->orderBy('published_at', 'desc');
        }
    }

    /**
     * Apply eager loading.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $filters
     * @return void
     */
    private function applyEagerLoading($query, array $filters): void
    {
        $defaultRelations = ['author', 'category', 'tags'];

        if (isset($filters['with'])) {
            $relations = is_array($filters['with']) ? $filters['with'] : explode(',', $filters['with']);
            $defaultRelations = array_merge($defaultRelations, $relations);
        }

        $query->with(array_unique($defaultRelations));
    }
}
