<?php

namespace Database\Factories;

use App\Models\AnalyticsEvent;
use App\Models\User;
use App\Models\Post;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AnalyticsEvent>
 */
class AnalyticsEventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event_type' => fake()->randomElement([
                AnalyticsEvent::TYPE_PAGE_VIEW,
                AnalyticsEvent::TYPE_PAGE_VIEW,
                AnalyticsEvent::TYPE_POST_VIEW,
                AnalyticsEvent::TYPE_POST_VIEW,
                AnalyticsEvent::TYPE_POST_CLICK,
                AnalyticsEvent::TYPE_SEARCH,
                AnalyticsEvent::TYPE_CATEGORY_VIEW,
            ]),
            'user_id' => User::factory(),
            'post_id' => null,
            'session_id' => Str::random(64),
            'metadata' => null,
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
            'referrer' => fake()->optional(0.4)->url(),
            'url' => fake()->url(),
            'occurred_at' => fake()->dateTimeBetween('-30 days', 'now'),
        ];
    }

    /**
     * Indicate that the event is a page view.
     */
    public function pageView(): static
    {
        return $this->state(fn (array $attributes) => [
            'event_type' => AnalyticsEvent::TYPE_PAGE_VIEW,
            'post_id' => null,
        ]);
    }

    /**
     * Indicate that the event is a post view.
     */
    public function postView(?int $postId = null): static
    {
        return $this->state(fn (array $attributes) => [
            'event_type' => AnalyticsEvent::TYPE_POST_VIEW,
            'post_id' => $postId ?? Post::factory(),
            'metadata' => [
                'time_on_page' => fake()->numberBetween(10, 600),
                'scroll_depth' => fake()->numberBetween(0, 100),
            ],
        ]);
    }

    /**
     * Indicate that the event is a post click.
     */
    public function postClick(?int $postId = null): static
    {
        return $this->state(fn (array $attributes) => [
            'event_type' => AnalyticsEvent::TYPE_POST_CLICK,
            'post_id' => $postId ?? Post::factory(),
        ]);
    }

    /**
     * Indicate that the event is a search.
     */
    public function search(): static
    {
        return $this->state(fn (array $attributes) => [
            'event_type' => AnalyticsEvent::TYPE_SEARCH,
            'post_id' => null,
            'metadata' => [
                'search_query' => fake()->words(3, true),
                'results_count' => fake()->numberBetween(0, 100),
            ],
        ]);
    }

    /**
     * Indicate that the event is a category view.
     */
    public function categoryView(): static
    {
        return $this->state(fn (array $attributes) => [
            'event_type' => AnalyticsEvent::TYPE_CATEGORY_VIEW,
            'post_id' => null,
        ]);
    }

    /**
     * Indicate that the event is a tag view.
     */
    public function tagView(): static
    {
        return $this->state(fn (array $attributes) => [
            'event_type' => AnalyticsEvent::TYPE_TAG_VIEW,
            'post_id' => null,
        ]);
    }

    /**
     * Indicate that the event is by a specific user.
     */
    public function byUser(?int $userId = null): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $userId ?? User::factory(),
        ]);
    }

    /**
     * Indicate that the event is anonymous (no user).
     */
    public function anonymous(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => null,
        ]);
    }

    /**
     * Indicate that the event is from today.
     */
    public function today(): static
    {
        return $this->state(fn (array $attributes) => [
            'occurred_at' => fake()->dateTimeBetween('today midnight', 'now'),
        ]);
    }

    /**
     * Indicate that the event is from this week.
     */
    public function thisWeek(): static
    {
        return $this->state(fn (array $attributes) => [
            'occurred_at' => fake()->dateTimeBetween('monday', 'now'),
        ]);
    }

    /**
     * Indicate that the event is from this month.
     */
    public function thisMonth(): static
    {
        return $this->state(fn (array $attributes) => [
            'occurred_at' => fake()->dateTimeBetween('first day of this month', 'now'),
        ]);
    }

    /**
     * Indicate that the event is from a specific session.
     */
    public function inSession(string $sessionId): static
    {
        return $this->state(fn (array $attributes) => [
            'session_id' => $sessionId,
        ]);
    }

    /**
     * Indicate that the event is from a search engine.
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
     * Indicate that the event is from social media.
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
}
