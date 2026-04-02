<?php

namespace Database\Factories;

use App\Models\Comment;
use App\Models\CommentEdit;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CommentEdit>
 */
class CommentEditFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = CommentEdit::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'comment_id' => Comment::factory(),
            'user_id' => User::factory(),
            'old_content' => fake()->paragraph(),
            'new_content' => fake()->paragraph(),
            'edit_reason' => fake()->optional(0.7)->sentence(),
            'ip_address' => fake()->ipv4(),
            'created_at' => fake()->dateTimeBetween('-1 month', 'now'),
        ];
    }

    /**
     * Indicate that the edit has a reason.
     */
    public function withReason(string $reason = 'Fixed typo'): static
    {
        return $this->state(fn (array $attributes) => [
            'edit_reason' => $reason,
        ]);
    }

    /**
     * Indicate that the edit is recent.
     */
    public function recent(): static
    {
        return $this->state(fn (array $attributes) => [
            'created_at' => now()->subMinutes(rand(1, 30)),
        ]);
    }
}
