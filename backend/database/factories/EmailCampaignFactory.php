<?php

namespace Database\Factories;

use App\Models\EmailCampaign;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\EmailCampaign>
 */
class EmailCampaignFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = EmailCampaign::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->sentence(3),
            'subject' => fake()->sentence(6),
            'subject_b' => null,
            'preview_text' => fake()->sentence(10),
            'from_user_id' => User::factory(),
            'from_name' => fake()->name(),
            'from_email' => fake()->safeEmail(),
            'reply_to' => fake()->safeEmail(),
            'template' => 'newsletter',
            'content' => [
                'html' => '<p>' . fake()->paragraph() . '</p>',
                'text' => fake()->paragraph(),
            ],
            'status' => 'draft',
            'scheduled_at' => null,
            'started_at' => null,
            'completed_at' => null,
            'total_recipients' => 0,
            'sent_count' => 0,
            'delivered_count' => 0,
            'opened_count' => 0,
            'clicked_count' => 0,
            'bounced_count' => 0,
            'complained_count' => 0,
            'unsubscribed_count' => 0,
            'is_ab_test' => false,
            'ab_test_split' => 50,
            'ab_test_sample_size' => 10,
            'ab_test_winner' => null,
            'ab_test_completed_at' => null,
            'segment_filters' => null,
            'metadata' => null,
        ];
    }

    /**
     * Indicate that the campaign is scheduled.
     */
    public function scheduled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'scheduled',
            'scheduled_at' => fake()->dateTimeBetween('now', '+1 month'),
        ]);
    }

    /**
     * Indicate that the campaign is sending.
     */
    public function sending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'sending',
            'started_at' => now(),
        ]);
    }

    /**
     * Indicate that the campaign is sent.
     */
    public function sent(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'sent',
            'started_at' => fake()->dateTimeBetween('-1 month', 'now'),
            'completed_at' => fake()->dateTimeBetween('-1 month', 'now'),
        ]);
    }

    /**
     * Indicate that the campaign is cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
        ]);
    }

    /**
     * Indicate that the campaign is an A/B test.
     */
    public function abTest(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_ab_test' => true,
            'subject_b' => fake()->sentence(6),
            'ab_test_split' => 50,
            'ab_test_sample_size' => 10,
        ]);
    }

    /**
     * Set campaign statistics.
     */
    public function withStats(int $sent, int $delivered, int $opened, int $clicked): static
    {
        return $this->state(fn (array $attributes) => [
            'sent_count' => $sent,
            'delivered_count' => $delivered,
            'opened_count' => $opened,
            'clicked_count' => $clicked,
            'bounced_count' => $sent - $delivered,
        ]);
    }

    /**
     * Create a campaign with specific subject.
     */
    public function withSubject(string $subject): static
    {
        return $this->state(fn (array $attributes) => [
            'subject' => $subject,
        ]);
    }
}
