<?php

namespace Tests\Feature\Api\V1;

use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Class PostCrudTest
 *
 * Feature tests for Post CRUD operations.
 * Tests create, read, update, delete operations with proper authorization.
 */
class PostCrudTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $editor;
    protected User $author;
    protected User $subscriber;
    protected Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->withRole('admin')->create();
        $this->editor = User::factory()->withRole('editor')->create();
        $this->author = User::factory()->withRole('user')->create(); // Using 'user' role as author
        $this->subscriber = User::factory()->withRole('user')->create(); // Using 'user' role as subscriber
        $this->category = Category::factory()->create();
    }

    /** @test */
    public function guest_can_view_published_posts(): void
    {
        Post::factory()->published()->count(5)->create();
        Post::factory()->draft()->count(3)->create();

        $response = $this->getJson('/api/v1/posts');

        $response->assertStatus(200)
            ->assertJsonPath('success', true);
        
        $data = $response->json('data');
        $this->assertCount(5, $data); // Only published posts
    }

    /** @test */
    public function authenticated_user_can_view_published_posts(): void
    {
        Post::factory()->published()->count(3)->create();

        $response = $this->actingAs($this->subscriber)
            ->getJson('/api/v1/posts');

        $response->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    /** @test */
    public function admin_can_view_all_posts_including_drafts(): void
    {
        Post::factory()->published()->count(3)->create();
        Post::factory()->draft()->count(2)->create();

        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/posts');

        $response->assertStatus(200)
            ->assertJsonPath('data.length', 5);
    }

    /** @test */
    public function author_can_view_published_and_own_draft_posts(): void
    {
        Post::factory()->published()->count(2)->create();
        Post::factory()->draft()->create(['user_id' => $this->author->id]);
        Post::factory()->draft()->create(['user_id' => $this->editor->id]);

        $response = $this->actingAs($this->author)
            ->getJson('/api/v1/posts');

        $response->assertStatus(200);
        // Should see published + own drafts
    }

    /** @test */
    public function can_filter_posts_by_status(): void
    {
        Post::factory()->published()->count(3)->create();
        Post::factory()->draft()->count(2)->create();
        Post::factory()->archived()->create();

        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/posts?status=published');

        $response->assertStatus(200)
            ->assertJsonPath('data.length', 3);
    }

    /** @test */
    public function can_filter_posts_by_category(): void
    {
        $category1 = Category::factory()->create();
        $category2 = Category::factory()->create();

        Post::factory()->published()->count(3)->create(['category_id' => $category1->id]);
        Post::factory()->published()->count(2)->create(['category_id' => $category2->id]);

        $response = $this->getJson("/api/v1/posts?category={$category1->slug}");

        $response->assertStatus(200)
            ->assertJsonPath('data.length', 3);
    }

    /** @test */
    public function can_filter_posts_by_tag(): void
    {
        $tag = Tag::factory()->create();
        $post = Post::factory()->published()->create();
        $post->tags()->attach($tag);

        Post::factory()->published()->count(2)->create();

        $response = $this->getJson("/api/v1/posts?tag={$tag->slug}");

        $response->assertStatus(200)
            ->assertJsonPath('data.length', 1);
    }

    /** @test */
    public function can_search_posts(): void
    {
        Post::factory()->published()->create(['title' => 'Laravel Best Practices']);
        Post::factory()->published()->create(['title' => 'PHP Tips and Tricks']);

        $response = $this->getJson('/api/v1/posts?search=Laravel');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertContains('Laravel Best Practices', array_column($data, 'title'));
    }

    /** @test */
    public function can_sort_posts(): void
    {
        Post::factory()->published()->create(['title' => 'A Post', 'published_at' => now()->subDays(2)]);
        Post::factory()->published()->create(['title' => 'B Post', 'published_at' => now()->subDays(1)]);
        Post::factory()->published()->create(['title' => 'C Post', 'published_at' => now()]);

        $response = $this->getJson('/api/v1/posts?sort=title&order=asc');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertEquals('A Post', $data[0]['title']);
    }

    /** @test */
    public function can_paginate_posts(): void
    {
        Post::factory()->published()->count(25)->create();

        $response = $this->getJson('/api/v1/posts?per_page=10&page=1');

        $response->assertStatus(200)
            ->assertJsonPath('meta.per_page', 10)
            ->assertJsonPath('meta.total', 25);
    }

    /** @test */
    public function can_get_single_post_by_slug(): void
    {
        $post = Post::factory()->published()->create([
            'slug' => 'test-post-slug',
        ]);

        $response = $this->getJson('/api/v1/posts/test-post-slug');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.slug', 'test-post-slug');
    }

    /** @test */
    public function can_get_single_post_by_id(): void
    {
        $post = Post::factory()->published()->create();

        $response = $this->getJson("/api/v1/posts/{$post->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $post->id);
    }

    /** @test */
    public function returns_404_for_nonexistent_post(): void
    {
        $response = $this->getJson('/api/v1/posts/nonexistent-slug');

        $response->assertStatus(404)
            ->assertJsonPath('success', false);
    }

    /** @test */
    public function draft_post_is_not_visible_to_guests(): void
    {
        $post = Post::factory()->draft()->create();

        $response = $this->getJson("/api/v1/posts/{$post->slug}");

        $response->assertStatus(404);
    }

    /** @test */
    public function draft_post_is_visible_to_author(): void
    {
        $post = Post::factory()->draft()->create(['user_id' => $this->author->id]);

        $response = $this->actingAs($this->author)
            ->getJson("/api/v1/posts/{$post->slug}");

        $response->assertStatus(200);
    }

    /** @test */
    public function draft_post_is_visible_to_editor(): void
    {
        $post = Post::factory()->draft()->create(['user_id' => $this->author->id]);

        $response = $this->actingAs($this->editor)
            ->getJson("/api/v1/posts/{$post->slug}");

        $response->assertStatus(200);
    }

    /** @test */
    public function author_can_create_post(): void
    {
        $postData = [
            'title' => 'New Test Post',
            'content' => 'This is the content of the test post. It has enough words to pass validation.',
            'excerpt' => 'Test excerpt',
            'category_id' => $this->category->id,
            'status' => 'draft',
        ];

        $response = $this->actingAs($this->author)
            ->postJson('/api/v1/posts', $postData);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.title', 'New Test Post');

        $this->assertDatabaseHas('posts', [
            'title' => 'New Test Post',
            'user_id' => $this->author->id,
            'status' => 'draft',
        ]);
    }

    /** @test */
    public function post_creation_requires_title(): void
    {
        $postData = [
            'content' => 'This is the content of the test post. It has enough words to pass validation.',
            'category_id' => $this->category->id,
        ];

        $response = $this->actingAs($this->author)
            ->postJson('/api/v1/posts', $postData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('title');
    }

    /** @test */
    public function post_creation_requires_content(): void
    {
        $postData = [
            'title' => 'Test Post',
            'category_id' => $this->category->id,
        ];

        $response = $this->actingAs($this->author)
            ->postJson('/api/v1/posts', $postData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('content');
    }

    /** @test */
    public function post_creation_requires_category(): void
    {
        $postData = [
            'title' => 'Test Post',
            'content' => 'This is the content of the test post. It has enough words to pass validation.',
        ];

        $response = $this->actingAs($this->author)
            ->postJson('/api/v1/posts', $postData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('category_id');
    }

    /** @test */
    public function slug_is_auto_generated_from_title(): void
    {
        $postData = [
            'title' => 'My Amazing Blog Post',
            'content' => 'This is the content of the test post. It has enough words to pass validation.',
            'category_id' => $this->category->id,
        ];

        $response = $this->actingAs($this->author)
            ->postJson('/api/v1/posts', $postData);

        $response->assertStatus(201);
        $this->assertDatabaseHas('posts', [
            'slug' => 'my-amazing-blog-post',
        ]);
    }

    /** @test */
    public function duplicate_slug_gets_increment(): void
    {
        Post::factory()->create(['slug' => 'test-post']);

        $postData = [
            'title' => 'Test Post',
            'content' => 'This is the content of the test post. It has enough words to pass validation.',
            'category_id' => $this->category->id,
        ];

        $response = $this->actingAs($this->author)
            ->postJson('/api/v1/posts', $postData);

        $response->assertStatus(201);
        $this->assertDatabaseHas('posts', [
            'slug' => 'test-post-1',
        ]);
    }

    /** @test */
    public function author_can_update_own_post(): void
    {
        $post = Post::factory()->draft()->create(['user_id' => $this->author->id]);

        $updateData = [
            'title' => 'Updated Title',
            'content' => 'Updated content with more words for validation purposes.',
        ];

        $response = $this->actingAs($this->author)
            ->putJson("/api/v1/posts/{$post->id}", $updateData);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'title' => 'Updated Title',
        ]);
    }

    /** @test */
    public function author_cannot_update_others_post(): void
    {
        $post = Post::factory()->draft()->create(['user_id' => $this->editor->id]);

        $updateData = [
            'title' => 'Updated Title',
        ];

        $response = $this->actingAs($this->author)
            ->putJson("/api/v1/posts/{$post->id}", $updateData);

        $response->assertStatus(403);
    }

    /** @test */
    public function editor_can_update_any_post(): void
    {
        $post = Post::factory()->draft()->create(['user_id' => $this->author->id]);

        $updateData = [
            'title' => 'Editor Updated Title',
        ];

        $response = $this->actingAs($this->editor)
            ->putJson("/api/v1/posts/{$post->id}", $updateData);

        $response->assertStatus(200);
        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'title' => 'Editor Updated Title',
        ]);
    }

    /** @test */
    public function author_can_delete_own_post(): void
    {
        $post = Post::factory()->draft()->create(['user_id' => $this->author->id]);

        $response = $this->actingAs($this->author)
            ->deleteJson("/api/v1/posts/{$post->id}");

        $response->assertStatus(204);
        $this->assertSoftDeleted('posts', ['id' => $post->id]);
    }

    /** @test */
    public function author_cannot_delete_others_post(): void
    {
        $post = Post::factory()->draft()->create(['user_id' => $this->editor->id]);

        $response = $this->actingAs($this->author)
            ->deleteJson("/api/v1/posts/{$post->id}");

        $response->assertStatus(403);
    }

    /** @test */
    public function admin_can_delete_any_post(): void
    {
        $post = Post::factory()->draft()->create(['user_id' => $this->author->id]);

        $response = $this->actingAs($this->admin)
            ->deleteJson("/api/v1/posts/{$post->id}");

        $response->assertStatus(204);
        $this->assertSoftDeleted('posts', ['id' => $post->id]);
    }

    /** @test */
    public function deleted_post_can_be_restored_by_admin(): void
    {
        $post = Post::factory()->draft()->create(['user_id' => $this->author->id]);
        $post->delete();

        $response = $this->actingAs($this->admin)
            ->postJson("/api/v1/posts/{$post->id}/restore");

        $response->assertStatus(200);
        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'deleted_at' => null,
        ]);
    }

    /** @test */
    public function reading_time_is_calculated_automatically(): void
    {
        $content = str_repeat('This is a test word. ', 400); // ~800 words = 4 min read

        $postData = [
            'title' => 'Long Post',
            'content' => $content,
            'category_id' => $this->category->id,
        ];

        $this->actingAs($this->author)
            ->postJson('/api/v1/posts', $postData);

        $this->assertDatabaseHas('posts', [
            'title' => 'Long Post',
            'reading_time' => 4,
        ]);
    }

    /** @test */
    public function post_creation_with_tags(): void
    {
        $tags = Tag::factory()->count(3)->create();

        $postData = [
            'title' => 'Post with Tags',
            'content' => 'This is the content of the test post. It has enough words to pass validation.',
            'category_id' => $this->category->id,
            'tags' => $tags->pluck('id')->toArray(),
        ];

        $response = $this->actingAs($this->author)
            ->postJson('/api/v1/posts', $postData);

        $response->assertStatus(201);

        $postId = $response->json('data.id');
        $post = Post::find($postId);

        $this->assertEquals(3, $post->tags()->count());
    }
}
