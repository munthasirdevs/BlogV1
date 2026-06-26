<?php

namespace App\Observers;

use App\Models\Comment;

class CommentObserver
{
    public function created(Comment $comment): void
    {
        if ($post = $comment->post) {
            $post->increment('comments_count');
        }
    }

    public function deleted(Comment $comment): void
    {
        if ($post = $comment->post) {
            $post->decrement('comments_count');
        }
    }
}
