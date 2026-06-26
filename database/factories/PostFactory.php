<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PostFactory extends Factory
{
    protected $model = Post::class;

    public function definition(): array
    {
        $title = $this->faker->sentence(6);

        return [
            'uuid' => (string) Str::uuid(),
            'author_id' => User::factory(),
            'category_id' => Category::factory(),
            'title' => $title,
            'slug' => Str::slug($title) . '-' . Str::random(4),
            'content' => $this->faker->paragraphs(10, true),
            'excerpt' => $this->faker->paragraph(2),
            'status' => 'draft',
            'visibility' => 'public',
            'content_format' => 'html',
            'is_featured' => false,
            'is_scheduled' => false,
            'word_count' => 200,
            'reading_time' => 1,
            'views_count' => 0,
            'likes_count' => 0,
            'shares_count' => 0,
            'seo_score' => 0,
            'ai_score' => 0,
            'published_at' => now(),
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'published',
            'published_at' => now(),
        ]);
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
            'published_at' => null,
        ]);
    }
}
