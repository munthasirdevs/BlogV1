<?php

namespace Database\Factories;

use App\Models\PostView;
use App\Models\User;
use App\Models\Post;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PostView>
 */
class PostViewFactory extends Factory
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
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
            'session_id' => Str::random(64),
            'referrer' => fake()->optional(0.4)->url(),
            'time_on_page' => fake()->optional(0.6)->numberBetween(10, 600),
            'is_unique' => true,
            'viewed_at' => fake()->dateTimeBetween('-30 days', 'now'),
        ];
    }

    /**
     * Indicate that the view is unique.
     */
    public function unique(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_unique' => true,
        ]);
    }

    /**
     * Indicate that the view is not unique (repeat view).
     */
    public function repeat(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_unique' => false,
        ]);
    }

    /**
     * Indicate that the view is by a logged-in user.
     */
    public function byLoggedInUser(?int $userId = null): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $userId ?? User::factory(),
        ]);
    }

    /**
     * Indicate that the view is anonymous.
     */
    public function anonymous(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => null,
        ]);
    }

    /**
     * Indicate that the view is for a specific post.
     */
    public function forPost(?int $postId = null): static
    {
        return $this->state(fn (array $attributes) => [
            'post_id' => $postId ?? Post::factory(),
        ]);
    }

    /**
     * Indicate that the view is from today.
     */
    public function today(): static
    {
        return $this->state(fn (array $attributes) => [
            'viewed_at' => fake()->dateTimeBetween('today midnight', 'now'),
        ]);
    }

    /**
     * Indicate that the view is from this week.
     */
    public function thisWeek(): static
    {
        return $this->state(fn (array $attributes) => [
            'viewed_at' => fake()->dateTimeBetween('monday', 'now'),
        ]);
    }

    /**
     * Indicate that the view is from this month.
     */
    public function thisMonth(): static
    {
        return $this->state(fn (array $attributes) => [
            'viewed_at' => fake()->dateTimeBetween('first day of this month', 'now'),
        ]);
    }

    /**
     * Indicate that the view has a long time on page.
     */
    public function longRead(): static
    {
        return $this->state(fn (array $attributes) => [
            'time_on_page' => fake()->numberBetween(300, 1800), // 5-30 minutes
        ]);
    }

    /**
     * Indicate that the view has a short time on page.
     */
    public function shortRead(): static
    {
        return $this->state(fn (array $attributes) => [
            'time_on_page' => fake()->numberBetween(5, 60), // Less than 1 minute
        ]);
    }

    /**
     * Indicate that the view is from a search engine.
     */
    public function fromSearchEngine(): static
    {
        return $this->state(fn (array $attributes) => [
            'referrer' => fake()->randomElement([
                'https://www.google.com/search?q=laravel+blog',
                'https://www.bing.com/search?q=php+tutorial',
                'https://duckduckgo.com/?q=web+development',
            ]),
        ]);
    }

    /**
     * Indicate that the view is from social media.
     */
    public function fromSocialMedia(): static
    {
        return $this->state(fn (array $attributes) => [
            'referrer' => fake()->randomElement([
                'https://twitter.com/',
                'https://www.facebook.com/',
                'https://www.linkedin.com/',
                'https://www.reddit.com/',
            ]),
        ]);
    }

    /**
     * Indicate that the view is from a specific session.
     */
    public function inSession(string $sessionId): static
    {
        return $this->state(fn (array $attributes) => [
            'session_id' => $sessionId,
        ]);
    }

    /**
     * Indicate that the view is from mobile.
     */
    public function fromMobile(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_agent' => fake()->randomElement([
                'Mozilla/5.0 (iPhone; CPU iPhone OS 14_0 like Mac OS X)',
                'Mozilla/5.0 (Linux; Android 11; SM-G991B)',
                'Mozilla/5.0 (iPad; CPU OS 14_0 like Mac OS X)',
            ]),
        ]);
    }

    /**
     * Indicate that the view is from desktop.
     */
    public function fromDesktop(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_agent' => fake()->randomElement([
                'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36',
                'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36',
            ]),
        ]);
    }
}
