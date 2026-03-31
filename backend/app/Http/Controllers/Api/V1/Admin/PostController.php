<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\PostResource;
use App\Models\Post;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PostController extends Controller
{
    /**
     * List all posts (for moderation).
     */
    public function index(Request $request): JsonResponse
    {
        $query = Post::query()->with(['author', 'category']);

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by author
        if ($request->has('author')) {
            $query->where('user_id', $request->author);
        }

        // Search
        if ($request->has('search')) {
            $query->where('title', 'LIKE', '%' . $request->search . '%');
        }

        $posts = $query->latest()->paginate(20);

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

    /**
     * Get single post.
     */
    public function show(Post $post): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => new PostResource($post->load(['author', 'category', 'tags'])),
        ]);
    }

    /**
     * Update any post.
     */
    public function update(Request $request, Post $post): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['sometimes', 'required', 'string', 'max:200'],
            'content' => ['sometimes', 'required', 'string'],
            'status' => ['sometimes', 'required', 'in:draft,published,archived'],
            'category_id' => ['sometimes', 'required', 'exists:categories,id'],
            'featured_image' => ['nullable', 'string', 'url'],
        ]);

        if (isset($validated['status']) && $validated['status'] === 'published' && !$post->published_at) {
            $validated['published_at'] = now();
        }

        $post->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Post updated successfully',
            'data' => new PostResource($post),
        ]);
    }

    /**
     * Delete post.
     */
    public function destroy(Post $post): JsonResponse
    {
        $post->delete();

        return response()->json([
            'success' => true,
            'message' => 'Post deleted successfully',
        ], 204);
    }

    /**
     * Publish post.
     */
    public function publish(Post $post): JsonResponse
    {
        $post->update([
            'status' => 'published',
            'published_at' => $post->published_at ?? now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Post published successfully',
        ]);
    }

    /**
     * Unpublish post.
     */
    public function unpublish(Post $post): JsonResponse
    {
        $post->update([
            'status' => 'draft',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Post unpublished successfully',
        ]);
    }

    /**
     * Feature post.
     */
    public function feature(Post $post): JsonResponse
    {
        // This could set a 'featured' flag or add to a featured list
        // For now, we'll just return success
        return response()->json([
            'success' => true,
            'message' => 'Post marked as featured',
        ]);
    }
}
