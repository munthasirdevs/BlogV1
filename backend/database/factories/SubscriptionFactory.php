<?php

namespace Database\Factories;

use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Subscription>
 */
class SubscriptionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'email' => fake()->unique()->safeEmail(),
            'user_id' => User::factory(),
            'token' => Str::random(64),
            'subscribed_at' => fake()->dateTimeBetween('-1 year', 'now'),
            'confirmed_at' => null,
            'unsubscribed_at' => null,
            'is_confirmed' => false,
            'is_active' => true,
            'preferences' => [
                'new_posts' => true,
                'weekly_digest' => fake()->boolean(30),
                'newsletter' => fake()->boolean(50),
            ],
            'frequency' => fake()->randomElement(['instant', 'instant', 'daily', 'weekly', 'monthly']),
            'ip_address' => fake()->optional(0.5)->ipv4(),
            'user_agent' => fake()->optional(0.5)->userAgent(),
        ];
    }

    /**
     * Indicate that the subscription is confirmed.
     */
    public function confirmed(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_confirmed' => true,
            'confirmed_at' => fake()->dateTimeBetween('-1 year', 'now'),
        ]);
    }

    /**
     * Indicate that the subscription is unconfirmed.
     */
    public function unconfirmed(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_confirmed' => false,
            'confirmed_at' => null,
        ]);
    }

    /**
     * Indicate that the subscription is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
            'unsubscribed_at' => null,
        ]);
    }

    /**
     * Indicate that the subscription is unsubscribed.
     */
    public function unsubscribed(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
            'unsubscribed_at' => fake()->dateTimeBetween('-6 months', 'now'),
        ]);
    }

    /**
     * Indicate that the subscription has instant frequency.
     */
    public function instant(): static
    {
        return $this->state(fn (array $attributes) => [
            'frequency' => 'instant',
        ]);
    }

    /**
     * Indicate that the subscription has daily frequency.
     */
    public function daily(): static
    {
        return $this->state(fn (array $attributes) => [
            'frequency' => 'daily',
        ]);
    }

    /**
     * Indicate that the subscription has weekly frequency.
     */
    public function weekly(): static
    {
        return $this->state(fn (array $attributes) => [
            'frequency' => 'weekly',
        ]);
    }

    /**
     * Indicate that the subscription has monthly frequency.
     */
    public function monthly(): static
    {
        return $this->state(fn (array $attributes) => [
            'frequency' => 'monthly',
        ]);
    }

    /**
     * Indicate that the subscription is for a specific user.
     */
    public function forUser(?int $userId = null): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $userId ?? User::factory(),
        ]);
    }

    /**
     * Indicate that the subscription wants weekly digest.
     */
    public function wantsWeeklyDigest(): static
    {
        return $this->state(fn (array $attributes) => [
            'preferences' => [
                'new_posts' => true,
                'weekly_digest' => true,
                'newsletter' => true,
            ],
        ]);
    }

    /**
     * Create a subscription with a specific email.
     */
    public function withEmail(string $email): static
    {
        return $this->state(fn (array $attributes) => [
            'email' => $email,
        ]);
    }
}
