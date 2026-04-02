<?php

namespace App\Repositories;

use App\Models\Like;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * Class LikeRepository
 *
 * Repository for managing like operations with advanced query methods.
 *
 * @package App\Repositories
 */
class LikeRepository extends BaseRepository
{
    /**
     * Specify Model class name.
     *
     * @return string
     */
    protected function model(): string
    {
        return Like::class;
    }

    /**
     * Get like by user and likeable model.
     *
     * @param int $userId
     * @param string $likeableType
     * @param int $likeableId
     * @param array $columns
     * @return Like|null
     */
    public function getByUserAndLikeable(
        int $userId,
        string $likeableType,
        int $likeableId,
        array $columns = ['*']
    ): ?Like {
        return $this->model->newQuery()
            ->where('user_id', $userId)
            ->where('likeable_type', $likeableType)
            ->where('likeable_id', $likeableId)
            ->select($columns)
            ->first();
    }

    /**
     * Check if user has liked a model.
     *
     * @param int $userId
     * @param string $likeableType
     * @param int $likeableId
     * @return bool
     */
    public function hasLiked(int $userId, string $likeableType, int $likeableId): bool
    {
        return $this->model->newQuery()
            ->where('user_id', $userId)
            ->where('likeable_type', $likeableType)
            ->where('likeable_id', $likeableId)
            ->exists();
    }

    /**
     * Get likes for a specific model.
     *
     * @param string $likeableType
     * @param int $likeableId
     * @param array $with
     * @param int $perPage
     * @param array $columns
     * @return LengthAwarePaginator
     */
    public function getForModel(
        string $likeableType,
        int $likeableId,
        array $with = ['user'],
        int $perPage = 15,
        array $columns = ['*']
    ): LengthAwarePaginator {
        $query = $this->model->newQuery()
            ->where('likeable_type', $likeableType)
            ->where('likeable_id', $likeableId)
            ->select($columns);

        if (!empty($with)) {
            $query->with($with);
        }

        return $query->latest()->paginate($perPage);
    }

    /**
     * Get user's liked items.
     *
     * @param int $userId
     * @param string|null $likeableType Filter by type (e.g., Post::class, Comment::class)
     * @param int $perPage
     * @param array $columns
     * @return LengthAwarePaginator
     */
    public function getUserLikes(
        int $userId,
        ?string $likeableType = null,
        int $perPage = 15,
        array $columns = ['*']
    ): LengthAwarePaginator {
        $query = $this->model->newQuery()
            ->where('user_id', $userId)
            ->select($columns);

        if ($likeableType) {
            $query->where('likeable_type', $likeableType);
        }

        return $query->latest()->paginate($perPage);
    }

    /**
     * Get user's liked posts with post data.
     *
     * @param int $userId
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getUserLikedPosts(int $userId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->where('user_id', $userId)
            ->where('likeable_type', \App\Models\Post::class)
            ->with(['likeable' => function ($q) {
                $q->with(['author', 'category']);
            }])
            ->latest()
            ->paginate($perPage)
            ->through(function ($like) {
                if (!$like->likeable) {
                    return null;
                }
                
                return [
                    'id' => $like->id,
                    'likeable' => $like->likeable,
                    'liked_at' => $like->created_at->toIso8601String(),
                ];
            })
            ->filter(); // Remove null entries
    }

    /**
     * Get user's liked comments with comment data.
     *
     * @param int $userId
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getUserLikedComments(int $userId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->where('user_id', $userId)
            ->where('likeable_type', \App\Models\Comment::class)
            ->with(['likeable' => function ($q) {
                $q->approved()
                    ->with(['author', 'post']);
            }])
            ->latest()
            ->paginate($perPage)
            ->through(function ($like) {
                return [
                    'id' => $like->id,
                    'likeable' => $like->likeable,
                    'liked_at' => $like->created_at->toIso8601String(),
                ];
            });
    }

    /**
     * Get like count for a model.
     *
     * @param string $likeableType
     * @param int $likeableId
     * @return int
     */
    public function getCountForModel(string $likeableType, int $likeableId): int
    {
        return $this->model->newQuery()
            ->where('likeable_type', $likeableType)
            ->where('likeable_id', $likeableId)
            ->count();
    }

    /**
     * Toggle like with database transaction for race condition handling.
     *
     * @param int $userId
     * @param string $likeableType
     * @param int $likeableId
     * @return array ['action' => 'created'|'deleted', 'liked' => bool]
     * @throws \Exception
     */
    public function toggle(int $userId, string $likeableType, int $likeableId): array
    {
        return DB::transaction(function () use ($userId, $likeableType, $likeableId) {
            // Use forUpdate to lock the row and prevent race conditions
            $existing = $this->model->newQuery()
                ->where('user_id', $userId)
                ->where('likeable_type', $likeableType)
                ->where('likeable_id', $likeableId)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                $existing->delete();
                return ['action' => 'deleted', 'liked' => false];
            }

            $this->model->newQuery()->create([
                'user_id' => $userId,
                'likeable_id' => $likeableId,
                'likeable_type' => $likeableType,
            ]);

            return ['action' => 'created', 'liked' => true];
        });
    }

    /**
     * Create a like (with duplicate prevention).
     *
     * @param array $data
     * @return Like|null Returns null if duplicate exists
     */
    public function createOrIgnore(array $data): ?Like
    {
        // Check for existing like
        $existing = $this->getByUserAndLikeable(
            $data['user_id'],
            $data['likeable_type'],
            $data['likeable_id']
        );

        if ($existing) {
            return null;
        }

        return $this->create($data);
    }

    /**
     * Bulk get like counts for multiple models.
     *
     * @param string $likeableType
     * @param array $likeableIds
     * @return array [likeableId => count]
     */
    public function getBulkCounts(string $likeableType, array $likeableIds): array
    {
        return $this->model->newQuery()
            ->where('likeable_type', $likeableType)
            ->whereIn('likeable_id', $likeableIds)
            ->selectRaw('likeable_id, COUNT(*) as count')
            ->groupBy('likeable_id')
            ->get()
            ->pluck('count', 'likeable_id')
            ->toArray();
    }

    /**
     * Get top likers for a model type.
     *
     * @param string $likeableType
     * @param int $limit
     * @return Collection
     */
    public function getTopLikers(string $likeableType, int $limit = 10): Collection
    {
        return $this->model->newQuery()
            ->where('likeable_type', $likeableType)
            ->selectRaw('user_id, COUNT(*) as like_count')
            ->groupBy('user_id')
            ->orderByDesc('like_count')
            ->limit($limit)
            ->with('user')
            ->get();
    }

    /**
     * Get recent likes with model data.
     *
     * @param int $limit
     * @return Collection
     */
    public function getRecentLikes(int $limit = 20): Collection
    {
        return $this->model->newQuery()
            ->with(['likeable', 'user'])
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * Delete all likes by user.
     *
     * @param int $userId
     * @return int Number of deleted likes
     */
    public function deleteByUser(int $userId): int
    {
        return $this->model->newQuery()
            ->where('user_id', $userId)
            ->delete();
    }

    /**
     * Delete all likes for a model.
     *
     * @param string $likeableType
     * @param int $likeableId
     * @return int Number of deleted likes
     */
    public function deleteForModel(string $likeableType, int $likeableId): int
    {
        return $this->model->newQuery()
            ->where('likeable_type', $likeableType)
            ->where('likeable_id', $likeableId)
            ->delete();
    }
}
