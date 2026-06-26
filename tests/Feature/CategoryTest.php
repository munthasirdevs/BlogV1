<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    }

    public function test_public_category_page_shows_posts(): void
    {
        $category = Category::factory()->published()->create();
        $response = $this->get(route('category.show', $category->slug));
        $response->assertStatus(200);
    }

    public function test_admin_can_view_category_list(): void
    {
        $user = User::factory()->create()->assignRole('admin');
        Category::factory()->count(3)->create();

        $response = $this->actingAs($user)->get(route('admin.categories.index'));
        $response->assertStatus(200);
    }

    public function test_admin_can_create_category(): void
    {
        $user = User::factory()->create()->assignRole('admin');

        $response = $this->actingAs($user)->post(route('admin.categories.store'), [
            'name' => 'Test Category',
            'slug' => 'test-category',
            'status' => 'published',
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('categories', [
            'name' => 'Test Category',
            'slug' => 'test-category',
        ]);
    }

    public function test_admin_can_update_category(): void
    {
        $user = User::factory()->create()->assignRole('admin');
        $category = Category::factory()->create(['name' => 'Original']);

        $response = $this->actingAs($user)->put(route('admin.categories.update', $category), [
            'name' => 'Updated',
            'slug' => 'updated',
            'status' => 'published',
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'Updated',
        ]);
    }

    public function test_admin_can_delete_category(): void
    {
        $user = User::factory()->create()->assignRole('admin');
        $category = Category::factory()->create();

        $response = $this->actingAs($user)->delete(route('admin.categories.destroy', $category));
        $response->assertSessionHas('success');
        $this->assertSoftDeleted($category);
    }

    public function test_admin_can_restore_category(): void
    {
        $user = User::factory()->create()->assignRole('admin');
        $category = Category::factory()->create();
        $category->delete();

        $response = $this->actingAs($user)->post(route('admin.categories.restore', $category->id));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('categories', ['id' => $category->id, 'deleted_at' => null]);
    }

    public function test_circular_reference_is_prevented(): void
    {
        $user = User::factory()->create()->assignRole('admin');
        $parent = Category::factory()->create();
        $child = Category::factory()->create(['parent_id' => $parent->id]);

        $response = $this->actingAs($user)->put(route('admin.categories.update', $parent), [
            'name' => $parent->name,
            'slug' => $parent->slug,
            'parent_id' => $child->id,
            'status' => 'published',
        ]);

        $response->assertStatus(422);
    }

    public function test_category_slug_must_be_unique(): void
    {
        $user = User::factory()->create()->assignRole('admin');
        Category::factory()->create(['slug' => 'test-slug']);

        $response = $this->actingAs($user)->post(route('admin.categories.store'), [
            'name' => 'Test',
            'slug' => 'test-slug',
            'status' => 'published',
        ]);

        $response->assertSessionHasErrors('slug');
    }

    public function test_public_cannot_access_admin_category_routes(): void
    {
        $response = $this->get(route('admin.categories.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_category_hierarchy_shows_on_public_page(): void
    {
        $parent = Category::factory()->published()->create(['name' => 'Tech']);
        $child = Category::factory()->published()->create(['name' => 'Laravel', 'parent_id' => $parent->id]);

        $response = $this->get(route('category.show', $child->slug));
        $response->assertStatus(200);
    }

    public function test_article_count_syncs_on_post_create(): void
    {
        $category = Category::factory()->published()->create(['article_count' => 0]);
        $user = User::factory()->create()->assignRole('admin');

        $this->actingAs($user)->post(route('admin.posts.store'), [
            'title' => 'Test Post',
            'slug' => 'test-post',
            'content' => 'Content here',
            'category_id' => $category->id,
            'status' => 'published',
        ]);

        $this->assertDatabaseHas('categories', ['id' => $category->id, 'article_count' => 1]);
    }
}
