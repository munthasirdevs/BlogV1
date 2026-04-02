<?php

namespace Database\Factories;

use App\Models\Post;
use App\Models\User;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Post>
 */
class PostFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->sentence(rand(6, 12));
        $content = $this->generateContent();
        
        return [
            'user_id' => User::factory(),
            'category_id' => Category::factory(),
            'title' => $title,
            'slug' => Str::slug($title),
            'excerpt' => fake()->optional(0.9)->paragraph(2),
            'content' => $content,
            'featured_image' => fake()->optional(0.7)->imageUrl(1200, 630, 'business', true),
            'is_featured' => fake()->boolean(10),
            'reading_time' => max(1, (int) ceil(str_word_count($content) / 200)),
            'status' => 'draft',
            'views_count' => fake()->numberBetween(0, 5000),
            'likes_count' => fake()->numberBetween(0, 500),
            'comments_count' => fake()->numberBetween(0, 100),
            'published_at' => null,
            'scheduled_for' => null,
            'meta_title' => fake()->optional(0.6)->sentence(8),
            'meta_description' => fake()->optional(0.6)->paragraph(),
            'meta_keywords' => fake()->optional(0.5)->words(5),
            'custom_fields' => null,
        ];
    }

    /**
     * Indicate that the post is published.
     */
    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'published',
            'published_at' => fake()->dateTimeBetween('-1 year', 'now'),
        ]);
    }

    /**
     * Indicate that the post is a draft.
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
            'published_at' => null,
        ]);
    }

    /**
     * Indicate that the post is scheduled.
     */
    public function scheduled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'scheduled',
            'scheduled_for' => fake()->dateTimeBetween('now', '+1 month'),
        ]);
    }

    /**
     * Indicate that the post is archived.
     */
    public function archived(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'archived',
            'published_at' => fake()->dateTimeBetween('-2 years', '-6 months'),
        ]);
    }

    /**
     * Indicate that the post is featured.
     */
    public function featured(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_featured' => true,
        ]);
    }

    /**
     * Indicate that the post is popular (high views).
     */
    public function popular(): static
    {
        return $this->state(fn (array $attributes) => [
            'views_count' => fake()->numberBetween(5000, 50000),
            'likes_count' => fake()->numberBetween(500, 5000),
            'comments_count' => fake()->numberBetween(100, 1000),
        ]);
    }

    /**
     * Indicate that the post is recent.
     */
    public function recent(): static
    {
        return $this->state(fn (array $attributes) => [
            'published_at' => fake()->dateTimeBetween('-7 days', 'now'),
        ]);
    }

    /**
     * Create a post with tags.
     */
    public function withTags(int $count = 3): static
    {
        return $this->afterCreating(function ($post) use ($count) {
            $post->tags()->attach(
                \App\Models\Tag::factory()->count($count)->create()->pluck('id')
            );
        });
    }

    /**
     * Create a post with comments.
     */
    public function withComments(int $count = 5): static
    {
        return $this->afterCreating(function ($post) use ($count) {
            \App\Models\Comment::factory()
                ->count($count)
                ->for($post, 'post')
                ->create();
        });
    }

    /**
     * Create a post with likes.
     */
    public function withLikes(int $count = 10): static
    {
        return $this->afterCreating(function ($post) use ($count) {
            \App\Models\Like::factory()
                ->count($count)
                ->for($post, 'likeable')
                ->create();
        });
    }

    /**
     * Create a post with bookmarks.
     */
    public function withBookmarks(int $count = 5): static
    {
        return $this->afterCreating(function ($post) use ($count) {
            \App\Models\Bookmark::factory()
                ->count($count)
                ->for($post, 'post')
                ->create();
        });
    }

    /**
     * Generate realistic content.
     */
    private function generateContent(): string
    {
        $paragraphs = fake()->paragraphs(rand(5, 15), false);

        // Add some HTML structure
        $content = "<p>" . implode("</p>\n\n<p>", $paragraphs) . "</p>";

        // Add a heading
        $content .= "\n\n<h2>" . fake()->sentence(5) . "</h2>\n\n";
        $content .= "<p>" . implode("</p><p>", fake()->paragraphs(2, false)) . "</p>";

        // Add a list
        $content .= "\n\n<h3>Key Points</h3>\n<ul>\n";
        for ($i = 0; $i < rand(3, 5); $i++) {
            $content .= "<li>" . fake()->sentence() . "</li>\n";
        }
        $content .= "</ul>";

        // Add a quote
        $content .= "\n\n<blockquote><p>" . fake()->sentence() . "</p></blockquote>\n\n";

        // Add conclusion
        $content .= "<h2>Conclusion</h2>\n<p>" . fake()->paragraph() . "</p>";

        return $content;
    }
}
