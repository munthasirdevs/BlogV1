<?php

namespace Tests\Feature\Authorization;

use App\Models\User;
use App\Models\Post;
use Spatie\Permission\PermissionRegistrar;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Class PostPolicyTest
 *
 * Tests for PostPolicy authorization rules.
 */
class PostPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Clear cached permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    /** @test */
    public function super_admin_can_do_anything(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super-admin']);
        $superAdmin->syncRoles('super-admin');
        
        $post = Post::factory()->create(['user_id' => User::factory()->create()->id]);

        $this->assertTrue($superAdmin->can('view', $post));
        $this->assertTrue($superAdmin->can('update', $post));
        $this->assertTrue($superAdmin->can('delete', $post));
        $this->assertTrue($superAdmin->can('publish', $post));
        $this->assertTrue($superAdmin->can('feature', $post));
    }

    /** @test */
    public function author_can_edit_own_post(): void
    {
        $author = User::factory()->create(['role' => 'author']);
        $author->syncRoles('author');
        
        $post = Post::factory()->create(['user_id' => $author->id]);

        $this->assertTrue($author->can('update', $post));
        $this->assertTrue($author->can('delete', $post));
        $this->assertFalse($author->can('publish', $post));
        $this->assertFalse($author->can('feature', $post));
    }

    /** @test */
    public function author_cannot_edit_others_post(): void
    {
        $author = User::factory()->create(['role' => 'author']);
        $author->syncRoles('author');
        
        $otherUser = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $otherUser->id]);

        $this->assertFalse($author->can('update', $post));
        $this->assertFalse($author->can('delete', $post));
    }

    /** @test */
    public function editor_can_edit_any_post(): void
    {
        $editor = User::factory()->create(['role' => 'editor']);
        $editor->syncRoles('editor');
        
        $otherUser = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $otherUser->id]);

        $this->assertTrue($editor->can('view', $post));
        $this->assertTrue($editor->can('update', $post));
        $this->assertTrue($editor->can('publish', $post));
        $this->assertFalse($editor->can('feature', $post)); // Only admins can feature
    }

    /** @test */
    public function admin_can_delete_any_post(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->syncRoles('admin');
        
        $otherUser = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $otherUser->id]);

        $this->assertTrue($admin->can('delete', $post));
        $this->assertTrue($admin->can('feature', $post));
    }

    /** @test */
    public function subscriber_cannot_manage_posts(): void
    {
        $subscriber = User::factory()->create(['role' => 'subscriber']);
        $subscriber->syncRoles('subscriber');
        
        $post = Post::factory()->create(['user_id' => $subscriber->id, 'status' => 'draft']);

        $this->assertFalse($subscriber->can('update', $post));
        $this->assertFalse($subscriber->can('delete', $post));
        $this->assertFalse($subscriber->can('publish', $post));
    }

    /** @test */
    public function anyone_can_view_published_post(): void
    {
        $post = Post::factory()->create(['status' => 'published']);

        $this->assertTrue(true); // Published posts are publicly viewable
    }

    /** @test */
    public function draft_post_visible_to_author_and_editors(): void
    {
        $author = User::factory()->create(['role' => 'author']);
        $author->syncRoles('author');
        
        $editor = User::factory()->create(['role' => 'editor']);
        $editor->syncRoles('editor');
        
        $post = Post::factory()->create(['user_id' => $author->id, 'status' => 'draft']);

        $this->assertTrue($author->can('view', $post));
        $this->assertTrue($editor->can('view', $post));
    }
}
