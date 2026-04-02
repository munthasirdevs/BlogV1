<?php

namespace App\Repositories;

use App\Models\Category;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Class CategoryRepository
 *
 * Repository for Category model operations.
 *
 * @extends BaseRepository<Category>
 */
class CategoryRepository extends BaseRepository
{
    /**
     * Maximum depth for category tree.
     */
    const MAX_DEPTH = 3;

    /**
     * Cache key prefix.
     */
    const CACHE_PREFIX = 'categories.';

    /**
     * Cache TTL in seconds (1 hour).
     */
    const CACHE_TTL = 3600;

    /**
     * Specify Model class name.
     *
     * @return string
     */
    protected function model(): string
    {
        return Category::class;
    }

    /**
     * Get all categories as a tree structure with depth limiting.
     *
     * @param int $maxDepth
     * @return Collection
     */
    public function getTree(int $maxDepth = self::MAX_DEPTH): Collection
    {
        return Cache::remember(
            self::CACHE_PREFIX . 'tree',
            self::CACHE_TTL,
            function () use ($maxDepth) {
                return $this->buildTree(0, 0, $maxDepth);
            }
        );
    }

    /**
     * Build tree recursively with depth limiting.
     *
     * @param int|null $parentId
     * @param int $currentDepth
     * @param int $maxDepth
     * @return Collection
     */
    protected function buildTree(?int $parentId, int $currentDepth, int $maxDepth): Collection
    {
        if ($currentDepth >= $maxDepth) {
            return collect();
        }

        $categories = $this->model
            ->where('parent_id', $parentId)
            ->ordered()
            ->withCount(['posts as published_posts_count' => function ($q) {
                $q->published();
            }])
            ->get();

        foreach ($categories as $category) {
            $category->depth = $currentDepth;
            $category->children = $this->buildTree($category->id, $currentDepth + 1, $maxDepth);
            $category->total_posts_count = $this->calculateTotalPostsCount($category);
        }

        return $categories;
    }

    /**
     * Calculate total posts count including child categories.
     *
     * @param Category $category
     * @return int
     */
    public function calculateTotalPostsCount(Category $category): int
    {
        $count = $category->published_posts_count ?? 0;

        foreach ($category->children as $child) {
            $count += $this->calculateTotalPostsCount($child);
        }

        return $count;
    }

    /**
     * Get all categories as a flat list with depth.
     *
     * @return Collection
     */
    public function getFlatList(): Collection
    {
        $categories = $this->model->ordered()->get();

        return $categories->map(function ($category) use ($categories) {
            $depth = 0;
            $parent = $category->parent;

            while ($parent) {
                $depth++;
                $parent = $categories->find($parent->id)?->parent;
            }

            $category->depth = $depth;
            return $category;
        });
    }

    /**
     * Get active categories.
     *
     * @param array $columns
     * @return Collection
     */
    public function findActive(array $columns = ['*']): Collection
    {
        return $this->model->where('is_active', true)->get($columns);
    }

    /**
     * Get featured categories.
     *
     * @param array $columns
     * @return Collection
     */
    public function findFeatured(array $columns = ['*']): Collection
    {
        return $this->model->where('is_featured', true)->get($columns);
    }

    /**
     * Get top-level categories (no parent).
     *
     * @param array $columns
     * @return Collection
     */
    public function findTopLevel(array $columns = ['*']): Collection
    {
        return $this->model->whereNull('parent_id')->get($columns);
    }

    /**
     * Get children of a category.
     *
     * @param int $parentId
     * @param array $columns
     * @return Collection
     */
    public function findChildren(int $parentId, array $columns = ['*']): Collection
    {
        return $this->model->where('parent_id', $parentId)->get($columns);
    }

    /**
     * Get all descendants of a category.
     *
     * @param int $categoryId
     * @return Collection
     */
    public function findDescendants(int $categoryId): Collection
    {
        $category = $this->findOrFail($categoryId);
        return $category->descendants();
    }

    /**
     * Get category with post count.
     *
     * @param array $columns
     * @return Collection
     */
    public function withPostsCount(array $columns = ['*']): Collection
    {
        return $this->model->withCount(['posts as published_posts_count' => function ($q) {
            $q->published();
        }])->get($columns);
    }

    /**
     * Search categories.
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
     * Get category by slug.
     *
     * @param string $slug
     * @param array $columns
     * @return Category|null
     */
    public function findBySlug(string $slug, array $columns = ['*']): ?Category
    {
        return $this->model->where('slug', $slug)->first($columns);
    }

    /**
     * Get categories with recent posts.
     *
     * @param int $limit
     * @param array $columns
     * @return Collection
     */
    public function withRecentPosts(int $limit = 5, array $columns = ['*']): Collection
    {
        return $this->model->active()
            ->with(['publishedPosts' => function ($q) use ($limit) {
                $q->latest()->limit($limit);
            }])
            ->get($columns);
    }

    /**
     * Reorder categories.
     *
     * @param array $order Array of ['id' => categoryId, 'sort_order' => order]
     * @return bool
     */
    public function reorder(array $order): bool
    {
        foreach ($order as $item) {
            $this->update($item['id'], ['sort_order' => $item['sort_order']]);
        }
        
        // Clear cache after reordering
        $this->clearTreeCache();
        
        return true;
    }

    /**
     * Get category path (ancestors).
     *
     * @param int $categoryId
     * @return array
     */
    public function getPath(int $categoryId): array
    {
        $category = $this->findOrFail($categoryId);
        return $category->path;
    }

    /**
     * Check if category can have a parent (depth check).
     *
     * @param int|null $parentId
     * @return bool
     */
    public function canHaveParent(?int $parentId): bool
    {
        if ($parentId === null) {
            return true;
        }

        $parent = $this->find($parentId);
        if (!$parent) {
            return false;
        }

        $depth = 0;
        $current = $parent;

        while ($current && $current->parent_id !== null) {
            $depth++;
            $current = $this->find($current->parent_id);
            
            if ($depth >= self::MAX_DEPTH - 1) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check for circular reference.
     *
     * @param int $categoryId
     * @param int|null $newParentId
     * @return bool
     */
    public function hasCircularReference(int $categoryId, ?int $newParentId): bool
    {
        if ($newParentId === null) {
            return false;
        }

        // Check if the new parent is a descendant of the category
        $descendants = $this->findDescendants($categoryId);
        return $descendants->contains('id', $newParentId);
    }

    /**
     * Get posts in category including children.
     *
     * @param int $categoryId
     * @return Collection
     */
    public function getPostsIncludingChildren(int $categoryId): Collection
    {
        $category = $this->findOrFail($categoryId);
        $categoryIds = [$categoryId];

        // Get all descendant IDs
        $descendants = $category->descendants();
        $categoryIds = array_merge($categoryIds, $descendants->pluck('id')->toArray());

        return \App\Models\Post::query()
            ->published()
            ->whereIn('category_id', $categoryIds)
            ->latest()
            ->get();
    }

    /**
     * Get paginated posts in category including children.
     *
     * @param int $categoryId
     * @param int $perPage
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function getPaginatedPostsIncludingChildren(int $categoryId, int $perPage = 15): \Illuminate\Pagination\LengthAwarePaginator
    {
        $category = $this->findOrFail($categoryId);
        $categoryIds = [$categoryId];

        // Get all descendant IDs
        $descendants = $category->descendants();
        $categoryIds = array_merge($categoryIds, $descendants->pluck('id')->toArray());

        return \App\Models\Post::query()
            ->published()
            ->whereIn('category_id', $categoryIds)
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Clear the tree cache.
     *
     * @return void
     */
    public function clearTreeCache(): void
    {
        Cache::forget(self::CACHE_PREFIX . 'tree');
    }

    /**
     * Get category depth.
     *
     * @param int $categoryId
     * @return int
     */
    public function getDepth(int $categoryId): int
    {
        $category = $this->findOrFail($categoryId);
        $depth = 0;
        $current = $category;

        while ($current->parent_id !== null) {
            $depth++;
            $current = $this->find($current->parent_id);
        }

        return $depth;
    }
}
