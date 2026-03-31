<?php

namespace App\Services;

use App\Models\Post;
use App\Models\Comment;

class CommentService
{
    /**
     * Get post comments with pagination.
     */
    public function getPostComments(Post $post, array $filters = []): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = $post->approvedComments()
            ->topLevel()
            ->with(['author', 'approvedReplies.author'])
            ->latest('created_at');

        $perPage = min((int) ($filters['per_page'] ?? 20), 50);

        return $query->paginate($perPage);
    }

    /**
     * Create a comment.
     */
    public function createComment(array $data): Comment
    {
        $comment = Comment::create([
            'post_id' => $data['post_id'],
            'user_id' => $data['user_id'],
            'parent_id' => $data['parent_id'] ?? null,
            'content' => $data['content'],
            'status' => $data['status'] ?? 'pending',
        ]);

        // Increment comment count on post
        if ($comment->isApproved()) {
            $comment->post()->increment('comments_count');
        }

        return $comment->fresh(['author', 'post']);
    }

    /**
     * Update a comment.
     */
    public function updateComment(Comment $comment, array $data): Comment
    {
        $comment->update($data);

        return $comment->fresh(['author']);
    }

    /**
     * Delete a comment.
     */
    public function deleteComment(Comment $comment): void
    {
        // Decrement comment count if approved
        if ($comment->isApproved()) {
            $comment->post()->decrement('comments_count');
        }

        $comment->delete();
    }

    /**
     * Approve a comment.
     */
    public function approveComment(Comment $comment): Comment
    {
        $wasPending = $comment->isPending();
        
        $comment->update(['status' => 'approved']);

        if ($wasPending) {
            $comment->post()->increment('comments_count');
        }

        return $comment;
    }

    /**
     * Reject a comment.
     */
    public function rejectComment(Comment $comment): Comment
    {
        $wasApproved = $comment->isApproved();
        
        $comment->update(['status' => 'rejected']);

        if ($wasApproved) {
            $comment->post()->decrement('comments_count');
        }

        return $comment;
    }

    /**
     * Get comment tree (nested comments).
     */
    public function getCommentTree(Post $post, int $maxDepth = 3): array
    {
        $comments = $post->approvedComments()
            ->topLevel()
            ->with(['author', 'approvedReplies.author'])
            ->latest('created_at')
            ->get();

        return $comments->map(function ($comment) use ($maxDepth, $post) {
            return $this->buildCommentTree($comment, 1, $maxDepth);
        })->toArray();
    }

    /**
     * Build nested comment structure.
     */
    private function buildCommentTree(Comment $comment, int $currentDepth, int $maxDepth): array
    {
        $data = [
            'id' => $comment->id,
            'content' => $comment->content,
            'is_edited' => $comment->is_edited,
            'created_at' => $comment->created_at->toIso8601String(),
            'author' => [
                'id' => $comment->author->id,
                'name' => $comment->author->name,
                'avatar' => $comment->author->avatar,
            ],
            'replies' => [],
        ];

        if ($currentDepth < $maxDepth) {
            $replies = $comment->approvedReplies()
                ->with('author')
                ->get();

            foreach ($replies as $reply) {
                $data['replies'][] = $this->buildCommentTree(
                    $reply,
                    $currentDepth + 1,
                    $maxDepth
                );
            }
        }

        return $data;
    }
}
