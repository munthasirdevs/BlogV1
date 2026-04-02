<?php

namespace App\Events;

use App\Models\Post;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Class PostRestored
 *
 * Fired when a deleted post is restored.
 */
class PostRestored
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The restored post.
     */
    public Post $post;

    /**
     * The user who restored the post.
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
