<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\Like;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LikeController extends Controller
{
    /**
     * Toggle like on post.
     */
    public function toggle(Post $post, Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $like = Like::where('user_id', $userId)
            ->where('post_id', $post->id)
            ->first();

        if ($like) {
            // Unlike
            $like->delete();
            $post->decrement('likes_count');
            $isLiked = false;
        } else {
            // Like
            Like::create([
                'user_id' => $userId,
                'post_id' => $post->id,
            ]);
            $post->increment('likes_count');
            $isLiked = true;
        }

        return response()->json([
            'success' => true,
            'data' => [
                'is_liked' => $isLiked,
                'likes_count' => $post->likes_count,
            ],
        ]);
    }
}
