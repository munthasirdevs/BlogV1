<?php

namespace Tests\Feature\Api\V1;

use App\Models\Bookmark;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Class BookmarkFeatureTest
 *
 * Feature tests for bookmark functionality.
 */
class BookmarkFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $otherUser;
    protected Post $post;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->otherUser = User::factory()->create();
        $this->post = Post::factory()->create(['user_id' => $this->user->id, 'status' => 'published']);
    }

    /** @test */
    public function authenticated_user_can_bookmark_post(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/posts/{$this->post->id}/bookmark");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'bookmarked' => true,
                    'action' => 'created',
                ],
            ]);

        $this->assertDatabaseHas('bookmarks', [
            'user_id' => $this->user->id,
            'post_id' => $this->post->id,
            'collection_name' => 'default',
        ]);
    }

    /** @test */
    public function authenticated_user_can_remove_bookmark(): void
    {
        Bookmark::create([
            'user_id' => $this->user->id,
            'post_id' => $this->post->id,
            'collection_name' => 'default',
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/posts/{$this->post->id}/bookmark");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'bookmarked' => false,
                    'action' => 'deleted',
                ],
            ]);

        $this->assertDatabaseMissing('bookmarks', [
            'user_id' => $this->user->id,
            'post_id' => $this->post->id,
        ]);
    }

    /** @test */
    public function user_can_bookmark_with_custom_collection(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/posts/{$this->post->id}/bookmark", [
                'collection' => 'favorites',
                'notes' => 'Great article!',
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('bookmarks', [
            'user_id' => $this->user->id,
            'post_id' => $this->post->id,
            'collection_name' => 'favorites',
            'notes' => 'Great article!',
        ]);
    }

    /** @test */
    public function user_can_get_their_bookmarks(): void
    {
        Bookmark::create([
            'user_id' => $this->user->id,
            'post_id' => $this->post->id,
            'collection_name' => 'default',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/user/bookmarks');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'post',
                        'collection',
                        'notes',
                        'bookmarked_at',
                    ],
                ],
                'meta',
            ]);
    }

    /** @test */
    public function user_can_filter_bookmarks_by_collection(): void
    {
        Bookmark::create([
            'user_id' => $this->user->id,
            'post_id' => $this->post->id,
            'collection_name' => 'favorites',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/user/bookmarks?collection=favorites');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    /** @test */
    public function user_can_get_collections(): void
    {
        Bookmark::create([
            'user_id' => $this->user->id,
            'post_id' => $this->post->id,
            'collection_name' => 'favorites',
        ]);

        Bookmark::create([
            'user_id' => $this->user->id,
            'post_id' => Post::factory()->create()->id,
            'collection_name' => 'reading-later',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/bookmarks/collections');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'name',
                        'display_name',
                        'count',
                    ],
                ],
            ]);
    }

    /** @test */
    public function user_can_create_collection(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/bookmarks/collections', [
                'name' => 'tech-articles',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'name' => 'tech_articles',
                ],
            ]);
    }

    /** @test */
    public function user_can_rename_collection(): void
    {
        Bookmark::create([
            'user_id' => $this->user->id,
            'post_id' => $this->post->id,
            'collection_name' => 'old-name',
        ]);

        $response = $this->actingAs($this->user)
            ->putJson('/api/v1/bookmarks/collections/old-name', [
                'name' => 'new-name',
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('bookmarks', [
            'user_id' => $this->user->id,
            'collection_name' => 'new_name',
        ]);
    }

    /** @test */
    public function user_can_delete_collection(): void
    {
        Bookmark::create([
            'user_id' => $this->user->id,
            'post_id' => $this->post->id,
            'collection_name' => 'to-delete',
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson('/api/v1/bookmarks/collections/to-delete');

        $response->assertStatus(200);

        $this->assertDatabaseMissing('bookmarks', [
            'user_id' => $this->user->id,
            'collection_name' => 'to_delete',
        ]);
    }

    /** @test */
    public function user_can_move_bookmark_to_collection(): void
    {
        $bookmark = Bookmark::create([
            'user_id' => $this->user->id,
            'post_id' => $this->post->id,
            'collection_name' => 'default',
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/bookmarks/{$bookmark->id}/collection", [
                'collection' => 'favorites',
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('bookmarks', [
            'id' => $bookmark->id,
            'collection_name' => 'favorites',
        ]);
    }

    /** @test */
    public function user_can_update_bookmark_notes(): void
    {
        $bookmark = Bookmark::create([
            'user_id' => $this->user->id,
            'post_id' => $this->post->id,
        ]);

        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/bookmarks/{$bookmark->id}/notes", [
                'notes' => 'Updated notes',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'notes' => 'Updated notes',
                ],
            ]);
    }

    /** @test */
    public function user_can_get_bookmark_stats(): void
    {
        Bookmark::create([
            'user_id' => $this->user->id,
            'post_id' => $this->post->id,
            'collection_name' => 'default',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/user/bookmarks/stats');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'total_bookmarks',
                    'total_collections',
                    'collections',
                ],
            ]);
    }

    /** @test */
    public function user_can_search_bookmarks(): void
    {
        $post = Post::factory()->create(['title' => 'Unique Test Post Title']);
        
        Bookmark::create([
            'user_id' => $this->user->id,
            'post_id' => $post->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/user/bookmarks/search?q=Test');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    /** @test */
    public function unauthenticated_user_cannot_bookmark(): void
    {
        $response = $this->postJson("/api/v1/posts/{$this->post->id}/bookmark");

        $response->assertStatus(401);
    }

    /** @test */
    public function user_cannot_delete_default_collection(): void
    {
        $response = $this->actingAs($this->user)
            ->deleteJson('/api/v1/bookmarks/collections/default');

        $response->assertStatus(422);
    }
}
