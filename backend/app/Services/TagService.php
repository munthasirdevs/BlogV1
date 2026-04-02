<?php

namespace App\Services;

use App\Repositories\TagRepository;
use App\Models\Tag;
use App\Models\Post;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Class TagService
 *
 * Service class for tag-related operations.
 * Handles tag CRUD, popularity tracking, and suggestions.
 */
class TagService extends BaseService
{
    /**
     * Initialize the repository.
     */
    protected function initializeRepository(): void
    {
        $this->repository = new TagRepository();
    }

    /**
     * Get the repository instance.
     *
     * @return TagRepository
     */
    public function repository(): TagRepository
    {
        return $this->repository;
    }

    /**
     * Get paginated tags.
     *
     * @param array $filters
     * @param int $perPage
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function getPaginatedTags(array $filters = [], int $perPage = 15): \Illuminate\Pagination\LengthAwarePaginator
    {
        $query = Tag::query();

        // Search filter
        if (isset($filters['search'])) {
            $query->search($filters['search']);
        }

        // Featured filter
        if (isset($filters['is_featured'])) {
            $query->where('is_featured', $filters['is_featured']);
        }

        // Sorting
        $sortField = $filters['sort'] ?? 'name';
        $sortOrder = ($filters['order'] ?? 'asc') === 'asc' ? 'asc' : 'desc';

        $allowedSorts = ['name', 'created_at', 'updated_at'];
        if (in_array($sortField, $allowedSorts)) {
            $query->orderBy($sortField, $sortOrder);
        } else {
            $query->orderBy('name');
        }

        return $query->paginate($perPage);
    }

    /**
     * Create a new tag.
     *
     * @param array $data
     * @return Tag
     */
    public function createTag(array $data): Tag
    {
        return DB::transaction(function () use ($data) {
            // Auto-generate slug if not provided
            if (empty($data['slug'])) {
                $data['slug'] = Str::slug($data['name']);
            }

            // Ensure slug is unique
            $data['slug'] = $this->generateUniqueSlug($data['slug']);

            // Set default color if not provided
            if (empty($data['color'])) {
                $data['color'] = '#6B7280';
            }

            return $this->repository->create($data);
        });
    }

    /**
     * Update a tag.
     *
     * @param int $id
     * @param array $data
     * @return Tag
     */
    public function updateTag(int $id, array $data): Tag
    {
        return DB::transaction(function () use ($id, $data) {
            $tag = $this->findOrFail($id);

            // Auto-generate slug if name changed and slug wasn't manually set
            if (isset($data['name']) && $tag->getOriginal('slug') === Str::slug($tag->getOriginal('name'))) {
                $data['slug'] = Str::slug($data['name']);
            }

            // Ensure slug is unique (excluding current tag)
            if (isset($data['slug'])) {
                $data['slug'] = $this->generateUniqueSlug($data['slug'], $id);
            }

            return $this->repository->update($id, $data);
        });
    }

    /**
     * Get tag by slug.
     *
     * @param string $slug
     * @param bool $includePosts
     * @return Tag|null
     */
    public function findBySlug(string $slug, bool $includePosts = false): ?Tag
    {
        $tag = $this->repository->findBySlug($slug);

        if ($tag && $includePosts) {
            $tag->load(['posts' => function ($q) {
                $q->published()->latest()->limit(10);
            }]);
        }

        return $tag;
    }

    /**
     * Get popular tags with caching.
     *
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getPopularTags(int $limit = 20): \Illuminate\Database\Eloquent\Collection
    {
        return $this->repository->getPopular($limit);
    }

    /**
     * Get featured tags.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getFeaturedTags(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->repository->findFeatured();
    }

    /**
     * Get trending tags.
     *
     * @param int $days
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getTrendingTags(int $days = 30, int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return $this->repository->getTrending($days, $limit);
    }

    /**
     * Search tags.
     *
     * @param string $search
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function searchTags(string $search): \Illuminate\Database\Eloquent\Collection
    {
        return $this->repository->search($search);
    }

    /**
     * Get tag suggestions with caching.
     *
     * @param string $search
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getTagSuggestions(string $search, int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        if (empty(trim($search))) {
            return collect();
        }

        return $this->repository->getSuggestions($search, $limit);
    }

    /**
     * Get or create tag by name.
     *
     * @param string $name
     * @param array $attributes
     * @return Tag
     */
    public function getOrCreateTag(string $name, array $attributes = []): Tag
    {
        return $this->repository->firstOrCreateByName($name, $attributes);
    }

    /**
     * Sync tags by names.
     *
     * @param array $names
     * @return array Array of tag IDs
     */
    public function syncTagsByNames(array $names): array
    {
        return $this->repository->syncByNames($names);
    }

    /**
     * Get tags with post count.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getTagsWithPostCount(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->repository->withPostsCount();
    }

    /**
     * Delete unused tags.
     *
     * @return int Number of deleted tags
     */
    public function deleteUnusedTags(): int
    {
        return $this->repository->deleteUnused();
    }

    /**
     * Delete a tag.
     *
     * @param int $id
     * @return bool|null
     */
    public function deleteTag(int $id): ?bool
    {
        $tag = $this->findOrFail($id);

        // Check if tag has posts
        if ($tag->posts()->count() > 0) {
            throw new \RuntimeException('Cannot delete a tag that is attached to posts. Please detach the tag from all posts first.');
        }

        return $this->delete($id);
    }

    /**
     * Get tag by ID with posts.
     *
     * @param int $id
     * @return Tag|null
     */
    public function getTagWithPosts(int $id): ?Tag
    {
        return $this->repository->getWithTagPosts($id);
    }

    /**
     * Get tags by multiple slugs.
     *
     * @param array $slugs
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getTagsBySlugs(array $slugs): \Illuminate\Database\Eloquent\Collection
    {
        return $this->repository->findBySlugs($slugs);
    }

    /**
     * Toggle featured status.
     *
     * @param int $id
     * @return Tag
     */
    public function toggleFeatured(int $id): Tag
    {
        $tag = $this->findOrFail($id);
        return $this->update($id, ['is_featured' => !$tag->is_featured]);
    }

    /**
     * Get tag statistics.
     *
     * @param int $id
     * @return array
     */
    public function getTagStats(int $id): array
    {
        $tag = $this->findOrFail($id);

        return [
            'posts_count' => $tag->posts()->count(),
            'published_posts_count' => $tag->posts()->published()->count(),
            'recent_posts_count' => $tag->posts()->where('published_at', '>=', now()->subDays(30))->count(),
        ];
    }

    /**
     * Generate a unique slug.
     *
     * @param string $slug
     * @param int|null $excludeId
     * @return string
     */
    protected function generateUniqueSlug(string $slug, ?int $excludeId = null): string
    {
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
    protected function slugExists(string $slug, ?int $excludeId = null): bool
    {
        $query = Tag::where('slug', $slug);

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    /**
     * Get all tags alphabetically.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAllAlphabetically(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->repository->getAlphabetical();
    }

    /**
     * Get tags for autocomplete.
     *
     * @param string $query
     * @param int $limit
     * @return array
     */
    public function getForAutocomplete(string $query, int $limit = 10): array
    {
        return $this->repository->getForAutocomplete($query, $limit);
    }

    /**
     * Attach tags to a post.
     *
     * @param int $postId
     * @param array $tagIds
     * @param bool $createIfNotExist
     * @return array Array of attached tag IDs
     */
    public function attachTagsToPost(int $postId, array $tagIds, bool $createIfNotExist = false): array
    {
        return DB::transaction(function () use ($postId, $tagIds, $createIfNotExist) {
            $post = Post::findOrFail($postId);
            $attachedIds = [];

            foreach ($tagIds as $tagId) {
                // If tagId is a string, it might be a tag name
                if (is_string($tagId)) {
                    if ($createIfNotExist) {
                        $tag = $this->getOrCreateTag($tagId);
                        $tagId = $tag->id;
                    } else {
                        $tag = $this->repository->findBySlug($tagId);
                        if (!$tag) {
                            continue;
                        }
                        $tagId = $tag->id;
                    }
                }

                // Attach if not already attached
                if (!$post->tags()->where('tag_id', $tagId)->exists()) {
                    $post->tags()->attach($tagId);
                    $this->repository->updatePostCount($tagId);
                }

                $attachedIds[] = $tagId;
            }

            return $attachedIds;
        });
    }

    /**
     * Sync tags for a post.
     *
     * @param int $postId
     * @param array $tagIds
     * @param bool $createIfNotExist
     * @return array Array of synced tag IDs
     */
    public function syncTagsForPost(int $postId, array $tagIds, bool $createIfNotExist = false): array
    {
        return DB::transaction(function () use ($postId, $tagIds, $createIfNotExist) {
            $post = Post::findOrFail($postId);
            $processedIds = [];

            // Process each tag ID or name
            foreach ($tagIds as $tagId) {
                if (is_string($tagId)) {
                    if ($createIfNotExist) {
                        $tag = $this->getOrCreateTag($tagId);
                        $tagId = $tag->id;
                    } else {
                        $tag = $this->repository->findBySlug($tagId);
                        if (!$tag) {
                            continue;
                        }
                        $tagId = $tag->id;
                    }
                }
                $processedIds[] = $tagId;
            }

            // Get current tag IDs
            $currentIds = $post->tags()->pluck('tag_id')->toArray();

            // Detach tags that are no longer in the list
            $toDetach = array_diff($currentIds, $processedIds);
            foreach ($toDetach as $detachId) {
                $post->tags()->detach($detachId);
                $this->repository->updatePostCount($detachId);
            }

            // Attach new tags
            $toAttach = array_diff($processedIds, $currentIds);
            foreach ($toAttach as $attachId) {
                $post->tags()->attach($attachId);
                $this->repository->updatePostCount($attachId);
            }

            return $processedIds;
        });
    }

    /**
     * Detach a tag from a post.
     *
     * @param int $postId
     * @param int $tagId
     * @return bool
     */
    public function detachTagFromPost(int $postId, int $tagId): bool
    {
        return DB::transaction(function () use ($postId, $tagId) {
            $post = Post::findOrFail($postId);
            $result = $post->tags()->detach($tagId);
            
            // Update post count
            $this->repository->updatePostCount($tagId);
            
            return $result > 0;
        });
    }

    /**
     * Get paginated posts for a tag.
     *
     * @param string $slug
     * @param int $perPage
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function getPostsByTagSlug(string $slug, int $perPage = 15): \Illuminate\Pagination\LengthAwarePaginator
    {
        return $this->repository->getPaginatedPostsBySlug($slug, $perPage);
    }

    /**
     * Get tag by slug with posts.
     *
     * @param string $slug
     * @param int $postsLimit
     * @return Tag|null
     */
    public function getTagBySlugWithPosts(string $slug, int $postsLimit = 10): ?Tag
    {
        return $this->repository->getBySlugWithPosts($slug, $postsLimit);
    }

    /**
     * Update all tag post counts.
     *
     * @return void
     */
    public function updateAllTagPostCounts(): void
    {
        $this->repository->updateAllPostCounts();
    }

    /**
     * Get tags as a cloud with weights.
     *
     * @param int $limit
     * @return \Illuminate\Support\Collection
     */
    public function getTagCloud(int $limit = 20): \Illuminate\Support\Collection
    {
        $tags = $this->repository->withPostsCount();
        $tags = $tags->filter(fn($tag) => $tag->posts_count > 0)
            ->sortByDesc('posts_count')
            ->take($limit);

        if ($tags->isEmpty()) {
            return $tags;
        }

        $maxPosts = $tags->max('posts_count');
        $minPosts = $tags->min('posts_count');
        $range = max(1, $maxPosts - $minPosts);

        return $tags->map(function ($tag) use ($minPosts, $range) {
            // Calculate weight from 1 to 5 based on posts count
            $tag->weight = (int) ceil((($tag->posts_count - $minPosts) / $range) * 4) + 1;
            return $tag;
        });
    }
}
