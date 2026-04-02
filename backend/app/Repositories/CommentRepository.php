<?php

namespace App\Repositories;

use App\Models\Comment;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Class CommentRepository
 *
 * Repository for Comment model operations.
 *
 * Features:
 * - Tree building for nested comments
 * - Caching for performance
 * - Search and filtering
 * - Bulk operations
 *
 * @extends BaseRepository<Comment>
 */
class CommentRepository extends BaseRepository
{
    /**
     * Cache key prefix for comment trees.
     */
    const CACHE_PREFIX = 'repo:comments:tree:';

    /**
     * Cache TTL in minutes.
     */
    const CACHE_TTL = 30;

    /**
     * Specify Model class name.
     *
     * @return string
     */
    protected function model(): string
    {
        return Comment::class;
    }

    /**
     * Get approved comments.
     *
     * @param array $columns
     * @return Collection
     */
    public function findApproved(array $columns = ['*']): Collection
    {
        return $this->model->where('status', 'approved')->get($columns);
    }

    /**
     * Get pending comments (for moderation).
     *
     * @param array $columns
     * @return Collection
     */
    public function findPending(array $columns = ['*']): Collection
    {
        return $this->model->where('status', 'pending')->get($columns);
    }

    /**
     * Get comments by post.
     *
     * @param int $postId
     * @param array $columns
     * @return Collection
     */
    public function findByPost(int $postId, array $columns = ['*']): Collection
    {
        return $this->model->where('post_id', $postId)->get($columns);
    }

    /**
     * Get approved comments by post.
     *
     * @param int $postId
     * @param array $columns
     * @return Collection
     */
    public function findApprovedByPost(int $postId, array $columns = ['*']): Collection
    {
        return $this->model->where('post_id', $postId)
            ->where('status', 'approved')
            ->get($columns);
    }

    /**
     * Get top-level comments by post (no parent).
     *
     * @param int $postId
     * @param array $columns
     * @return Collection
     */
    public function findTopLevelByPost(int $postId, array $columns = ['*']): Collection
    {
        return $this->model->where('post_id', $postId)
            ->whereNull('parent_id')
            ->orderBy('created_at', 'asc')
            ->get($columns);
    }

    /**
     * Get paginated top-level comments by post.
     *
     * @param int $postId
     * @param int $perPage
     * @param bool $approvedOnly
     * @return LengthAwarePaginator
     */
    public function paginateTopLevelByPost(int $postId, int $perPage = 20, bool $approvedOnly = true): LengthAwarePaginator
    {
        $query = $this->model->where('post_id', $postId)
            ->whereNull('parent_id');

        if ($approvedOnly) {
            $query->where('status', 'approved');
        }

        return $query->with('author')
            ->orderBy('created_at', 'asc')
            ->paginate($perPage);
    }

    /**
     * Get replies to a comment.
     *
     * @param int $parentId
     * @param array $columns
     * @return Collection
     */
    public function findReplies(int $parentId, array $columns = ['*']): Collection
    {
        return $this->model->where('parent_id', $parentId)
            ->orderBy('created_at', 'asc')
            ->get($columns);
    }

    /**
     * Get paginated replies to a comment.
     *
     * @param int $parentId
     * @param int $perPage
     * @param bool $approvedOnly
     * @return LengthAwarePaginator
     */
    public function paginateReplies(int $parentId, int $perPage = 20, bool $approvedOnly = true): LengthAwarePaginator
    {
        $query = $this->model->where('parent_id', $parentId);

        if ($approvedOnly) {
            $query->where('status', 'approved');
        }

        return $query->with('author')
            ->orderBy('created_at', 'asc')
            ->paginate($perPage);
    }

    /**
     * Get comments by user.
     *
     * @param int $userId
     * @param array $columns
     * @return Collection
     */
    public function findByUser(int $userId, array $columns = ['*']): Collection
    {
        return $this->model->where('user_id', $userId)->get($columns);
    }

    /**
     * Get paginated comments by user.
     *
     * @param int $userId
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function paginateByUser(int $userId, int $perPage = 20): LengthAwarePaginator
    {
        return $this->model->where('user_id', $userId)
            ->with('post')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get recent comments.
     *
     * @param int $limit
     * @param array $columns
     * @return Collection
     */
    public function findRecent(int $limit = 10, array $columns = ['*']): Collection
    {
        return $this->model->where('status', 'approved')
            ->with(['author', 'post'])
            ->latest()
            ->limit($limit)
            ->get($columns);
    }

    /**
     * Approve a comment.
     *
     * @param int $id
     * @return Comment
     */
    public function approve(int $id): Comment
    {
        return $this->update($id, [
            'status' => 'approved',
            'moderated_at' => now(),
        ]);
    }

    /**
     * Reject a comment.
     *
     * @param int $id
     * @return Comment
     */
    public function reject(int $id): Comment
    {
        return $this->update($id, [
            'status' => 'rejected',
            'moderated_at' => now(),
        ]);
    }

    /**
     * Mark comment as spam.
     *
     * @param int $id
     * @return Comment
     */
    public function markAsSpam(int $id): Comment
    {
        return $this->update($id, [
            'status' => 'spam',
            'moderated_at' => now(),
        ]);
    }

    /**
     * Get comment tree for a post (cached).
     *
     * @param int $postId
     * @param int $maxDepth
     * @param bool $approvedOnly
     * @return Collection
     */
    public function getCommentTree(int $postId, int $maxDepth = 5, bool $approvedOnly = true): Collection
    {
        $cacheKey = self::CACHE_PREFIX . $postId . ':' . ($approvedOnly ? 'approved' : 'all') . ':v2';

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($postId, $maxDepth, $approvedOnly) {
            $comments = $this->findTopLevelByPost($postId);

            if ($approvedOnly) {
                $comments = $comments->where('status', 'approved');
            }

            foreach ($comments as $comment) {
                $comment->setRelation('replies', $this->loadRepliesRecursive($comment->id, 1, $maxDepth, $approvedOnly));
            }

            return $comments;
        });
    }

    /**
     * Load replies recursively.
     *
     * @param int $parentId
     * @param int $currentDepth
     * @param int $maxDepth
     * @param bool $approvedOnly
     * @return Collection
     */
    protected function loadRepliesRecursive(int $parentId, int $currentDepth, int $maxDepth, bool $approvedOnly = true): Collection
    {
        if ($currentDepth >= $maxDepth) {
            return collect();
        }

        $query = $this->model->where('parent_id', $parentId);

        if ($approvedOnly) {
            $query->where('status', 'approved');
        }

        $replies = $query->with('author')->orderBy('created_at', 'asc')->get();

        foreach ($replies as $reply) {
            $reply->setRelation('replies', $this->loadRepliesRecursive($reply->id, $currentDepth + 1, $maxDepth, $approvedOnly));
        }

        return $replies;
    }

    /**
     * Get comment tree with eager loading (alternative approach).
     *
     * @param int $postId
     * @param int $maxDepth
     * @param bool $approvedOnly
     * @return Collection
     */
    public function getCommentTreeEager(int $postId, int $maxDepth = 5, bool $approvedOnly = true): Collection
    {
        $query = $this->model->where('post_id', $postId)
            ->whereNull('parent_id');

        if ($approvedOnly) {
            $query->where('status', 'approved');
        }

        // Eager load nested replies up to max depth
        $withRelations = ['author'];

        // Build nested eager loading
        $currentRelation = 'replies';
        for ($i = 1; $i < $maxDepth; $i++) {
            $withRelations[] = "{$currentRelation}.author";
            $currentRelation .= ".replies";
        }

        return $query->with($withRelations)
            ->orderBy('created_at', 'asc')
            ->get();
    }

    /**
     * Get flat list of comments for a post.
     *
     * @param int $postId
     * @param bool $approvedOnly
     * @param array $columns
     * @return Collection
     */
    public function getFlatComments(int $postId, bool $approvedOnly = true, array $columns = ['*']): Collection
    {
        $query = $this->model->where('post_id', $postId);

        if ($approvedOnly) {
            $query->where('status', 'approved');
        }

        return $query->with('author')
            ->orderBy('created_at', 'asc')
            ->get($columns);
    }

    /**
     * Get comments count by status.
     *
     * @param int $postId
     * @return array
     */
    public function getCountByStatus(int $postId): array
    {
        return [
            'total' => $this->model->where('post_id', $postId)->count(),
            'approved' => $this->model->where('post_id', $postId)->where('status', 'approved')->count(),
            'pending' => $this->model->where('post_id', $postId)->where('status', 'pending')->count(),
            'rejected' => $this->model->where('post_id', $postId)->where('status', 'rejected')->count(),
            'spam' => $this->model->where('post_id', $postId)->where('status', 'spam')->count(),
        ];
    }

    /**
     * Search comments with filters.
     *
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function search(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = $this->model->query();

        // Search by content
        if (!empty($filters['search'])) {
            $query->where('content', 'LIKE', "%{$filters['search']}%");
        }

        // Filter by status
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Filter by post
        if (!empty($filters['post_id'])) {
            $query->where('post_id', $filters['post_id']);
        }

        // Filter by user
        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        // Date range filter
        if (!empty($filters['from_date'])) {
            $query->where('created_at', '>=', $filters['from_date']);
        }
        if (!empty($filters['to_date'])) {
            $query->where('created_at', '<=', $filters['to_date']);
        }

        // Sorting
        $sortField = $filters['sort'] ?? 'created_at';
        $sortOrder = ($filters['order'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        $allowedSorts = ['created_at', 'updated_at', 'content', 'status', 'likes_count'];
        if (in_array($sortField, $allowedSorts)) {
            $query->orderBy($sortField, $sortOrder);
        } else {
            $query->latest();
        }

        return $query->with(['author', 'post'])->paginate($perPage);
    }

    /**
     * Get spam comments.
     *
     * @param array $columns
     * @return Collection
     */
    public function findSpam(array $columns = ['*']): Collection
    {
        return $this->model->where('status', 'spam')->get($columns);
    }

    /**
     * Delete spam comments older than X days.
     *
     * @param int $days
     * @return int Number of deleted comments
     */
    public function deleteOldSpam(int $days = 30): int
    {
        return $this->model->where('status', 'spam')
            ->where('created_at', '<', now()->subDays($days))
            ->delete();
    }

    /**
     * Bulk update comment status.
     *
     * @param array $commentIds
     * @param string $status
     * @return int Number of updated comments
     */
    public function bulkUpdateStatus(array $commentIds, string $status): int
    {
        return $this->model->whereIn('id', $commentIds)
            ->update([
                'status' => $status,
                'moderated_at' => now(),
            ]);
    }

    /**
     * Bulk delete comments.
     *
     * @param array $commentIds
     * @return int Number of deleted comments
     */
    public function bulkDelete(array $commentIds): int
    {
        return $this->model->whereIn('id', $commentIds)->delete();
    }

    /**
     * Get comments pending moderation.
     *
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getPendingModeration(int $perPage = 20): LengthAwarePaginator
    {
        return $this->model->where('status', 'pending')
            ->with(['author', 'post'])
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Get comment statistics.
     *
     * @return array
     */
    public function getStatistics(): array
    {
        return [
            'total' => $this->model->count(),
            'approved' => $this->model->where('status', 'approved')->count(),
            'pending' => $this->model->where('status', 'pending')->count(),
            'rejected' => $this->model->where('status', 'rejected')->count(),
            'spam' => $this->model->where('status', 'spam')->count(),
            'today' => $this->model->whereDate('created_at', today())->count(),
            'this_week' => $this->model->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
            'this_month' => $this->model->whereMonth('created_at', now()->month)->count(),
        ];
    }

    /**
     * Get user's comment count.
     *
     * @param int $userId
     * @param string|null $status
     * @return int
     */
    public function getUserCommentCount(int $userId, ?string $status = null): int
    {
        $query = $this->model->where('user_id', $userId);

        if ($status) {
            $query->where('status', $status);
        }

        return $query->count();
    }

    /**
     * Check if user has approved comments.
     *
     * @param int $userId
     * @param int $minimum
     * @return bool
     */
    public function userHasMinimumApprovedComments(int $userId, int $minimum = 5): bool
    {
        return $this->getUserCommentCount($userId, 'approved') >= $minimum;
    }

    /**
     * Clear the comment tree cache for a post.
     *
     * @param int $postId
     * @return void
     */
    public function clearTreeCache(int $postId): void
    {
        Cache::forget(self::CACHE_PREFIX . $postId . ':approved:v2');
        Cache::forget(self::CACHE_PREFIX . $postId . ':all:v2');
    }

    /**
     * Get comments with most replies.
     *
     * @param int $limit
     * @return Collection
     */
    public function getMostReplied(int $limit = 10): Collection
    {
        return $this->model->whereNull('parent_id')
            ->where('status', 'approved')
            ->withCount('replies')
            ->orderBy('replies_count', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get comments by a specific user on a specific post.
     *
     * @param int $userId
     * @param int $postId
     * @return Collection
     */
    public function findByUserAndPost(int $userId, int $postId): Collection
    {
        return $this->model->where('user_id', $userId)
            ->where('post_id', $postId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Soft delete a comment and optionally its replies.
     *
     * @param int $id
     * @param bool $cascade
     * @return bool
     */
    public function softDelete(int $id, bool $cascade = false): bool
    {
        $comment = $this->find($id);

        if (!$comment) {
            return false;
        }

        if ($cascade) {
            // Delete all replies first
            $this->deleteReplies($id);
        }

        return $comment->delete();
    }

    /**
     * Delete all replies to a comment.
     *
     * @param int $parentId
     * @return int
     */
    public function deleteReplies(int $parentId): int
    {
        $replies = $this->findReplies($parentId);
        $count = 0;

        foreach ($replies as $reply) {
            $this->deleteReplies($reply->id);
            $reply->delete();
            $count++;
        }

        return $count;
    }

    /**
     * Restore a soft-deleted comment.
     *
     * @param int $id
     * @return bool
     */
    public function restore(int $id): bool
    {
        $comment = $this->model->withTrashed()->find($id);

        if (!$comment) {
            return false;
        }

        return $comment->restore();
    }

    /**
     * Get a comment with all its relationships.
     *
     * @param int $id
     * @return Comment|null
     */
    public function findWithRelations(int $id): ?Comment
    {
        return $this->model->with(['author', 'parent', 'replies', 'edits.editor'])
            ->find($id);
    }
}
