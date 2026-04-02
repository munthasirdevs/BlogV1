<?php

namespace Tests\Feature\Services;

use App\Services\CategoryService;
use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Class CategoryServiceTest
 *
 * Tests for the CategoryService class.
 */
class CategoryServiceTest extends TestCase
{
    use RefreshDatabase;

    protected CategoryService $categoryService;
    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->categoryService = new CategoryService();
        
        $this->adminUser = User::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);
    }

    public function test_create_category(): void
    {
        $data = [
            'name' => 'Technology',
            'description' => 'Tech related posts',
            'color' => '#3B82F6',
        ];

        $category = $this->categoryService->createCategory($data);

        $this->assertInstanceOf(Category::class, $category);
        $this->assertEquals('Technology', $category->name);
        $this->assertEquals('technology', $category->slug);
        $this->assertEquals('#3B82F6', $category->color);
        $this->assertTrue($category->is_active);
    }

    public function test_create_category_generates_unique_slug(): void
    {
        Category::create([
            'name' => 'Technology',
            'slug' => 'technology',
        ]);

        $category = $this->categoryService->createCategory([
            'name' => 'Technology',
        ]);

        $this->assertEquals('technology-1', $category->slug);
    }

    public function test_update_category(): void
    {
        $category = Category::create([
            'name' => 'Original',
            'slug' => 'original',
        ]);

        $updated = $this->categoryService->updateCategory($category->id, [
            'name' => 'Updated',
            'description' => 'New description',
        ]);

        $this->assertEquals('Updated', $updated->name);
        $this->assertEquals('updated', $updated->slug);
        $this->assertEquals('New description', $updated->description);
    }

    public function test_find_by_slug(): void
    {
        Category::create([
            'name' => 'Technology',
            'slug' => 'technology',
        ]);

        $category = $this->categoryService->findBySlug('technology');

        $this->assertInstanceOf(Category::class, $category);
        $this->assertEquals('Technology', $category->name);
    }

    public function test_get_category_tree(): void
    {
        $parent = Category::create([
            'name' => 'Parent',
            'slug' => 'parent',
            'sort_order' => 1,
        ]);

        $child = Category::create([
            'name' => 'Child',
            'slug' => 'child',
            'parent_id' => $parent->id,
            'sort_order' => 1,
        ]);

        $tree = $this->categoryService->getCategoryTree();

        $this->assertCount(1, $tree);
        $this->assertEquals('Parent', $tree->first()->name);
        $this->assertCount(1, $tree->first()->children);
    }

    public function test_get_active_categories(): void
    {
        Category::create(['name' => 'Active', 'slug' => 'active', 'is_active' => true]);
        Category::create(['name' => 'Inactive', 'slug' => 'inactive', 'is_active' => false]);

        $categories = $this->categoryService->getActiveCategories();

        $this->assertCount(1, $categories);
        $this->assertEquals('Active', $categories->first()->name);
    }

    public function test_get_featured_categories(): void
    {
        Category::create(['name' => 'Featured', 'slug' => 'featured', 'is_featured' => true]);
        Category::create(['name' => 'Normal', 'slug' => 'normal', 'is_featured' => false]);

        $categories = $this->categoryService->getFeaturedCategories();

        $this->assertCount(1, $categories);
        $this->assertEquals('Featured', $categories->first()->name);
    }

    public function test_get_children(): void
    {
        $parent = Category::create(['name' => 'Parent', 'slug' => 'parent']);
        Category::create(['name' => 'Child 1', 'slug' => 'child-1', 'parent_id' => $parent->id]);
        Category::create(['name' => 'Child 2', 'slug' => 'child-2', 'parent_id' => $parent->id]);

        $children = $this->categoryService->getChildren($parent->id);

        $this->assertCount(2, $children);
    }

    public function test_delete_category_with_children_fails(): void
    {
        $parent = Category::create(['name' => 'Parent', 'slug' => 'parent']);
        Category::create(['name' => 'Child', 'slug' => 'child', 'parent_id' => $parent->id]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot delete a category with child categories');

        $this->categoryService->deleteCategory($parent->id);
    }

    public function test_delete_category_with_posts_fails(): void
    {
        $category = Category::create(['name' => 'Category', 'slug' => 'category']);
        
        Post::create([
            'title' => 'Test Post',
            'slug' => 'test-post',
            'content' => 'Content',
            'user_id' => $this->adminUser->id,
            'category_id' => $category->id,
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot delete a category with posts');

        $this->categoryService->deleteCategory($category->id);
    }

    public function test_delete_category_without_children_or_posts(): void
    {
        $category = Category::create(['name' => 'Category', 'slug' => 'category']);

        $result = $this->categoryService->deleteCategory($category->id);

        $this->assertTrue($result);
        $this->assertSoftDeleted('categories', ['id' => $category->id]);
    }

    public function test_move_category(): void
    {
        $parent = Category::create(['name' => 'Parent', 'slug' => 'parent']);
        $category = Category::create(['name' => 'Category', 'slug' => 'category']);

        $moved = $this->categoryService->moveCategory($category->id, $parent->id);

        $this->assertEquals($parent->id, $moved->parent_id);
    }

    public function test_move_category_to_descendant_fails(): void
    {
        $parent = Category::create(['name' => 'Parent', 'slug' => 'parent']);
        $child = Category::create(['name' => 'Child', 'slug' => 'child', 'parent_id' => $parent->id]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot move category to one of its descendants');

        $this->categoryService->moveCategory($parent->id, $child->id);
    }

    public function test_get_paginated_categories(): void
    {
        Category::factory()->count(20)->create();

        $paginated = $this->categoryService->getPaginatedCategories([], 10);

        $this->assertEquals(10, $paginated->perPage());
        $this->assertGreaterThanOrEqual(20, $paginated->total());
    }

    public function test_search_categories(): void
    {
        Category::create(['name' => 'Technology', 'slug' => 'technology']);
        Category::create(['name' => 'Science', 'slug' => 'science']);

        $categories = $this->categoryService->searchCategories('Tech');

        $this->assertCount(1, $categories);
        $this->assertEquals('Technology', $categories->first()->name);
    }
}
