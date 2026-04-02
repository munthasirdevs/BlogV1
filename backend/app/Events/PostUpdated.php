<?php

namespace App\Events;

use App\Models\Post;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Class PostUpdated
 *
 * Fired when a post is updated.
 */
class PostUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The updated post.
     */
    public Post $post;

    /**
     * The user who updated the post.
     */
    public User $user;

    /**
     * The fields that were changed.
     */
    public array $changes;

    /**
     * Create a new event instance.
     */
    public function __construct(Post $post, User $user, array $changes = [])
    {
        $this->post = $post;
        $this->user = $user;
        $this->changes = $changes;
    }
}
