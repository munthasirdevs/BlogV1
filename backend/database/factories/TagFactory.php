<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tag>
 */
class TagFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);
        
        return [
            'name' => $name,
            'slug' => \Illuminate\Support\Str::slug($name),
            'description' => fake()->optional(0.6)->sentence(),
            'color' => fake()->randomElement(['#6B7280', '#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6', '#EC4899', '#06B6D4']),
            'posts_count' => 0,
            'is_featured' => fake()->boolean(15),
        ];
    }

    /**
     * Indicate that the tag is featured.
     */
    public function featured(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_featured' => true,
        ]);
    }

    /**
     * Indicate that the tag is popular.
     */
    public function popular(): static
    {
        return $this->state(fn (array $attributes) => [
            'posts_count' => fake()->numberBetween(50, 500),
        ]);
    }

    /**
     * Create a tag with posts.
     */
    public function withPosts(int $count = 5): static
    {
        return $this->afterCreating(function ($tag) use ($count) {
            \App\Models\Post::factory()
                ->count($count)
                ->published()
                ->create()
                ->each(function ($post) use ($tag) {
                    $post->tags()->attach($tag->id);
                });
        });
    }
}
