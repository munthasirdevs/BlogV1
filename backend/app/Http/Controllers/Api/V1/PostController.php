<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Post\StorePostRequest;
use App\Http\Requests\Post\UpdatePostRequest;
use App\Http\Resources\PostResource;
use App\Models\Post;
use App\Models\Category;
use App\Services\PostService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PostController extends Controller
{
    public function __construct(
        private PostService $postService
    ) {}

    /**
     * List all posts.
     */
    public function index(Request $request): JsonResponse
    {
        $posts = $this->postService->getPaginatedPosts($request->all());

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
     * Get single post.
     */
    public function show(string $slug): JsonResponse
    {
        $post = $this->postService->getPostBySlug($slug);

        if (!$post) {
            return response()->json([
                'success' => false,
                'message' => 'Post not found',
            ], 404);
        }

        // Increment view count
        $this->postService->incrementViews($post);

        return response()->json([
            'success' => true,
            'data' => new PostResource($post->load(['author', 'category', 'tags'])),
        ]);
    }

    /**
     * Create new post.
     */
    public function store(StorePostRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['user_id'] = $request->user()->id;

        // Auto-publish if status is published
        if (($data['status'] ?? 'draft') === 'published' && !isset($data['published_at'])) {
            $data['published_at'] = now();
        }

        $post = $this->postService->createPost($data);

        return response()->json([
            'success' => true,
            'message' => 'Post created successfully',
            'data' => [
                'id' => $post->id,
                'title' => $post->title,
                'slug' => $post->slug,
                'status' => $post->status,
                'created_at' => $post->created_at->toIso8601String(),
            ],
        ], 201);
    }

    /**
     * Update post.
     */
    public function update(UpdatePostRequest $request, Post $post): JsonResponse
    {
        // Check authorization
        if ($request->user()->id !== $post->user_id && !$request->user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to edit this post',
            ], 403);
        }

        $updated = $this->postService->updatePost($post, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Post updated successfully',
            'data' => [
                'id' => $updated->id,
                'title' => $updated->title,
                'slug' => $updated->slug,
                'updated_at' => $updated->updated_at->toIso8601String(),
            ],
        ]);
    }

    /**
     * Delete post.
     */
    public function destroy(Post $post): JsonResponse
    {
        // Check authorization
        if (request()->user()->id !== $post->user_id && !request()->user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to delete this post',
            ], 403);
        }

        $this->postService->deletePost($post);

        return response()->json([
            'success' => true,
            'message' => 'Post deleted successfully',
        ], 204);
    }

    /**
     * Get user's posts.
     */
    public function userPosts(Request $request): JsonResponse
    {
        $posts = $this->postService->getUserPosts($request->user()->id, $request->all());

        return response()->json([
            'success' => true,
            'data' => PostResource::collection($posts),
            'meta' => [
                'current_page' => $posts->currentPage(),
                'per_page' => $posts->perPage(),
                'total' => $posts->total(),
                'total_pages' => $posts->lastPage(),
            ],
        ]);
    }
}
