<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tag\StoreTagRequest;
use App\Http\Requests\Tag\UpdateTagRequest;
use App\Http\Requests\Tag\AttachTagsToPostRequest;
use App\Http\Requests\Tag\DetachTagFromPostRequest;
use App\Http\Resources\TagResource;
use App\Http\Resources\PostResource;
use App\Models\Tag;
use App\Models\Post;
use App\Services\TagService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Class TagController
 *
 * Controller for managing blog post tags.
 * Handles CRUD operations, suggestions, and post associations.
 *
 * @package App\Http\Controllers\Api\V1
 */
class TagController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(
        private TagService $tagService
    ) {}

    /**
     * List all tags.
     *
     * @param Request $request
     * @return JsonResponse
     *
     * @OA\Get(
     *     path="/api/v1/tags",
     *     summary="List tags",
     *     tags={"Tags"},
     *     @OA\Parameter(name="search", in="query", @OA\Schema(type="string")),
     *     @OA\Parameter(name="is_featured", in="query", @OA\Schema(type="boolean")),
     *     @OA\Parameter(name="sort", in="query", @OA\Schema(type="string", enum={"name", "created_at", "posts_count"}, default="name")),
     *     @OA\Parameter(name="order", in="query", @OA\Schema(type="string", enum={"asc", "desc"}, default="asc")),
     *     @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer", default=15)),
     *     @OA\Parameter(name="page", in="query", @OA\Schema(type="integer", default=1)),
     *     @OA\Response(response=200, description="Successful response", @OA\JsonContent(
     *         @OA\Property(property="success", type="boolean", example=true),
     *         @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/TagResource")),
     *         @OA\Property(property="meta", type="object")
     *     ))
     * )
     */
    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Tag::class);

        $filters = $this->extractFilters($request);
        $perPage = min((int) ($request->get('per_page', 15)), 100);

        $tags = $this->tagService->getPaginatedTags($filters, $perPage);

        return response()->json([
            'success' => true,
            'data' => TagResource::collection($tags),
            'meta' => [
                'current_page' => $tags->currentPage(),
                'per_page' => $tags->perPage(),
                'total' => $tags->total(),
                'total_pages' => $tags->lastPage(),
                'has_more' => $tags->hasMorePages(),
            ],
            'links' => [
                'first' => $tags->url(1),
                'prev' => $tags->previousPageUrl(),
                'next' => $tags->nextPageUrl(),
                'last' => $tags->url($tags->lastPage()),
            ],
        ]);
    }

    /**
     * Get popular tags.
     *
     * @param Request $request
     * @return JsonResponse
     *
     * @OA\Get(
     *     path="/api/v1/tags/popular",
     *     summary="Get popular tags",
     *     tags={"Tags"},
     *     @OA\Parameter(name="limit", in="query", @OA\Schema(type="integer", default=20, description="Number of tags to return (max 50)")),
     *     @OA\Response(response=200, description="Successful response", @OA\JsonContent(
     *         @OA\Property(property="success", type="boolean", example=true),
     *         @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/TagResource"))
     *     ))
     * )
     */
    public function popular(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Tag::class);

        $limit = min((int) ($request->get('limit', 20)), 50);

        $tags = $this->tagService->getPopularTags($limit);

        return response()->json([
            'success' => true,
            'data' => TagResource::collection($tags),
        ]);
    }

    /**
     * Get tag suggestions.
     *
     * @param Request $request
     * @return JsonResponse
     *
     * @OA\Get(
     *     path="/api/v1/tags/suggest",
     *     summary="Get tag suggestions",
     *     tags={"Tags"},
     *     @OA\Parameter(name="q", in="query", required=true, @OA\Schema(type="string", description="Search query")),
     *     @OA\Parameter(name="limit", in="query", @OA\Schema(type="integer", default=10, description="Number of suggestions (max 20)")),
     *     @OA\Response(response=200, description="Successful response", @OA\JsonContent(
     *         @OA\Property(property="success", type="boolean", example=true),
     *         @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/TagResource"))
     *     ))
     * )
     */
    public function suggest(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Tag::class);

        $query = $request->get('q', '');
        
        if (empty(trim($query))) {
            return response()->json([
                'success' => true,
                'data' => [],
            ]);
        }

        $limit = min((int) ($request->get('limit', 10)), 20);

        $tags = $this->tagService->getTagSuggestions($query, $limit);

        return response()->json([
            'success' => true,
            'data' => TagResource::collection($tags),
        ]);
    }

    /**
     * Get single tag by slug.
     *
     * @param string $slug
     * @return JsonResponse
     *
     * @OA\Get(
     *     path="/api/v1/tags/{slug}",
     *     summary="Get single tag",
     *     tags={"Tags"},
     *     @OA\Parameter(name="slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Successful response", @OA\JsonContent(
     *         @OA\Property(property="success", type="boolean", example=true),
     *         @OA\Property(property="data", ref="#/components/schemas/TagResource")
     *     )),
     *     @OA\Response(response=404, description="Tag not found")
     * )
     */
    public function show(string $slug): JsonResponse
    {
        $tag = $this->tagService->findBySlug($slug, true);

        if (!$tag) {
            return response()->json([
                'success' => false,
                'message' => 'Tag not found',
            ], 404);
        }

        Gate::authorize('view', $tag);

        return response()->json([
            'success' => true,
            'data' => new TagResource($tag),
        ]);
    }

    /**
     * Get posts with a tag.
     *
     * @param string $slug
     * @param Request $request
     * @return JsonResponse
     *
     * @OA\Get(
     *     path="/api/v1/tags/{slug}/posts",
     *     summary="Get posts by tag",
     *     tags={"Tags"},
     *     @OA\Parameter(name="slug", in="path", required=true, @OA\Schema(type="string")),
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
        $tag = $this->tagService->findBySlug($slug);

        if (!$tag) {
            return response()->json([
                'success' => false,
                'message' => 'Tag not found',
            ], 404);
        }

        $perPage = min((int) ($request->get('per_page', 15)), 100);

        $posts = $this->tagService->getPostsByTagSlug($slug, $perPage);

        return response()->json([
            'success' => true,
            'data' => PostResource::collection($posts),
            'meta' => [
                'current_page' => $posts->currentPage(),
                'per_page' => $posts->perPage(),
                'total' => $posts->total(),
                'total_pages' => $posts->lastPage(),
                'tag' => [
                    'id' => $tag->id,
                    'name' => $tag->name,
                    'slug' => $tag->slug,
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
     * Create a new tag.
     *
     * @param StoreTagRequest $request
     * @return JsonResponse
     *
     * @OA\Post(
     *     path="/api/v1/tags",
     *     summary="Create tag",
     *     tags={"Tags"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(ref="#/components/schemas/StoreTagRequest"),
     *     @OA\Response(response=201, description="Tag created successfully"),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(StoreTagRequest $request): JsonResponse
    {
        Gate::authorize('create', Tag::class);

        $validated = $request->validated();

        $tag = $this->tagService->createTag($validated);

        return response()->json([
            'success' => true,
            'message' => 'Tag created successfully',
            'data' => new TagResource($tag),
        ], 201);
    }

    /**
     * Update an existing tag.
     *
     * @param UpdateTagRequest $request
     * @param Tag $tag
     * @return JsonResponse
     *
     * @OA\Put(
     *     path="/api/v1/tags/{tag}",
     *     summary="Update tag",
     *     tags={"Tags"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="tag", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(ref="#/components/schemas/UpdateTagRequest"),
     *     @OA\Response(response=200, description="Tag updated successfully"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function update(UpdateTagRequest $request, Tag $tag): JsonResponse
    {
        Gate::authorize('update', $tag);

        $validated = $request->validated();

        $updatedTag = $this->tagService->updateTag($tag->id, $validated);

        return response()->json([
            'success' => true,
            'message' => 'Tag updated successfully',
            'data' => new TagResource($updatedTag),
        ]);
    }

    /**
     * Delete a tag.
     *
     * @param Tag $tag
     * @return JsonResponse
     *
     * @OA\Delete(
     *     path="/api/v1/tags/{tag}",
     *     summary="Delete tag",
     *     tags={"Tags"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="tag", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=204, description="Tag deleted successfully"),
     *     @OA\Response(response=400, description="Cannot delete tag attached to posts"),
     *     @OA\Response(response=403, description="Forbidden")
     * )
     */
    public function destroy(Tag $tag): JsonResponse
    {
        Gate::authorize('delete', $tag);

        try {
            $this->tagService->deleteTag($tag->id);

            return response()->json([
                'success' => true,
                'message' => 'Tag deleted successfully',
            ], 204);
        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Attach tags to a post.
     *
     * @param AttachTagsToPostRequest $request
     * @param Post $post
     * @return JsonResponse
     *
     * @OA\Post(
     *     path="/api/v1/posts/{postId}/tags",
     *     summary="Attach tags to post",
     *     tags={"Tags", "Posts"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="postId", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(ref="#/components/schemas/AttachTagsToPostRequest"),
     *     @OA\Response(response=200, description="Tags attached successfully"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function attachToPost(AttachTagsToPostRequest $request, Post $post): JsonResponse
    {
        Gate::authorize('update', $post);

        $validated = $request->validated();
        $createIfNotExist = $validated['create_if_not_exist'] ?? false;

        $attachedIds = $this->tagService->syncTagsForPost(
            $post->id,
            $validated['tags'],
            $createIfNotExist
        );

        // Load attached tags
        $post->load('tags');

        return response()->json([
            'success' => true,
            'message' => 'Tags attached successfully',
            'data' => [
                'post_id' => $post->id,
                'tags' => TagResource::collection($post->tags),
                'attached_count' => count($attachedIds),
            ],
        ]);
    }

    /**
     * Detach a tag from a post.
     *
     * @param DetachTagFromPostRequest $request
     * @param Post $post
     * @param Tag $tag
     * @return JsonResponse
     *
     * @OA\Delete(
     *     path="/api/v1/posts/{postId}/tags/{tagId}",
     *     summary="Detach tag from post",
     *     tags={"Tags", "Posts"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="postId", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="tagId", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Tag detached successfully"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="Tag not attached to post")
     * )
     */
    public function detachFromPost(DetachTagFromPostRequest $request, Post $post, Tag $tag): JsonResponse
    {
        Gate::authorize('update', $post);

        // Check if tag is attached to post
        if (!$post->tags()->where('tag_id', $tag->id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Tag is not attached to this post',
            ], 404);
        }

        $result = $this->tagService->detachTagFromPost($post->id, $tag->id);

        return response()->json([
            'success' => true,
            'message' => 'Tag detached successfully',
            'data' => [
                'post_id' => $post->id,
                'tag_id' => $tag->id,
            ],
        ]);
    }

    /**
     * Get tag statistics.
     *
     * @param Tag $tag
     * @return JsonResponse
     */
    public function stats(Tag $tag): JsonResponse
    {
        Gate::authorize('view', $tag);

        $stats = $this->tagService->getTagStats($tag->id);

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Get tags as a cloud.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function cloud(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Tag::class);

        $limit = min((int) ($request->get('limit', 20)), 50);

        $tags = $this->tagService->getTagCloud($limit);

        return response()->json([
            'success' => true,
            'data' => TagResource::collection($tags),
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
            ->only(['search', 'is_featured', 'sort', 'order'])
            ->filter()
            ->toArray();
    }
}
