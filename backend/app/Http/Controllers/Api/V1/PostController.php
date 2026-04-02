<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Post\StorePostRequest;
use App\Http\Requests\Post\UpdatePostRequest;
use App\Http\Requests\Post\PublishPostRequest;
use App\Http\Requests\Post\AutosavePostRequest;
use App\Http\Requests\Post\FeaturePostRequest;
use App\Http\Requests\Post\RestorePostRequest;
use App\Http\Requests\Post\BulkPostsRequest;
use App\Http\Requests\Post\SearchPostsRequest;
use App\Http\Resources\PostResource;
use App\Http\Resources\PostCollection;
use App\Http\Resources\UserResource;
use App\Models\Post;
use App\Models\User;
use App\Models\PostReadingProgress;
use App\Services\PostService;
use App\Helpers\Ability;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

/**
 * Class PostController
 *
 * Controller for managing blog posts.
 * Handles CRUD operations, publishing, featuring, and search.
 *
 * @package App\Http\Controllers\Api\V1
 */
class PostController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(
        private PostService $postService
    ) {}

    /**
     * List all posts with filtering, sorting, and pagination.
     *
     * @param Request $request
     * @return JsonResponse
     *
     * @OA\Get(
     *     path="/api/v1/posts",
     *     summary="List posts",
     *     tags={"Posts"},
     *     @OA\Parameter(name="status", in="query", @OA\Schema(type="string", enum={"draft", "published", "scheduled", "archived"})),
     *     @OA\Parameter(name="category", in="query", @OA\Schema(type="string")),
     *     @OA\Parameter(name="tag", in="query", @OA\Schema(type="string")),
     *     @OA\Parameter(name="author", in="query", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="search", in="query", @OA\Schema(type="string")),
     *     @OA\Parameter(name="featured", in="query", @OA\Schema(type="boolean")),
     *     @OA\Parameter(name="sort", in="query", @OA\Schema(type="string", default="published_at")),
     *     @OA\Parameter(name="order", in="query", @OA\Schema(type="string", enum={"asc", "desc"}, default="desc")),
     *     @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer", default=15)),
     *     @OA\Parameter(name="page", in="query", @OA\Schema(type="integer", default=1)),
     *     @OA\Response(response=200, description="Successful response", @OA\JsonContent(
     *         @OA\Property(property="success", type="boolean", example=true),
     *         @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/PostResource")),
     *         @OA\Property(property="meta", type="object"),
     *         @OA\Property(property="links", type="object")
     *     ))
     * )
     */
    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Post::class);

        $filters = $this->extractFilters($request);
        $perPage = min((int) ($request->get('per_page', 15)), 100);

        // Admins and editors see all posts, others see only published
        $user = $request->user();
        if ($user && Ability::hasAnyRole($user, ['admin', 'editor'])) {
            $posts = $this->postService->getPaginatedPosts($filters, $perPage);
        } elseif ($user) {
            // Authenticated users see published + their own drafts
            $posts = $this->postService->getPublishedPosts($filters, $perPage);
        } else {
            // Guests see only published
            $posts = $this->postService->getPublishedPosts($filters, $perPage);
        }

        return response()->json([
            'success' => true,
            'data' => PostResource::collection($posts),
            'meta' => [
                'current_page' => $posts->currentPage(),
                'per_page' => $posts->perPage(),
                'total' => $posts->total(),
                'total_pages' => $posts->lastPage(),
                'has_more' => $posts->hasMorePages(),
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
     * Get single post by slug or ID.
     *
     * @param Request $request
     * @param string $identifier
     * @return JsonResponse
     *
     * @OA\Get(
     *     path="/api/v1/posts/{identifier}",
     *     summary="Get single post",
     *     tags={"Posts"},
     *     @OA\Parameter(name="identifier", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Successful response", @OA\JsonContent(
     *         @OA\Property(property="success", type="boolean", example=true),
     *         @OA\Property(property="data", ref="#/components/schemas/PostResource")
     *     )),
     *     @OA\Response(response=404, description="Post not found")
     * )
     */
    public function show(Request $request, string $identifier): JsonResponse
    {
        // Try to find by ID first, then by slug
        $post = is_numeric($identifier)
            ? $this->postService->getPostById((int) $identifier)
            : $this->postService->getPostBySlug($identifier);

        if (!$post) {
            return response()->json([
                'success' => false,
                'message' => 'Post not found',
            ], 404);
        }

        // Authorize view
        Gate::authorize('view', $post);

        // Increment view count (for published posts)
        if ($post->isPublished()) {
            $this->postService->incrementViews($post, $request->user(), $request->ip());
        }

        return response()->json([
            'success' => true,
            'data' => new PostResource($post->load(['author', 'category', 'tags'])),
        ]);
    }

    /**
     * Create a new post.
     *
     * @param StorePostRequest $request
     * @return JsonResponse
     *
     * @OA\Post(
     *     path="/api/v1/posts",
     *     summary="Create post",
     *     tags={"Posts"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(ref="#/components/schemas/StorePostRequest"),
     *     @OA\Response(response=201, description="Post created successfully"),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(StorePostRequest $request): JsonResponse
    {
        Gate::authorize('create', Post::class);

        $data = $request->validated();

        // Handle featured image upload
        if ($request->hasFile('featured_image')) {
            $data['featured_image_file'] = $request->file('featured_image');
        }

        $post = $this->postService->createPost($data, $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Post created successfully',
            'data' => new PostResource($post->load(['author', 'category', 'tags'])),
        ], 201);
    }

    /**
     * Update an existing post.
     *
     * @param UpdatePostRequest $request
     * @param Post $post
     * @return JsonResponse
     *
     * @OA\Put(
     *     path="/api/v1/posts/{post}",
     *     summary="Update post",
     *     tags={"Posts"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="post", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(ref="#/components/schemas/UpdatePostRequest"),
     *     @OA\Response(response=200, description="Post updated successfully"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function update(UpdatePostRequest $request, Post $post): JsonResponse
    {
        Gate::authorize('update', $post);

        $data = $request->validated();

        // Handle featured image upload
        if ($request->hasFile('featured_image')) {
            $data['featured_image_file'] = $request->file('featured_image');
        }

        // Handle remove featured image
        if ($request->boolean('remove_featured_image')) {
            $data['remove_featured_image'] = true;
        }

        $updatedPost = $this->postService->updatePost($post, $data, $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Post updated successfully',
            'data' => new PostResource($updatedPost->load(['author', 'category', 'tags'])),
        ]);
    }

    /**
     * Delete a post (soft delete).
     *
     * @param Request $request
     * @param Post $post
     * @return JsonResponse
     *
     * @OA\Delete(
     *     path="/api/v1/posts/{post}",
     *     summary="Delete post",
     *     tags={"Posts"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="post", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=204, description="Post deleted successfully"),
     *     @OA\Response(response=403, description="Forbidden")
     * )
     */
    public function destroy(Request $request, Post $post): JsonResponse
    {
        Gate::authorize('delete', $post);

        $this->postService->deletePost($post, $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Post deleted successfully',
        ], 204);
    }

    /**
     * Publish a post.
     *
     * @param PublishPostRequest $request
     * @param Post $post
     * @return JsonResponse
     *
     * @OA\Post(
     *     path="/api/v1/posts/{post}/publish",
     *     summary="Publish post",
     *     tags={"Posts"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="post", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Post published successfully"),
     *     @OA\Response(response=403, description="Forbidden")
     * )
     */
    public function publish(PublishPostRequest $request, Post $post): JsonResponse
    {
        Gate::authorize('publish', $post);

        $validated = $request->validated();
        $publishedAt = $validated['published_at'] ?? null;

        $publishedPost = $this->postService->publishPost($post, $request->user(), $publishedAt);

        return response()->json([
            'success' => true,
            'message' => 'Post published successfully',
            'data' => new PostResource($publishedPost->load(['author', 'category', 'tags'])),
        ]);
    }

    /**
     * Unpublish a post.
     *
     * @param Request $request
     * @param Post $post
     * @return JsonResponse
     */
    public function unpublish(Request $request, Post $post): JsonResponse
    {
        Gate::authorize('unpublish', $post);

        $unpublishedPost = $this->postService->unpublishPost($post, $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Post unpublished successfully',
            'data' => new PostResource($unpublishedPost->load(['author', 'category', 'tags'])),
        ]);
    }

    /**
     * Auto-save a post draft.
     *
     * @param AutosavePostRequest $request
     * @param Post $post
     * @return JsonResponse
     *
     * @OA\Post(
     *     path="/api/v1/posts/{post}/autosave",
     *     summary="Auto-save post draft",
     *     tags={"Posts"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="post", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         @OA\MediaType(mediaType="application/json",
     *             @OA\Schema(
     *                 @OA\Property(property="title", type="string"),
     *                 @OA\Property(property="content", type="string"),
     *                 @OA\Property(property="excerpt", type="string"),
     *             )
     *         )
     *     ),
     *     @OA\Response(response=200, description="Post auto-saved successfully")
     * )
     */
    public function autosave(AutosavePostRequest $request, Post $post): JsonResponse
    {
        $data = $request->validated();

        $savedPost = $this->postService->autosavePost($post, $data, $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Draft auto-saved successfully',
            'data' => [
                'id' => $savedPost->id,
                'updated_at' => $savedPost->updated_at->toISOString(),
            ],
        ]);
    }

    /**
     * Feature a post.
     *
     * @param FeaturePostRequest $request
     * @param Post $post
     * @return JsonResponse
     *
     * @OA\Post(
     *     path="/api/v1/posts/{post}/feature",
     *     summary="Feature post",
     *     tags={"Posts"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="post", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Post featured successfully"),
     *     @OA\Response(response=403, description="Forbidden")
     * )
     */
    public function feature(FeaturePostRequest $request, Post $post): JsonResponse
    {
        Gate::authorize('feature', $post);

        $featuredPost = $this->postService->featurePost($post, $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Post featured successfully',
            'data' => new PostResource($featuredPost->load(['author', 'category', 'tags'])),
        ]);
    }

    /**
     * Unfeature a post.
     *
     * @param Request $request
     * @param Post $post
     * @return JsonResponse
     */
    public function unfeature(Request $request, Post $post): JsonResponse
    {
        Gate::authorize('unfeature', $post);

        $unfeaturedPost = $this->postService->unfeaturePost($post, $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Post unfeatured successfully',
            'data' => new PostResource($unfeaturedPost->load(['author', 'category', 'tags'])),
        ]);
    }

    /**
     * Restore a deleted post.
     *
     * @param RestorePostRequest $request
     * @param Post $post
     * @return JsonResponse
     *
     * @OA\Post(
     *     path="/api/v1/posts/{post}/restore",
     *     summary="Restore deleted post",
     *     tags={"Posts"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="post", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Post restored successfully"),
     *     @OA\Response(response=403, description="Forbidden")
     * )
     */
    public function restore(RestorePostRequest $request, Post $post): JsonResponse
    {
        Gate::authorize('restore', $post);

        $restoredPost = $this->postService->restorePost($post, $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Post restored successfully',
            'data' => new PostResource($restoredPost->load(['author', 'category', 'tags'])),
        ]);
    }

    /**
     * Get post preview with signed token.
     *
     * @param Request $request
     * @param Post $post
     * @return JsonResponse
     */
    public function preview(Request $request, Post $post): JsonResponse
    {
        // Only author, editors, and admins can generate preview
        if (!$request->user()->can('update', $post)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to preview this post',
            ], 403);
        }

        $token = $this->postService->generatePreviewToken($post, $request->user());

        // Generate preview URL
        $previewUrl = route('api.v1.posts.show', $post->id) . '?preview_token=' . $token;

        return response()->json([
            'success' => true,
            'data' => [
                'preview_url' => $previewUrl,
                'token' => $token,
                'expires_at' => now()->addHours(24)->toISOString(),
            ],
        ]);
    }

    /**
     * Get post author information.
     *
     * @param Post $post
     * @return JsonResponse
     *
     * @OA\Get(
     *     path="/api/v1/posts/{post}/author",
     *     summary="Get post author",
     *     tags={"Posts"},
     *     @OA\Parameter(name="post", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Successful response")
     * )
     */
    public function author(Post $post): JsonResponse
    {
        $authorInfo = $this->postService->getAuthorInfo($post);

        if (empty($authorInfo)) {
            return response()->json([
                'success' => false,
                'message' => 'Author not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $authorInfo,
        ]);
    }

    /**
     * Get related posts.
     *
     * @param Post $post
     * @param Request $request
     * @return JsonResponse
     *
     * @OA\Get(
     *     path="/api/v1/posts/{post}/related",
     *     summary="Get related posts",
     *     tags={"Posts"},
     *     @OA\Parameter(name="post", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="limit", in="query", @OA\Schema(type="integer", default=4)),
     *     @OA\Response(response=200, description="Successful response")
     * )
     */
    public function related(Post $post, Request $request): JsonResponse
    {
        $limit = min((int) ($request->get('limit', 4)), 10);

        $relatedPosts = $this->postService->getRelatedPosts($post, $limit);

        return response()->json([
            'success' => true,
            'data' => PostResource::collection($relatedPosts),
        ]);
    }

    /**
     * Get trending posts.
     *
     * @param Request $request
     * @return JsonResponse
     *
     * @OA\Get(
     *     path="/api/v1/posts/trending",
     *     summary="Get trending posts",
     *     tags={"Posts"},
     *     @OA\Parameter(name="days", in="query", @OA\Schema(type="integer", default=7)),
     *     @OA\Parameter(name="limit", in="query", @OA\Schema(type="integer", default=10)),
     *     @OA\Response(response=200, description="Successful response")
     * )
     */
    public function trending(Request $request): JsonResponse
    {
        $days = min((int) ($request->get('days', 7)), 30);
        $limit = min((int) ($request->get('limit', 10)), 20);

        $trendingPosts = $this->postService->getTrendingPosts($days, $limit);

        return response()->json([
            'success' => true,
            'data' => PostResource::collection($trendingPosts),
        ]);
    }

    /**
     * Get featured posts.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function featured(Request $request): JsonResponse
    {
        $limit = min((int) ($request->get('limit', 5)), 20);

        $featuredPosts = $this->postService->getFeaturedPosts($limit);

        return response()->json([
            'success' => true,
            'data' => PostResource::collection($featuredPosts),
        ]);
    }

    /**
     * Search posts.
     *
     * @param SearchPostsRequest $request
     * @return JsonResponse
     *
     * @OA\Get(
     *     path="/api/v1/posts/search",
     *     summary="Search posts",
     *     tags={"Posts"},
     *     @OA\Parameter(name="q", in="query", required=true, @OA\Schema(type="string")),
     *     @OA\Parameter(name="category", in="query", @OA\Schema(type="string")),
     *     @OA\Parameter(name="tag", in="query", @OA\Schema(type="string")),
     *     @OA\Parameter(name="author", in="query", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="boolean", in="query", @OA\Schema(type="boolean")),
     *     @OA\Response(response=200, description="Successful response")
     * )
     */
    public function search(SearchPostsRequest $request): JsonResponse
    {
        $query = $request->validated()['q'];
        $filters = collect($request->validated())
            ->except('q')
            ->filter()
            ->toArray();

        $perPage = min((int) ($request->get('per_page', 15)), 100);

        $posts = $this->postService->searchPosts($query, $filters, $perPage);

        return response()->json([
            'success' => true,
            'data' => PostResource::collection($posts),
            'meta' => [
                'current_page' => $posts->currentPage(),
                'per_page' => $posts->perPage(),
                'total' => $posts->total(),
                'query' => $query,
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
     * Bulk actions on posts.
     *
     * @param BulkPostsRequest $request
     * @return JsonResponse
     *
     * @OA\Post(
     *     path="/api/v1/posts/bulk-actions",
     *     summary="Bulk actions on posts",
     *     tags={"Posts"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"action", "post_ids"},
     *             @OA\Property(property="action", type="string", enum={"publish", "archive", "delete", "feature", "restore"}),
     *             @OA\Property(property="post_ids", type="array", @OA\Items(type="integer"))
     *         )
     *     ),
     *     @OA\Response(response=200, description="Successful response")
     * )
     */
    public function bulkActions(BulkPostsRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $results = $this->postService->bulkAction(
            $validated['post_ids'],
            $validated['action'],
            $request->user()
        );

        $statusCode = empty($results['failed']) ? 200 : 207;

        return response()->json([
            'success' => true,
            'message' => "Bulk action '{$validated['action']}' completed",
            'data' => [
                'action' => $validated['action'],
                'success_count' => count($results['success']),
                'failed_count' => count($results['failed']),
                'successful' => $results['success'],
                'failed' => $results['failed'],
            ],
        ], $statusCode);
    }

    /**
     * Get user's posts.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function userPosts(Request $request): JsonResponse
    {
        $user = $request->user();
        $filters = $this->extractFilters($request);
        $perPage = min((int) ($request->get('per_page', 15)), 100);

        $posts = $this->postService->getUserPosts($user->id, $filters, $perPage);

        return response()->json([
            'success' => true,
            'data' => PostResource::collection($posts),
            'meta' => [
                'current_page' => $posts->currentPage(),
                'per_page' => $posts->perPage(),
                'total' => $posts->total(),
                'total_pages' => $posts->lastPage(),
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
     * Get posts count by status.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function counts(Request $request): JsonResponse
    {
        $user = $request->user();

        // Admins/editors see all counts, others see only their own
        $userId = Ability::hasAnyRole($user, ['admin', 'editor']) ? null : $user->id;

        $counts = $this->postService->getCountByStatus($userId);

        return response()->json([
            'success' => true,
            'data' => $counts,
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
            ->only(['status', 'category', 'tag', 'author', 'search', 'featured', 'from_date', 'to_date', 'sort', 'order'])
            ->filter()
            ->toArray();
    }

    /**
     * Update reading progress for a post.
     *
     * @param Post $post
     * @param Request $request
     * @return JsonResponse
     */
    public function updateReadingProgress(Post $post, Request $request): JsonResponse
    {
        $request->validate([
            'percentage' => ['required', 'integer', 'min:0', 'max:100'],
            'time_spent' => ['nullable', 'integer', 'min:0'],
        ]);

        $userId = $request->user()->id;
        $percentage = (int) $request->input('percentage');
        $timeSpent = $request->input('time_spent');

        // Get or create progress record
        $progress = PostReadingProgress::getOrCreate($userId, $post->id);

        // Update progress
        $progress->updateProgress($percentage, $timeSpent);

        return response()->json([
            'success' => true,
            'data' => [
                'post_id' => $post->id,
                'percentage' => $progress->percentage,
                'time_spent' => $progress->time_spent,
                'time_spent_formatted' => $progress->time_spent_formatted,
                'is_complete' => $progress->isComplete(),
                'last_read_at' => $progress->last_read_at->toIso8601String(),
            ],
            'message' => 'Reading progress updated successfully',
        ]);
    }

    /**
     * Get user's reading progress for a post.
     *
     * @param Post $post
     * @param Request $request
     * @return JsonResponse
     */
    public function getReadingProgress(Post $post, Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $progress = PostReadingProgress::getUserProgress($userId, $post->id);

        if (!$progress) {
            return response()->json([
                'success' => true,
                'data' => [
                    'post_id' => $post->id,
                    'percentage' => 0,
                    'time_spent' => 0,
                    'is_complete' => false,
                ],
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'post_id' => $post->id,
                'percentage' => $progress->percentage,
                'time_spent' => $progress->time_spent,
                'time_spent_formatted' => $progress->time_spent_formatted,
                'is_complete' => $progress->isComplete(),
                'last_read_at' => $progress->last_read_at->toIso8601String(),
            ],
        ]);
    }

    /**
     * Get user's reading statistics.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getReadingStats(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $stats = PostReadingProgress::getUserStats($userId);

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Get user's reading history.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getReadingHistory(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $perPage = $request->get('per_page', 15);
        $days = $request->get('days', 30);

        $history = PostReadingProgress::byUser($userId)
            ->recent($days)
            ->with(['post' => function ($q) {
                $q->published()->with(['author', 'category']);
            }])
            ->orderBy('last_read_at', 'desc')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $history->getCollection()->map(function ($progress) {
                return [
                    'post' => [
                        'id' => $progress->post->id,
                        'title' => $progress->post->title,
                        'slug' => $progress->post->slug,
                        'excerpt' => $progress->post->excerpt,
                        'featured_image' => $progress->post->featured_image,
                        'reading_time' => $progress->post->reading_time,
                    ],
                    'progress' => [
                        'percentage' => $progress->percentage,
                        'time_spent' => $progress->time_spent,
                        'time_spent_formatted' => $progress->time_spent_formatted,
                        'is_complete' => $progress->isComplete(),
                    ],
                    'last_read_at' => $progress->last_read_at->toIso8601String(),
                ];
            }),
            'meta' => [
                'current_page' => $history->currentPage(),
                'per_page' => $history->perPage(),
                'total' => $history->total(),
                'total_pages' => $history->lastPage(),
            ],
        ]);
    }
}
