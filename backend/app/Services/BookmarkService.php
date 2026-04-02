<?php

namespace App\Services;

use App\Models\Bookmark;
use App\Models\Post;
use App\Repositories\BookmarkRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Class BookmarkService
 *
 * Service for managing bookmark operations with collection management,
 * race condition handling, and organization features.
 *
 * @package App\Services
 */
class BookmarkService extends BaseService
{
    /**
     * The bookmark repository instance.
     *
     * @var BookmarkRepository
     */
    protected $repository;

    /**
     * Default collection name.
     */
    const DEFAULT_COLLECTION = 'default';

    /**
     * Reserved collection names (cannot be used by users).
     */
    const RESERVED_COLLECTIONS = ['default', 'uncategorized', 'all', 'favorites'];

    /**
     * BookmarkService constructor.
     */
    public function __construct(BookmarkRepository $repository)
    {
        $this->repository = $repository;
        $this->modelClass = Bookmark::class;
    }

    /**
     * Initialize the repository.
     */
    protected function initializeRepository(): void
    {
        // Repository is injected via constructor
    }

    /**
     * Toggle bookmark on a post.
     *
     * @param int $userId
     * @param int $postId
     * @param string|null $collection
     * @param string|null $notes
     * @return array ['bookmarked' => bool, 'bookmark' => Bookmark|null, 'action' => string]
     * @throws \Exception
     */
    public function toggle(
        int $userId,
        int $postId,
        ?string $collection = null,
        ?string $notes = null
    ): array {
        $result = $this->repository->toggle($userId, $postId, $collection, $notes);

        return [
            'bookmarked' => $result['bookmarked'],
            'bookmark' => $result['bookmark'],
            'action' => $result['action'],
        ];
    }

    /**
     * Add a bookmark.
     *
     * @param int $userId
     * @param int $postId
     * @param string|null $collection
     * @param string|null $notes
     * @return array ['bookmark' => Bookmark, 'created' => bool]
     */
    public function addBookmark(
        int $userId,
        int $postId,
        ?string $collection = null,
        ?string $notes = null
    ): array {
        // Check if already bookmarked in this collection
        $existing = $this->repository->getByUserAndPost($userId, $postId, $collection);

        if ($existing) {
            return [
                'bookmark' => $existing,
                'created' => false,
            ];
        }

        $collection = $collection ?? self::DEFAULT_COLLECTION;

        $bookmark = $this->repository->create([
            'user_id' => $userId,
            'post_id' => $postId,
            'collection_name' => $collection,
            'notes' => $notes,
        ]);

        return [
            'bookmark' => $bookmark,
            'created' => true,
        ];
    }

    /**
     * Remove a bookmark.
     *
     * @param int $userId
     * @param int $postId
     * @param string|null $collection
     * @return bool
     */
    public function removeBookmark(int $userId, int $postId, ?string $collection = null): bool
    {
        $bookmark = $this->repository->getByUserAndPost($userId, $postId, $collection);

        if (!$bookmark) {
            return false;
        }

        return $this->repository->delete($bookmark->id);
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
        return $this->repository->hasBookmarked($userId, $postId, $collection);
    }

    /**
     * Get user's bookmarks.
     *
     * @param int $userId
     * @param string|null $collection
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getUserBookmarks(
        int $userId,
        ?string $collection = null,
        int $perPage = 15
    ): LengthAwarePaginator {
        return $this->repository->getUserBookmarks($userId, $collection, $perPage);
    }

    /**
     * Get user's bookmark collections.
     *
     * @param int $userId
     * @return Collection
     */
    public function getUserCollections(int $userId): Collection
    {
        return $this->repository->getUserCollections($userId);
    }

    /**
     * Create a bookmark collection.
     *
     * @param int $userId
     * @param string $name
     * @return array ['name' => string, 'display_name' => string, 'count' => int]
     */
    public function createCollection(int $userId, string $name): array
    {
        $name = $this->normalizeCollectionName($name);

        // Check if collection already exists
        $existing = $this->repository->getOrCreateCollection($userId, $name);

        return $existing;
    }

    /**
     * Update a bookmark collection name.
     *
     * @param int $userId
     * @param string $oldName
     * @param string $newName
     * @return int Number of bookmarks updated
     */
    public function renameCollection(int $userId, string $oldName, string $newName): int
    {
        $oldName = $this->normalizeCollectionName($oldName);
        $newName = $this->normalizeCollectionName($newName);

        // Check if new name already exists
        $existing = $this->repository->getUserCollections($userId)
            ->where('collection_name', $newName)
            ->first();

        if ($existing && $oldName !== $newName) {
            throw new \InvalidArgumentException("Collection '{$newName}' already exists");
        }

        return $this->repository->renameCollection($userId, $oldName, $newName);
    }

    /**
     * Delete a bookmark collection.
     *
     * @param int $userId
     * @param string $name
     * @param bool $moveToDefault Whether to move bookmarks to default collection
     * @return int Number of bookmarks deleted
     */
    public function deleteCollection(int $userId, string $name, bool $moveToDefault = false): int
    {
        $name = $this->normalizeCollectionName($name);

        // Cannot delete default collection
        if ($name === self::DEFAULT_COLLECTION) {
            throw new \InvalidArgumentException("Cannot delete default collection");
        }

        if ($moveToDefault) {
            // Move bookmarks to default collection
            return $this->repository->renameCollection($userId, $name, self::DEFAULT_COLLECTION);
        }

        // Delete all bookmarks in collection
        return $this->repository->deleteCollection($userId, $name);
    }

    /**
     * Assign bookmark to collection.
     *
     * @param int $bookmarkId
     * @param int $userId
     * @param string $collectionName
     * @return Bookmark|null
     */
    public function assignToCollection(int $bookmarkId, int $userId, string $collectionName): ?Bookmark
    {
        $collectionName = $this->normalizeCollectionName($collectionName);

        return $this->repository->moveToCollection($bookmarkId, $userId, $collectionName);
    }

    /**
     * Get bookmark with collection info.
     *
     * @param int $bookmarkId
     * @param int $userId
     * @return Bookmark|null
     */
    public function getBookmarkWithCollection(int $bookmarkId, int $userId): ?Bookmark
    {
        return $this->repository->getWithCollectionInfo($bookmarkId, $userId);
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
        return $this->repository->updateNotes($bookmarkId, $userId, $notes);
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
        return $this->repository->getUserBookmarkCount($userId, $collection);
    }

    /**
     * Get bookmarks by collection.
     *
     * @param int $userId
     * @param string $collectionName
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getByCollection(int $userId, string $collectionName, int $perPage = 15): LengthAwarePaginator
    {
        $collectionName = $this->normalizeCollectionName($collectionName);

        return $this->repository->getByCollection($userId, $collectionName, ['post'], $perPage);
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
        return $this->repository->getRecentBookmarks($userId, $limit);
    }

    /**
     * Search user's bookmarks.
     *
     * @param int $userId
     * @param string $searchTerm
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function searchBookmarks(int $userId, string $searchTerm, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->searchBookmarks($userId, $searchTerm, $perPage);
    }

    /**
     * Normalize collection name.
     *
     * @param string $name
     * @return string
     */
    protected function normalizeCollectionName(string $name): string
    {
        $name = trim(strtolower($name));
        $name = preg_replace('/\s+/', '_', $name);
        $name = preg_replace('/[^a-z0-9_]/', '', $name);

        return $name ?: self::DEFAULT_COLLECTION;
    }

    /**
     * Check if collection name is reserved.
     *
     * @param string $name
     * @return bool
     */
    public function isReservedCollection(string $name): bool
    {
        return in_array(strtolower($name), self::RESERVED_COLLECTIONS);
    }

    /**
     * Get or create collection for user.
     *
     * @param int $userId
     * @param string $name
     * @return array
     */
    public function getOrCreateCollection(int $userId, string $name): array
    {
        $name = $this->normalizeCollectionName($name);

        return $this->repository->getOrCreateCollection($userId, $name);
    }

    /**
     * Delete all bookmarks by user.
     *
     * @param int $userId
     * @return int Number of deleted bookmarks
     */
    public function deleteByUser(int $userId): int
    {
        return $this->repository->deleteByUser($userId);
    }

    /**
     * Delete all bookmarks for a post.
     *
     * @param int $postId
     * @return int Number of deleted bookmarks
     */
    public function deleteForPost(int $postId): int
    {
        return $this->repository->deleteForPost($postId);
    }

    /**
     * Get bookmark status for a post.
     *
     * @param int $userId
     * @param int $postId
     * @return array ['bookmarked' => bool, 'collections' => array]
     */
    public function getBookmarkStatus(int $userId, int $postId): array
    {
        $bookmarks = Bookmark::where('user_id', $userId)
            ->where('post_id', $postId)
            ->get(['collection_name', 'created_at']);

        $collections = $bookmarks->pluck('collection_name')->toArray();

        return [
            'bookmarked' => $bookmarks->isNotEmpty(),
            'collections' => $collections,
            'bookmarked_at' => $bookmarks->first()?->created_at?->toIso8601String(),
        ];
    }

    /**
     * Get collection stats for user.
     *
     * @param int $userId
     * @return array
     */
    public function getCollectionStats(int $userId): array
    {
        $collections = $this->getUserCollections($userId);
        $totalBookmarks = $this->getUserBookmarkCount($userId);

        return [
            'total_bookmarks' => $totalBookmarks,
            'total_collections' => $collections->count(),
            'collections' => $collections->map(function ($collection) {
                return [
                    'name' => $collection->collection_name,
                    'display_name' => $collection->display_name,
                    'count' => $collection->count,
                ];
            })->toArray(),
        ];
    }

    /**
     * Move multiple bookmarks to a collection.
     *
     * @param int $userId
     * @param array $bookmarkIds
     * @param string $collectionName
     * @return int Number of bookmarks moved
     */
    public function bulkMoveToCollection(int $userId, array $bookmarkIds, string $collectionName): int
    {
        $collectionName = $this->normalizeCollectionName($collectionName);
        $moved = 0;

        foreach ($bookmarkIds as $bookmarkId) {
            if ($this->repository->moveToCollection($bookmarkId, $userId, $collectionName)) {
                $moved++;
            }
        }

        return $moved;
    }

    /**
     * Get bookmarks with posts that are no longer published.
     *
     * @param int $userId
     * @return Collection
     */
    public function getUnpublishedBookmarks(int $userId): Collection
    {
        return Bookmark::where('user_id', $userId)
            ->whereHas('post', function ($q) {
                $q->where('status', '!=', Post::STATUS_PUBLISHED);
            })
            ->with('post')
            ->get();
    }

    /**
     * Clean up unpublished bookmarks for user.
     *
     * @param int $userId
     * @return int Number of deleted bookmarks
     */
    public function cleanupUnpublishedBookmarks(int $userId): int
    {
        $unpublished = $this->getUnpublishedBookmarks($userId);

        foreach ($unpublished as $bookmark) {
            $bookmark->delete();
        }

        return $unpublished->count();
    }
}
