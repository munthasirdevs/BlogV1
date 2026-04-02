<?php

namespace App\Events;

use App\Models\Post;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Class PostDeleted
 *
 * Fired when a post is deleted (soft deleted).
 */
class PostDeleted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The deleted post.
     */
    public Post $post;

    /**
     * The user who deleted the post.
     */
    public User $user;

    /**
     * Create a new event instance.
     */
    public function __construct(Post $post, User $user)
    {
        $this->post = $post;
        $this->user = $user;
    }
}
