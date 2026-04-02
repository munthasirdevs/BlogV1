<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Interaction\BookmarkCollectionRequest;
use App\Http\Requests\Interaction\BookmarkRequest;
use App\Models\Post;
use App\Services\BookmarkService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Class BookmarkController
 *
 * Controller for managing bookmark operations with collection support.
 */
class BookmarkController extends Controller
{
    /**
     * The bookmark service instance.
     *
     * @var BookmarkService
     */
    protected BookmarkService $bookmarkService;

    /**
     * BookmarkController constructor.
     *
     * @param BookmarkService $bookmarkService
     */
    public function __construct(BookmarkService $bookmarkService)
    {
        $this->bookmarkService = $bookmarkService;
    }

    /**
     * Toggle bookmark on a post.
     *
     * @param Post $post
     * @param BookmarkRequest $request
     * @return JsonResponse
     */
    public function toggle(Post $post, BookmarkRequest $request): JsonResponse
    {
        $userId = $request->user()->id;
        $collection = $request->getCollection();
        $notes = $request->getNotes();

        $result = $this->bookmarkService->toggle($userId, $post->id, $collection, $notes);

        return response()->json([
            'success' => true,
            'data' => [
                'bookmarked' => $result['bookmarked'],
                'bookmark' => $result['bookmark'] ? [
                    'id' => $result['bookmark']->id,
                    'collection' => $result['bookmark']->collection_name,
                    'notes' => $result['bookmark']->notes,
                    'created_at' => $result['bookmark']->created_at->toIso8601String(),
                ] : null,
                'action' => $result['action'],
            ],
            'message' => $result['bookmarked'] ? 'Post bookmarked successfully' : 'Bookmark removed successfully',
        ]);
    }

    /**
     * Get user's bookmarks.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $collection = $request->get('collection');
        $perPage = $request->get('per_page', 15);

        $bookmarks = $this->bookmarkService->getUserBookmarks($userId, $collection, $perPage);

        return response()->json([
            'success' => true,
            'data' => $bookmarks->getCollection()->map(function ($bookmark) {
                return [
                    'id' => $bookmark->id,
                    'post' => [
                        'id' => $bookmark->post->id,
                        'title' => $bookmark->post->title,
                        'slug' => $bookmark->post->slug,
                        'excerpt' => $bookmark->post->excerpt,
                        'featured_image' => $bookmark->post->featured_image,
                        'reading_time' => $bookmark->post->reading_time,
                        'published_at' => $bookmark->post->published_at?->toIso8601String(),
                        'author' => [
                            'id' => $bookmark->post->author->id,
                            'name' => $bookmark->post->author->name,
                            'avatar' => $bookmark->post->author->avatar,
                        ],
                        'category' => [
                            'id' => $bookmark->post->category->id,
                            'name' => $bookmark->post->category->name,
                            'slug' => $bookmark->post->category->slug,
                        ],
                    ],
                    'collection' => [
                        'name' => $bookmark->collection_name,
                        'display_name' => ucfirst($bookmark->collection_name),
                    ],
                    'notes' => $bookmark->notes,
                    'bookmarked_at' => $bookmark->created_at->toIso8601String(),
                ];
            }),
            'meta' => [
                'current_page' => $bookmarks->currentPage(),
                'per_page' => $bookmarks->perPage(),
                'total' => $bookmarks->total(),
                'total_pages' => $bookmarks->lastPage(),
            ],
        ]);
    }

    /**
     * Remove bookmark.
     *
     * @param Post $post
     * @param Request $request
     * @return JsonResponse
     */
    public function destroy(Post $post, Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $collection = $request->get('collection');

        $removed = $this->bookmarkService->removeBookmark($userId, $post->id, $collection);

        if (!$removed) {
            return response()->json([
                'success' => false,
                'message' => 'Bookmark not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Bookmark removed successfully',
        ], 200);
    }

    /**
     * Get user's bookmark collections.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getCollections(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $collections = $this->bookmarkService->getUserCollections($userId);

        return response()->json([
            'success' => true,
            'data' => $collections->map(function ($collection) {
                return [
                    'name' => $collection->collection_name,
                    'display_name' => $collection->display_name,
                    'count' => $collection->count,
                ];
            }),
        ]);
    }

    /**
     * Create a bookmark collection.
     *
     * @param BookmarkCollectionRequest $request
     * @return JsonResponse
     */
    public function createCollection(BookmarkCollectionRequest $request): JsonResponse
    {
        $userId = $request->user()->id;
        $name = $request->getName();

        $collection = $this->bookmarkService->createCollection($userId, $name);

        return response()->json([
            'success' => true,
            'data' => $collection,
            'message' => 'Collection created successfully',
        ], 201);
    }

    /**
     * Update a bookmark collection name.
     *
     * @param BookmarkCollectionRequest $request
     * @param string $collection
     * @return JsonResponse
     */
    public function updateCollection(BookmarkCollectionRequest $request, string $collection): JsonResponse
    {
        $userId = $request->user()->id;
        $newName = $request->getName();

        try {
            $updated = $this->bookmarkService->renameCollection($userId, $collection, $newName);

            return response()->json([
                'success' => true,
                'data' => [
                    'old_name' => $collection,
                    'new_name' => $newName,
                    'updated_count' => $updated,
                ],
                'message' => 'Collection renamed successfully',
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Delete a bookmark collection.
     *
     * @param Request $request
     * @param string $collection
     * @return JsonResponse
     */
    public function deleteCollection(Request $request, string $collection): JsonResponse
    {
        $userId = $request->user()->id;
        $moveToDefault = $request->boolean('move_to_default', false);

        try {
            $deleted = $this->bookmarkService->deleteCollection($userId, $collection, $moveToDefault);

            return response()->json([
                'success' => true,
                'data' => [
                    'collection' => $collection,
                    'affected_count' => $deleted,
                    'action' => $moveToDefault ? 'moved_to_default' : 'deleted',
                ],
                'message' => $moveToDefault 
                    ? 'Bookmarks moved to default collection' 
                    : 'Collection deleted successfully',
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Assign bookmark to a collection.
     *
     * @param Request $request
     * @param int $bookmarkId
     * @return JsonResponse
     */
    public function assignCollection(Request $request, int $bookmarkId): JsonResponse
    {
        $userId = $request->user()->id;

        $request->validate([
            'collection' => 'required|string|max:50',
        ]);

        $collection = $request->input('collection');

        $bookmark = $this->bookmarkService->assignToCollection($bookmarkId, $userId, $collection);

        if (!$bookmark) {
            return response()->json([
                'success' => false,
                'message' => 'Bookmark not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'bookmark_id' => $bookmark->id,
                'collection' => $bookmark->collection_name,
            ],
            'message' => 'Bookmark assigned to collection successfully',
        ]);
    }

    /**
     * Get bookmark with collection info.
     *
     * @param Request $request
     * @param int $bookmarkId
     * @return JsonResponse
     */
    public function getBookmarkCollection(Request $request, int $bookmarkId): JsonResponse
    {
        $userId = $request->user()->id;

        $bookmark = $this->bookmarkService->getBookmarkWithCollection($bookmarkId, $userId);

        if (!$bookmark) {
            return response()->json([
                'success' => false,
                'message' => 'Bookmark not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $bookmark->id,
                'post' => [
                    'id' => $bookmark->post->id,
                    'title' => $bookmark->post->title,
                    'slug' => $bookmark->post->slug,
                ],
                'collection' => [
                    'name' => $bookmark->collection_name,
                    'display_name' => ucfirst($bookmark->collection_name),
                ],
                'notes' => $bookmark->notes,
                'bookmarked_at' => $bookmark->created_at->toIso8601String(),
            ],
        ]);
    }

    /**
     * Update bookmark notes.
     *
     * @param BookmarkRequest $request
     * @param int $bookmarkId
     * @return JsonResponse
     */
    public function updateNotes(BookmarkRequest $request, int $bookmarkId): JsonResponse
    {
        $userId = $request->user()->id;
        $notes = $request->getNotes();

        $bookmark = $this->bookmarkService->updateNotes($bookmarkId, $userId, $notes);

        if (!$bookmark) {
            return response()->json([
                'success' => false,
                'message' => 'Bookmark not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $bookmark->id,
                'notes' => $bookmark->notes,
            ],
            'message' => 'Bookmark notes updated successfully',
        ]);
    }

    /**
     * Get bookmarks by collection.
     *
     * @param Request $request
     * @param string $collection
     * @return JsonResponse
     */
    public function getBookmarksByCollection(Request $request, string $collection): JsonResponse
    {
        $userId = $request->user()->id;
        $perPage = $request->get('per_page', 15);

        $bookmarks = $this->bookmarkService->getByCollection($userId, $collection, $perPage);

        return response()->json([
            'success' => true,
            'data' => $bookmarks->getCollection()->map(function ($bookmark) {
                return [
                    'id' => $bookmark->id,
                    'post' => [
                        'id' => $bookmark->post->id,
                        'title' => $bookmark->post->title,
                        'slug' => $bookmark->post->slug,
                        'excerpt' => $bookmark->post->excerpt,
                        'featured_image' => $bookmark->post->featured_image,
                        'published_at' => $bookmark->post->published_at?->toIso8601String(),
                    ],
                    'notes' => $bookmark->notes,
                    'bookmarked_at' => $bookmark->created_at->toIso8601String(),
                ];
            }),
            'meta' => [
                'current_page' => $bookmarks->currentPage(),
                'per_page' => $bookmarks->perPage(),
                'total' => $bookmarks->total(),
                'total_pages' => $bookmarks->lastPage(),
            ],
        ]);
    }

    /**
     * Get bookmark statistics for user.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getStats(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $stats = $this->bookmarkService->getCollectionStats($userId);

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Search user's bookmarks.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function search(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $searchTerm = $request->get('q', '');
        $perPage = $request->get('per_page', 15);

        if (empty($searchTerm)) {
            return response()->json([
                'success' => false,
                'message' => 'Search term is required',
            ], 400);
        }

        $bookmarks = $this->bookmarkService->searchBookmarks($userId, $searchTerm, $perPage);

        return response()->json([
            'success' => true,
            'data' => $bookmarks->getCollection()->map(function ($bookmark) {
                return [
                    'id' => $bookmark->id,
                    'post' => [
                        'id' => $bookmark->post->id,
                        'title' => $bookmark->post->title,
                        'slug' => $bookmark->post->slug,
                        'excerpt' => $bookmark->post->excerpt,
                    ],
                    'collection' => [
                        'name' => $bookmark->collection_name,
                        'display_name' => ucfirst($bookmark->collection_name),
                    ],
                    'bookmarked_at' => $bookmark->created_at->toIso8601String(),
                ];
            }),
            'meta' => [
                'current_page' => $bookmarks->currentPage(),
                'per_page' => $bookmarks->perPage(),
                'total' => $bookmarks->total(),
                'total_pages' => $bookmarks->lastPage(),
            ],
        ]);
    }
}
