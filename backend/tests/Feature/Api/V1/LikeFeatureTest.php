<?php

namespace Tests\Feature\Api\V1;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use App\Models\Like;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Class LikeFeatureTest
 *
 * Feature tests for like functionality.
 */
class LikeFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $otherUser;
    protected Post $post;
    protected Comment $comment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->otherUser = User::factory()->create();
        $this->post = Post::factory()->create(['user_id' => $this->user->id, 'status' => 'published']);
        $this->comment = Comment::factory()->create(['post_id' => $this->post->id, 'user_id' => $this->otherUser->id]);
    }

    /** @test */
    public function authenticated_user_can_like_post(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/posts/{$this->post->id}/like");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'liked' => true,
                    'likes_count' => 1,
                ],
            ]);

        $this->assertDatabaseHas('likes', [
            'user_id' => $this->user->id,
            'likeable_type' => Post::class,
            'likeable_id' => $this->post->id,
        ]);
    }

    /** @test */
    public function authenticated_user_can_unlike_post(): void
    {
        // First like the post
        Like::create([
            'user_id' => $this->user->id,
            'likeable_type' => Post::class,
            'likeable_id' => $this->post->id,
        ]);
        $this->post->update(['likes_count' => 1]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/posts/{$this->post->id}/like");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'liked' => false,
                    'likes_count' => 0,
                ],
            ]);

        $this->assertDatabaseMissing('likes', [
            'user_id' => $this->user->id,
            'likeable_type' => Post::class,
            'likeable_id' => $this->post->id,
        ]);
    }

    /** @test */
    public function user_cannot_like_post_twice(): void
    {
        // First like
        $this->actingAs($this->user)
            ->postJson("/api/v1/posts/{$this->post->id}/like")
            ->assertStatus(200);

        // Second like should unlike
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/posts/{$this->post->id}/like");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'liked' => false,
                ],
            ]);
    }

    /** @test */
    public function authenticated_user_can_like_comment(): void
    {
        $initialCount = $this->comment->likes_count;

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/comments/{$this->comment->id}/like");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'liked' => true,
                ],
            ]);

        // Verify count incremented
        $this->comment->refresh();
        $this->assertEquals($initialCount + 1, $this->comment->likes_count);
    }

    /** @test */
    public function unauthenticated_user_cannot_like(): void
    {
        $response = $this->postJson("/api/v1/posts/{$this->post->id}/like");

        $response->assertStatus(401);
    }

    /** @test */
    public function can_get_post_likes(): void
    {
        // Create some likes
        Like::create([
            'user_id' => $this->user->id,
            'likeable_type' => Post::class,
            'likeable_id' => $this->post->id,
        ]);
        Like::create([
            'user_id' => $this->otherUser->id,
            'likeable_type' => Post::class,
            'likeable_id' => $this->post->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/posts/{$this->post->id}/likes");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'user' => [
                            'id',
                            'name',
                            'avatar',
                        ],
                        'liked_at',
                    ],
                ],
                'meta' => [
                    'current_page',
                    'per_page',
                    'total',
                    'total_pages',
                ],
            ]);
    }

    /** @test */
    public function can_get_comment_likes(): void
    {
        Like::create([
            'user_id' => $this->user->id,
            'likeable_type' => Comment::class,
            'likeable_id' => $this->comment->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/comments/{$this->comment->id}/likes");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data',
                'meta',
            ]);
    }

    /** @test */
    public function user_can_get_their_liked_posts(): void
    {
        Like::create([
            'user_id' => $this->user->id,
            'likeable_type' => Post::class,
            'likeable_id' => $this->post->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/users/{$this->user->id}/likes/posts");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'post',
                        'liked_at',
                    ],
                ],
                'meta',
            ]);
    }

    /** @test */
    public function user_can_get_their_liked_comments(): void
    {
        Like::create([
            'user_id' => $this->user->id,
            'likeable_type' => Comment::class,
            'likeable_id' => $this->comment->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/users/{$this->user->id}/likes/comments");

        $response->assertStatus(200);
    }

    /** @test */
    public function user_cannot_view_another_users_likes_without_permission(): void
    {
        $response = $this->actingAs($this->otherUser)
            ->getJson("/api/v1/users/{$this->user->id}/likes/posts");

        $response->assertStatus(403);
    }

    /** @test */
    public function likes_count_updates_correctly(): void
    {
        $this->actingAs($this->user)
            ->postJson("/api/v1/posts/{$this->post->id}/like");

        $this->post->refresh();
        $this->assertEquals(1, $this->post->likes_count);

        $this->actingAs($this->user)
            ->postJson("/api/v1/posts/{$this->post->id}/like");

        $this->post->refresh();
        $this->assertEquals(0, $this->post->likes_count);
    }

    /** @test */
    public function multiple_users_can_like_same_post(): void
    {
        $this->actingAs($this->user)
            ->postJson("/api/v1/posts/{$this->post->id}/like");

        $this->actingAs($this->otherUser)
            ->postJson("/api/v1/posts/{$this->post->id}/like");

        $this->post->refresh();
        $this->assertEquals(2, $this->post->likes_count);

        $this->assertDatabaseHas('likes', [
            'user_id' => $this->user->id,
            'likeable_id' => $this->post->id,
        ]);

        $this->assertDatabaseHas('likes', [
            'user_id' => $this->otherUser->id,
            'likeable_id' => $this->post->id,
        ]);
    }
}
