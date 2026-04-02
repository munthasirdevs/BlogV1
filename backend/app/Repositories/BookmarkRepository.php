<?php

namespace App\Repositories;

use App\Models\Bookmark;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * Class BookmarkRepository
 *
 * Repository for managing bookmark operations with collection support.
 *
 * @package App\Repositories
 */
class BookmarkRepository extends BaseRepository
{
    /**
     * Specify Model class name.
     *
     * @return string
     */
    protected function model(): string
    {
        return Bookmark::class;
    }

    /**
     * Get bookmark by user and post.
     *
     * @param int $userId
     * @param int $postId
     * @param string|null $collection
     * @param array $columns
     * @return Bookmark|null
     */
    public function getByUserAndPost(
        int $userId,
        int $postId,
        ?string $collection = null,
        array $columns = ['*']
    ): ?Bookmark {
        $query = $this->model->newQuery()
            ->where('user_id', $userId)
            ->where('post_id', $postId);

        if ($collection) {
            $query->where('collection_name', $collection);
        }

        return $query->select($columns)->first();
    }

    /**
     * Check if user has bookmarked a post.
     *
     * @param int $userId
     * @param int $postId
     * @param string|null $collection
     * @return bool
     */
    public function hasBookmarked(int $userId, int $postId, ?string $collection = null): bool
    {
        return $this->getByUserAndPost($userId, $postId, $collection) !== null;
    }

    /**
     * Get user's bookmarks with post data.
     *
     * @param int $userId
     * @param string|null $collection
     * @param int $perPage
     * @param array $columns
     * @return LengthAwarePaginator
     */
    public function getUserBookmarks(
        int $userId,
        ?string $collection = null,
        int $perPage = 15,
        array $columns = ['*']
    ): LengthAwarePaginator {
        $query = $this->model->newQuery()
            ->where('user_id', $userId)
            ->with(['post' => function ($q) {
                $q->published()
                    ->with(['author', 'category']);
            }])
            ->select($columns);

        if ($collection) {
            $query->where('collection_name', $collection);
        }

        return $query->latest()->paginate($perPage);
    }

    /**
     * Get user's bookmark collections with counts.
     *
     * @param int $userId
     * @return Collection
     */
    public function getUserCollections(int $userId): Collection
    {
        return $this->model->newQuery()
            ->where('user_id', $userId)
            ->selectRaw('collection_name, COUNT(*) as count')
            ->groupBy('collection_name')
            ->get()
            ->map(function ($item) {
                $item->display_name = ucfirst($item->collection_name);
                return $item;
            });
    }

    /**
     * Get or create a collection for user.
     *
     * @param int $userId
     * @param string $collectionName
     * @return array ['name' => string, 'count' => int]
     */
    public function getOrCreateCollection(int $userId, string $collectionName): array
    {
        $count = $this->model->newQuery()
            ->where('user_id', $userId)
            ->where('collection_name', $collectionName)
            ->count();

        return [
            'name' => $collectionName,
            'display_name' => ucfirst($collectionName),
            'count' => $count,
        ];
    }

    /**
     * Toggle bookmark with database transaction.
     *
     * @param int $userId
     * @param int $postId
     * @param string|null $collection
     * @param string|null $notes
     * @return array ['action' => 'created'|'deleted', 'bookmarked' => bool, 'bookmark' => Bookmark|null]
     * @throws \Exception
     */
    public function toggle(
        int $userId,
        int $postId,
        ?string $collection = null,
        ?string $notes = null
    ): array {
        return DB::transaction(function () use ($userId, $postId, $collection, $notes) {
            $collection = $collection ?? Bookmark::DEFAULT_COLLECTION;

            // Use forUpdate to lock the row and prevent race conditions
            $existing = $this->model->newQuery()
                ->where('user_id', $userId)
                ->where('post_id', $postId)
                ->where('collection_name', $collection)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                $existing->delete();
                return ['action' => 'deleted', 'bookmarked' => false, 'bookmark' => null];
            }

            $bookmark = $this->model->newQuery()->create([
                'user_id' => $userId,
                'post_id' => $postId,
                'collection_name' => $collection,
                'notes' => $notes,
            ]);

            return ['action' => 'created', 'bookmarked' => true, 'bookmark' => $bookmark];
        });
    }

    /**
     * Assign bookmark to collection.
     *
     * @param int $bookmarkId
     * @param string $collectionName
     * @return Bookmark|null
     */
    public function assignToCollection(int $bookmarkId, string $collectionName): ?Bookmark
    {
        $bookmark = $this->find($bookmarkId);

        if (!$bookmark) {
            return null;
        }

        $bookmark->update(['collection_name' => $collectionName]);

        return $bookmark;
    }

    /**
     * Move bookmark to different collection.
     *
     * @param int $bookmarkId
     * @param int $userId
     * @param string $newCollection
     * @return Bookmark|null
     */
    public function moveToCollection(int $bookmarkId, int $userId, string $newCollection): ?Bookmark
    {
        $bookmark = $this->model->newQuery()
            ->where('id', $bookmarkId)
            ->where('user_id', $userId)
            ->first();

        if (!$bookmark) {
            return null;
        }

        $bookmark->update(['collection_name' => $newCollection]);

        return $bookmark;
    }

    /**
     * Get bookmark with collection info.
     *
     * @param int $bookmarkId
     * @param int $userId
     * @param array $columns
     * @return Bookmark|null
     */
    public function getWithCollectionInfo(int $bookmarkId, int $userId, array $columns = ['*']): ?Bookmark
    {
        return $this->model->newQuery()
            ->where('id', $bookmarkId)
            ->where('user_id', $userId)
            ->with(['post' => function ($q) {
                $q->with(['author', 'category']);
            }])
            ->select($columns)
            ->first();
    }

    /**
     * Get bookmarks by collection.
     *
     * @param int $userId
     * @param string $collectionName
     * @param array $with
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getByCollection(
        int $userId,
        string $collectionName,
        array $with = ['post'],
        int $perPage = 15
    ): LengthAwarePaginator {
        $query = $this->model->newQuery()
            ->where('user_id', $userId)
            ->where('collection_name', $collectionName);

        if (!empty($with)) {
            $query->with($with);
        }

        return $query->latest()->paginate($perPage);
    }

    /**
     * Update bookmark notes.
     *
     * @param int $bookmarkId
     * @param int $userId
     * @param string|null $notes
     * @return Bookmark|null
     */
    public function updateNotes(int $bookmarkId, int $userId, ?string $notes): ?Bookmark
    {
        $bookmark = $this->model->newQuery()
            ->where('id', $bookmarkId)
            ->where('user_id', $userId)
            ->first();

        if (!$bookmark) {
            return null;
        }

        $bookmark->update(['notes' => $notes]);

        return $bookmark;
    }

    /**
     * Get user's bookmark count.
     *
     * @param int $userId
     * @param string|null $collection
     * @return int
     */
    public function getUserBookmarkCount(int $userId, ?string $collection = null): int
    {
        $query = $this->model->newQuery()->where('user_id', $userId);

        if ($collection) {
            $query->where('collection_name', $collection);
        }

        return $query->count();
    }

    /**
     * Delete all bookmarks by user.
     *
     * @param int $userId
     * @return int Number of deleted bookmarks
     */
    public function deleteByUser(int $userId): int
    {
        return $this->model->newQuery()
            ->where('user_id', $userId)
            ->delete();
    }

    /**
     * Delete all bookmarks for a post.
     *
     * @param int $postId
     * @return int Number of deleted bookmarks
     */
    public function deleteForPost(int $postId): int
    {
        return $this->model->newQuery()
            ->where('post_id', $postId)
            ->delete();
    }

    /**
     * Delete collection for user.
     *
     * @param int $userId
     * @param string $collectionName
     * @return int Number of deleted bookmarks
     */
    public function deleteCollection(int $userId, string $collectionName): int
    {
        return $this->model->newQuery()
            ->where('user_id', $userId)
            ->where('collection_name', $collectionName)
            ->delete();
    }

    /**
     * Rename collection for user.
     *
     * @param int $userId
     * @param string $oldName
     * @param string $newName
     * @return int Number of updated bookmarks
     */
    public function renameCollection(int $userId, string $oldName, string $newName): int
    {
        return $this->model->newQuery()
            ->where('user_id', $userId)
            ->where('collection_name', $oldName)
            ->update(['collection_name' => $newName]);
    }

    /**
     * Get recent bookmarks.
     *
     * @param int $userId
     * @param int $limit
     * @return Collection
     */
    public function getRecentBookmarks(int $userId, int $limit = 10): Collection
    {
        return $this->model->newQuery()
            ->where('user_id', $userId)
            ->with(['post' => function ($q) {
                $q->with(['author', 'category']);
            }])
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * Search user's bookmarks by post title.
     *
     * @param int $userId
     * @param string $searchTerm
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function searchBookmarks(int $userId, string $searchTerm, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->where('user_id', $userId)
            ->whereHas('post', function ($q) use ($searchTerm) {
                $q->where('title', 'LIKE', "%{$searchTerm}%");
            })
            ->with(['post' => function ($q) {
                $q->with(['author', 'category']);
            }])
            ->latest()
            ->paginate($perPage);
    }
}
