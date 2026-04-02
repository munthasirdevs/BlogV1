<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Category\StoreCategoryRequest;
use App\Http\Requests\Category\UpdateCategoryRequest;
use App\Http\Requests\Category\ReorderCategoriesRequest;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\CategoryCollection;
use App\Http\Resources\PostResource;
use App\Models\Category;
use App\Services\CategoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Class CategoryController
 *
 * Controller for managing blog post categories.
 * Handles CRUD operations, hierarchical structure, and post filtering.
 *
 * @package App\Http\Controllers\Api\V1
 */
class CategoryController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(
        private CategoryService $categoryService
    ) {}

    /**
     * List all categories.
     *
     * @param Request $request
     * @return JsonResponse
     *
     * @OA\Get(
     *     path="/api/v1/categories",
     *     summary="List categories",
     *     tags={"Categories"},
     *     @OA\Parameter(name="search", in="query", @OA\Schema(type="string")),
     *     @OA\Parameter(name="parent_id", in="query", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="is_active", in="query", @OA\Schema(type="boolean")),
     *     @OA\Parameter(name="is_featured", in="query", @OA\Schema(type="boolean")),
     *     @OA\Parameter(name="sort", in="query", @OA\Schema(type="string", enum={"name", "sort_order", "created_at"}, default="sort_order")),
     *     @OA\Parameter(name="order", in="query", @OA\Schema(type="string", enum={"asc", "desc"}, default="asc")),
     *     @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer", default=15)),
     *     @OA\Parameter(name="page", in="query", @OA\Schema(type="integer", default=1)),
     *     @OA\Response(response=200, description="Successful response", @OA\JsonContent(
     *         @OA\Property(property="success", type="boolean", example=true),
     *         @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/CategoryResource")),
     *         @OA\Property(property="meta", type="object")
     *     ))
     * )
     */
    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Category::class);

        $filters = $this->extractFilters($request);
        $perPage = min((int) ($request->get('per_page', 15)), 100);

        $categories = $this->categoryService->getPaginatedCategories($filters, $perPage);

        return response()->json([
            'success' => true,
            'data' => CategoryResource::collection($categories),
            'meta' => [
                'current_page' => $categories->currentPage(),
                'per_page' => $categories->perPage(),
                'total' => $categories->total(),
                'total_pages' => $categories->lastPage(),
                'has_more' => $categories->hasMorePages(),
            ],
            'links' => [
                'first' => $categories->url(1),
                'prev' => $categories->previousPageUrl(),
                'next' => $categories->nextPageUrl(),
                'last' => $categories->url($categories->lastPage()),
            ],
        ]);
    }

    /**
     * Get category tree structure.
     *
     * @return JsonResponse
     *
     * @OA\Get(
     *     path="/api/v1/categories/tree",
     *     summary="Get category tree",
     *     tags={"Categories"},
     *     @OA\Parameter(name="max_depth", in="query", @OA\Schema(type="integer", default=3, description="Maximum depth (1-3)")),
     *     @OA\Response(response=200, description="Successful response", @OA\JsonContent(
     *         @OA\Property(property="success", type="boolean", example=true),
     *         @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/CategoryResource"))
     *     ))
     * )
     */
    public function tree(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Category::class);

        $maxDepth = min((int) ($request->get('max_depth', 3)), CategoryRepository::MAX_DEPTH);
        $maxDepth = max(1, $maxDepth);

        $tree = $this->categoryService->getCategoryTree($maxDepth);

        return response()->json([
            'success' => true,
            'data' => CategoryResource::collection($tree),
        ]);
    }

    /**
     * Get single category by slug.
     *
     * @param string $slug
     * @return JsonResponse
     *
     * @OA\Get(
     *     path="/api/v1/categories/{slug}",
     *     summary="Get single category",
     *     tags={"Categories"},
     *     @OA\Parameter(name="slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Successful response", @OA\JsonContent(
     *         @OA\Property(property="success", type="boolean", example=true),
     *         @OA\Property(property="data", ref="#/components/schemas/CategoryResource")
     *     )),
     *     @OA\Response(response=404, description="Category not found")
     * )
     */
    public function show(string $slug): JsonResponse
    {
        $category = $this->categoryService->findBySlug($slug, true);

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Category not found',
            ], 404);
        }

        Gate::authorize('view', $category);

        // Load additional relationships
        $category->load([
            'parent',
            'children' => function ($q) {
                $q->ordered()->withCount(['posts as published_posts_count' => fn($query) => $query->published()]);
            },
        ]);

        // Calculate total posts count including children
        $category->total_posts_count = $this->categoryService->repository()->calculateTotalPostsCount($category);

        return response()->json([
            'success' => true,
            'data' => new CategoryResource($category),
        ]);
    }

    /**
     * Get posts in a category.
     *
     * @param string $slug
     * @param Request $request
     * @return JsonResponse
     *
     * @OA\Get(
     *     path="/api/v1/categories/{slug}/posts",
     *     summary="Get posts by category",
     *     tags={"Categories"},
     *     @OA\Parameter(name="slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Parameter(name="include_children", in="query", @OA\Schema(type="boolean", default=true, description="Include posts from child categories")),
     *     @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer", default=15)),
     *     @OA\Response(response=200, description="Successful response", @OA\JsonContent(
     *         @OA\Property(property="success", type="boolean", example=true),
     *         @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/PostResource")),
     *         @OA\Property(property="meta", type="object")
     *     ))
     * )
     */
    public function posts(string $slug, Request $request): JsonResponse
    {
        $category = $this->categoryService->findBySlug($slug);

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Category not found',
            ], 404);
        }

        $includeChildren = $request->boolean('include_children', true);
        $perPage = min((int) ($request->get('per_page', 15)), 100);

        if ($includeChildren) {
            $posts = $this->categoryService->getPaginatedPostsIncludingChildren($category->id, $perPage);
        } else {
            $posts = $category->publishedPosts()
                ->with(['author', 'category', 'tags'])
                ->latest()
                ->paginate($perPage);
        }

        return response()->json([
            'success' => true,
            'data' => PostResource::collection($posts),
            'meta' => [
                'current_page' => $posts->currentPage(),
                'per_page' => $posts->perPage(),
                'total' => $posts->total(),
                'total_pages' => $posts->lastPage(),
                'category' => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                ],
            ],
            'links' => [
                'first' => $posts->url(1),
                'prev' => $posts->previousPageUrl(),
                'next' => $posts->nextPageUrl(),
                'last' => $posts->url($posts->lastPage()),
            ],
        ]);
    }

    /**
     * Create a new category.
     *
     * @param StoreCategoryRequest $request
     * @return JsonResponse
     *
     * @OA\Post(
     *     path="/api/v1/categories",
     *     summary="Create category",
     *     tags={"Categories"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(ref="#/components/schemas/StoreCategoryRequest"),
     *     @OA\Response(response=201, description="Category created successfully"),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(StoreCategoryRequest $request): JsonResponse
    {
        Gate::authorize('create', Category::class);

        $validated = $request->validated();

        $category = $this->categoryService->createCategory($validated);

        return response()->json([
            'success' => true,
            'message' => 'Category created successfully',
            'data' => new CategoryResource($category->load(['parent', 'children'])),
        ], 201);
    }

    /**
     * Update an existing category.
     *
     * @param UpdateCategoryRequest $request
     * @param Category $category
     * @return JsonResponse
     *
     * @OA\Put(
     *     path="/api/v1/categories/{category}",
     *     summary="Update category",
     *     tags={"Categories"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="category", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(ref="#/components/schemas/UpdateCategoryRequest"),
     *     @OA\Response(response=200, description="Category updated successfully"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function update(UpdateCategoryRequest $request, Category $category): JsonResponse
    {
        Gate::authorize('update', $category);

        $validated = $request->validated();

        $updatedCategory = $this->categoryService->updateCategory($category->id, $validated);

        return response()->json([
            'success' => true,
            'message' => 'Category updated successfully',
            'data' => new CategoryResource($updatedCategory->load(['parent', 'children'])),
        ]);
    }

    /**
     * Reorder categories.
     *
     * @param ReorderCategoriesRequest $request
     * @return JsonResponse
     *
     * @OA\Post(
     *     path="/api/v1/categories/reorder",
     *     summary="Reorder categories",
     *     tags={"Categories"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(ref="#/components/schemas/ReorderCategoriesRequest"),
     *     @OA\Response(response=200, description="Categories reordered successfully"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function reorder(ReorderCategoriesRequest $request): JsonResponse
    {
        Gate::authorize('manage', Category::class);

        $validated = $request->validated();

        $this->categoryService->reorderCategories($validated['categories']);

        return response()->json([
            'success' => true,
            'message' => 'Categories reordered successfully',
        ]);
    }

    /**
     * Delete a category.
     *
     * @param Category $category
     * @return JsonResponse
     *
     * @OA\Delete(
     *     path="/api/v1/categories/{category}",
     *     summary="Delete category",
     *     tags={"Categories"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="category", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=204, description="Category deleted successfully"),
     *     @OA\Response(response=400, description="Cannot delete category with children or posts"),
     *     @OA\Response(response=403, description="Forbidden")
     * )
     */
    public function destroy(Category $category): JsonResponse
    {
        Gate::authorize('delete', $category);

        try {
            $this->categoryService->deleteCategory($category->id);

            return response()->json([
                'success' => true,
                'message' => 'Category deleted successfully',
            ], 204);
        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Delete a category with cascade option.
     *
     * @param Request $request
     * @param Category $category
     * @return JsonResponse
     */
    public function destroyWithCascade(Request $request, Category $category): JsonResponse
    {
        Gate::authorize('delete', $category);

        $cascade = $request->boolean('cascade', false);

        try {
            $this->categoryService->deleteCategoryWithChildren($category->id, $cascade);

            return response()->json([
                'success' => true,
                'message' => $cascade 
                    ? 'Category and its children deleted successfully' 
                    : 'Category deleted successfully (children moved to root level)',
            ], 204);
        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get category statistics.
     *
     * @param Category $category
     * @return JsonResponse
     */
    public function stats(Category $category): JsonResponse
    {
        Gate::authorize('view', $category);

        $stats = $this->categoryService->getCategoryStats($category->id);

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Extract filters from request.
     *
     * @param Request $request
     * @return array
     */
    private function extractFilters(Request $request): array
    {
        return collect($request->all())
            ->only(['search', 'parent_id', 'is_active', 'is_featured', 'sort', 'order', 'include_all'])
            ->filter()
            ->toArray();
    }
}
