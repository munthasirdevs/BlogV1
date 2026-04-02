<?php

namespace App\Services;

use App\Models\Like;
use App\Models\Post;
use App\Models\Comment;
use App\Models\User;
use App\Repositories\LikeRepository;
use App\Services\NotificationService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Class LikeService
 *
 * Service for managing like operations with business logic,
 * race condition handling, and count caching.
 *
 * @package App\Services
 */
class LikeService extends BaseService
{
    /**
     * The like repository instance.
     *
     * @var LikeRepository
     */
    protected $repository;

    /**
     * The notification service instance.
     */
    protected NotificationService $notificationService;

    /**
     * Cache TTL for like counts in seconds.
     */
    protected int $countCacheTtl = 300; // 5 minutes

    /**
     * LikeService constructor.
     */
    public function __construct(LikeRepository $repository, NotificationService $notificationService)
    {
        $this->repository = $repository;
        $this->notificationService = $notificationService;
        $this->modelClass = Like::class;
    }

    /**
     * Initialize the repository.
     */
    protected function initializeRepository(): void
    {
        // Repository is injected via constructor
    }

    /**
     * Toggle like on a model (post or comment).
     *
     * @param int $userId
     * @param Model $likeable
     * @return array ['liked' => bool, 'count' => int]
     * @throws \Exception
     */
    public function toggle(int $userId, Model $likeable): array
    {
        $likeableType = get_class($likeable);
        $likeableId = $likeable->id;
        $liker = User::find($userId);

        // Use repository's toggle method with transaction for race condition handling
        $result = $this->repository->toggle($userId, $likeableType, $likeableId);

        // Update the like count on the model
        if ($result['action'] === 'created') {
            $likeable->increment('likes_count');

            // Trigger notification to the owner
            if ($likeable instanceof Post && $liker) {
                $this->notificationService->notifyPostLike($likeable, $liker);
            } elseif ($likeable instanceof Comment && $liker) {
                $this->notificationService->notifyCommentLike($likeable, $liker);
            }
        } else {
            $likeable->decrement('likes_count');
        }

        // Invalidate count cache
        $this->invalidateCountCache($likeableType, $likeableId);

        return [
            'liked' => $result['liked'],
            'count' => $likeable->likes_count,
        ];
    }

    /**
     * Like a model.
     *
     * @param int $userId
     * @param Model $likeable
     * @return array ['liked' => bool, 'count' => int, 'created' => bool]
     */
    public function like(int $userId, Model $likeable): array
    {
        $likeableType = get_class($likeable);
        $likeableId = $likeable->id;

        // Check if already liked
        if ($this->hasLiked($userId, $likeable)) {
            return [
                'liked' => true,
                'count' => $this->getCount($likeable),
                'created' => false,
            ];
        }

        // Create like
        $like = $this->repository->createOrIgnore([
            'user_id' => $userId,
            'likeable_id' => $likeableId,
            'likeable_type' => $likeableType,
        ]);

        if ($like) {
            $likeable->increment('likes_count');
            $this->invalidateCountCache($likeableType, $likeableId);
        }

        return [
            'liked' => true,
            'count' => $likeable->likes_count,
            'created' => $like !== null,
        ];
    }

    /**
     * Unlike a model.
     *
     * @param int $userId
     * @param Model $likeable
     * @return array ['liked' => bool, 'count' => int, 'deleted' => bool]
     */
    public function unlike(int $userId, Model $likeable): array
    {
        $like = $this->repository->getByUserAndLikeable(
            $userId,
            get_class($likeable),
            $likeable->id
        );

        if (!$like) {
            return [
                'liked' => false,
                'count' => $this->getCount($likeable),
                'deleted' => false,
            ];
        }

        $like->delete();
        $likeable->decrement('likes_count');
        $this->invalidateCountCache(get_class($likeable), $likeable->id);

        return [
            'liked' => false,
            'count' => $likeable->likes_count,
            'deleted' => true,
        ];
    }

    /**
     * Check if user has liked a model.
     *
     * @param int $userId
     * @param Model $likeable
     * @return bool
     */
    public function hasLiked(int $userId, Model $likeable): bool
    {
        return $this->repository->hasLiked(
            $userId,
            get_class($likeable),
            $likeable->id
        );
    }

    /**
     * Get like count for a model.
     *
     * @param Model $likeable
     * @param bool $useCache
     * @return int
     */
    public function getCount(Model $likeable, bool $useCache = true): int
    {
        if ($useCache) {
            return $this->getCachedCount(get_class($likeable), $likeable->id);
        }

        return $this->repository->getCountForModel(
            get_class($likeable),
            $likeable->id
        );
    }

    /**
     * Get cached count for a model.
     *
     * @param string $likeableType
     * @param int $likeableId
     * @return int
     */
    protected function getCachedCount(string $likeableType, int $likeableId): int
    {
        $cacheKey = $this->getCountCacheKey($likeableType, $likeableId);

        return Cache::remember($cacheKey, $this->countCacheTtl, function () use ($likeableType, $likeableId) {
            return $this->repository->getCountForModel($likeableType, $likeableId);
        });
    }

    /**
     * Invalidate count cache for a model.
     *
     * @param string $likeableType
     * @param int $likeableId
     * @return void
     */
    protected function invalidateCountCache(string $likeableType, int $likeableId): void
    {
        $cacheKey = $this->getCountCacheKey($likeableType, $likeableId);
        Cache::forget($cacheKey);
    }

    /**
     * Get cache key for count.
     *
     * @param string $likeableType
     * @param int $likeableId
     * @return string
     */
    protected function getCountCacheKey(string $likeableType, int $likeableId): string
    {
        $modelName = class_basename($likeableType);
        return "likes:{$modelName}:{$likeableId}:count";
    }

    /**
     * Get likes for a model.
     *
     * @param Model $likeable
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getForModel(Model $likeable, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->getForModel(
            get_class($likeable),
            $likeable->id,
            ['user'],
            $perPage
        );
    }

    /**
     * Get user's liked posts.
     *
     * @param int $userId
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getUserLikedPosts(int $userId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->getUserLikedPosts($userId, $perPage);
    }

    /**
     * Get user's liked comments.
     *
     * @param int $userId
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getUserLikedComments(int $userId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->getUserLikedComments($userId, $perPage);
    }

    /**
     * Get user's all likes.
     *
     * @param int $userId
     * @param string|null $likeableType
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getUserLikes(
        int $userId,
        ?string $likeableType = null,
        int $perPage = 15
    ): LengthAwarePaginator {
        return $this->repository->getUserLikes($userId, $likeableType, $perPage);
    }

    /**
     * Get likers for a post.
     *
     * @param Post $post
     * @param int $limit
     * @return Collection
     */
    public function getPostLikers(Post $post, int $limit = 10): Collection
    {
        return $this->repository->getForModel(
            Post::class,
            $post->id,
            ['user'],
            $limit
        )->getCollection();
    }

    /**
     * Get likers for a comment.
     *
     * @param Comment $comment
     * @param int $limit
     * @return Collection
     */
    public function getCommentLikers(Comment $comment, int $limit = 10): Collection
    {
        return $this->repository->getForModel(
            Comment::class,
            $comment->id,
            ['user'],
            $limit
        )->getCollection();
    }

    /**
     * Get bulk like counts for multiple posts.
     *
     * @param array $postIds
     * @return array [postId => count]
     */
    public function getBulkPostCounts(array $postIds): array
    {
        return $this->repository->getBulkCounts(Post::class, $postIds);
    }

    /**
     * Refresh like count from database.
     *
     * @param Model $likeable
     * @return int New count
     */
    public function refreshCount(Model $likeable): int
    {
        $count = $this->repository->getCountForModel(
            get_class($likeable),
            $likeable->id
        );

        // Update the model's count
        $likeable->update(['likes_count' => $count]);

        // Invalidate cache
        $this->invalidateCountCache(get_class($likeable), $likeable->id);

        return $count;
    }

    /**
     * Sync like count with actual likes.
     *
     * @param Model $likeable
     * @return int Corrected count
     */
    public function syncCount(Model $likeable): int
    {
        $actualCount = $this->repository->getCountForModel(
            get_class($likeable),
            $likeable->id
        );

        if ($actualCount !== $likeable->likes_count) {
            $likeable->update(['likes_count' => $actualCount]);
            $this->invalidateCountCache(get_class($likeable), $likeable->id);
        }

        return $actualCount;
    }

    /**
     * Delete all likes by user.
     *
     * @param int $userId
     * @return int Number of deleted likes
     */
    public function deleteByUser(int $userId): int
    {
        return $this->repository->deleteByUser($userId);
    }

    /**
     * Delete all likes for a model.
     *
     * @param Model $likeable
     * @return int Number of deleted likes
     */
    public function deleteForModel(Model $likeable): int
    {
        $count = $this->repository->deleteForModel(
            get_class($likeable),
            $likeable->id
        );

        // Update model count
        $likeable->update(['likes_count' => 0]);
        $this->invalidateCountCache(get_class($likeable), $likeable->id);

        return $count;
    }

    /**
     * Get top likers for posts.
     *
     * @param int $limit
     * @return Collection
     */
    public function getTopPostLikers(int $limit = 10): Collection
    {
        return $this->repository->getTopLikers(Post::class, $limit);
    }

    /**
     * Get recent likes across all models.
     *
     * @param int $limit
     * @return Collection
     */
    public function getRecentLikes(int $limit = 20): Collection
    {
        return $this->repository->getRecentLikes($limit);
    }

    /**
     * Get like status and count for a model.
     *
     * @param int $userId
     * @param Model $likeable
     * @return array ['liked' => bool, 'count' => int]
     */
    public function getLikeStatus(int $userId, Model $likeable): array
    {
        return [
            'liked' => $this->hasLiked($userId, $likeable),
            'count' => $this->getCount($likeable),
        ];
    }

    /**
     * Batch get like status for multiple models.
     *
     * @param int $userId
     * @param array $models
     * @return array [modelId => ['liked' => bool, 'count' => int]]
     */
    public function getBatchLikeStatus(int $userId, array $models): array
    {
        $result = [];

        foreach ($models as $model) {
            $result[$model->id] = $this->getLikeStatus($userId, $model);
        }

        return $result;
    }
}
