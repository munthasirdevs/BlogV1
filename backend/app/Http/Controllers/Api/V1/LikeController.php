<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Interaction\LikeRequest;
use App\Models\Comment;
use App\Models\Post;
use App\Services\LikeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Class LikeController
 *
 * Controller for managing like operations on posts and comments.
 */
class LikeController extends Controller
{
    /**
     * The like service instance.
     *
     * @var LikeService
     */
    protected LikeService $likeService;

    /**
     * LikeController constructor.
     *
     * @param LikeService $likeService
     */
    public function __construct(LikeService $likeService)
    {
        $this->likeService = $likeService;
    }

    /**
     * Toggle like on a post.
     *
     * @param Post $post
     * @param LikeRequest $request
     * @return JsonResponse
     */
    public function togglePostLike(Post $post, LikeRequest $request): JsonResponse
    {
        $userId = $request->user()->id;

        $result = $this->likeService->toggle($userId, $post);

        return response()->json([
            'success' => true,
            'data' => [
                'liked' => $result['liked'],
                'likes_count' => $result['count'],
            ],
            'message' => $result['liked'] ? 'Post liked successfully' : 'Post unliked successfully',
        ]);
    }

    /**
     * Toggle like on a comment.
     *
     * @param Comment $comment
     * @param LikeRequest $request
     * @return JsonResponse
     */
    public function toggleCommentLike(Comment $comment, LikeRequest $request): JsonResponse
    {
        $userId = $request->user()->id;

        $result = $this->likeService->toggle($userId, $comment);

        return response()->json([
            'success' => true,
            'data' => [
                'liked' => $result['liked'],
                'likes_count' => $result['count'],
            ],
            'message' => $result['liked'] ? 'Comment liked successfully' : 'Comment unliked successfully',
        ]);
    }

    /**
     * Get likes for a post.
     *
     * @param Post $post
     * @param Request $request
     * @return JsonResponse
     */
    public function getPostLikes(Post $post, Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 15);

        $likes = $this->likeService->getForModel($post, $perPage);

        return response()->json([
            'success' => true,
            'data' => $likes->getCollection()->map(function ($like) {
                return [
                    'id' => $like->id,
                    'user' => [
                        'id' => $like->user->id,
                        'name' => $like->user->name,
                        'avatar' => $like->user->avatar,
                    ],
                    'liked_at' => $like->created_at->toIso8601String(),
                ];
            }),
            'meta' => [
                'current_page' => $likes->currentPage(),
                'per_page' => $likes->perPage(),
                'total' => $likes->total(),
                'total_pages' => $likes->lastPage(),
            ],
        ]);
    }

    /**
     * Get likes for a comment.
     *
     * @param Comment $comment
     * @param Request $request
     * @return JsonResponse
     */
    public function getCommentLikes(Comment $comment, Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 15);

        $likes = $this->likeService->getForModel($comment, $perPage);

        return response()->json([
            'success' => true,
            'data' => $likes->getCollection()->map(function ($like) {
                return [
                    'id' => $like->id,
                    'user' => [
                        'id' => $like->user->id,
                        'name' => $like->user->name,
                        'avatar' => $like->user->avatar,
                    ],
                    'liked_at' => $like->created_at->toIso8601String(),
                ];
            }),
            'meta' => [
                'current_page' => $likes->currentPage(),
                'per_page' => $likes->perPage(),
                'total' => $likes->total(),
                'total_pages' => $likes->lastPage(),
            ],
        ]);
    }

    /**
     * Get user's liked posts.
     *
     * @param int $userId
     * @param Request $request
     * @return JsonResponse
     */
    public function getUserLikedPosts(int $userId, Request $request): JsonResponse
    {
        // Only allow users to view their own likes
        if ($request->user()->id !== $userId) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $perPage = $request->get('per_page', 15);

        $likedPosts = $this->likeService->getUserLikedPosts($userId, $perPage);

        return response()->json([
            'success' => true,
            'data' => $likedPosts->getCollection()->map(function ($item) {
                return [
                    'id' => $item['id'],
                    'post' => [
                        'id' => $item['likeable']->id,
                        'title' => $item['likeable']->title,
                        'slug' => $item['likeable']->slug,
                        'excerpt' => $item['likeable']->excerpt,
                        'featured_image' => $item['likeable']->featured_image,
                        'reading_time' => $item['likeable']->reading_time,
                        'published_at' => $item['likeable']->published_at?->toIso8601String(),
                        'author' => [
                            'id' => $item['likeable']->author->id,
                            'name' => $item['likeable']->author->name,
                            'avatar' => $item['likeable']->author->avatar,
                        ],
                        'category' => [
                            'id' => $item['likeable']->category->id,
                            'name' => $item['likeable']->category->name,
                            'slug' => $item['likeable']->category->slug,
                        ],
                    ],
                    'liked_at' => $item['liked_at'],
                ];
            }),
            'meta' => [
                'current_page' => $likedPosts->currentPage(),
                'per_page' => $likedPosts->perPage(),
                'total' => $likedPosts->total(),
                'total_pages' => $likedPosts->lastPage(),
            ],
        ]);
    }

    /**
     * Get user's liked comments.
     *
     * @param int $userId
     * @param Request $request
     * @return JsonResponse
     */
    public function getUserLikedComments(int $userId, Request $request): JsonResponse
    {
        // Only allow users to view their own likes or if they have admin permission
        if ($request->user()->id !== $userId && !$request->user()->can('viewAny', Comment::class)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $perPage = $request->get('per_page', 15);

        $likedComments = $this->likeService->getUserLikedComments($userId, $perPage);

        return response()->json([
            'success' => true,
            'data' => $likedComments->getCollection()->map(function ($item) {
                return [
                    'id' => $item['id'],
                    'comment' => [
                        'id' => $item['likeable']->id,
                        'content' => $item['likeable']->content,
                        'excerpt' => substr($item['likeable']->content, 0, 100) . '...',
                        'likes_count' => $item['likeable']->likes_count,
                        'created_at' => $item['likeable']->created_at->toIso8601String(),
                        'post' => [
                            'id' => $item['likeable']->post->id,
                            'title' => $item['likeable']->post->title,
                            'slug' => $item['likeable']->post->slug,
                        ],
                        'author' => [
                            'id' => $item['likeable']->author->id,
                            'name' => $item['likeable']->author->name,
                            'avatar' => $item['likeable']->author->avatar,
                        ],
                    ],
                    'liked_at' => $item['liked_at'],
                ];
            }),
            'meta' => [
                'current_page' => $likedComments->currentPage(),
                'per_page' => $likedComments->perPage(),
                'total' => $likedComments->total(),
                'total_pages' => $likedComments->lastPage(),
            ],
        ]);
    }

    /**
     * Get like status for a post (for current user).
     *
     * @param Post $post
     * @param Request $request
     * @return JsonResponse
     */
    public function getLikeStatus(Post $post, Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $status = $this->likeService->getLikeStatus($userId, $post);

        return response()->json([
            'success' => true,
            'data' => $status,
        ]);
    }

    /**
     * Get top likers for posts.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getTopLikers(Request $request): JsonResponse
    {
        $limit = $request->get('limit', 10);

        $topLikers = $this->likeService->getTopPostLikers($limit);

        return response()->json([
            'success' => true,
            'data' => $topLikers->map(function ($liker) {
                return [
                    'user' => [
                        'id' => $liker->user->id,
                        'name' => $liker->user->name,
                        'avatar' => $liker->user->avatar,
                    ],
                    'like_count' => $liker->like_count,
                ];
            }),
        ]);
    }

    /**
     * Get recent likes across all posts.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getRecentLikes(Request $request): JsonResponse
    {
        $limit = $request->get('limit', 20);

        $recentLikes = $this->likeService->getRecentLikes($limit);

        return response()->json([
            'success' => true,
            'data' => $recentLikes->map(function ($like) {
                return [
                    'id' => $like->id,
                    'user' => [
                        'id' => $like->user->id,
                        'name' => $like->user->name,
                        'avatar' => $like->user->avatar,
                    ],
                    'likeable' => [
                        'type' => class_basename($like->likeable_type),
                        'id' => $like->likeable_id,
                        'title' => $like->likeable instanceof Post ? $like->likeable->title : null,
                    ],
                    'liked_at' => $like->created_at->toIso8601String(),
                ];
            }),
        ]);
    }
}
