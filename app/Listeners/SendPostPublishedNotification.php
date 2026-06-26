<?php

namespace App\Listeners;

use App\Events\PostPublished;
use App\Models\User;
use App\Notifications\ContentApprovalNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;

class SendPostPublishedNotification implements ShouldQueue
{
    public function handle(PostPublished $event): void
    {
        $admins = User::permission(['publish_posts', 'edit_posts'])->get();
        Notification::send($admins, new ContentApprovalNotification($event->post, 'published'));
    }
}
