<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class CommentResource
 *
 * Resource for Comment model with nested replies support.
 *
 * Features:
 * - Nested replies structure
 * - Author information
 * - Edit history indicator
 * - Mention parsing
 * - Permission checks
 */
class CommentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        return [
            // Basic fields
            'id' => $this->id,
            'content' => $this->content,
            'content_with_mentions' => $this->contentWithMentions,
            'status' => $this->status,
            'depth' => $this->depth,
            'is_edited' => $this->is_edited,

            // Counts
            'likes_count' => $this->likes_count,
            'reply_count' => $this->reply_count ?? $this->replies->count(),

            // Timestamps
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'moderated_at' => $this->moderated_at?->toISOString(),

            // Display helpers
            'time_ago' => $this->timeAgo,
            'excerpt' => $this->excerpt,

            // Author (conditionally included)
            'author' => when($this->whenLoaded('author') && !$this->isAnonymous(), fn() => new UserResource($this->author)),
            'author_name' => $this->authorName,
            'author_avatar' => $this->authorAvatar,

            // Parent comment (for replies)
            'parent' => when($this->whenLoaded('parent'), fn() => new self($this->parent)),
            'parent_id' => $this->parent_id,

            // Nested replies
            'replies' => when($this->whenLoaded('replies'), fn() => self::collection($this->replies)),
            'has_replies' => $this->whenLoaded('replies', fn() => $this->replies->count() > 0),

            // Edit history (conditionally included)
            'edits' => when($this->whenLoaded('edits'), fn() => $this->edits->map(fn($edit) => [
                'id' => $edit->id,
                'edit_reason' => $edit->edit_reason,
                'edited_at' => $edit->created_at->toISOString(),
                'editor' => [
                    'id' => $edit->editor->id,
                    'name' => $edit->editor->name,
                ],
            ])),

            // Mentioned users
            'mentioned_users' => when($this->whenLoaded('author'), fn() => $this->mentionedUsers->map(fn($user) => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
            ])),

            // Post reference (conditionally included)
            'post' => when($this->whenLoaded('post'), fn() => new PostResource($this->post)),
            'post_id' => $this->post_id,

            // Permissions
            'can' => [
                'update' => $this->canUpdate($request),
                'delete' => $this->canDelete($request),
                'approve' => $this->canApprove($request),
                'reject' => $this->canReject($request),
                'reply' => $this->canReply(),
            ],

            // Meta
            'is_reply' => $this->isReply(),
            'can_have_replies' => $this->canHaveReplies(),
            'is_pending' => $this->isPending(),
            'is_approved' => $this->isApproved(),
        ];
    }

    /**
     * Check if the comment is anonymous.
     */
    protected function isAnonymous(): bool
    {
        // Add is_anonymous field check if your model supports it
        return false;
    }

    /**
     * Check if user can update the comment.
     */
    protected function canUpdate(Request $request): bool
    {
        if (!$request->user()) {
            return false;
        }

        return $request->user()->can('update', $this->resource);
    }

    /**
     * Check if user can delete the comment.
     */
    protected function canDelete(Request $request): bool
    {
        if (!$request->user()) {
            return false;
        }

        return $request->user()->can('delete', $this->resource);
    }

    /**
     * Check if user can approve the comment.
     */
    protected function canApprove(Request $request): bool
    {
        if (!$request->user()) {
            return false;
        }

        return $request->user()->can('approve', $this->resource);
    }

    /**
     * Check if user can reject the comment.
     */
    protected function canReject(Request $request): bool
    {
        if (!$request->user()) {
            return false;
        }

        return $request->user()->can('reject', $this->resource);
    }

    /**
     * Check if user can reply to the comment.
     */
    protected function canReply(): bool
    {
        return $this->canHaveReplies() && $this->isApproved();
    }

    /**
     * Additional metadata for the response.
     */
    public function with(Request $request): array
    {
        return [
            'meta' => [
                'resource_type' => 'comment',
                'api_version' => 'v1',
            ],
        ];
    }
}
