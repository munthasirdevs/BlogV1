<?php

namespace Tests\Feature\Api\V1;

use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Class PostWorkflowTest
 *
 * Feature tests for Post workflow operations.
 * Tests publish, unpublish, feature, archive, autosave operations.
 */
class PostWorkflowTest extends TestCase
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
    public function editor_can_publish_draft_post(): void
    {
        $post = Post::factory()->draft()->create(['user_id' => $this->author->id]);

        $response = $this->actingAs($this->editor)
            ->postJson("/api/v1/posts/{$post->id}/publish");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'published');

        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'status' => 'published',
        ]);
        $this->assertNotNull(Post::find($post->id)->published_at);
    }

    /** @test */
    public function admin_can_publish_draft_post(): void
    {
        $post = Post::factory()->draft()->create(['user_id' => $this->author->id]);

        $response = $this->actingAs($this->admin)
            ->postJson("/api/v1/posts/{$post->id}/publish");

        $response->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    /** @test */
    public function author_cannot_publish_own_post(): void
    {
        $post = Post::factory()->draft()->create(['user_id' => $this->author->id]);

        $response = $this->actingAs($this->author)
            ->postJson("/api/v1/posts/{$post->id}/publish");

        $response->assertStatus(403);
    }

    /** @test */
    public function subscriber_cannot_publish_post(): void
    {
        $post = Post::factory()->draft()->create(['user_id' => $this->author->id]);
        $subscriber = User::factory()->withRole('user')->create();

        $response = $this->actingAs($subscriber)
            ->postJson("/api/v1/posts/{$post->id}/publish");

        $response->assertStatus(403);
    }

    /** @test */
    public function editor_can_unpublish_published_post(): void
    {
        $post = Post::factory()->published()->create(['user_id' => $this->author->id]);

        $response = $this->actingAs($this->editor)
            ->postJson("/api/v1/posts/{$post->id}/unpublish");

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'draft');
    }

    /** @test */
    public function admin_can_feature_post(): void
    {
        $post = Post::factory()->published()->create();

        $response = $this->actingAs($this->admin)
            ->postJson("/api/v1/posts/{$post->id}/feature");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.is_featured', true);

        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'is_featured' => true,
        ]);
    }

    /** @test */
    public function editor_cannot_feature_post(): void
    {
        $post = Post::factory()->published()->create();

        $response = $this->actingAs($this->editor)
            ->postJson("/api/v1/posts/{$post->id}/feature");

        $response->assertStatus(403);
    }

    /** @test */
    public function admin_can_unfeature_post(): void
    {
        $post = Post::factory()->published()->create(['is_featured' => true]);

        $response = $this->actingAs($this->admin)
            ->deleteJson("/api/v1/posts/{$post->id}/feature");

        $response->assertStatus(200)
            ->assertJsonPath('data.is_featured', false);
    }

    /** @test */
    public function author_can_autosave_own_draft(): void
    {
        $post = Post::factory()->draft()->create(['user_id' => $this->author->id]);

        $autosaveData = [
            'title' => 'Autosaved Title',
            'content' => 'Autosaved content with more words for validation.',
        ];

        $response = $this->actingAs($this->author)
            ->postJson("/api/v1/posts/{$post->id}/autosave", $autosaveData);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'title' => 'Autosaved Title',
            'status' => 'draft', // Status should not change
        ]);
    }

    /** @test */
    public function editor_can_autosave_any_post(): void
    {
        $post = Post::factory()->draft()->create(['user_id' => $this->author->id]);

        $autosaveData = [
            'title' => 'Editor Autosaved Title',
        ];

        $response = $this->actingAs($this->editor)
            ->postJson("/api/v1/posts/{$post->id}/autosave", $autosaveData);

        $response->assertStatus(200);
    }

    /** @test */
    public function author_cannot_autosave_others_post(): void
    {
        $post = Post::factory()->draft()->create(['user_id' => $this->editor->id]);

        $autosaveData = [
            'title' => 'Unauthorized Autosave',
        ];

        $response = $this->actingAs($this->author)
            ->postJson("/api/v1/posts/{$post->id}/autosave", $autosaveData);

        $response->assertStatus(403);
    }

    /** @test */
    public function autosave_does_not_change_status(): void
    {
        $post = Post::factory()->draft()->create(['user_id' => $this->author->id]);

        $autosaveData = [
            'title' => 'New Title',
            'content' => 'New content with more words for validation purposes.',
        ];

        $this->actingAs($this->author)
            ->postJson("/api/v1/posts/{$post->id}/autosave", $autosaveData);

        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'status' => 'draft',
        ]);
    }

    /** @test */
    public function can_archive_post(): void
    {
        $post = Post::factory()->published()->create();

        $response = $this->actingAs($this->admin)
            ->postJson("/api/v1/posts/{$post->id}/archive");

        $response->assertStatus(200);
        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'status' => 'archived',
        ]);
    }

    /** @test */
    public function can_get_trending_posts(): void
    {
        $post1 = Post::factory()->published()->create(['views_count' => 100, 'likes_count' => 20, 'comments_count' => 10]);
        $post2 = Post::factory()->published()->create(['views_count' => 50, 'likes_count' => 10, 'comments_count' => 5]);

        $response = $this->getJson('/api/v1/posts/trending');

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $data = $response->json('data');
        $this->assertGreaterThanOrEqual(1, count($data));
    }

    /** @test */
    public function can_get_featured_posts(): void
    {
        Post::factory()->published()->count(3)->create(['is_featured' => true]);
        Post::factory()->published()->count(2)->create(['is_featured' => false]);

        $response = $this->getJson('/api/v1/posts/featured');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertEquals(3, count($data));
    }

    /** @test */
    public function can_get_related_posts(): void
    {
        $category = Category::factory()->create();
        $post = Post::factory()->published()->create(['category_id' => $category->id]);
        $relatedPost = Post::factory()->published()->create(['category_id' => $category->id]);
        Post::factory()->published()->create(); // Different category

        $response = $this->getJson("/api/v1/posts/{$post->id}/related");

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertGreaterThanOrEqual(1, count($data));
    }

    /** @test */
    public function can_get_post_author(): void
    {
        $post = Post::factory()->published()->create(['user_id' => $this->author->id]);

        $response = $this->getJson("/api/v1/posts/{$post->id}/author");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $this->author->id);
    }

    /** @test */
    public function can_generate_preview_url_for_unpublished_post(): void
    {
        $post = Post::factory()->draft()->create(['user_id' => $this->author->id]);

        $response = $this->actingAs($this->author)
            ->getJson("/api/v1/posts/{$post->id}/preview");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.preview_url');
        $this->assertStringContainsString('preview_token', $response->json('data.preview_url'));
    }

    /** @test */
    public function unauthorized_user_cannot_generate_preview(): void
    {
        $post = Post::factory()->draft()->create(['user_id' => $this->author->id]);

        $response = $this->actingAs($this->subscriber)
            ->getJson("/api/v1/posts/{$post->id}/preview");

        $response->assertStatus(403);
    }

    /** @test */
    public function can_get_post_counts(): void
    {
        Post::factory()->published()->count(3)->create(['user_id' => $this->author->id]);
        Post::factory()->draft()->count(2)->create(['user_id' => $this->author->id]);

        $response = $this->actingAs($this->author)
            ->getJson('/api/v1/posts/counts');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.published', 3)
            ->assertJsonPath('data.draft', 2);
    }

    /** @test */
    public function admin_sees_all_counts(): void
    {
        Post::factory()->published()->count(5)->create();
        Post::factory()->draft()->count(3)->create();

        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/posts/counts');

        $response->assertStatus(200)
            ->assertJsonPath('data.all', 8);
    }

    /** @test */
    public function view_count_increments_on_show(): void
    {
        $post = Post::factory()->published()->create(['views_count' => 10]);

        $this->getJson("/api/v1/posts/{$post->id}");

        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'views_count' => 11,
        ]);
    }

    /** @test */
    public function view_count_does_not_increment_for_draft(): void
    {
        $post = Post::factory()->draft()->create(['user_id' => $this->author->id, 'views_count' => 10]);

        $this->actingAs($this->author)
            ->getJson("/api/v1/posts/{$post->id}");

        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'views_count' => 10, // Unchanged
        ]);
    }
}
