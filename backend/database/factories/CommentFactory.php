<?php

namespace Database\Factories;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Comment>
 */
class CommentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'post_id' => Post::factory(),
            'user_id' => User::factory(),
            'parent_id' => null,
            'content' => fake()->paragraphs(rand(1, 3), true),
            'status' => fake()->randomElement(['pending', 'approved', 'approved', 'approved', 'rejected']),
            'depth' => 0,
            'is_edited' => fake()->boolean(10),
            'likes_count' => fake()->numberBetween(0, 50),
            'ip_address' => fake()->optional(0.5)->ipv4(),
            'user_agent' => fake()->optional(0.5)->userAgent(),
        ];
    }

    /**
     * Indicate that the comment is approved.
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
        ]);
    }

    /**
     * Indicate that the comment is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
        ]);
    }

    /**
     * Indicate that the comment is rejected.
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'rejected',
        ]);
    }

    /**
     * Indicate that the comment is spam.
     */
    public function spam(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'spam',
        ]);
    }

    /**
     * Indicate that the comment is a reply.
     */
    public function reply(?int $parentId = null): static
    {
        return $this->state(fn (array $attributes) => [
            'parent_id' => $parentId ?? Comment::factory(),
            'depth' => 1,
        ]);
    }

    /**
     * Indicate that the comment is edited.
     */
    public function edited(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_edited' => true,
        ]);
    }

    /**
     * Indicate that the comment has many likes.
     */
    public function popular(): static
    {
        return $this->state(fn (array $attributes) => [
            'likes_count' => fake()->numberBetween(50, 500),
        ]);
    }

    /**
     * Create a comment with replies.
     */
    public function withReplies(int $count = 3): static
    {
        return $this->afterCreating(function ($comment) use ($count) {
            Comment::factory()
                ->count($count)
                ->reply($comment->id)
                ->create();
        });
    }

    /**
     * Create a comment with likes.
     */
    public function withLikes(int $count = 5): static
    {
        return $this->afterCreating(function ($comment) use ($count) {
            \App\Models\Like::factory()
                ->count($count)
                ->for($comment, 'likeable')
                ->create();
        });
    }

    /**
     * Create a nested comment thread.
     */
    public function thread(int $depth = 3): static
    {
        return $this->afterCreating(function ($comment) use ($depth) {
            if ($depth > 0 && $comment->depth < Comment::MAX_DEPTH) {
                Comment::factory()
                    ->count(rand(1, 3))
                    ->reply($comment->id)
                    ->create()
                    ->each(function ($reply) use ($depth) {
                        $reply->update(['depth' => $comment->depth + 1]);
                    });
            }
        });
    }
}
