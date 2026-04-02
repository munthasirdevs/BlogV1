<?php

namespace Tests\Feature\Authorization;

use App\Models\User;
use App\Helpers\Ability;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Class AbilityHelperTest
 *
 * Tests for the Ability helper class.
 */
class AbilityHelperTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Clear cached permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        Cache::flush();
    }

    /** @test */
    public function has_permission_checks_user_permission(): void
    {
        $user = User::factory()->create(['role' => 'author']);
        Permission::create(['name' => 'create-post', 'guard_name' => 'sanctum']);
        $role = Role::create(['name' => 'author', 'guard_name' => 'sanctum']);
        $role->givePermissionTo('create-post');
        $user->syncRoles('author');

        $this->assertTrue(Ability::hasPermission($user, 'create-post'));
        $this->assertFalse(Ability::hasPermission($user, 'publish-post'));
    }

    /** @test */
    public function has_any_permission_returns_true_if_user_has_any(): void
    {
        $user = User::factory()->create(['role' => 'author']);
        Permission::create(['name' => 'create-post', 'guard_name' => 'sanctum']);
        Permission::create(['name' => 'edit-post', 'guard_name' => 'sanctum']);
        $role = Role::create(['name' => 'author', 'guard_name' => 'sanctum']);
        $role->givePermissionTo('create-post');
        $user->syncRoles('author');

        $this->assertTrue(Ability::hasAnyPermission($user, ['create-post', 'publish-post']));
    }

    /** @test */
    public function has_all_permissions_returns_true_only_if_user_has_all(): void
    {
        $user = User::factory()->create(['role' => 'author']);
        Permission::create(['name' => 'create-post', 'guard_name' => 'sanctum']);
        Permission::create(['name' => 'edit-post', 'guard_name' => 'sanctum']);
        $role = Role::create(['name' => 'author', 'guard_name' => 'sanctum']);
        $role->givePermissionTo('create-post');
        $user->syncRoles('author');

        $this->assertFalse(Ability::hasAllPermissions($user, ['create-post', 'edit-post']));
    }

    /** @test */
    public function has_role_checks_user_role(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $user->syncRoles('admin');

        $this->assertTrue(Ability::hasRole($user, 'admin'));
        $this->assertFalse(Ability::hasRole($user, 'editor'));
    }

    /** @test */
    public function has_any_role_returns_true_if_user_has_any_role(): void
    {
        $user = User::factory()->create(['role' => 'editor']);
        $user->syncRoles('editor');

        $this->assertTrue(Ability::hasAnyRole($user, ['admin', 'editor']));
    }

    /** @test */
    public function can_edit_post_allows_author_and_editor(): void
    {
        $author = User::factory()->create(['role' => 'author']);
        $author->syncRoles('author');
        
        $editor = User::factory()->create(['role' => 'editor']);
        $editor->syncRoles('editor');
        
        $postUserId = User::factory()->create()->id;

        // Author can edit own post
        $this->assertTrue(Ability::canEditPost($author, $author->id));
        
        // Editor can edit any post
        $this->assertTrue(Ability::canEditPost($editor, $postUserId));
    }

    /** @test */
    public function can_publish_post_allows_only_editors_and_admins(): void
    {
        $author = User::factory()->create(['role' => 'author']);
        $author->syncRoles('author');
        
        $editor = User::factory()->create(['role' => 'editor']);
        $editor->syncRoles('editor');
        
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->syncRoles('admin');

        $this->assertFalse(Ability::canPublishPost($author));
        $this->assertTrue(Ability::canPublishPost($editor));
        $this->assertTrue(Ability::canPublishPost($admin));
    }

    /** @test */
    public function can_manage_comments_allows_moderators_and_admins(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $user->syncRoles('subscriber');
        
        $moderator = User::factory()->create(['role' => 'moderator']);
        $moderator->syncRoles('moderator');
        
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->syncRoles('admin');

        $this->assertFalse(Ability::canManageComments($user));
        $this->assertTrue(Ability::canManageComments($moderator));
        $this->assertTrue(Ability::canManageComments($admin));
    }

    /** @test */
    public function can_access_admin_panel_allows_staff(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $user->syncRoles('subscriber');
        
        $editor = User::factory()->create(['role' => 'editor']);
        $editor->syncRoles('editor');
        
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->syncRoles('admin');

        $this->assertFalse(Ability::canAccessAdminPanel($user));
        $this->assertTrue(Ability::canAccessAdminPanel($editor));
        $this->assertTrue(Ability::canAccessAdminPanel($admin));
    }

    /** @test */
    public function invalidate_cache_clears_user_cache(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $user->syncRoles('admin');

        // Populate cache
        Ability::hasRole($user, 'admin');

        // Verify cache exists
        $cacheKey = Ability::CACHE_PREFIX . "user:{$user->id}:role:admin";
        $this->assertTrue(Cache::has($cacheKey));

        // Invalidate cache
        Ability::invalidateCache($user);

        // Cache should be cleared
        $this->assertFalse(Cache::has($cacheKey));
    }
}
