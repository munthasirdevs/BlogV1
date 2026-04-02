<?php

namespace Database\Factories;

use App\Models\Bookmark;
use App\Models\User;
use App\Models\Post;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Bookmark>
 */
class BookmarkFactory extends Factory
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
            'post_id' => Post::factory(),
            'collection_name' => fake()->randomElement(['default', 'default', 'default', 'reading-list', 'favorites', 'to-read', 'references']),
            'notes' => fake()->optional(0.3)->sentence(),
        ];
    }

    /**
     * Indicate that the bookmark is in the default collection.
     */
    public function defaultCollection(): static
    {
        return $this->state(fn (array $attributes) => [
            'collection_name' => 'default',
        ]);
    }

    /**
     * Indicate that the bookmark is in a specific collection.
     */
    public function inCollection(string $collectionName): static
    {
        return $this->state(fn (array $attributes) => [
            'collection_name' => $collectionName,
        ]);
    }

    /**
     * Indicate that the bookmark has notes.
     */
    public function withNotes(): static
    {
        return $this->state(fn (array $attributes) => [
            'notes' => fake()->paragraph(),
        ]);
    }

    /**
     * Indicate that the bookmark is by a specific user.
     */
    public function byUser(?int $userId = null): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $userId ?? User::factory(),
        ]);
    }

    /**
     * Indicate that the bookmark is for a specific post.
     */
    public function forPost(?int $postId = null): static
    {
        return $this->state(fn (array $attributes) => [
            'post_id' => $postId ?? Post::factory(),
        ]);
    }

    /**
     * Create bookmarks in multiple collections.
     */
    public function inMultipleCollections(array $collections = ['reading-list', 'favorites', 'to-read']): static
    {
        return $this->afterCreating(function ($bookmark) use ($collections) {
            foreach ($collections as $collection) {
                Bookmark::factory()
                    ->state([
                        'user_id' => $bookmark->user_id,
                        'post_id' => $bookmark->post_id,
                        'collection_name' => $collection,
                    ])
                    ->create();
            }
        });
    }
}
