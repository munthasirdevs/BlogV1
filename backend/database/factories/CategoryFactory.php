<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Category>
 */
class CategoryFactory extends Factory
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
            'parent_id' => null,
            'name' => $name,
            'slug' => \Illuminate\Support\Str::slug($name),
            'description' => fake()->optional(0.8)->paragraph(),
            'color' => fake()->randomElement(['#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6', '#EC4899', '#06B6D4', '#84CC16']),
            'icon' => fake()->optional(0.5)->randomElement(['📝', '💻', '🎨', '📊', '🔧', '📱', '🌐', '🚀']),
            'sort_order' => fake()->numberBetween(0, 100),
            'is_featured' => fake()->boolean(20),
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the category is featured.
     */
    public function featured(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_featured' => true,
        ]);
    }

    /**
     * Indicate that the category is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the category is a child category.
     */
    public function child(?int $parentId = null): static
    {
        return $this->state(fn (array $attributes) => [
            'parent_id' => $parentId ?? Category::factory(),
        ]);
    }

    /**
     * Create a category with children.
     */
    public function withChildren(int $count = 3): static
    {
        return $this->afterCreating(function ($category) use ($count) {
            Category::factory()
                ->count($count)
                ->child($category->id)
                ->create();
        });
    }

    /**
     * Create a category with posts.
     */
    public function withPosts(int $count = 5): static
    {
        return $this->afterCreating(function ($category) use ($count) {
            \App\Models\Post::factory()
                ->count($count)
                ->published()
                ->for($category, 'category')
                ->create();
        });
    }
}
