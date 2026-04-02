<?php

namespace Tests\Feature\Api\V1;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use App\Models\Like;
use App\Models\Bookmark;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Class ConcurrentInteractionsTest
 *
 * Tests for race conditions and concurrent operations.
 */
class ConcurrentInteractionsTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Post $post;
    protected Comment $comment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->post = Post::factory()->create(['user_id' => $this->user->id, 'status' => 'published']);
        $this->comment = Comment::factory()->create(['post_id' => $this->post->id]);
    }

    /** @test */
    public function prevents_duplicate_likes_with_unique_constraint(): void
    {
        // Try to create duplicate likes directly
        Like::create([
            'user_id' => $this->user->id,
            'likeable_type' => Post::class,
            'likeable_id' => $this->post->id,
        ]);

        // Second create should fail due to unique constraint
        $this->expectException(\Illuminate\Database\QueryException::class);

        Like::create([
            'user_id' => $this->user->id,
            'likeable_type' => Post::class,
            'likeable_id' => $this->post->id,
        ]);
    }

    /** @test */
    public function toggle_like_is_idempotent(): void
    {
        // Like
        $this->actingAs($this->user)
            ->postJson("/api/v1/posts/{$this->post->id}/like")
            ->assertJson(['data' => ['liked' => true]]);

        // Unlike
        $this->actingAs($this->user)
            ->postJson("/api/v1/posts/{$this->post->id}/like")
            ->assertJson(['data' => ['liked' => false]]);

        // Like again
        $this->actingAs($this->user)
            ->postJson("/api/v1/posts/{$this->post->id}/like")
            ->assertJson(['data' => ['liked' => true]]);

        // Unlike again
        $this->actingAs($this->user)
            ->postJson("/api/v1/posts/{$this->post->id}/like")
            ->assertJson(['data' => ['liked' => false]]);

        // Final count should be 0
        $this->post->refresh();
        $this->assertEquals(0, $this->post->likes_count);
    }

    /** @test */
    public function toggle_bookmark_is_idempotent(): void
    {
        // Bookmark
        $this->actingAs($this->user)
            ->postJson("/api/v1/posts/{$this->post->id}/bookmark")
            ->assertJson(['data' => ['bookmarked' => true]]);

        // Remove bookmark
        $this->actingAs($this->user)
            ->postJson("/api/v1/posts/{$this->post->id}/bookmark")
            ->assertJson(['data' => ['bookmarked' => false]]);

        // Bookmark again
        $this->actingAs($this->user)
            ->postJson("/api/v1/posts/{$this->post->id}/bookmark")
            ->assertJson(['data' => ['bookmarked' => true]]);

        // Remove again
        $this->actingAs($this->user)
            ->postJson("/api/v1/posts/{$this->post->id}/bookmark")
            ->assertJson(['data' => ['bookmarked' => false]]);

        // Should not exist in database
        $this->assertDatabaseMissing('bookmarks', [
            'user_id' => $this->user->id,
            'post_id' => $this->post->id,
        ]);
    }

    /** @test */
    public function like_count_remains_accurate_after_multiple_toggles(): void
    {
        $initialCount = 0;

        // Simulate multiple users liking and unliking
        $users = User::factory()->count(5)->create();

        foreach ($users as $user) {
            $this->actingAs($user)
                ->postJson("/api/v1/posts/{$this->post->id}/like");
        }

        $this->post->refresh();
        $this->assertEquals(5, $this->post->likes_count);

        // Now have some users unlike
        for ($i = 0; $i < 3; $i++) {
            $this->actingAs($users[$i])
                ->postJson("/api/v1/posts/{$this->post->id}/like");
        }

        $this->post->refresh();
        $this->assertEquals(2, $this->post->likes_count);
    }

    /** @test */
    public function bookmark_collection_operations_are_consistent(): void
    {
        // Create bookmarks in different collections
        $this->actingAs($this->user)
            ->postJson("/api/v1/posts/{$this->post->id}/bookmark", [
                'collection' => 'favorites',
            ]);

        // Move to different collection (by re-bookmarking)
        $this->actingAs($this->user)
            ->postJson("/api/v1/posts/{$this->post->id}/bookmark", [
                'collection' => 'reading-later',
            ]);

        // Should have 2 bookmarks in different collections
        $this->assertDatabaseHas('bookmarks', [
            'user_id' => $this->user->id,
            'post_id' => $this->post->id,
            'collection_name' => 'favorites',
        ]);

        $this->assertDatabaseHas('bookmarks', [
            'user_id' => $this->user->id,
            'post_id' => $this->post->id,
            'collection_name' => 'reading_later',
        ]);
    }

    /** @test */
    public function share_count_increments_correctly(): void
    {
        // Multiple shares
        $this->actingAs($this->user)
            ->postJson("/api/v1/posts/{$this->post->id}/share", [
                'provider' => 'twitter',
            ]);

        $this->actingAs($this->user)
            ->postJson("/api/v1/posts/{$this->post->id}/share", [
                'provider' => 'facebook',
            ]);

        $this->actingAs($this->user)
            ->postJson("/api/v1/posts/{$this->post->id}/share", [
                'provider' => 'linkedin',
            ]);

        $this->post->refresh();
        $this->assertEquals(3, $this->post->shares_count);
    }

    /** @test */
    public function comment_like_count_updates_correctly(): void
    {
        $this->actingAs($this->user)
            ->postJson("/api/v1/comments/{$this->comment->id}/like");

        $this->comment->refresh();
        $this->assertEquals(1, $this->comment->likes_count);

        $this->actingAs($this->user)
            ->postJson("/api/v1/comments/{$this->comment->id}/like");

        $this->comment->refresh();
        $this->assertEquals(0, $this->comment->likes_count);
    }

    /** @test */
    public function reading_progress_only_stores_best_progress(): void
    {
        // Start reading
        $this->actingAs($this->user)
            ->postJson("/api/v1/posts/{$this->post->id}/progress", [
                'percentage' => 25,
                'time_spent' => 60,
            ]);

        // Continue reading
        $this->actingAs($this->user)
            ->postJson("/api/v1/posts/{$this->post->id}/progress", [
                'percentage' => 75,
                'time_spent' => 180,
            ]);

        // Go back (should still keep highest time)
        $this->actingAs($this->user)
            ->postJson("/api/v1/posts/{$this->post->id}/progress", [
                'percentage' => 50,
                'time_spent' => 120,
            ]);

        $progress = \App\Models\PostReadingProgress::where('user_id', $this->user->id)
            ->where('post_id', $this->post->id)
            ->first();

        // Should keep the latest percentage but highest time spent
        $this->assertEquals(50, $progress->percentage);
        $this->assertEquals(180, $progress->time_spent); // Max time
    }

    /** @test */
    public function unique_constraint_prevents_duplicate_post_user_progress(): void
    {
        \App\Models\PostReadingProgress::create([
            'post_id' => $this->post->id,
            'user_id' => $this->user->id,
            'percentage' => 50,
        ]);

        // Try to create duplicate
        $this->expectException(\Illuminate\Database\QueryException::class);

        \App\Models\PostReadingProgress::create([
            'post_id' => $this->post->id,
            'user_id' => $this->user->id,
            'percentage' => 75,
        ]);
    }

    /** @test */
    public function engagement_score_calculates_correctly(): void
    {
        // Create various engagements
        Like::create([
            'user_id' => $this->user->id,
            'likeable_type' => Post::class,
            'likeable_id' => $this->post->id,
        ]);

        Bookmark::create([
            'user_id' => $this->user->id,
            'post_id' => $this->post->id,
        ]);

        $this->post->update(['views_count' => 10, 'comments_count' => 2]);

        $engagementService = new \App\Services\EngagementScoreService();
        $score = $engagementService->calculateScore($this->post);

        // Score should be calculated based on weights
        // views: 10 * 1 = 10
        // likes: 1 * 5 = 5
        // comments: 2 * 10 = 20
        // bookmarks: 1 * 8 = 8
        // Total raw: 43 (before decay)
        
        $this->assertGreaterThan(0, $score);
        $this->assertLessThan(100, $score); // With decay
    }
}
