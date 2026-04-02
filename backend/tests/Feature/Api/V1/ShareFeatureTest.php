<?php

namespace Tests\Feature\Api\V1;

use App\Models\Post;
use App\Models\PostShare;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Class ShareFeatureTest
 *
 * Feature tests for share functionality.
 */
class ShareFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Post $post;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->post = Post::factory()->create(['user_id' => $this->user->id, 'status' => 'published']);
    }

    /** @test */
    public function can_track_share(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/posts/{$this->post->id}/share", [
                'provider' => 'twitter',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'provider' => 'twitter',
                ],
            ]);

        $this->assertDatabaseHas('post_shares', [
            'post_id' => $this->post->id,
            'provider' => 'twitter',
            'user_id' => $this->user->id,
        ]);
    }

    /** @test */
    public function share_count_increments(): void
    {
        $this->actingAs($this->user)
            ->postJson("/api/v1/posts/{$this->post->id}/share", [
                'provider' => 'twitter',
            ]);

        $this->actingAs($this->user)
            ->postJson("/api/v1/posts/{$this->post->id}/share", [
                'provider' => 'facebook',
            ]);

        $this->post->refresh();
        $this->assertEquals(2, $this->post->shares_count);
    }

    /** @test */
    public function can_get_share_count(): void
    {
        PostShare::create([
            'post_id' => $this->post->id,
            'provider' => 'twitter',
        ]);

        PostShare::create([
            'post_id' => $this->post->id,
            'provider' => 'facebook',
        ]);

        $response = $this->getJson("/api/v1/posts/{$this->post->id}/share-count");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'total' => 2,
                    'by_provider' => [
                        'twitter' => 1,
                        'facebook' => 1,
                    ],
                ],
            ]);
    }

    /** @test */
    public function can_generate_share_url(): void
    {
        $response = $this->getJson("/api/v1/posts/{$this->post->id}/share-url?provider=twitter");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'provider',
                    'share_url',
                    'post_url',
                ],
            ]);

        $data = $response->json('data');
        $this->assertStringContainsString('twitter', $data['share_url']);
        $this->assertStringContainsString('utm_source=twitter', $data['post_url']);
    }

    /** @test */
    public function can_get_share_providers(): void
    {
        $response = $this->getJson('/api/v1/shares/providers');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'twitter' => ['label', 'icon', 'color'],
                    'facebook' => ['label', 'icon', 'color'],
                    'linkedin' => ['label', 'icon', 'color'],
                ],
            ]);
    }

    /** @test */
    public function invalid_provider_rejected(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/posts/{$this->post->id}/share", [
                'provider' => 'invalid-provider',
            ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function provider_required(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/posts/{$this->post->id}/share");

        $response->assertStatus(422)
            ->assertJsonValidationErrors('provider');
    }

    /** @test */
    public function can_get_shares_for_post(): void
    {
        PostShare::create([
            'post_id' => $this->post->id,
            'provider' => 'twitter',
            'user_id' => $this->user->id,
        ]);

        $response = $this->getJson("/api/v1/posts/{$this->post->id}/shares");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'provider',
                        'provider_display',
                        'user',
                        'share_url',
                        'shared_at',
                    ],
                ],
                'meta',
            ]);
    }

    /** @test */
    public function can_get_share_statistics(): void
    {
        PostShare::create(['post_id' => $this->post->id, 'provider' => 'twitter']);
        PostShare::create(['post_id' => $this->post->id, 'provider' => 'twitter']);
        PostShare::create(['post_id' => $this->post->id, 'provider' => 'facebook']);

        $response = $this->getJson("/api/v1/posts/{$this->post->id}/share-stats");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'total',
                    'by_provider',
                    'most_popular',
                ],
            ]);
    }

    /** @test */
    public function can_get_share_analytics(): void
    {
        PostShare::create(['post_id' => $this->post->id, 'provider' => 'twitter']);

        $response = $this->getJson("/api/v1/posts/{$this->post->id}/share-analytics?days=7");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'total',
                    'by_provider',
                    'by_date',
                    'period',
                ],
            ]);
    }

    /** @test */
    public function can_get_trending_posts_by_shares(): void
    {
        PostShare::create(['post_id' => $this->post->id, 'provider' => 'twitter']);

        $response = $this->getJson('/api/v1/shares/trending');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data',
            ]);
    }

    /** @test */
    public function user_can_get_their_shares(): void
    {
        PostShare::create([
            'post_id' => $this->post->id,
            'provider' => 'twitter',
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/user/shares');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    /** @test */
    public function share_url_contains_utm_parameters(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/posts/{$this->post->id}/share", [
                'provider' => 'linkedin',
            ]);

        $data = $response->json('data.share_url');
        
        $this->assertStringContainsString('utm_source=linkedin', $data);
        $this->assertStringContainsString('utm_medium=social', $data);
        $this->assertStringContainsString('utm_campaign=', $data);
    }
}
