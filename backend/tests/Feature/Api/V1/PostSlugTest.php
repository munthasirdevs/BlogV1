<?php

namespace Tests\Feature\Api\V1;

use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Class PostSlugTest
 *
 * Feature tests for Post slug generation and uniqueness.
 */
class PostSlugTest extends TestCase
{
    use RefreshDatabase;

    protected User $author;
    protected Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->author = User::factory()->withRole('user')->create(); // Using 'user' role as author
        $this->category = Category::factory()->create();
    }

    /** @test */
    public function slug_is_generated_from_title(): void
    {
        $postData = [
            'title' => 'My Amazing Blog Post Title',
            'content' => 'This is the content of the test post. It has enough words to pass validation.',
            'category_id' => $this->category->id,
        ];

        $response = $this->actingAs($this->author)
            ->postJson('/api/v1/posts', $postData);

        $response->assertStatus(201);
        $this->assertDatabaseHas('posts', [
            'slug' => 'my-amazing-blog-post-title',
        ]);
    }

    /** @test */
    public function slug_handles_special_characters(): void
    {
        $postData = [
            'title' => 'Post with Special Characters: @#$% & More!',
            'content' => 'This is the content of the test post. It has enough words to pass validation.',
            'category_id' => $this->category->id,
        ];

        $response = $this->actingAs($this->author)
            ->postJson('/api/v1/posts', $postData);

        $response->assertStatus(201);
        $post = Post::find($response->json('data.id'));
        // Special characters should be removed, spaces become hyphens
        $this->assertEquals('post-with-special-characters-more', $post->slug);
    }

    /** @test */
    public function slug_handles_non_ascii_characters(): void
    {
        $postData = [
            'title' => 'Post with émojis and àccénts',
            'content' => 'This is the content of the test post. It has enough words to pass validation.',
            'category_id' => $this->category->id,
        ];

        $response = $this->actingAs($this->author)
            ->postJson('/api/v1/posts', $postData);

        $response->assertStatus(201);
        $post = Post::find($response->json('data.id'));
        // Non-ASCII characters should be transliterated or removed
        $this->assertNotEmpty($post->slug);
        $this->assertStringContainsString('post-with', $post->slug);
    }

    /** @test */
    public function duplicate_slug_gets_increment_suffix(): void
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
    public function multiple_duplicate_slugs_get_sequential_numbers(): void
    {
        Post::factory()->create(['slug' => 'test-post']);
        Post::factory()->create(['slug' => 'test-post-1']);
        Post::factory()->create(['slug' => 'test-post-2']);

        $postData = [
            'title' => 'Test Post',
            'content' => 'This is the content of the test post. It has enough words to pass validation.',
            'category_id' => $this->category->id,
        ];

        $response = $this->actingAs($this->author)
            ->postJson('/api/v1/posts', $postData);

        $response->assertStatus(201);
        $this->assertDatabaseHas('posts', [
            'slug' => 'test-post-3',
        ]);
    }

    /** @test */
    public function custom_slug_can_be_provided(): void
    {
        $postData = [
            'title' => 'My Post Title',
            'slug' => 'custom-slug-here',
            'content' => 'This is the content of the test post. It has enough words to pass validation.',
            'category_id' => $this->category->id,
        ];

        $response = $this->actingAs($this->author)
            ->postJson('/api/v1/posts', $postData);

        $response->assertStatus(201);
        $this->assertDatabaseHas('posts', [
            'slug' => 'custom-slug-here',
        ]);
    }

    /** @test */
    public function custom_slug_must_be_unique(): void
    {
        Post::factory()->create(['slug' => 'existing-slug']);

        $postData = [
            'title' => 'My Post',
            'slug' => 'existing-slug',
            'content' => 'This is the content of the test post. It has enough words to pass validation.',
            'category_id' => $this->category->id,
        ];

        $response = $this->actingAs($this->author)
            ->postJson('/api/v1/posts', $postData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('slug');
    }

    /** @test */
    public function slug_is_updated_when_title_changes(): void
    {
        $post = Post::factory()->draft()->create([
            'user_id' => $this->author->id,
            'title' => 'Original Title',
            'slug' => 'original-title',
        ]);

        $updateData = [
            'title' => 'New Updated Title',
        ];

        $response = $this->actingAs($this->author)
            ->putJson("/api/v1/posts/{$post->id}", $updateData);

        $response->assertStatus(200);
        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'slug' => 'new-updated-title',
        ]);
    }

    /** @test */
    public function manually_set_slug_is_not_overridden_on_title_update(): void
    {
        $post = Post::factory()->draft()->create([
            'user_id' => $this->author->id,
            'title' => 'Original Title',
            'slug' => 'custom-manual-slug',
        ]);

        $updateData = [
            'title' => 'New Title',
        ];

        $this->actingAs($this->author)
            ->putJson("/api/v1/posts/{$post->id}", $updateData);

        // The slug should remain as the custom one since it was manually set
        $post->refresh();
        $this->assertEquals('custom-manual-slug', $post->slug);
    }

    /** @test */
    public function slug_is_unique_across_deleted_posts(): void
    {
        $deletedPost = Post::factory()->create(['slug' => 'test-slug']);
        $deletedPost->delete();

        $postData = [
            'title' => 'Test Post',
            'slug' => 'test-slug',
            'content' => 'This is the content of the test post. It has enough words to pass validation.',
            'category_id' => $this->category->id,
        ];

        $response = $this->actingAs($this->author)
            ->postJson('/api/v1/posts', $postData);

        // Should fail because slug exists even in trashed posts
        $response->assertStatus(422)
            ->assertJsonValidationErrors('slug');
    }

    /** @test */
    public function slug_update_checks_uniqueness_excluding_current_post(): void
    {
        $post = Post::factory()->draft()->create([
            'user_id' => $this->author->id,
            'title' => 'Original',
            'slug' => 'original-slug',
        ]);

        // Update without changing slug - should succeed
        $updateData = [
            'title' => 'Updated Original',
            'slug' => 'original-slug',
        ];

        $response = $this->actingAs($this->author)
            ->putJson("/api/v1/posts/{$post->id}", $updateData);

        $response->assertStatus(200);
    }

    /** @test */
    public function very_long_title_generates_reasonable_slug(): void
    {
        $longTitle = str_repeat('Very Long Title Word ', 50);

        $postData = [
            'title' => $longTitle,
            'content' => 'This is the content of the test post. It has enough words to pass validation.',
            'category_id' => $this->category->id,
        ];

        $response = $this->actingAs($this->author)
            ->postJson('/api/v1/posts', $postData);

        $response->assertStatus(201);
        $post = Post::find($response->json('data.id'));
        $this->assertLessThanOrEqual(255, strlen($post->slug));
    }

    /** @test */
    public function slug_with_only_special_characters_is_handled(): void
    {
        $postData = [
            'title' => '@#$%^&*()',
            'content' => 'This is the content of the test post. It has enough words to pass validation.',
            'category_id' => $this->category->id,
        ];

        $response = $this->actingAs($this->author)
            ->postJson('/api/v1/posts', $postData);

        $response->assertStatus(201);
        $post = Post::find($response->json('data.id'));
        // Should generate some fallback slug
        $this->assertNotEmpty($post->slug);
    }

    /** @test */
    public function slug_with_numbers_is_preserved(): void
    {
        $postData = [
            'title' => 'Top 10 Laravel Tips for 2024',
            'content' => 'This is the content of the test post. It has enough words to pass validation.',
            'category_id' => $this->category->id,
        ];

        $response = $this->actingAs($this->author)
            ->postJson('/api/v1/posts', $postData);

        $response->assertStatus(201);
        $this->assertDatabaseHas('posts', [
            'slug' => 'top-10-laravel-tips-for-2024',
        ]);
    }

    /** @test */
    public function slug_with_hyphens_in_title_is_normalized(): void
    {
        $postData = [
            'title' => 'Post - With - Multiple - Hyphens',
            'content' => 'This is the content of the test post. It has enough words to pass validation.',
            'category_id' => $this->category->id,
        ];

        $response = $this->actingAs($this->author)
            ->postJson('/api/v1/posts', $postData);

        $response->assertStatus(201);
        // Multiple hyphens should be normalized
        $post = Post::find($response->json('data.id'));
        $this->assertStringContainsString('post-with-multiple-hyphens', $post->slug);
    }
}
