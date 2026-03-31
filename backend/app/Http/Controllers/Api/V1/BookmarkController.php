<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\PostResource;
use App\Models\Post;
use App\Models\Bookmark;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookmarkController extends Controller
{
    /**
     * Toggle bookmark on post.
     */
    public function toggle(Post $post, Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $bookmark = Bookmark::where('user_id', $userId)
            ->where('post_id', $post->id)
            ->first();

        if ($bookmark) {
            // Remove bookmark
            $bookmark->delete();
            $isBookmarked = false;
        } else {
            // Add bookmark
            Bookmark::create([
                'user_id' => $userId,
                'post_id' => $post->id,
            ]);
            $isBookmarked = true;
        }

        return response()->json([
            'success' => true,
            'data' => [
                'is_bookmarked' => $isBookmarked,
            ],
        ]);
    }

    /**
     * Get user bookmarks.
     */
    public function index(Request $request): JsonResponse
    {
        $bookmarks = $request->user()
            ->bookmarks()
            ->with('post.author')
            ->latest()
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => PostResource::collection($bookmarks->pluck('post')),
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
     */
    public function destroy(Post $post, Request $request): JsonResponse
    {
        Bookmark::where('user_id', $request->user()->id)
            ->where('post_id', $post->id)
            ->delete();

        return response()->json([
            'success' => true,
            'message' => 'Bookmark removed',
        ], 204);
    }
}
