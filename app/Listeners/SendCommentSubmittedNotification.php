<?php

namespace App\Listeners;

use App\Events\CommentSubmitted;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;

class SendCommentSubmittedNotification implements ShouldQueue
{
    public function handle(CommentSubmitted $event): void
    {
        if ($event->comment->post?->author) {
            $author = $event->comment->post->author;
            $author->notify(new \App\Notifications\ContentApprovalNotification(
                $event->comment->post,
                $event->comment->status === 'spam' ? 'rejected' : 'submitted'
            ));
        }
    }
}
