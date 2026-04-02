<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Comment\StoreCommentRequest;
use App\Http\Requests\Comment\UpdateCommentRequest;
use App\Http\Requests\Comment\AdminCommentRequest;
use App\Http\Requests\Comment\SearchCommentsRequest;
use App\Http\Resources\CommentResource;
use App\Http\Resources\CommentCollection;
use App\Models\Post;
use App\Models\Comment;
use App\Services\CommentService;
use App\Helpers\Ability;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Class CommentController
 *
 * Handles all comment-related API endpoints.
 *
 * Features:
 * - CRUD operations for comments
 * - Nested replies support
 * - Moderation workflow
 * - Search and filtering
 * - Bulk operations
 * - Rate limiting
 */
class CommentController extends Controller
{
    public function __construct(
        private CommentService $commentService
    ) {}

    /**
     * Get comments for a post.
     *
     * @param Post $post
     * @param Request $request
     * @return JsonResponse
     *
     * @OA\Get(
     *     path="/api/v1/posts/{postId}/comments",
     *     summary="Get post comments",
     *     tags={"Comments"},
     *     @OA\Parameter(name="postId", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="flat", in="query", @OA\Schema(type="boolean")),
     *     @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Successful response")
     * )
     */
    public function index(Post $post, Request $request): JsonResponse
    {
        // Authorize - anyone can view approved comments
        Gate::authorize('viewAny', Comment::class);

        $filters = [
            'approved_only' => $request->boolean('approved_only', true),
            'flat' => $request->boolean('flat', false),
            'per_page' => $request->integer('per_page', 20),
        ];

        $comments = $this->commentService->getPostComments($post, $filters);

        // If flat, return paginated collection
        if ($filters['flat']) {
            return response()->json([
                'success' => true,
                'data' => CommentResource::collection($comments),
                'meta' => [
                    'total' => $comments->total(),
                    'current_page' => $comments->currentPage(),
                    'per_page' => $comments->perPage(),
                    'last_page' => $comments->lastPage(),
                ],
            ]);
        }

        // Return tree structure
        return response()->json([
            'success' => true,
            'data' => CommentResource::collection($comments),
            'meta' => [
                'total' => $comments->count(),
                'post_id' => $post->id,
            ],
        ]);
    }

    /**
     * Get a single comment.
     *
     * @param Comment $comment
     * @return JsonResponse
     *
     * @OA\Get(
     *     path="/api/v1/comments/{id}",
     *     summary="Get single comment",
     *     tags={"Comments"},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Successful response")
     * )
     */
    public function show(Comment $comment): JsonResponse
    {
        // Authorize
        Gate::authorize('view', $comment);

        $comment->load(['author', 'parent', 'replies', 'edits.editor']);

        return response()->json([
            'success' => true,
            'data' => new CommentResource($comment),
        ]);
    }

    /**
     * Create a new comment.
     *
     * @param Post $post
     * @param StoreCommentRequest $request
     * @return JsonResponse
     *
     * @OA\Post(
     *     path="/api/v1/posts/{postId}/comments",
     *     summary="Create comment",
     *     tags={"Comments"},
     *     @OA\Parameter(name="postId", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/StoreCommentRequest")
     *     ),
     *     @OA\Response(response=201, description="Comment created")
     * )
     */
    public function store(Post $post, StoreCommentRequest $request): JsonResponse
    {
        // Authorize create
        Gate::authorize('create', Comment::class);

        // Check rate limit
        $rateLimit = $this->commentService->checkRateLimit($request->user());
        if (!$rateLimit['allowed']) {
            return response()->json([
                'success' => false,
                'message' => 'Rate limit exceeded. Please try again later.',
            ], 429)->header('Retry-After', $rateLimit['retry_after']);
        }

        $data = $request->validated();
        $data['post_id'] = $post->id;
        $data['user_id'] = $request->user()->id;

        $comment = $this->commentService->createComment($data, $request->user());

        return response()->json([
            'success' => true,
            'message' => $comment->isPending()
                ? 'Comment submitted for approval'
                : 'Comment added successfully',
            'data' => new CommentResource($comment->load('author')),
        ], 201);
    }

    /**
     * Update a comment.
     *
     * @param UpdateCommentRequest $request
     * @param Comment $comment
     * @return JsonResponse
     *
     * @OA\Put(
     *     path="/api/v1/comments/{id}",
     *     summary="Update comment",
     *     tags={"Comments"},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/UpdateCommentRequest")
     *     ),
     *     @OA\Response(response=200, description="Comment updated")
     * )
     */
    public function update(UpdateCommentRequest $request, Comment $comment): JsonResponse
    {
        // Authorize using policy
        Gate::authorize('update', $comment);

        // Check if can edit
        $canEdit = $this->commentService->canEditComment($comment, $request->user());
        if (!$canEdit['can_edit']) {
            return response()->json([
                'success' => false,
                'message' => $canEdit['reason'],
            ], 403);
        }

        $data = $request->validated();
        $editReason = $request->getEditReason();

        $comment = $this->commentService->updateComment($comment, $data, $request->user(), $editReason);

        return response()->json([
            'success' => true,
            'message' => 'Comment updated successfully',
            'data' => new CommentResource($comment),
        ]);
    }

    /**
     * Delete a comment.
     *
     * @param Comment $comment
     * @param Request $request
     * @return JsonResponse
     *
     * @OA\Delete(
     *     path="/api/v1/comments/{id}",
     *     summary="Delete comment",
     *     tags={"Comments"},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="cascade", in="query", @OA\Schema(type="boolean")),
     *     @OA\Response(response=204, description="Comment deleted")
     * )
     */
    public function destroy(Comment $comment, Request $request): JsonResponse
    {
        // Authorize using policy
        Gate::authorize('delete', $comment);

        $cascade = $request->boolean('cascade', false);

        $this->commentService->deleteComment($comment, $cascade);

        return response()->json([
            'success' => true,
            'message' => 'Comment deleted successfully',
        ], 204);
    }

    /**
     * Get replies to a comment.
     *
     * @param Comment $comment
     * @param Request $request
     * @return JsonResponse
     *
     * @OA\Get(
     *     path="/api/v1/comments/{id}/replies",
     *     summary="Get comment replies",
     *     tags={"Comments"},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Successful response")
     * )
     */
    public function replies(Comment $comment, Request $request): JsonResponse
    {
        // Authorize
        Gate::authorize('view', $comment);

        $perPage = $request->integer('per_page', 20);
        $approvedOnly = $request->boolean('approved_only', true);

        $replies = $this->commentService->getReplies($comment->id, $approvedOnly, $perPage);

        if ($replies instanceof AnonymousResourceCollection) {
            return response()->json([
                'success' => true,
                'data' => CommentResource::collection($replies),
                'meta' => [
                    'total' => $replies->total(),
                    'current_page' => $replies->currentPage(),
                    'per_page' => $replies->perPage(),
                ],
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => CommentResource::collection($replies),
            'meta' => [
                'total' => $replies->count(),
            ],
        ]);
    }

    /**
     * Approve a comment (Admin/Moderator).
     *
     * @param Comment $comment
     * @param Request $request
     * @return JsonResponse
     *
     * @OA\Post(
     *     path="/api/v1/comments/{id}/approve",
     *     summary="Approve comment",
     *     tags={"Comments - Moderation"},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Comment approved")
     * )
     */
    public function approve(Comment $comment, Request $request): JsonResponse
    {
        // Authorize using policy
        Gate::authorize('approve', $comment);

        $this->commentService->approveComment($comment, $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Comment approved',
        ]);
    }

    /**
     * Reject a comment (Admin/Moderator).
     *
     * @param Comment $comment
     * @param Request $request
     * @return JsonResponse
     *
     * @OA\Post(
     *     path="/api/v1/comments/{id}/reject",
     *     summary="Reject comment",
     *     tags={"Comments - Moderation"},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="reason", type="string")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Comment rejected")
     * )
     */
    public function reject(Comment $comment, Request $request): JsonResponse
    {
        // Authorize using policy
        Gate::authorize('reject', $comment);

        $reason = $request->input('reason');

        $this->commentService->rejectComment($comment, $request->user(), $reason);

        return response()->json([
            'success' => true,
            'message' => 'Comment rejected',
        ]);
    }

    /**
     * Mark a comment as spam (Admin/Moderator).
     *
     * @param Comment $comment
     * @param Request $request
     * @return JsonResponse
     *
     * @OA\Post(
     *     path="/api/v1/comments/{id}/spam",
     *     summary="Mark comment as spam",
     *     tags={"Comments - Moderation"},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Comment marked as spam")
     * )
     */
    public function markAsSpam(Comment $comment, Request $request): JsonResponse
    {
        // Authorize using policy
        Gate::authorize('reject', $comment);

        $this->commentService->markCommentAsSpam($comment, $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Comment marked as spam',
        ]);
    }

    /**
     * Get pending comments for moderation.
     *
     * @param Request $request
     * @return JsonResponse
     *
     * @OA\Get(
     *     path="/api/v1/comments/pending",
     *     summary="Get pending comments",
     *     tags={"Comments - Moderation"},
     *     @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Successful response")
     * )
     */
    public function pending(Request $request): JsonResponse
    {
        $user = $request->user();

        // Authorize using policy
        Gate::authorize('moderate', $user);

        $perPage = $request->integer('per_page', 20);

        $comments = $this->commentService->getPendingComments($perPage);

        return response()->json([
            'success' => true,
            'data' => CommentResource::collection($comments),
            'meta' => [
                'current_page' => $comments->currentPage(),
                'per_page' => $comments->perPage(),
                'total' => $comments->total(),
                'last_page' => $comments->lastPage(),
            ],
        ]);
    }

    /**
     * Search comments (Admin/Moderator).
     *
     * @param SearchCommentsRequest $request
     * @return JsonResponse
     *
     * @OA\Get(
     *     path="/api/v1/admin/comments/search",
     *     summary="Search comments",
     *     tags={"Comments - Admin"},
     *     @OA\Parameter(name="search", in="query", @OA\Schema(type="string")),
     *     @OA\Parameter(name="status", in="query", @OA\Schema(type="string")),
     *     @OA\Parameter(name="post_id", in="query", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="user_id", in="query", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="from_date", in="query", @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="to_date", in="query", @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="sort", in="query", @OA\Schema(type="string")),
     *     @OA\Parameter(name="order", in="query", @OA\Schema(type="string")),
     *     @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Successful response")
     * )
     */
    public function search(SearchCommentsRequest $request): JsonResponse
    {
        $filters = $request->getFilters();
        $filters['sort'] = $request->getSortField();
        $filters['order'] = $request->getSortOrder();
        $perPage = $request->getPerPage();

        $comments = $this->commentService->searchComments($filters, $perPage);

        return response()->json([
            'success' => true,
            'data' => CommentResource::collection($comments),
            'meta' => [
                'current_page' => $comments->currentPage(),
                'per_page' => $comments->perPage(),
                'total' => $comments->total(),
                'last_page' => $comments->lastPage(),
            ],
        ]);
    }

    /**
     * Bulk moderate comments (Admin/Moderator).
     *
     * @param AdminCommentRequest $request
     * @return JsonResponse
     *
     * @OA\Post(
     *     path="/api/v1/admin/comments/bulk-moderate",
     *     summary="Bulk moderate comments",
     *     tags={"Comments - Admin"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/BulkModerateRequest")
     *     ),
     *     @OA\Response(response=200, description="Successful response")
     * )
     */
    public function bulkModerate(AdminCommentRequest $request): JsonResponse
    {
        $commentIds = $request->getCommentIds();
        $action = $request->getAction();
        $reason = $request->getReason();

        $results = $this->commentService->bulkModerate(
            $commentIds,
            $action,
            $request->user(),
            $reason
        );

        $successCount = count(array_filter($results, fn($r) => $r['success']));
        $failCount = count($results) - $successCount;

        return response()->json([
            'success' => true,
            'message' => "Processed {$successCount} successful, {$failCount} failed",
            'data' => [
                'results' => $results,
                'summary' => [
                    'total' => count($results),
                    'successful' => $successCount,
                    'failed' => $failCount,
                ],
            ],
        ]);
    }

    /**
     * Get comment statistics (Admin/Moderator).
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function statistics(Request $request): JsonResponse
    {
        $user = $request->user();

        // Authorize
        Gate::authorize('moderate', $user);

        $stats = $this->commentService->getStatistics();

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Get mention suggestions.
     *
     * @param Request $request
     * @return JsonResponse
     *
     * @OA\Get(
     *     path="/api/v1/comments/mentions/suggest",
     *     summary="Get mention suggestions",
     *     tags={"Comments"},
     *     @OA\Parameter(name="q", in="query", required=true, @OA\Schema(type="string")),
     *     @OA\Parameter(name="limit", in="query", @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Successful response")
     * )
     */
    public function mentionSuggestions(Request $request): JsonResponse
    {
        $query = $request->input('q', '');
        $limit = $request->integer('limit', 5);

        if (strlen($query) < 2) {
            return response()->json([
                'success' => true,
                'data' => [],
            ]);
        }

        $users = $this->commentService->getMentionSuggestions($query, $limit);

        return response()->json([
            'success' => true,
            'data' => $users->map(fn($user) => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'avatar' => $user->avatar,
            ]),
        ]);
    }

    /**
     * Get edit history for a comment.
     *
     * @param Comment $comment
     * @return JsonResponse
     *
     * @OA\Get(
     *     path="/api/v1/comments/{id}/edits",
     *     summary="Get comment edit history",
     *     tags={"Comments"},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Successful response")
     * )
     */
    public function editHistory(Comment $comment): JsonResponse
    {
        // Authorize - only author and moderators can view edit history
        if (!auth()->check() ||
            (!auth()->user()->hasRole(['admin', 'moderator', 'editor']) &&
             auth()->user()->id !== $comment->user_id)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $edits = $this->commentService->getEditHistory($comment);

        return response()->json([
            'success' => true,
            'data' => $edits->map(fn($edit) => [
                'id' => $edit->id,
                'old_content' => $edit->old_content,
                'new_content' => $edit->new_content,
                'edit_reason' => $edit->edit_reason,
                'editor' => [
                    'id' => $edit->editor->id,
                    'name' => $edit->editor->name,
                ],
                'edited_at' => $edit->created_at->toISOString(),
            ]),
        ]);
    }
}
