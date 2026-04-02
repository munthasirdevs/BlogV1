<?php

namespace App\Services;

use App\Repositories\CommentRepository;
use App\Models\Comment;
use App\Models\CommentEdit;
use App\Models\Post;
use App\Models\User;
use App\Helpers\MentionParser;
use App\Helpers\ProfanityFilter;
use App\Services\NotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Class CommentService
 *
 * Service class for comment-related operations.
 * Handles comment CRUD, nested replies, moderation, mentions, and notifications.
 *
 * Features:
 * - Comment CRUD with validation
 * - Nested reply support (max 5 levels)
 * - Moderation workflow (approve/reject/spam)
 * - Edit history tracking
 * - @mention parsing and notifications
 * - Rate limiting
 * - Caching for performance
 * - Bulk operations
 */
class CommentService extends BaseService
{
    /**
     * The notification service instance.
     */
    protected NotificationService $notificationService;

    /**
     * Create a new CommentService instance.
     */
    public function __construct(NotificationService $notificationService)
    {
        parent::__construct();
        $this->notificationService = $notificationService;
    }

    /**
     * Initialize the repository.
     */
    protected function initializeRepository(): void
    {
        $this->repository = new CommentRepository();
    }

    /**
     * Get the comment repository.
     *
     * @return CommentRepository
     */
    protected function repository(): CommentRepository
    {
        return parent::repository();
    }

    /**
     * Get paginated comments for a post (with nesting support).
     *
     * @param Post $post
     * @param array $filters
     * @return Collection|LengthAwarePaginator
     */
    public function getPostComments(Post $post, array $filters = [])
    {
        $approvedOnly = $filters['approved_only'] ?? true;
        $flat = $filters['flat'] ?? false;
        $perPage = $filters['per_page'] ?? 20;

        if ($flat) {
            return $this->repository()->paginateTopLevelByPost($post->id, $perPage, $approvedOnly);
        }

        // Return tree structure
        return $this->repository()->getCommentTree($post->id, Comment::MAX_DEPTH, $approvedOnly);
    }

    /**
     * Get a single comment with relationships.
     *
     * @param int $id
     * @return Comment|null
     */
    public function getComment(int $id): ?Comment
    {
        return $this->repository()->findWithRelations($id);
    }

    /**
     * Create a new comment.
     *
     * @param array $data
     * @param User $user
     * @return Comment
     */
    public function createComment(array $data, User $user): Comment
    {
        return DB::transaction(function () use ($data, $user) {
            // Verify post exists and is published
            $post = Post::find($data['post_id']);
            if (!$post) {
                throw new \RuntimeException('Post not found.');
            }

            if (!$post->isPublished()) {
                throw new \RuntimeException('Cannot comment on unpublished post.');
            }

            // Determine status based on user role and history
            $data['status'] = $this->determineCommentStatus($user);

            // Handle parent comment (reply)
            if (isset($data['parent_id'])) {
                $this->validateParentComment($data['parent_id'], $data['post_id']);
            }

            // Filter content for profanity if needed
            if (!ProfanityFilter::shouldBypass($user)) {
                $data['content'] = ProfanityFilter::filter($data['content']);
            }

            // Create the comment
            $comment = $this->repository()->create($data);

            // Increment reply count on parent if this is a reply
            if ($comment->parent) {
                $comment->parent->incrementReplyCount();
            }

            // Process mentions and trigger notifications
            $this->processMentions($comment);

            // Trigger notification to post author (if comment is approved)
            if ($comment->isApproved()) {
                $this->notificationService->notifyPostAuthor($comment);

                // Trigger notification to parent comment author if this is a reply
                if ($comment->parent) {
                    $this->notificationService->notifyCommentAuthor($comment);
                }
            }

            // Clear cache
            $this->repository()->clearTreeCache($post->id);

            // Log the action
            Log::info('Comment created', [
                'comment_id' => $comment->id,
                'post_id' => $post->id,
                'user_id' => $user->id,
                'status' => $comment->status,
            ]);

            return $comment->load(['author', 'parent']);
        });
    }

    /**
     * Determine the initial status for a comment.
     *
     * @param User $user
     * @return string
     */
    protected function determineCommentStatus(User $user): string
    {
        // Auto-approve for trusted users
        if ($user->hasRole(['admin', 'editor', 'moderator'])) {
            return Comment::STATUS_APPROVED;
        }

        // Check if user has enough approved comments to be trusted
        $approvedCount = Comment::where('user_id', $user->id)
            ->approved()
            ->count();

        if ($approvedCount >= 5) {
            return Comment::STATUS_APPROVED;
        }

        // First-time commenters go to pending
        return Comment::STATUS_PENDING;
    }

    /**
     * Validate parent comment for replies.
     *
     * @param int $parentId
     * @param int $postId
     * @return void
     * @throws \RuntimeException
     */
    protected function validateParentComment(int $parentId, int $postId): void
    {
        $parent = Comment::find($parentId);

        if (!$parent) {
            throw new \RuntimeException('Parent comment not found.');
        }

        if ($parent->trashed()) {
            throw new \RuntimeException('Cannot reply to a deleted comment.');
        }

        if ($parent->post_id !== $postId) {
            throw new \RuntimeException('Cannot reply to a comment from a different post.');
        }

        if ($parent->depth >= Comment::MAX_DEPTH) {
            throw new \RuntimeException('Maximum reply depth reached.');
        }
    }

    /**
     * Process mentions in a comment and trigger notifications.
     *
     * @param Comment $comment
     * @return void
     */
    protected function processMentions(Comment $comment): void
    {
        $mentionedUsers = MentionParser::parse($comment->content);

        foreach ($mentionedUsers as $mentionedUser) {
            // Don't notify if user mentioned themselves
            if ($mentionedUser->id === $comment->user_id) {
                continue;
            }

            // Trigger notification (implement based on your notification system)
            $this->triggerMentionNotification($comment, $mentionedUser);
        }
    }

    /**
     * Trigger notification for mentioned user.
     *
     * @param Comment $comment
     * @param User $mentionedUser
     * @return void
     */
    protected function triggerMentionNotification(Comment $comment, User $mentionedUser): void
    {
        // Log the notification trigger
        Log::info('Mention notification triggered', [
            'comment_id' => $comment->id,
            'mentioned_user_id' => $mentionedUser->id,
            'post_id' => $comment->post_id,
        ]);

        // Send mention notification via NotificationService
        $this->notificationService->notifyMentions($comment);
    }

    /**
     * Update a comment with edit history.
     *
     * @param Comment $comment
     * @param array $data
     * @param User $user
     * @param string|null $editReason
     * @return Comment
     */
    public function updateComment(Comment $comment, array $data, User $user, ?string $editReason = null): Comment
    {
        return DB::transaction(function () use ($comment, $data, $user, $editReason) {
            $oldContent = $comment->content;
            $newContent = $data['content'];

            // Filter profanity if needed
            if (!ProfanityFilter::shouldBypass($user)) {
                $newContent = ProfanityFilter::filter($newContent);
            }

            // Update the comment
            $comment->update([
                'content' => $newContent,
                'is_edited' => true,
            ]);

            // Record edit history
            $comment->recordEdit($oldContent, $newContent, $editReason);

            // Clear cache
            if ($comment->post) {
                $this->repository()->clearTreeCache($comment->post_id);
            }

            // Log the edit
            Log::info('Comment updated', [
                'comment_id' => $comment->id,
                'user_id' => $user->id,
                'edit_reason' => $editReason,
            ]);

            return $comment->fresh(['author', 'edits']);
        });
    }

    /**
     * Delete a comment (soft delete).
     *
     * @param Comment $comment
     * @param bool $cascade
     * @return bool
     */
    public function deleteComment(Comment $comment, bool $cascade = false): bool
    {
        return DB::transaction(function () use ($comment, $cascade) {
            $postId = $comment->post_id;

            // Decrement reply count on parent if this is a reply
            if ($comment->parent) {
                $comment->parent->decrementReplyCount();
            }

            // Cascade delete replies if requested
            if ($cascade) {
                $this->repository()->deleteReplies($comment->id);
            }

            // Soft delete the comment
            $result = $comment->delete();

            // Update post comment count
            if ($result && $postId) {
                $post = Post::find($postId);
                if ($post) {
                    $post->refreshCounts();
                }
            }

            // Clear cache
            if ($postId) {
                $this->repository()->clearTreeCache($postId);
            }

            // Log the deletion
            Log::info('Comment deleted', [
                'comment_id' => $comment->id,
                'cascade' => $cascade,
            ]);

            return $result;
        });
    }

    /**
     * Approve a comment.
     *
     * @param Comment $comment
     * @param User $moderator
     * @return Comment
     */
    public function approveComment(Comment $comment, ?User $moderator = null): Comment
    {
        return DB::transaction(function () use ($comment, $moderator) {
            $comment->approve();

            // Update post comment count
            $comment->post?->refreshCounts();

            // Clear cache
            $this->repository()->clearTreeCache($comment->post_id);

            // Notify the comment author
            $this->notifyCommentApproved($comment);

            // Log the moderation
            Log::info('Comment approved', [
                'comment_id' => $comment->id,
                'moderated_by' => $moderator?->id,
            ]);

            return $comment->fresh(['author', 'post']);
        });
    }

    /**
     * Reject a comment.
     *
     * @param Comment $comment
     * @param User $moderator
     * @param string|null $reason
     * @return Comment
     */
    public function rejectComment(Comment $comment, ?User $moderator = null, ?string $reason = null): Comment
    {
        $comment->reject();

        // Clear cache
        $this->repository()->clearTreeCache($comment->post_id);

        // Log the rejection
        Log::info('Comment rejected', [
            'comment_id' => $comment->id,
            'moderated_by' => $moderator?->id,
            'reason' => $reason,
        ]);

        return $comment;
    }

    /**
     * Mark a comment as spam.
     *
     * @param Comment $comment
     * @param User $moderator
     * @return Comment
     */
    public function markCommentAsSpam(Comment $comment, ?User $moderator = null): Comment
    {
        $comment->markAsSpam();

        // Update post comment count
        $comment->post?->refreshCounts();

        // Clear cache
        $this->repository()->clearTreeCache($comment->post_id);

        // Log the action
        Log::info('Comment marked as spam', [
            'comment_id' => $comment->id,
            'moderated_by' => $moderator?->id,
        ]);

        return $comment;
    }

    /**
     * Get replies to a comment.
     *
     * @param int $parentId
     * @param bool $approvedOnly
     * @param int $perPage
     * @return LengthAwarePaginator|Collection
     */
    public function getReplies(int $parentId, bool $approvedOnly = true, int $perPage = 0)
    {
        if ($perPage > 0) {
            return $this->repository()->paginateReplies($parentId, $perPage, $approvedOnly);
        }

        return $this->repository()->findReplies($parentId);
    }

    /**
     * Get pending comments for moderation.
     *
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getPendingComments(int $perPage = 20): LengthAwarePaginator
    {
        return $this->repository()->getPendingModeration($perPage);
    }

    /**
     * Search comments (admin/moderator).
     *
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function searchComments(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return $this->repository()->search($filters, $perPage);
    }

    /**
     * Bulk moderate comments.
     *
     * @param array $commentIds
     * @param string $action
     * @param User $moderator
     * @param string|null $reason
     * @return array
     */
    public function bulkModerate(array $commentIds, string $action, User $moderator, ?string $reason = null): array
    {
        $results = [];

        foreach ($commentIds as $commentId) {
            try {
                $comment = Comment::find($commentId);

                if (!$comment) {
                    $results[$commentId] = [
                        'success' => false,
                        'error' => 'Comment not found',
                    ];
                    continue;
                }

                switch ($action) {
                    case 'approve':
                        $this->approveComment($comment, $moderator);
                        break;
                    case 'reject':
                        $this->rejectComment($comment, $moderator, $reason);
                        break;
                    case 'spam':
                        $this->markCommentAsSpam($comment, $moderator);
                        break;
                    case 'delete':
                        $this->deleteComment($comment, false);
                        break;
                    default:
                        throw new \RuntimeException('Invalid action');
                }

                $results[$commentId] = [
                    'success' => true,
                    'action' => $action,
                ];
            } catch (\Exception $e) {
                $results[$commentId] = [
                    'success' => false,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    /**
     * Get comment statistics.
     *
     * @return array
     */
    public function getStatistics(): array
    {
        return $this->repository()->getStatistics();
    }

    /**
     * Get user's comments.
     *
     * @param int $userId
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getUserComments(int $userId, int $perPage = 20): LengthAwarePaginator
    {
        return $this->repository()->paginateByUser($userId, $perPage);
    }

    /**
     * Get recent comments.
     *
     * @param int $limit
     * @return Collection
     */
    public function getRecentComments(int $limit = 10): Collection
    {
        return $this->repository()->findRecent($limit);
    }

    /**
     * Get comment count by status for a post.
     *
     * @param int $postId
     * @return array
     */
    public function getCountByStatus(int $postId): array
    {
        return $this->repository()->getCountByStatus($postId);
    }

    /**
     * Notify user when their comment is approved.
     *
     * @param Comment $comment
     * @return void
     */
    protected function notifyCommentApproved(Comment $comment): void
    {
        Log::info('Comment approved notification', [
            'comment_id' => $comment->id,
            'user_id' => $comment->user_id,
        ]);

        // TODO: Implement actual notification sending
        // Example: Notification::send($comment->author, new CommentApprovedNotification($comment));
    }

    /**
     * Get edit history for a comment.
     *
     * @param Comment $comment
     * @return Collection
     */
    public function getEditHistory(Comment $comment): Collection
    {
        return $comment->edits()->with('editor')->get();
    }

    /**
     * Check if a user can edit a comment.
     *
     * @param Comment $comment
     * @param User $user
     * @return array{can_edit: bool, reason: string|null}
     */
    public function canEditComment(Comment $comment, User $user): array
    {
        // Staff can always edit
        if ($user->hasRole(['admin', 'editor', 'moderator'])) {
            return ['can_edit' => true, 'reason' => null];
        }

        // Must be the author
        if ($user->id !== $comment->user_id) {
            return ['can_edit' => false, 'reason' => 'Not the comment author'];
        }

        // Check edit window
        if ($comment->created_at->diffInMinutes(now()) > Comment::EDIT_WINDOW_MINUTES) {
            return ['can_edit' => false, 'reason' => 'Edit window expired'];
        }

        // Check max edits
        if ($comment->edits()->count() >= Comment::MAX_EDITS) {
            return ['can_edit' => false, 'reason' => 'Maximum edit limit reached'];
        }

        return ['can_edit' => true, 'reason' => null];
    }

    /**
     * Get mention suggestions.
     *
     * @param string $query
     * @param int $limit
     * @return Collection
     */
    public function getMentionSuggestions(string $query, int $limit = 5): Collection
    {
        return MentionParser::suggestUsers($query, $limit);
    }

    /**
     * Get comments with most engagement.
     *
     * @param int $limit
     * @return Collection
     */
    public function getMostEngagedComments(int $limit = 10): Collection
    {
        return Comment::where('status', 'approved')
            ->whereNull('parent_id')
            ->orderByRaw('(likes_count + reply_count * 2) DESC')
            ->limit($limit)
            ->get();
    }

    /**
     * Restore a deleted comment.
     *
     * @param int $id
     * @return bool
     */
    public function restoreComment(int $id): bool
    {
        return $this->repository()->restore($id);
    }

    /**
     * Get flat list of comments for a post.
     *
     * @param int $postId
     * @param bool $approvedOnly
     * @return Collection
     */
    public function getFlatComments(int $postId, bool $approvedOnly = true): Collection
    {
        return $this->repository()->getFlatComments($postId, $approvedOnly);
    }

    /**
     * Check rate limit for comment creation.
     *
     * @param User $user
     * @return array{allowed: bool, retry_after: int|null}
     */
    public function checkRateLimit(User $user): array
    {
        $userId = $user->id;

        // Check comments in last minute
        $minuteCount = Comment::where('user_id', $userId)
            ->where('created_at', '>=', now()->subMinute())
            ->count();

        if ($minuteCount >= 3) {
            return ['allowed' => false, 'retry_after' => 60];
        }

        // Check comments in last hour
        $hourCount = Comment::where('user_id', $userId)
            ->where('created_at', '>=', now()->subHour())
            ->count();

        if ($hourCount >= 10) {
            return ['allowed' => false, 'retry_after' => 3600];
        }

        return ['allowed' => true, 'retry_after' => null];
    }
}
