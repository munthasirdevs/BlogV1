<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_view_published_posts(): void
    {
        Post::factory()->count(3)->create(['status' => 'published']);

        $response = $this->get(route('posts.index'));

        $response->assertStatus(200);
    }

    public function test_can_create_post(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $response = $this->actingAs($user)->post(route('posts.store'), [
            'title' => 'Test Post',
            'content' => 'This is the post content.',
            'category_id' => $category->id,
            'status' => 'draft',
        ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('posts', [
            'title' => 'Test Post',
            'status' => 'draft',
        ]);
    }

    public function test_can_update_post(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create([
            'author_id' => $user->id,
            'title' => 'Original Title',
        ]);

        $response = $this->actingAs($user)->put(route('posts.update', $post), [
            'title' => 'Updated Title',
            'content' => 'Updated content.',
        ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'title' => 'Updated Title',
        ]);
    }

    public function test_can_delete_post(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create([
            'author_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->delete(route('posts.destroy', $post));

        $response->assertStatus(302);
        $this->assertSoftDeleted($post);
    }

    public function test_post_publishing_workflow(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $response = $this->actingAs($user)->post(route('posts.store'), [
            'title' => 'Draft Post',
            'content' => 'Draft content.',
            'category_id' => $category->id,
            'status' => 'draft',
        ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('posts', ['title' => 'Draft Post', 'status' => 'draft']);

        $post = Post::where('title', 'Draft Post')->first();

        $response = $this->actingAs($user)->put(route('posts.update', $post), [
            'title' => 'Draft Post',
            'content' => 'Draft content.',
            'status' => 'published',
        ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'status' => 'published',
        ]);
    }
}
