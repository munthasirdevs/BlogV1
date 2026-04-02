<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->name();

        return [
            'name' => $name,
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'avatar' => fake()->optional(0.3)->imageUrl(200, 200, 'people', true, $name),
            'bio' => fake()->optional(0.7)->paragraphs(2, true),
            'role' => fake()->randomElement(['user', 'admin', 'editor', 'moderator']),
            'status' => fake()->randomElement(['active', 'active', 'active', 'banned', 'suspended']),
            'website' => fake()->optional(0.3)->url(),
            'twitter' => fake()->optional(0.3)->userName(),
            'github' => fake()->optional(0.3)->userName(),
            'linkedin' => fake()->optional(0.2)->userName(),
            'facebook' => fake()->optional(0.2)->userName(),
            'location' => fake()->optional(0.4)->city(),
            'timezone' => fake()->randomElement(['UTC', 'America/New_York', 'America/Los_Angeles', 'Europe/London', 'Europe/Paris', 'Asia/Tokyo', 'Asia/Kolkata']),
            'preferences' => [
                'newsletter' => fake()->boolean(70),
                'notifications' => fake()->boolean(80),
                'dark_mode' => fake()->boolean(50),
            ],
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Indicate that the user is an admin.
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'admin',
        ]);
    }

    /**
     * Indicate that the user is an editor.
     */
    public function editor(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'editor',
        ]);
    }

    /**
     * Indicate that the user is a moderator.
     */
    public function moderator(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'moderator',
        ]);
    }

    /**
     * Indicate that the user is a regular user.
     */
    public function regularUser(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'user',
        ]);
    }

    /**
     * Indicate that the user is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
        ]);
    }

    /**
     * Indicate that the user is banned.
     */
    public function banned(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'banned',
        ]);
    }

    /**
     * Indicate that the user has a complete profile.
     */
    public function completeProfile(): static
    {
        return $this->state(fn (array $attributes) => [
            'avatar' => fake()->imageUrl(200, 200, 'people', true),
            'bio' => fake()->paragraphs(3, true),
            'website' => fake()->url(),
            'twitter' => fake()->userName(),
            'github' => fake()->userName(),
            'linkedin' => fake()->userName(),
            'location' => fake()->city(),
        ]);
    }

    /**
     * Indicate that the user is an author (has published posts).
     */
    public function author(): static
    {
        return $this->afterCreating(function ($user) {
            // Create some posts for this user
            \App\Models\Post::factory()
                ->count(rand(3, 10))
                ->published()
                ->for($user, 'author')
                ->create();
        });
    }

    /**
     * Configure the model with a specific role.
     */
    public function withRole(string $role): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => $role,
        ]);
        // Note: Spatie roles are not created as we use the role column directly
    }
}
