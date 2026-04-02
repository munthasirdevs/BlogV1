<?php

namespace Database\Factories;

use App\Models\EmailTracking;
use App\Models\Subscription;
use App\Models\EmailCampaign;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\EmailTracking>
 */
class EmailTrackingFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = EmailTracking::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'subscription_id' => Subscription::factory(),
            'email_campaign_id' => null,
            'email_type' => 'newsletter',
            'subject' => fake()->sentence(6),
            'message_id' => '<' . Str::random(32) . '@mail.example.com>',
            'sent_at' => fake()->dateTimeBetween('-1 month', 'now'),
            'delivered_at' => null,
            'opened_at' => null,
            'open_count' => 0,
            'clicked_at' => null,
            'click_count' => 0,
            'bounced_at' => null,
            'bounce_type' => null,
            'bounce_reason' => null,
            'complained_at' => null,
            'complaint_type' => null,
            'is_unsubscribed' => false,
            'unsubscribed_at' => null,
            'ip_address' => fake()->optional(0.5)->ipv4(),
            'user_agent' => fake()->optional(0.5)->userAgent(),
            'metadata' => null,
        ];
    }

    /**
     * Indicate that the email was delivered.
     */
    public function delivered(): static
    {
        return $this->state(fn (array $attributes) => [
            'delivered_at' => fake()->dateTimeBetween($attributes['sent_at'], now()),
        ]);
    }

    /**
     * Indicate that the email was opened.
     */
    public function opened(int $count = 1): static
    {
        return $this->state(fn (array $attributes) => [
            'open_count' => $count,
            'opened_at' => fake()->dateTimeBetween($attributes['sent_at'] ?? now(), now()),
        ]);
    }

    /**
     * Indicate that a link was clicked.
     */
    public function clicked(int $count = 1): static
    {
        return $this->state(fn (array $attributes) => [
            'click_count' => $count,
            'clicked_at' => fake()->dateTimeBetween($attributes['sent_at'] ?? now(), now()),
        ]);
    }

    /**
     * Indicate that the email bounced.
     */
    public function bounced(string $type = 'soft', ?string $reason = null): static
    {
        return $this->state(fn (array $attributes) => [
            'bounced_at' => fake()->dateTimeBetween($attributes['sent_at'] ?? now(), now()),
            'bounce_type' => $type,
            'bounce_reason' => $reason ?? fake()->sentence(5),
        ]);
    }

    /**
     * Indicate a hard bounce.
     */
    public function hardBounced(?string $reason = null): static
    {
        return $this->bounced('hard', $reason);
    }

    /**
     * Indicate a soft bounce.
     */
    public function softBounced(?string $reason = null): static
    {
        return $this->bounced('soft', $reason);
    }

    /**
     * Indicate that a spam complaint was received.
     */
    public function complained(string $type = 'spam'): static
    {
        return $this->state(fn (array $attributes) => [
            'complained_at' => fake()->dateTimeBetween($attributes['sent_at'] ?? now(), now()),
            'complaint_type' => $type,
        ]);
    }

    /**
     * Indicate that the recipient unsubscribed.
     */
    public function unsubscribed(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_unsubscribed' => true,
            'unsubscribed_at' => fake()->dateTimeBetween($attributes['sent_at'] ?? now(), now()),
        ]);
    }

    /**
     * Set the email type.
     */
    public function type(string $type): static
    {
        return $this->state(fn (array $attributes) => [
            'email_type' => $type,
        ]);
    }

    /**
     * Associate with a campaign.
     */
    public function forCampaign(?int $campaignId = null): static
    {
        return $this->state(fn (array $attributes) => [
            'email_campaign_id' => $campaignId ?? EmailCampaign::factory(),
        ]);
    }

    /**
     * Create tracking for a specific subscription.
     */
    public function forSubscription(?int $subscriptionId = null): static
    {
        return $this->state(fn (array $attributes) => [
            'subscription_id' => $subscriptionId ?? Subscription::factory(),
        ]);
    }

    /**
     * Create tracking with high engagement.
     */
    public function highEngagement(): static
    {
        return $this->opened(fake()->numberBetween(5, 20))
            ->clicked(fake()->numberBetween(2, 10));
    }

    /**
     * Create tracking with low engagement.
     */
    public function lowEngagement(): static
    {
        return $this->opened(fake()->numberBetween(0, 1));
    }
}
