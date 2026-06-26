<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PostResource;
use App\Http\Responses\ApiResponse;
use App\Models\Post;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PostController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $posts = Post::published()
            ->with('category', 'author', 'tags')
            ->orderBy('published_at', 'desc')
            ->paginate($request->get('per_page', 12));

        return ApiResponse::paginated(
            PostResource::collection($posts),
            'Posts retrieved successfully'
        );
    }

    public function show(Post $post): JsonResponse
    {
        if ($post->status !== 'published') {
            return ApiResponse::notFound('Post not found');
        }

        $post->load('category', 'author', 'tags', 'seo');
        $post->increment('views_count');

        return ApiResponse::success(
            new PostResource($post),
            'Post retrieved successfully'
        );
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['exists:tags,id'],
            'status' => ['nullable', 'in:draft,published'],
        ]);

        $validated['author_id'] = auth()->id();
        $validated['slug'] = Post::generateUniqueSlug($validated['title']);

        $post = Post::create($validated);

        if (!empty($validated['tags'])) {
            $post->tags()->sync($validated['tags']);
        }

        return ApiResponse::created(new PostResource($post), 'Post created');
    }

    public function update(Request $request, Post $post): JsonResponse
    {
        if ($post->author_id !== auth()->id() && !auth()->user()->can('edit_all_posts')) {
            return ApiResponse::forbidden('You can only edit your own posts');
        }

        $validated = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'content' => ['sometimes', 'string'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'status' => ['nullable', 'in:draft,published'],
        ]);

        $post->update($validated);

        if ($request->has('tags')) {
            $post->tags()->sync($request->tags);
        }

        return ApiResponse::success(new PostResource($post->fresh()), 'Post updated');
    }

    public function destroy(Post $post): JsonResponse
    {
        if ($post->author_id !== auth()->id() && !auth()->user()->can('edit_all_posts')) {
            return ApiResponse::forbidden('You can only delete your own posts');
        }

        $post->delete();
        return ApiResponse::success(null, 'Post deleted');
    }
}
