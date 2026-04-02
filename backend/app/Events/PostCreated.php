<?php

namespace App\Events;

use App\Models\Post;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Class PostCreated
 *
 * Fired when a post is created.
 */
class PostCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The created post.
     */
    public Post $post;

    /**
     * The user who created the post.
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
