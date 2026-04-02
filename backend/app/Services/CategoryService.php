<?php

namespace App\Services;

use App\Repositories\CategoryRepository;
use App\Models\Category;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;

/**
 * Class CategoryService
 *
 * Service class for category-related operations.
 * Handles category CRUD, tree building, and post counting.
 */
class CategoryService extends BaseService
{
    /**
     * Initialize the repository.
     */
    protected function initializeRepository(): void
    {
        $this->repository = new CategoryRepository();
    }

    /**
     * Get the repository instance.
     *
     * @return CategoryRepository
     */
    public function repository(): CategoryRepository
    {
        return $this->repository;
    }

    /**
     * Get all categories as a tree structure with caching.
     *
     * @param int $maxDepth
     * @return \Illuminate\Support\Collection
     */
    public function getCategoryTree(int $maxDepth = CategoryRepository::MAX_DEPTH): \Illuminate\Support\Collection
    {
        return $this->repository->getTree($maxDepth);
    }

    /**
     * Get all categories as a flat list with depth.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getFlatList(): \Illuminate\Support\Collection
    {
        return $this->repository->getFlatList();
    }

    /**
     * Get paginated categories.
     *
     * @param array $filters
     * @param int $perPage
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function getPaginatedCategories(array $filters = [], int $perPage = 15): \Illuminate\Pagination\LengthAwarePaginator
    {
        $query = Category::query();

        // Search filter
        if (isset($filters['search'])) {
            $query->search($filters['search']);
        }

        // Active filter
        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        // Featured filter
        if (isset($filters['is_featured'])) {
            $query->where('is_featured', $filters['is_featured']);
        }

        // Parent filter
        if (isset($filters['parent_id'])) {
            $query->where('parent_id', $filters['parent_id']);
        } elseif (!isset($filters['include_all'])) {
            // Default to top-level categories only
            $query->whereNull('parent_id');
        }

        // Sorting
        $sortField = $filters['sort'] ?? 'sort_order';
        $sortOrder = ($filters['order'] ?? 'asc') === 'asc' ? 'asc' : 'desc';

        $allowedSorts = ['name', 'sort_order', 'created_at', 'updated_at'];
        if (in_array($sortField, $allowedSorts)) {
            $query->orderBy($sortField, $sortOrder);
        } else {
            $query->ordered();
        }

        return $query->paginate($perPage);
    }

    /**
     * Create a new category.
     *
     * @param array $data
     * @return Category
     */
    public function createCategory(array $data): Category
    {
        return DB::transaction(function () use ($data) {
            // Auto-generate slug if not provided
            if (empty($data['slug'])) {
                $data['slug'] = Str::slug($data['name']);
            }

            // Ensure slug is unique
            $data['slug'] = $this->generateUniqueSlug($data['slug']);

            // Set default sort order if not provided
            if (!isset($data['sort_order'])) {
                $data['sort_order'] = Category::max('sort_order') + 1;
            }

            // Set default color if not provided
            if (empty($data['color'])) {
                $data['color'] = '#6B7280';
            }

            // Set default active status if not provided
            if (!isset($data['is_active'])) {
                $data['is_active'] = true;
            }

            $category = $this->repository->create($data);
            
            // Clear tree cache
            $this->repository->clearTreeCache();
            
            return $category;
        });
    }

    /**
     * Update a category.
     *
     * @param int $id
     * @param array $data
     * @return Category
     */
    public function updateCategory(int $id, array $data): Category
    {
        return DB::transaction(function () use ($id, $data) {
            $category = $this->findOrFail($id);

            // Auto-generate slug if name changed and slug wasn't manually set
            if (isset($data['name']) && $category->getOriginal('slug') === Str::slug($category->getOriginal('name'))) {
                $data['slug'] = Str::slug($data['name']);
            }

            // Ensure slug is unique (excluding current category)
            if (isset($data['slug'])) {
                $data['slug'] = $this->generateUniqueSlug($data['slug'], $id);
            }

            $updated = $this->repository->update($id, $data);
            
            // Clear tree cache
            $this->repository->clearTreeCache();
            
            return $updated;
        });
    }

    /**
     * Get category by slug.
     *
     * @param string $slug
     * @param bool $includePosts
     * @return Category|null
     */
    public function findBySlug(string $slug, bool $includePosts = false): ?Category
    {
        $category = $this->repository->findBySlug($slug);

        if ($category && $includePosts) {
            $category->load(['publishedPosts' => function ($q) {
                $q->latest()->limit(10);
            }]);
        }

        return $category;
    }

    /**
     * Get category with posts.
     *
     * @param int $id
     * @return Category|null
     */
    public function getCategoryWithPosts(int $id): ?Category
    {
        $category = $this->find($id);

        if ($category) {
            $category->load([
                'publishedPosts' => function ($q) {
                    $q->latest()->limit(10);
                },
                'children' => function ($q) {
                    $q->ordered();
                },
            ]);
        }

        return $category;
    }

    /**
     * Get active categories.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getActiveCategories(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->repository->findActive();
    }

    /**
     * Get featured categories.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getFeaturedCategories(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->repository->findFeatured();
    }

    /**
     * Get top-level categories.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getTopLevelCategories(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->repository->findTopLevel();
    }

    /**
     * Get children of a category.
     *
     * @param int $parentId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getChildren(int $parentId): \Illuminate\Database\Eloquent\Collection
    {
        return $this->repository->findChildren($parentId);
    }

    /**
     * Get all descendants of a category.
     *
     * @param int $categoryId
     * @return \Illuminate\Support\Collection
     */
    public function getDescendants(int $categoryId): \Illuminate\Support\Collection
    {
        return $this->repository->findDescendants($categoryId);
    }

    /**
     * Get category path (ancestors).
     *
     * @param int $categoryId
     * @return array
     */
    public function getCategoryPath(int $categoryId): array
    {
        return $this->repository->getPath($categoryId);
    }

    /**
     * Get categories with post count.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getCategoriesWithPostCount(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->repository->withPostsCount();
    }

    /**
     * Search categories.
     *
     * @param string $search
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function searchCategories(string $search): \Illuminate\Database\Eloquent\Collection
    {
        return $this->repository->search($search);
    }

    /**
     * Reorder categories.
     *
     * @param array $order Array of ['id' => categoryId, 'sort_order' => order]
     * @return bool
     */
    public function reorderCategories(array $order): bool
    {
        return $this->repository->reorder($order);
    }

    /**
     * Delete a category.
     *
     * @param int $id
     * @return bool|null
     */
    public function deleteCategory(int $id): ?bool
    {
        $category = $this->findOrFail($id);

        // Check if category has children
        if ($category->hasChildren()) {
            throw new \RuntimeException('Cannot delete a category with child categories. Please delete or move the children first.');
        }

        // Check if category has posts
        if ($category->posts()->count() > 0) {
            throw new \RuntimeException('Cannot delete a category with posts. Please reassign or delete the posts first.');
        }

        $result = $this->delete($id);
        
        // Clear tree cache
        if ($result) {
            $this->repository->clearTreeCache();
        }
        
        return $result;
    }

    /**
     * Move category to a new parent.
     *
     * @param int $categoryId
     * @param int|null $newParentId
     * @return Category
     */
    public function moveCategory(int $categoryId, ?int $newParentId): Category
    {
        // Prevent circular reference
        if ($newParentId !== null) {
            if ($this->repository->hasCircularReference($categoryId, $newParentId)) {
                throw new \RuntimeException('Cannot move category to one of its descendants.');
            }
            
            // Check depth limit
            if (!$this->repository->canHaveParent($newParentId)) {
                throw new \RuntimeException('Cannot add more than ' . CategoryRepository::MAX_DEPTH . ' levels of category nesting.');
            }
        }

        $category = $this->update($categoryId, ['parent_id' => $newParentId]);
        
        // Clear tree cache
        $this->repository->clearTreeCache();
        
        return $category;
    }

    /**
     * Get categories with recent posts.
     *
     * @param int $postsLimit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getCategoriesWithRecentPosts(int $postsLimit = 5): \Illuminate\Database\Eloquent\Collection
    {
        return $this->repository->withRecentPosts($postsLimit);
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
        $query = Category::where('slug', $slug);

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    /**
     * Get category statistics.
     *
     * @param int $id
     * @return array
     */
    public function getCategoryStats(int $id): array
    {
        $category = $this->findOrFail($id);

        return [
            'posts_count' => $category->posts()->count(),
            'published_posts_count' => $category->publishedPosts()->count(),
            'children_count' => $category->children()->count(),
            'descendants_count' => $category->descendants()->count(),
        ];
    }

    /**
     * Get posts in category including children.
     *
     * @param int $categoryId
     * @return \Illuminate\Support\Collection
     */
    public function getPostsIncludingChildren(int $categoryId): \Illuminate\Support\Collection
    {
        return $this->repository->getPostsIncludingChildren($categoryId);
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
        return $this->repository->getPaginatedPostsIncludingChildren($categoryId, $perPage);
    }

    /**
     * Check if category can have a parent.
     *
     * @param int|null $parentId
     * @return bool
     */
    public function canHaveParent(?int $parentId): bool
    {
        return $this->repository->canHaveParent($parentId);
    }

    /**
     * Get category depth.
     *
     * @param int $categoryId
     * @return int
     */
    public function getCategoryDepth(int $categoryId): int
    {
        return $this->repository->getDepth($categoryId);
    }

    /**
     * Soft delete category and handle children.
     * Option 1: Children become root level (default)
     * Option 2: Cascade delete children
     *
     * @param int $id
     * @param bool $cascadeDelete
     * @return bool|null
     */
    public function deleteCategoryWithChildren(int $id, bool $cascadeDelete = false): ?bool
    {
        return DB::transaction(function () use ($id, $cascadeDelete) {
            $category = $this->findOrFail($id);

            if ($cascadeDelete) {
                // Delete all descendants first
                $descendants = $category->descendants();
                foreach ($descendants as $descendant) {
                    $this->delete($descendant->id);
                }
            } else {
                // Make children root level
                $category->children()->update(['parent_id' => null]);
            }

            $result = $this->delete($id);
            
            // Clear tree cache
            if ($result) {
                $this->repository->clearTreeCache();
            }
            
            return $result;
        });
    }
}
