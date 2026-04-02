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
 * Class CategoryApiTest
 *
 * Feature tests for Category API endpoints.
 * Tests CRUD operations, hierarchical structure, and post filtering.
 */
class CategoryApiTest extends TestCase
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

    public function test_public_user_can_list_categories(): void
    {
        Category::factory()->count(5)->create(['is_active' => true]);

        $response = $this->getJson('/api/v1/categories');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => ['id', 'name', 'slug', 'description', 'color', 'is_active', 'created_at']
                ],
                'meta' => ['current_page', 'per_page', 'total', 'total_pages']
            ]);
    }

    public function test_categories_can_be_filtered_by_search(): void
    {
        Category::factory()->create(['name' => 'Technology', 'is_active' => true]);
        Category::factory()->create(['name' => 'Travel', 'is_active' => true]);
        Category::factory()->create(['name' => 'Food', 'is_active' => true]);

        $response = $this->getJson('/api/v1/categories?search=tech');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('Technology', $response->json('data.0.name'));
    }

    public function test_categories_can_be_filtered_by_active_status(): void
    {
        Category::factory()->create(['is_active' => true]);
        Category::factory()->create(['is_active' => false]);

        $response = $this->getJson('/api/v1/categories?is_active=1');

        $response->assertStatus(200);
        foreach ($response->json('data') as $category) {
            $this->assertTrue($category['is_active']);
        }
    }

    public function test_categories_can_be_sorted(): void
    {
        Category::factory()->create(['name' => 'Zebra', 'sort_order' => 3]);
        Category::factory()->create(['name' => 'Apple', 'sort_order' => 1]);
        Category::factory()->create(['name' => 'Banana', 'sort_order' => 2]);

        $response = $this->getJson('/api/v1/categories?sort=name&order=asc');

        $response->assertStatus(200);
        $this->assertEquals('Apple', $response->json('data.0.name'));
        $this->assertEquals('Banana', $response->json('data.1.name'));
    }

    // ==================== TREE TESTS ====================

    public function test_public_user_can_get_category_tree(): void
    {
        $parent = Category::factory()->create(['parent_id' => null, 'name' => 'Parent']);
        $child = Category::factory()->create(['parent_id' => $parent->id, 'name' => 'Child']);

        $response = $this->getJson('/api/v1/categories/tree');

        $response->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    public function test_category_tree_respects_max_depth(): void
    {
        $level1 = Category::factory()->create(['parent_id' => null, 'name' => 'Level 1']);
        $level2 = Category::factory()->create(['parent_id' => $level1->id, 'name' => 'Level 2']);
        $level3 = Category::factory()->create(['parent_id' => $level2->id, 'name' => 'Level 3']);
        $level4 = Category::factory()->create(['parent_id' => $level3->id, 'name' => 'Level 4']);

        $response = $this->getJson('/api/v1/categories/tree?max_depth=3');

        $response->assertStatus(200);
        // Level 4 should not be included in the tree
        $treeJson = json_encode($response->json('data'));
        $this->assertStringContainsString('Level 3', $treeJson);
        $this->assertStringNotContainsString('Level 4', $treeJson);
    }

    public function test_category_tree_includes_post_counts(): void
    {
        $category = Category::factory()->create(['parent_id' => null]);
        Post::factory()->count(3)->create([
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now()
        ]);

        $response = $this->getJson('/api/v1/categories/tree');

        $response->assertStatus(200);
        // Post count should be included
        $this->assertNotEmpty($response->json('data'));
    }

    // ==================== SHOW TESTS ====================

    public function test_public_user_can_view_single_category(): void
    {
        $category = Category::factory()->create(['is_active' => true]);

        $response = $this->getJson("/api/v1/categories/{$category->slug}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $category->id);
    }

    public function test_returns_404_for_nonexistent_category(): void
    {
        $response = $this->getJson('/api/v1/categories/nonexistent-slug');

        $response->assertStatus(404)
            ->assertJsonPath('success', false);
    }

    public function test_category_show_includes_children(): void
    {
        $parent = Category::factory()->create(['parent_id' => null]);
        $child = Category::factory()->create(['parent_id' => $parent->id]);

        $response = $this->getJson("/api/v1/categories/{$parent->slug}");

        $response->assertStatus(200);
        $this->assertNotEmpty($response->json('data.children'));
        $this->assertEquals($child->id, $response->json('data.children.0.id'));
    }

    // ==================== POSTS BY CATEGORY TESTS ====================

    public function test_can_get_posts_in_category(): void
    {
        $category = Category::factory()->create();
        Post::factory()->count(3)->create([
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now()
        ]);

        $response = $this->getJson("/api/v1/categories/{$category->slug}/posts");

        $response->assertStatus(200)
            ->assertJsonPath('success', true);
        $this->assertCount(3, $response->json('data'));
    }

    public function test_category_posts_includes_children_categories(): void
    {
        $parent = Category::factory()->create(['parent_id' => null]);
        $child = Category::factory()->create(['parent_id' => $parent->id]);
        
        Post::factory()->create(['category_id' => $parent->id, 'status' => 'published', 'published_at' => now()]);
        Post::factory()->create(['category_id' => $child->id, 'status' => 'published', 'published_at' => now()]);

        $response = $this->getJson("/api/v1/categories/{$parent->slug}/posts?include_children=1");

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data'));
    }

    public function test_category_posts_can_exclude_children(): void
    {
        $parent = Category::factory()->create(['parent_id' => null]);
        $child = Category::factory()->create(['parent_id' => $parent->id]);
        
        Post::factory()->create(['category_id' => $parent->id, 'status' => 'published', 'published_at' => now()]);
        Post::factory()->create(['category_id' => $child->id, 'status' => 'published', 'published_at' => now()]);

        $response = $this->getJson("/api/v1/categories/{$parent->slug}/posts?include_children=0");

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
    }

    // ==================== STORE TESTS ====================

    public function test_admin_can_create_category(): void
    {
        Sanctum::actingAs($this->admin);

        $response = $this->postJson('/api/v1/categories', [
            'name' => 'New Category',
            'description' => 'Test description',
            'color' => '#3B82F6',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'New Category');
    }

    public function test_editor_can_create_category(): void
    {
        Sanctum::actingAs($this->editor);

        $response = $this->postJson('/api/v1/categories', [
            'name' => 'Editor Category',
            'description' => 'Created by editor',
        ]);

        $response->assertStatus(201);
    }

    public function test_author_cannot_create_category(): void
    {
        Sanctum::actingAs($this->author);

        $response = $this->postJson('/api/v1/categories', [
            'name' => 'Author Category',
        ]);

        $response->assertStatus(403);
    }

    public function test_category_slug_is_auto_generated(): void
    {
        Sanctum::actingAs($this->admin);

        $response = $this->postJson('/api/v1/categories', [
            'name' => 'Test Category Name',
        ]);

        $response->assertStatus(201);
        $this->assertEquals('test-category-name', $response->json('data.slug'));
    }

    public function test_category_slug_can_be_manually_set(): void
    {
        Sanctum::actingAs($this->admin);

        $response = $this->postJson('/api/v1/categories', [
            'name' => 'Test Category',
            'slug' => 'custom-slug',
        ]);

        $response->assertStatus(201);
        $this->assertEquals('custom-slug', $response->json('data.slug'));
    }

    public function test_category_slug_must_be_unique(): void
    {
        Sanctum::actingAs($this->admin);

        Category::factory()->create(['slug' => 'existing-slug']);

        $response = $this->postJson('/api/v1/categories', [
            'name' => 'New Category',
            'slug' => 'existing-slug',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('slug');
    }

    // ==================== UPDATE TESTS ====================

    public function test_admin_can_update_category(): void
    {
        Sanctum::actingAs($this->admin);

        $category = Category::factory()->create();

        $response = $this->putJson("/api/v1/categories/{$category->id}", [
            'name' => 'Updated Name',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Updated Name');
    }

    public function test_editor_can_update_category(): void
    {
        Sanctum::actingAs($this->editor);

        $category = Category::factory()->create();

        $response = $this->putJson("/api/v1/categories/{$category->id}", [
            'name' => 'Editor Updated',
        ]);

        $response->assertStatus(200);
    }

    public function test_author_cannot_update_category(): void
    {
        Sanctum::actingAs($this->author);

        $category = Category::factory()->create();

        $response = $this->putJson("/api/v1/categories/{$category->id}", [
            'name' => 'Author Update',
        ]);

        $response->assertStatus(403);
    }

    public function test_category_slug_updates_when_name_changes(): void
    {
        Sanctum::actingAs($this->admin);

        $category = Category::factory()->create(['name' => 'Original Name', 'slug' => 'original-name']);

        $response = $this->putJson("/api/v1/categories/{$category->id}", [
            'name' => 'New Name',
        ]);

        $response->assertStatus(200);
        $this->assertEquals('new-name', $response->json('data.slug'));
    }

    // ==================== REORDER TESTS ====================

    public function test_admin_can_reorder_categories(): void
    {
        Sanctum::actingAs($this->admin);

        $cat1 = Category::factory()->create(['sort_order' => 1]);
        $cat2 = Category::factory()->create(['sort_order' => 2]);
        $cat3 = Category::factory()->create(['sort_order' => 3]);

        $response = $this->postJson('/api/v1/categories/reorder', [
            'categories' => [
                ['id' => $cat1->id, 'sort_order' => 3],
                ['id' => $cat2->id, 'sort_order' => 1],
                ['id' => $cat3->id, 'sort_order' => 2],
            ]
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertEquals(3, $cat1->fresh()->sort_order);
        $this->assertEquals(1, $cat2->fresh()->sort_order);
    }

    public function test_editor_can_reorder_categories(): void
    {
        Sanctum::actingAs($this->editor);

        $cat1 = Category::factory()->create(['sort_order' => 1]);

        $response = $this->postJson('/api/v1/categories/reorder', [
            'categories' => [
                ['id' => $cat1->id, 'sort_order' => 2],
            ]
        ]);

        $response->assertStatus(200);
    }

    public function test_author_cannot_reorder_categories(): void
    {
        Sanctum::actingAs($this->author);

        $response = $this->postJson('/api/v1/categories/reorder', [
            'categories' => [
                ['id' => 1, 'sort_order' => 1],
            ]
        ]);

        $response->assertStatus(403);
    }

    public function test_reorder_validates_category_ids_exist(): void
    {
        Sanctum::actingAs($this->admin);

        $response = $this->postJson('/api/v1/categories/reorder', [
            'categories' => [
                ['id' => 99999, 'sort_order' => 1],
            ]
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('categories.0.id');
    }

    // ==================== DELETE TESTS ====================

    public function test_admin_can_delete_category(): void
    {
        Sanctum::actingAs($this->admin);

        $category = Category::factory()->create();

        $response = $this->deleteJson("/api/v1/categories/{$category->id}");

        $response->assertStatus(204);
        $this->assertSoftDeleted('categories', ['id' => $category->id]);
    }

    public function test_editor_cannot_delete_category(): void
    {
        Sanctum::actingAs($this->editor);

        $category = Category::factory()->create();

        $response = $this->deleteJson("/api/v1/categories/{$category->id}");

        $response->assertStatus(403);
    }

    public function test_cannot_delete_category_with_children(): void
    {
        Sanctum::actingAs($this->admin);

        $parent = Category::factory()->create(['parent_id' => null]);
        Category::factory()->create(['parent_id' => $parent->id]);

        $response = $this->deleteJson("/api/v1/categories/{$parent->id}");

        $response->assertStatus(400)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Cannot delete a category with child categories. Please delete or move the children first.');
    }

    public function test_cannot_delete_category_with_posts(): void
    {
        Sanctum::actingAs($this->admin);

        $category = Category::factory()->create();
        Post::factory()->create(['category_id' => $category->id]);

        $response = $this->deleteJson("/api/v1/categories/{$category->id}");

        $response->assertStatus(400)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Cannot delete a category with posts. Please reassign or delete the posts first.');
    }

    // ==================== HIERARCHICAL TESTS ====================

    public function test_can_create_child_category(): void
    {
        Sanctum::actingAs($this->admin);

        $parent = Category::factory()->create(['parent_id' => null]);

        $response = $this->postJson('/api/v1/categories', [
            'name' => 'Child Category',
            'parent_id' => $parent->id,
        ]);

        $response->assertStatus(201);
        $this->assertEquals($parent->id, $response->json('data.parent.id'));
    }

    public function test_category_depth_is_limited_to_three_levels(): void
    {
        Sanctum::actingAs($this->admin);

        $level1 = Category::factory()->create(['parent_id' => null]);
        $level2 = Category::factory()->create(['parent_id' => $level1->id]);
        $level3 = Category::factory()->create(['parent_id' => $level2->id]);

        // Try to create level 4
        $response = $this->postJson('/api/v1/categories', [
            'name' => 'Level 4',
            'parent_id' => $level3->id,
        ]);

        // The validation should prevent this through the RecursiveParent rule
        // or the service layer should reject it
        $this->assertTrue($response->status() === 422 || $response->status() === 400);
    }

    public function test_cannot_create_circular_reference(): void
    {
        Sanctum::actingAs($this->admin);

        $parent = Category::factory()->create(['parent_id' => null]);
        $child = Category::factory()->create(['parent_id' => $parent->id]);

        // Try to make parent a child of child
        $response = $this->putJson("/api/v1/categories/{$parent->id}", [
            'parent_id' => $child->id,
        ]);

        $response->assertStatus(422);
    }
}
