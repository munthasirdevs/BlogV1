<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Comment\StoreCommentRequest;
use App\Http\Requests\Comment\UpdateCommentRequest;
use App\Http\Resources\CommentResource;
use App\Models\Post;
use App\Models\Comment;
use App\Services\CommentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function __construct(
        private CommentService $commentService
    ) {}

    /**
     * Get post comments.
     */
    public function index(Post $post, Request $request): JsonResponse
    {
        $comments = $this->commentService->getPostComments($post, $request->all());

        return response()->json([
            'success' => true,
            'data' => CommentResource::collection($comments),
            'meta' => [
                'total' => $comments->total(),
                'current_page' => $comments->currentPage(),
                'per_page' => $comments->perPage(),
            ],
        ]);
    }

    /**
     * Create comment.
     */
    public function store(Post $post, StoreCommentRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['post_id'] = $post->id;
        $data['user_id'] = $request->user()->id;

        // Auto-approve if user is trusted (admin or has many approved comments)
        $user = $request->user();
        if ($user->isAdmin() || $user->comments()->approved()->count() >= 5) {
            $data['status'] = 'approved';
        } else {
            $data['status'] = 'pending';
        }

        $comment = $this->commentService->createComment($data);

        return response()->json([
            'success' => true,
            'message' => 'Comment added successfully',
            'data' => new CommentResource($comment->load('author')),
        ], 201);
    }

    /**
     * Update comment.
     */
    public function update(UpdateCommentRequest $request, Comment $comment): JsonResponse
    {
        // Check authorization
        if ($request->user()->id !== $comment->user_id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to edit this comment',
            ], 403);
        }

        // Check if within 24 hours
        if ($comment->created_at->diffInHours(now()) > 24) {
            return response()->json([
                'success' => false,
                'message' => 'Comments can only be edited within 24 hours',
            ], 403);
        }

        $data = $request->validated();
        $data['is_edited'] = true;

        $this->commentService->updateComment($comment, $data);

        return response()->json([
            'success' => true,
            'message' => 'Comment updated successfully',
        ]);
    }

    /**
     * Delete comment.
     */
    public function destroy(Comment $comment): JsonResponse
    {
        $user = request()->user();

        // Check authorization
        if ($user->id !== $comment->user_id && !$user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to delete this comment',
            ], 403);
        }

        $this->commentService->deleteComment($comment);

        return response()->json([
            'success' => true,
            'message' => 'Comment deleted successfully',
        ], 204);
    }

    /**
     * Approve comment (Admin).
     */
    public function approve(Comment $comment): JsonResponse
    {
        $this->commentService->approveComment($comment);

        return response()->json([
            'success' => true,
            'message' => 'Comment approved',
        ]);
    }

    /**
     * Reject comment (Admin).
     */
    public function reject(Comment $comment): JsonResponse
    {
        $this->commentService->rejectComment($comment);

        return response()->json([
            'success' => true,
            'message' => 'Comment rejected',
        ]);
    }

    /**
     * Get pending comments (Admin).
     */
    public function pending(): JsonResponse
    {
        $comments = Comment::with(['post', 'author'])
            ->pending()
            ->latest()
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => CommentResource::collection($comments),
            'meta' => [
                'current_page' => $comments->currentPage(),
                'per_page' => $comments->perPage(),
                'total' => $comments->total(),
            ],
        ]);
    }
}
