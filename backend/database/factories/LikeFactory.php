<?php

namespace Database\Factories;

use App\Models\Like;
use App\Models\User;
use App\Models\Post;
use App\Models\Comment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Like>
 */
class LikeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'likeable_id' => Post::factory(),
            'likeable_type' => Post::class,
        ];
    }

    /**
     * Configure the model factory.
     */
    public function configure(): static
    {
        return $this->afterMaking(function (Like $like) {
            // Ensure likeable_type matches the likeable model
            if ($like->likeable_type === Post::class) {
                $like->likeable_id = Post::factory()->create()->id;
            } elseif ($like->likeable_type === Comment::class) {
                $like->likeable_id = Comment::factory()->create()->id;
            }
        });
    }

    /**
     * Indicate that the like is for a post.
     */
    public function forPost(?int $postId = null): static
    {
        return $this->state(fn (array $attributes) => [
            'likeable_type' => Post::class,
            'likeable_id' => $postId ?? Post::factory(),
        ]);
    }

    /**
     * Indicate that the like is for a comment.
     */
    public function forComment(?int $commentId = null): static
    {
        return $this->state(fn (array $attributes) => [
            'likeable_type' => Comment::class,
            'likeable_id' => $commentId ?? Comment::factory(),
        ]);
    }

    /**
     * Indicate that the like is by a specific user.
     */
    public function byUser(?int $userId = null): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $userId ?? User::factory(),
        ]);
    }

    /**
     * Create a like for a specific model.
     */
    public function forModel($model): static
    {
        return $this->state(fn (array $attributes) => [
            'likeable_type' => get_class($model),
            'likeable_id' => $model->id,
        ]);
    }
}
