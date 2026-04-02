<?php

namespace Tests\Feature\Api\V1;

use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Class PostBulkActionsTest
 *
 * Feature tests for Post bulk operations and search.
 */
class PostBulkActionsTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $editor;
    protected User $author;
    protected Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->withRole('admin')->create();
        $this->editor = User::factory()->withRole('editor')->create();
        $this->author = User::factory()->withRole('user')->create(); // Using 'user' role as author
        $this->category = Category::factory()->create();
    }

    /** @test */
    public function admin_can_bulk_publish_posts(): void
    {
        $posts = Post::factory()->draft()->count(3)->create();

        $response = $this->actingAs($this->admin)
            ->postJson('/api/v1/posts/bulk-actions', [
                'action' => 'publish',
                'post_ids' => $posts->pluck('id')->toArray(),
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.success_count', 3)
            ->assertJsonPath('data.failed_count', 0);

        foreach ($posts as $post) {
            $this->assertDatabaseHas('posts', [
                'id' => $post->id,
                'status' => 'published',
            ]);
        }
    }

    /** @test */
    public function editor_can_bulk_publish_posts(): void
    {
        $posts = Post::factory()->draft()->count(2)->create();

        $response = $this->actingAs($this->editor)
            ->postJson('/api/v1/posts/bulk-actions', [
                'action' => 'publish',
                'post_ids' => $posts->pluck('id')->toArray(),
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.success_count', 2);
    }

    /** @test */
    public function author_cannot_bulk_publish(): void
    {
        $posts = Post::factory()->draft()->count(2)->create(['user_id' => $this->author->id]);

        $response = $this->actingAs($this->author)
            ->postJson('/api/v1/posts/bulk-actions', [
                'action' => 'publish',
                'post_ids' => $posts->pluck('id')->toArray(),
            ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function admin_can_bulk_archive_posts(): void
    {
        $posts = Post::factory()->published()->count(3)->create();

        $response = $this->actingAs($this->admin)
            ->postJson('/api/v1/posts/bulk-actions', [
                'action' => 'archive',
                'post_ids' => $posts->pluck('id')->toArray(),
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.success_count', 3);

        foreach ($posts as $post) {
            $this->assertDatabaseHas('posts', [
                'id' => $post->id,
                'status' => 'archived',
            ]);
        }
    }

    /** @test */
    public function admin_can_bulk_delete_posts(): void
    {
        $posts = Post::factory()->draft()->count(3)->create();

        $response = $this->actingAs($this->admin)
            ->postJson('/api/v1/posts/bulk-actions', [
                'action' => 'delete',
                'post_ids' => $posts->pluck('id')->toArray(),
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.success_count', 3);

        foreach ($posts as $post) {
            $this->assertSoftDeleted('posts', ['id' => $post->id]);
        }
    }

    /** @test */
    public function admin_can_bulk_feature_posts(): void
    {
        $posts = Post::factory()->published()->count(3)->create();

        $response = $this->actingAs($this->admin)
            ->postJson('/api/v1/posts/bulk-actions', [
                'action' => 'feature',
                'post_ids' => $posts->pluck('id')->toArray(),
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.success_count', 3);

        foreach ($posts as $post) {
            $this->assertDatabaseHas('posts', [
                'id' => $post->id,
                'is_featured' => true,
            ]);
        }
    }

    /** @test */
    public function editor_cannot_bulk_feature(): void
    {
        $posts = Post::factory()->published()->count(2)->create();

        $response = $this->actingAs($this->editor)
            ->postJson('/api/v1/posts/bulk-actions', [
                'action' => 'feature',
                'post_ids' => $posts->pluck('id')->toArray(),
            ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function admin_can_bulk_restore_deleted_posts(): void
    {
        $posts = Post::factory()->draft()->count(3)->create();
        $posts->each->delete();

        $response = $this->actingAs($this->admin)
            ->postJson('/api/v1/posts/bulk-actions', [
                'action' => 'restore',
                'post_ids' => $posts->pluck('id')->toArray(),
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.success_count', 3);

        foreach ($posts as $post) {
            $this->assertDatabaseHas('posts', [
                'id' => $post->id,
                'deleted_at' => null,
            ]);
        }
    }

    /** @test */
    public function bulk_action_returns_failed_for_unauthorized_posts(): void
    {
        $authorPost = Post::factory()->draft()->create(['user_id' => $this->author->id]);
        $otherPost = Post::factory()->draft()->create();

        $response = $this->actingAs($this->author)
            ->postJson('/api/v1/posts/bulk-actions', [
                'action' => 'delete',
                'post_ids' => [$authorPost->id, $otherPost->id],
            ]);

        $response->assertStatus(207) // Multi-status
            ->assertJsonPath('data.success_count', 1)
            ->assertJsonPath('data.failed_count', 1);
    }

    /** @test */
    public function bulk_action_requires_valid_action(): void
    {
        $response = $this->actingAs($this->admin)
            ->postJson('/api/v1/posts/bulk-actions', [
                'action' => 'invalid_action',
                'post_ids' => [1],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('action');
    }

    /** @test */
    public function bulk_action_requires_post_ids(): void
    {
        $response = $this->actingAs($this->admin)
            ->postJson('/api/v1/posts/bulk-actions', [
                'action' => 'publish',
                'post_ids' => [],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('post_ids');
    }

    /** @test */
    public function can_search_posts_by_query(): void
    {
        Post::factory()->published()->create(['title' => 'Laravel Tutorial']);
        Post::factory()->published()->create(['title' => 'PHP Best Practices']);
        Post::factory()->published()->create(['title' => 'JavaScript Guide']);

        $response = $this->getJson('/api/v1/posts/search?q=Laravel');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('meta.query', 'Laravel');

        $data = $response->json('data');
        $titles = array_column($data, 'title');
        $this->assertContains('Laravel Tutorial', $titles);
    }

    /** @test */
    public function search_requires_minimum_query_length(): void
    {
        $response = $this->getJson('/api/v1/posts/search?q=a');

        $response->assertStatus(422)
            ->assertJsonValidationErrors('q');
    }

    /** @test */
    public function can_filter_search_by_category(): void
    {
        $category1 = Category::factory()->create();
        $category2 = Category::factory()->create();

        Post::factory()->published()->create(['title' => 'Laravel Post', 'category_id' => $category1->id]);
        Post::factory()->published()->create(['title' => 'Laravel Post 2', 'category_id' => $category2->id]);

        $response = $this->getJson("/api/v1/posts/search?q=Laravel&category={$category1->slug}");

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertEquals(1, count($data));
    }

    /** @test */
    public function can_filter_search_by_tag(): void
    {
        $tag = Tag::factory()->create(['slug' => 'tutorial']);
        $post1 = Post::factory()->published()->create(['title' => 'Laravel Tutorial']);
        $post1->tags()->attach($tag);
        Post::factory()->published()->create(['title' => 'Laravel Guide']);

        $response = $this->getJson("/api/v1/posts/search?q=Laravel&tag={$tag->slug}");

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertEquals(1, count($data));
    }

    /** @test */
    public function can_search_with_boolean_mode(): void
    {
        Post::factory()->published()->create(['title' => 'Laravel PHP Tutorial']);
        Post::factory()->published()->create(['title' => 'Laravel JavaScript Guide']);
        Post::factory()->published()->create(['title' => 'Python Tutorial']);

        $response = $this->getJson('/api/v1/posts/search?q=+Laravel +PHP&boolean=1');

        $response->assertStatus(200);
        $data = $response->json('data');
        $titles = array_column($data, 'title');
        $this->assertContains('Laravel PHP Tutorial', $titles);
        $this->assertNotContains('Laravel JavaScript Guide', $titles);
    }

    /** @test */
    public function search_results_are_paginated(): void
    {
        Post::factory()->published()->count(25)->create(['title' => 'Test Post']);

        $response = $this->getJson('/api/v1/posts/search?q=Test&per_page=10');

        $response->assertStatus(200)
            ->assertJsonPath('meta.per_page', 10)
            ->assertJsonPath('meta.total', 25);
    }

    /** @test */
    public function search_can_filter_by_date_range(): void
    {
        Post::factory()->published()->create([
            'title' => 'Old Post',
            'published_at' => now()->subDays(30),
        ]);
        Post::factory()->published()->create([
            'title' => 'Recent Post',
            'published_at' => now()->subDays(5),
        ]);

        $fromDate = now()->subDays(10)->format('Y-m-d');
        $toDate = now()->format('Y-m-d');

        $response = $this->getJson("/api/v1/posts/search?q=Post&from_date={$fromDate}&to_date={$toDate}");

        $response->assertStatus(200);
        $data = $response->json('data');
        $titles = array_column($data, 'title');
        $this->assertContains('Recent Post', $titles);
        $this->assertNotContains('Old Post', $titles);
    }
}
