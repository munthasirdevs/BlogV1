<?php

namespace Database\Factories;

use App\Models\Media;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Media>
 */
class MediaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $filename = fake()->unique()->word() . '.' . fake()->fileExtension();
        
        return [
            'uploader_id' => User::factory(),
            'filename' => $filename,
            'original_filename' => fake()->optional(0.7)->word() . '.' . fake()->fileExtension(),
            'path' => 'media/' . date('Y') . '/' . date('m') . '/' . $filename,
            'disk' => 'public',
            'mime_type' => fake()->randomElement(['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'application/pdf', 'video/mp4']),
            'size' => fake()->numberBetween(10000, 5000000),
            'alt_text' => fake()->optional(0.6)->sentence(),
            'caption' => fake()->optional(0.4)->sentence(),
            'title' => fake()->optional(0.5)->words(3, true),
            'dimensions' => fake()->optional(0.7, function () {
                $width = fake()->randomElement([800, 1024, 1200, 1600, 1920]);
                $height = fake()->randomElement([600, 768, 900, 1080, 1440]);
                return ['width' => $width, 'height' => $height];
            }),
            'metadata' => fake()->optional(0.3, function () {
                return [
                    'camera' => fake()->optional()->company(),
                    'location' => fake()->optional()->city(),
                    'taken_at' => fake()->optional()->dateTime()->format('Y-m-d H:i:s'),
                ];
            }),
            'collection_name' => fake()->randomElement(['default', 'default', 'posts', 'avatars', 'banners', 'gallery']),
            'sort_order' => fake()->numberBetween(0, 100),
            'is_public' => true,
        ];
    }

    /**
     * Indicate that the media is an image.
     */
    public function image(): static
    {
        return $this->state(fn (array $attributes) => [
            'mime_type' => fake()->randomElement(['image/jpeg', 'image/png', 'image/gif', 'image/webp']),
            'dimensions' => [
                'width' => fake()->randomElement([800, 1024, 1200, 1600, 1920]),
                'height' => fake()->randomElement([600, 768, 900, 1080, 1440]),
            ],
        ]);
    }

    /**
     * Indicate that the media is a JPEG image.
     */
    public function jpeg(): static
    {
        return $this->state(fn (array $attributes) => [
            'mime_type' => 'image/jpeg',
            'filename' => fake()->unique()->word() . '.jpg',
        ]);
    }

    /**
     * Indicate that the media is a PNG image.
     */
    public function png(): static
    {
        return $this->state(fn (array $attributes) => [
            'mime_type' => 'image/png',
            'filename' => fake()->unique()->word() . '.png',
        ]);
    }

    /**
     * Indicate that the media is a PDF document.
     */
    public function pdf(): static
    {
        return $this->state(fn (array $attributes) => [
            'mime_type' => 'application/pdf',
            'filename' => fake()->unique()->word() . '.pdf',
            'dimensions' => null,
        ]);
    }

    /**
     * Indicate that the media is a video.
     */
    public function video(): static
    {
        return $this->state(fn (array $attributes) => [
            'mime_type' => 'video/mp4',
            'filename' => fake()->unique()->word() . '.mp4',
            'dimensions' => [
                'width' => 1920,
                'height' => 1080,
            ],
        ]);
    }

    /**
     * Indicate that the media is an avatar.
     */
    public function avatar(): static
    {
        return $this->state(fn (array $attributes) => [
            'collection_name' => 'avatars',
            'mime_type' => 'image/jpeg',
            'dimensions' => ['width' => 200, 'height' => 200],
        ]);
    }

    /**
     * Indicate that the media is a banner.
     */
    public function banner(): static
    {
        return $this->state(fn (array $attributes) => [
            'collection_name' => 'banners',
            'mime_type' => 'image/jpeg',
            'dimensions' => ['width' => 1920, 'height' => 400],
        ]);
    }

    /**
     * Indicate that the media is private.
     */
    public function private(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_public' => false,
        ]);
    }

    /**
     * Indicate that the media is in a specific collection.
     */
    public function inCollection(string $collectionName): static
    {
        return $this->state(fn (array $attributes) => [
            'collection_name' => $collectionName,
        ]);
    }

    /**
     * Indicate that the media is uploaded by a specific user.
     */
    public function uploadedBy(?int $userId = null): static
    {
        return $this->state(fn (array $attributes) => [
            'uploader_id' => $userId ?? User::factory(),
        ]);
    }

    /**
     * Indicate that the media has metadata.
     */
    public function withMetadata(): static
    {
        return $this->state(fn (array $attributes) => [
            'metadata' => [
                'camera' => fake()->company(),
                'location' => fake()->city(),
                'taken_at' => fake()->dateTime()->format('Y-m-d H:i:s'),
            ],
        ]);
    }
}
