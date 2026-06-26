<?php

namespace Database\Factories;

use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class TagFactory extends Factory
{
    protected $model = Tag::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->word();

        return [
            'uuid' => (string) Str::uuid(),
            'name' => ucfirst($name),
            'slug' => Str::slug($name),
            'color' => $this->faker->hexColor(),
            'usage_count' => $this->faker->numberBetween(0, 50),
            'trending_score' => $this->faker->randomFloat(2, 0, 10),
            'status' => 'active',
            'created_by' => User::factory(),
        ];
    }

    public function active(): static
    {
        return $this->state(fn() => ['status' => 'active']);
    }

    public function hidden(): static
    {
        return $this->state(fn() => ['status' => 'hidden']);
    }
}
