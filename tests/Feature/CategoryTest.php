<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_view_category_list(): void
    {
        Category::factory()->count(3)->create();

        $response = $this->get(route('categories.index'));

        $response->assertStatus(200);
    }

    public function test_can_create_category(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('categories.store'), [
            'name' => 'Test Category',
            'slug' => 'test-category',
            'status' => 'published',
        ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('categories', [
            'name' => 'Test Category',
            'slug' => 'test-category',
        ]);
    }

    public function test_can_update_category(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create([
            'name' => 'Original Name',
        ]);

        $response = $this->actingAs($user)->put(route('categories.update', $category), [
            'name' => 'Updated Name',
            'slug' => 'updated-name',
        ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'Updated Name',
        ]);
    }

    public function test_can_delete_category(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $response = $this->actingAs($user)->delete(route('categories.destroy', $category));

        $response->assertStatus(302);
        $this->assertSoftDeleted($category);
    }
}
