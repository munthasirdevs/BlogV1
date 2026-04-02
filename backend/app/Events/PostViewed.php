<?php

namespace App\Events;

use App\Models\Post;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Class PostViewed
 *
 * Fired when a post is viewed.
 */
class PostViewed
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The viewed post.
     */
    public Post $post;

    /**
     * The user who viewed the post (null if guest).
     */
    public ?User $user;

    /**
     * The IP address of the viewer.
     */
    public string $ipAddress;

    /**
     * Create a new event instance.
     */
    public function __construct(Post $post, ?User $user, string $ipAddress)
    {
        $this->post = $post;
        $this->user = $user;
        $this->ipAddress = $ipAddress;
    }
}
