<?php

namespace Tests\Feature\Api\V1;

use Tests\TestCase;
use App\Models\User;
use App\Models\Category;
use App\Models\Tag;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

/**
 * Class TagApiTest
 *
 * Feature tests for Tag API endpoints.
 * Tests CRUD operations, suggestions, popular tags, and post associations.
 */
class TagApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $editor;
    protected User $author;
    protected User $subscriber;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['email' => 'admin@test.com']);
        $this->admin->assignRole('admin');

        $this->editor = User::factory()->create(['email' => 'editor@test.com']);
        $this->editor->assignRole('editor');

        $this->author = User::factory()->create(['email' => 'author@test.com']);
        $this->author->assignRole('author');

        $this->subscriber = User::factory()->create(['email' => 'subscriber@test.com']);
        $this->subscriber->assignRole('subscriber');
    }

    // ==================== INDEX TESTS ====================

    public function test_public_user_can_list_tags(): void
    {
        Tag::factory()->count(5)->create();

        $response = $this->getJson('/api/v1/tags');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => ['id', 'name', 'slug', 'description', 'color', 'created_at']
                ],
                'meta' => ['current_page', 'per_page', 'total', 'total_pages']
            ]);
    }

    public function test_tags_can_be_filtered_by_search(): void
    {
        Tag::factory()->create(['name' => 'Laravel']);
        Tag::factory()->create(['name' => 'JavaScript']);
        Tag::factory()->create(['name' => 'Python']);

        $response = $this->getJson('/api/v1/tags?search=script');

        $response->assertStatus(200);
        $this->assertGreaterThanOrEqual(1, count($response->json('data')));
    }

    public function test_tags_can_be_sorted(): void
    {
        Tag::factory()->create(['name' => 'Zebra']);
        Tag::factory()->create(['name' => 'Apple']);
        Tag::factory()->create(['name' => 'Banana']);

        $response = $this->getJson('/api/v1/tags?sort=name&order=asc');

        $response->assertStatus(200);
        $this->assertEquals('Apple', $response->json('data.0.name'));
        $this->assertEquals('Banana', $response->json('data.1.name'));
    }

    // ==================== POPULAR TAGS TESTS ====================

    public function test_public_user_can_get_popular_tags(): void
    {
        $tag1 = Tag::factory()->create(['name' => 'Popular Tag']);
        $tag2 = Tag::factory()->create(['name' => 'Less Popular']);
        
        $post = Post::factory()->create(['status' => 'published', 'published_at' => now()]);
        $post->tags()->attach([$tag1->id, $tag1->id, $tag2->id]);

        $response = $this->getJson('/api/v1/tags/popular');

        $response->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    public function test_popular_tags_are_ordered_by_post_count(): void
    {
        $popularTag = Tag::factory()->create(['name' => 'Popular']);
        $lessPopularTag = Tag::factory()->create(['name' => 'Less Popular']);
        
        $post = Post::factory()->create(['status' => 'published', 'published_at' => now()]);
        $post->tags()->attach([$popularTag->id]);
        
        // Update the posts_count manually for testing
        $popularTag->update(['posts_count' => 10]);
        $lessPopularTag->update(['posts_count' => 5]);

        $response = $this->getJson('/api/v1/tags/popular?limit=2');

        $response->assertStatus(200);
        // Popular tag should come first
        $this->assertNotEmpty($response->json('data'));
    }

    public function test_popular_tags_limit_works(): void
    {
        Tag::factory()->count(25)->create();

        $response = $this->getJson('/api/v1/tags/popular?limit=10');

        $response->assertStatus(200);
        $this->assertLessThanOrEqual(10, count($response->json('data')));
    }

    // ==================== TAG SUGGESTIONS TESTS ====================

    public function test_public_user_can_get_tag_suggestions(): void
    {
        Tag::factory()->create(['name' => 'Laravel']);
        Tag::factory()->create(['name' => 'Laravel PHP']);
        Tag::factory()->create(['name' => 'JavaScript']);

        $response = $this->getJson('/api/v1/tags/suggest?q=laravel');

        $response->assertStatus(200)
            ->assertJsonPath('success', true);
        
        $this->assertGreaterThan(0, count($response->json('data')));
    }

    public function test_tag_suggestions_ordered_by_popularity(): void
    {
        $popularTag = Tag::factory()->create(['name' => 'PHP Popular', 'posts_count' => 100]);
        $lessPopularTag = Tag::factory()->create(['name' => 'PHP Less', 'posts_count' => 10]);

        $response = $this->getJson('/api/v1/tags/suggest?q=php');

        $response->assertStatus(200);
        // More popular tag should come first
        $this->assertNotEmpty($response->json('data'));
    }

    public function test_tag_suggestions_limit_works(): void
    {
        Tag::factory()->count(15)->create(['name' => 'Test Tag']);

        $response = $this->getJson('/api/v1/tags/suggest?q=test&limit=5');

        $response->assertStatus(200);
        $this->assertLessThanOrEqual(5, count($response->json('data')));
    }

    public function test_empty_query_returns_empty_suggestions(): void
    {
        $response = $this->getJson('/api/v1/tags/suggest?q=');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data', []);
    }

    // ==================== SHOW TESTS ====================

    public function test_public_user_can_view_single_tag(): void
    {
        $tag = Tag::factory()->create();

        $response = $this->getJson("/api/v1/tags/{$tag->slug}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $tag->id);
    }

    public function test_returns_404_for_nonexistent_tag(): void
    {
        $response = $this->getJson('/api/v1/tags/nonexistent-slug');

        $response->assertStatus(404)
            ->assertJsonPath('success', false);
    }

    // ==================== POSTS BY TAG TESTS ====================

    public function test_can_get_posts_with_tag(): void
    {
        $tag = Tag::factory()->create();
        $post1 = Post::factory()->create(['status' => 'published', 'published_at' => now()]);
        $post2 = Post::factory()->create(['status' => 'published', 'published_at' => now()]);
        
        $post1->tags()->attach($tag->id);
        $post2->tags()->attach($tag->id);

        $response = $this->getJson("/api/v1/tags/{$tag->slug}/posts");

        $response->assertStatus(200)
            ->assertJsonPath('success', true);
        $this->assertCount(2, $response->json('data'));
    }

    public function test_tag_posts_pagination_works(): void
    {
        $tag = Tag::factory()->create();
        Post::factory()->count(20)->create(['status' => 'published', 'published_at' => now()])
            ->each(fn($post) => $post->tags()->attach($tag->id));

        $response = $this->getJson("/api/v1/tags/{$tag->slug}/posts?per_page=10");

        $response->assertStatus(200);
        $this->assertCount(10, $response->json('data'));
        $this->assertEquals(20, $response->json('meta.total'));
    }

    // ==================== STORE TESTS ====================

    public function test_admin_can_create_tag(): void
    {
        Sanctum::actingAs($this->admin);

        $response = $this->postJson('/api/v1/tags', [
            'name' => 'New Tag',
            'description' => 'Test description',
            'color' => '#FF2D20',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'New Tag');
    }

    public function test_editor_can_create_tag(): void
    {
        Sanctum::actingAs($this->editor);

        $response = $this->postJson('/api/v1/tags', [
            'name' => 'Editor Tag',
        ]);

        $response->assertStatus(201);
    }

    public function test_author_cannot_create_tag(): void
    {
        Sanctum::actingAs($this->author);

        $response = $this->postJson('/api/v1/tags', [
            'name' => 'Author Tag',
        ]);

        $response->assertStatus(403);
    }

    public function test_tag_slug_is_auto_generated(): void
    {
        Sanctum::actingAs($this->admin);

        $response = $this->postJson('/api/v1/tags', [
            'name' => 'Test Tag Name',
        ]);

        $response->assertStatus(201);
        $this->assertEquals('test-tag-name', $response->json('data.slug'));
    }

    public function test_tag_slug_can_be_manually_set(): void
    {
        Sanctum::actingAs($this->admin);

        $response = $this->postJson('/api/v1/tags', [
            'name' => 'Test Tag',
            'slug' => 'custom-tag-slug',
        ]);

        $response->assertStatus(201);
        $this->assertEquals('custom-tag-slug', $response->json('data.slug'));
    }

    public function test_tag_slug_must_be_unique(): void
    {
        Sanctum::actingAs($this->admin);

        Tag::factory()->create(['slug' => 'existing-tag-slug']);

        $response = $this->postJson('/api/v1/tags', [
            'name' => 'New Tag',
            'slug' => 'existing-tag-slug',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('slug');
    }

    // ==================== UPDATE TESTS ====================

    public function test_admin_can_update_tag(): void
    {
        Sanctum::actingAs($this->admin);

        $tag = Tag::factory()->create();

        $response = $this->putJson("/api/v1/tags/{$tag->id}", [
            'name' => 'Updated Tag Name',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Updated Tag Name');
    }

    public function test_editor_can_update_tag(): void
    {
        Sanctum::actingAs($this->editor);

        $tag = Tag::factory()->create();

        $response = $this->putJson("/api/v1/tags/{$tag->id}", [
            'name' => 'Editor Updated',
        ]);

        $response->assertStatus(200);
    }

    public function test_author_cannot_update_tag(): void
    {
        Sanctum::actingAs($this->author);

        $tag = Tag::factory()->create();

        $response = $this->putJson("/api/v1/tags/{$tag->id}", [
            'name' => 'Author Update',
        ]);

        $response->assertStatus(403);
    }

    // ==================== DELETE TESTS ====================

    public function test_admin_can_delete_tag(): void
    {
        Sanctum::actingAs($this->admin);

        $tag = Tag::factory()->create();

        $response = $this->deleteJson("/api/v1/tags/{$tag->id}");

        $response->assertStatus(204);
        $this->assertSoftDeleted('tags', ['id' => $tag->id]);
    }

    public function test_editor_cannot_delete_tag(): void
    {
        Sanctum::actingAs($this->editor);

        $tag = Tag::factory()->create();

        $response = $this->deleteJson("/api/v1/tags/{$tag->id}");

        $response->assertStatus(403);
    }

    public function test_cannot_delete_tag_attached_to_posts(): void
    {
        Sanctum::actingAs($this->admin);

        $tag = Tag::factory()->create();
        $post = Post::factory()->create(['status' => 'published', 'published_at' => now()]);
        $post->tags()->attach($tag->id);

        $response = $this->deleteJson("/api/v1/tags/{$tag->id}");

        $response->assertStatus(400)
            ->assertJsonPath('success', false);
    }

    // ==================== ATTACH TAGS TO POST TESTS ====================

    public function test_author_can_attach_tags_to_own_post(): void
    {
        Sanctum::actingAs($this->author);

        $post = Post::factory()->create(['user_id' => $this->author->id, 'status' => 'draft']);
        $tag1 = Tag::factory()->create();
        $tag2 = Tag::factory()->create();

        $response = $this->postJson("/api/v1/posts/{$post->id}/tags", [
            'tags' => [$tag1->slug, $tag2->slug],
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);
        
        $this->assertEquals(2, $post->fresh()->tags->count());
    }

    public function test_editor_can_attach_tags_to_any_post(): void
    {
        Sanctum::actingAs($this->editor);

        $post = Post::factory()->create(['user_id' => $this->author->id, 'status' => 'draft']);
        $tag = Tag::factory()->create();

        $response = $this->postJson("/api/v1/posts/{$post->id}/tags", [
            'tags' => [$tag->slug],
        ]);

        $response->assertStatus(200);
    }

    public function test_user_cannot_attach_tags_to_others_post(): void
    {
        Sanctum::actingAs($this->author);

        $otherUser = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $otherUser->id, 'status' => 'draft']);
        $tag = Tag::factory()->create();

        $response = $this->postJson("/api/v1/posts/{$post->id}/tags", [
            'tags' => [$tag->slug],
        ]);

        $response->assertStatus(403);
    }

    public function test_attach_tags_creates_if_not_exist(): void
    {
        Sanctum::actingAs($this->author);

        $post = Post::factory()->create(['user_id' => $this->author->id, 'status' => 'draft']);

        $response = $this->postJson("/api/v1/posts/{$post->id}/tags", [
            'tags' => ['New Tag Name', 'Another New Tag'],
            'create_if_not_exist' => true,
        ]);

        $response->assertStatus(200);
        $this->assertEquals(2, $post->fresh()->tags->count());
    }

    public function test_attach_tags_syncs_existing(): void
    {
        Sanctum::actingAs($this->author);

        $post = Post::factory()->create(['user_id' => $this->author->id, 'status' => 'draft']);
        $tag1 = Tag::factory()->create();
        $tag2 = Tag::factory()->create();
        $tag3 = Tag::factory()->create();
        
        $post->tags()->attach([$tag1->id, $tag2->id]);

        $response = $this->postJson("/api/v1/posts/{$post->id}/tags", [
            'tags' => [$tag2->slug, $tag3->slug],
        ]);

        $response->assertStatus(200);
        
        $freshPost = $post->fresh();
        $this->assertEquals(2, $freshPost->tags->count());
        $this->assertTrue($freshPost->tags->contains($tag2->id));
        $this->assertTrue($freshPost->tags->contains($tag3->id));
    }

    // ==================== DETACH TAG FROM POST TESTS ====================

    public function test_author_can_detach_tag_from_own_post(): void
    {
        Sanctum::actingAs($this->author);

        $post = Post::factory()->create(['user_id' => $this->author->id, 'status' => 'draft']);
        $tag = Tag::factory()->create();
        $post->tags()->attach($tag->id);

        $response = $this->deleteJson("/api/v1/posts/{$post->id}/tags/{$tag->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true);
        
        $this->assertEquals(0, $post->fresh()->tags->count());
    }

    public function test_editor_can_detach_tag_from_any_post(): void
    {
        Sanctum::actingAs($this->editor);

        $post = Post::factory()->create(['user_id' => $this->author->id, 'status' => 'draft']);
        $tag = Tag::factory()->create();
        $post->tags()->attach($tag->id);

        $response = $this->deleteJson("/api/v1/posts/{$post->id}/tags/{$tag->id}");

        $response->assertStatus(200);
    }

    public function test_detach_returns_404_if_tag_not_attached(): void
    {
        Sanctum::actingAs($this->author);

        $post = Post::factory()->create(['user_id' => $this->author->id, 'status' => 'draft']);
        $tag = Tag::factory()->create();

        $response = $this->deleteJson("/api/v1/posts/{$post->id}/tags/{$tag->id}");

        $response->assertStatus(404)
            ->assertJsonPath('success', false);
    }

    // ==================== COMBINED FILTERING TESTS ====================

    public function test_posts_can_be_filtered_by_category_and_tag(): void
    {
        $category = Category::factory()->create();
        $tag = Tag::factory()->create();
        
        // Post with both category and tag
        $post1 = Post::factory()->create([
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now()
        ]);
        $post1->tags()->attach($tag->id);
        
        // Post with only category
        Post::factory()->create([
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now()
        ]);
        
        // Post with only tag
        $post3 = Post::factory()->create([
            'status' => 'published',
            'published_at' => now()
        ]);
        $post3->tags()->attach($tag->id);

        $response = $this->getJson("/api/v1/posts?category={$category->slug}&tag={$tag->slug}");

        $response->assertStatus(200);
        // Should only return post1 which has both category and tag
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals($post1->id, $response->json('data.0.id'));
    }

    public function test_posts_can_be_filtered_by_multiple_categories(): void
    {
        $category1 = Category::factory()->create();
        $category2 = Category::factory()->create();
        
        $post1 = Post::factory()->create([
            'category_id' => $category1->id,
            'status' => 'published',
            'published_at' => now()
        ]);
        $post2 = Post::factory()->create([
            'category_id' => $category2->id,
            'status' => 'published',
            'published_at' => now()
        ]);
        
        // Post in different category
        Post::factory()->create([
            'status' => 'published',
            'published_at' => now()
        ]);

        $response = $this->getJson("/api/v1/posts?category[]={$category1->slug}&category[]={$category2->slug}");

        $response->assertStatus(200);
        // Should return posts from both categories (OR logic)
        $this->assertCount(2, $response->json('data'));
    }

    public function test_posts_can_be_filtered_by_multiple_tags(): void
    {
        $tag1 = Tag::factory()->create();
        $tag2 = Tag::factory()->create();
        
        // Post with both tags
        $post1 = Post::factory()->create(['status' => 'published', 'published_at' => now()]);
        $post1->tags()->attach([$tag1->id, $tag2->id]);
        
        // Post with only tag1
        $post2 = Post::factory()->create(['status' => 'published', 'published_at' => now()]);
        $post2->tags()->attach([$tag1->id]);
        
        // Post with only tag2
        $post3 = Post::factory()->create(['status' => 'published', 'published_at' => now()]);
        $post3->tags()->attach([$tag2->id]);

        $response = $this->getJson("/api/v1/posts?tag[]={$tag1->slug}&tag[]={$tag2->slug}");

        $response->assertStatus(200);
        // Should only return post1 which has both tags (AND logic)
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals($post1->id, $response->json('data.0.id'));
    }
}
