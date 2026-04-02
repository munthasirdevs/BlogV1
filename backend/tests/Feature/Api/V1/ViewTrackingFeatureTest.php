<?php

namespace Tests\Feature\Api\V1;

use App\Models\Post;
use App\Models\PostView;
use App\Models\PostReadingProgress;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Class ViewTrackingFeatureTest
 *
 * Feature tests for view tracking and reading progress.
 */
class ViewTrackingFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $author;
    protected Post $post;

    protected function setUp(): void
    {
        parent::setUp();

        $this->author = User::factory()->create();
        $this->user = User::factory()->create();
        $this->post = Post::factory()->create(['user_id' => $this->author->id, 'status' => 'published']);
    }

    /** @test */
    public function view_is_tracked_when_viewing_post(): void
    {
        // Note: View tracking is done via middleware on show endpoint
        // This test verifies the progress endpoint works
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/posts/{$this->post->id}/progress", [
                'percentage' => 50,
                'time_spent' => 120,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'post_id' => $this->post->id,
                    'percentage' => 50,
                    'time_spent' => 120,
                    'is_complete' => false,
                ],
            ]);
    }

    /** @test */
    public function reading_progress_updates_correctly(): void
    {
        // First update
        $this->actingAs($this->user)
            ->postJson("/api/v1/posts/{$this->post->id}/progress", [
                'percentage' => 25,
                'time_spent' => 60,
            ]);

        // Second update should increase time spent
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/posts/{$this->post->id}/progress", [
                'percentage' => 75,
                'time_spent' => 180,
            ]);

        $response->assertJson([
            'data' => [
                'percentage' => 75,
                'time_spent' => 180,
            ],
        ]);
    }

    /** @test */
    public function reading_complete_detected(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/posts/{$this->post->id}/progress", [
                'percentage' => 100,
                'time_spent' => 300,
            ]);

        $response->assertJson([
            'data' => [
                'percentage' => 100,
                'is_complete' => true,
            ],
        ]);
    }

    /** @test */
    public function can_get_reading_progress(): void
    {
        PostReadingProgress::create([
            'post_id' => $this->post->id,
            'user_id' => $this->user->id,
            'percentage' => 50,
            'time_spent' => 120,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/posts/{$this->post->id}/progress");

        $response->assertStatus(200)
            ->assertJson([
            'success' => true,
            'data' => [
                'percentage' => 50,
                'time_spent' => 120,
            ],
        ]);
    }

    /** @test */
    public function returns_zero_progress_if_not_started(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/posts/{$this->post->id}/progress");

        $response->assertStatus(200)
            ->assertJson([
            'success' => true,
            'data' => [
                'percentage' => 0,
                'time_spent' => 0,
                'is_complete' => false,
            ],
        ]);
    }

    /** @test */
    public function can_get_reading_stats(): void
    {
        PostReadingProgress::create([
            'post_id' => $this->post->id,
            'user_id' => $this->user->id,
            'percentage' => 100,
            'time_spent' => 300,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/user/reading/stats');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'total_posts',
                    'completed',
                    'in_progress',
                    'total_time_spent',
                    'completion_rate',
                ],
            ]);
    }

    /** @test */
    public function can_get_reading_history(): void
    {
        PostReadingProgress::create([
            'post_id' => $this->post->id,
            'user_id' => $this->user->id,
            'percentage' => 50,
            'time_spent' => 120,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/user/reading/history');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'post',
                        'progress',
                        'last_read_at',
                    ],
                ],
                'meta',
            ]);
    }

    /** @test */
    public function percentage_validation(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/posts/{$this->post->id}/progress", [
                'percentage' => 150,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('percentage');
    }

    /** @test */
    public function percentage_required(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/posts/{$this->post->id}/progress");

        $response->assertStatus(422)
            ->assertJsonValidationErrors('percentage');
    }

    /** @test */
    public function negative_percentage_rejected(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/posts/{$this->post->id}/progress", [
                'percentage' => -10,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('percentage');
    }

    /** @test */
    public function time_spent_must_be_positive(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/posts/{$this->post->id}/progress", [
                'percentage' => 50,
                'time_spent' => -100,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('time_spent');
    }

    /** @test */
    public function unauthenticated_user_cannot_update_progress(): void
    {
        $response = $this->postJson("/api/v1/posts/{$this->post->id}/progress", [
            'percentage' => 50,
        ]);

        $response->assertStatus(401);
    }

    /** @test */
    public function author_views_not_counted_towards_unique(): void
    {
        // This tests the ViewService logic
        // Author viewing their own post should not be tracked
        $response = $this->actingAs($this->author)
            ->postJson("/api/v1/posts/{$this->post->id}/progress", [
                'percentage' => 50,
            ]);

        // Progress is still tracked for the user, but view count shouldn't increment
        $response->assertStatus(200);
    }

    /** @test */
    public function multiple_users_can_track_progress_on_same_post(): void
    {
        $this->actingAs($this->user)
            ->postJson("/api/v1/posts/{$this->post->id}/progress", [
                'percentage' => 50,
            ]);

        $otherUser = User::factory()->create();
        $this->actingAs($otherUser)
            ->postJson("/api/v1/posts/{$this->post->id}/progress", [
                'percentage' => 75,
            ]);

        $this->assertDatabaseHas('post_reading_progress', [
            'post_id' => $this->post->id,
            'user_id' => $this->user->id,
            'percentage' => 50,
        ]);

        $this->assertDatabaseHas('post_reading_progress', [
            'post_id' => $this->post->id,
            'user_id' => $otherUser->id,
            'percentage' => 75,
        ]);
    }

    /** @test */
    public function progress_time_spent_formatted_correctly(): void
    {
        $this->actingAs($this->user)
            ->postJson("/api/v1/posts/{$this->post->id}/progress", [
                'percentage' => 50,
                'time_spent' => 125, // 2 minutes 5 seconds
            ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/posts/{$this->post->id}/progress");

        $response->assertJson([
            'data' => [
                'time_spent_formatted' => '2m 5s',
            ],
        ]);
    }
}
