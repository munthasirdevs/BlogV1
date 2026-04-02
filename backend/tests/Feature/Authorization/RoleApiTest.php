<?php

namespace Tests\Feature\Authorization;

use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Class RoleApiTest
 *
 * Tests for role and permission API endpoints.
 */
class RoleApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Clear cached permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Create default roles
        $this->createDefaultRoles();
    }

    protected function createDefaultRoles(): void
    {
        $permissions = [
            'create-post', 'edit-post', 'delete-post', 'publish-post', 'feature-post',
            'create-comment', 'delete-comment', 'approve-comment',
            'manage-users', 'manage-roles', 'ban-users',
            'manage-settings', 'manage-categories', 'manage-tags',
            'access-admin-panel', 'view-analytics', 'manage-media',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission, 'guard_name' => 'sanctum']);
        }

        $roles = ['super-admin', 'admin', 'editor', 'moderator', 'author', 'subscriber'];
        foreach ($roles as $role) {
            Role::create(['name' => $role, 'guard_name' => 'sanctum']);
        }
    }

    /** @test */
    public function unauthenticated_user_cannot_list_roles(): void
    {
        $response = $this->getJson('/api/v1/admin/roles');

        $response->assertStatus(401);
    }

    /** @test */
    public function non_admin_user_cannot_list_roles(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $user->syncRoles('subscriber');

        $response = $this->actingAs($user)
            ->getJson('/api/v1/admin/roles');

        $response->assertStatus(403);
    }

    /** @test */
    public function admin_can_list_roles(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->syncRoles('admin');

        $response = $this->actingAs($admin)
            ->getJson('/api/v1/admin/roles');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => ['id', 'name', 'guard_name', 'permissions_count', 'permissions'],
                ],
                'meta',
            ]);
    }

    /** @test */
    public function admin_can_list_permissions(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->syncRoles('admin');

        $response = $this->actingAs($admin)
            ->getJson('/api/v1/admin/roles/permissions');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'data',
                'grouped' => ['posts', 'comments', 'users', 'settings', 'system'],
                'meta',
            ]);
    }

    /** @test */
    public function admin_can_get_role_permissions(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->syncRoles('admin');

        $response = $this->actingAs($admin)
            ->getJson('/api/v1/admin/roles/admin/permissions');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'data' => ['id', 'name', 'guard_name', 'permissions', 'permissions_count'],
            ]);
    }

    /** @test */
    public function admin_can_assign_roles_to_user(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->syncRoles('admin');
        
        $user = User::factory()->create(['role' => 'user']);
        $user->syncRoles('subscriber');

        $response = $this->actingAs($admin)
            ->postJson('/api/v1/admin/users/' . $user->id . '/roles', [
                'roles' => ['author'],
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.roles', ['author']);

        $this->assertTrue($user->fresh()->hasRole('author'));
    }

    /** @test */
    public function admin_cannot_assign_super_admin_role(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->syncRoles('admin');
        
        $user = User::factory()->create(['role' => 'user']);
        $user->syncRoles('subscriber');

        $response = $this->actingAs($admin)
            ->postJson('/api/v1/admin/users/' . $user->id . '/roles', [
                'roles' => ['super-admin'],
            ]);

        $response->assertStatus(403)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Cannot assign super-admin role. This action is restricted.');
    }

    /** @test */
    public function admin_can_revoke_role_from_user(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->syncRoles('admin');
        
        $user = User::factory()->create(['role' => 'user']);
        $user->syncRoles(['subscriber', 'author']);

        $response = $this->actingAs($admin)
            ->deleteJson('/api/v1/admin/users/' . $user->id . '/roles/author');

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertFalse($user->fresh()->hasRole('author'));
        $this->assertTrue($user->fresh()->hasRole('subscriber'));
    }

    /** @test */
    public function cannot_revoke_last_role_from_user(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->syncRoles('admin');
        
        $user = User::factory()->create(['role' => 'user']);
        $user->syncRoles('subscriber');

        $response = $this->actingAs($admin)
            ->deleteJson('/api/v1/admin/users/' . $user->id . '/roles/subscriber');

        $response->assertStatus(400)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Cannot revoke last role. User must have at least one role.');
    }

    /** @test */
    public function admin_can_get_user_permissions(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->syncRoles('admin');
        
        $user = User::factory()->create(['role' => 'user']);
        $user->syncRoles('author');

        $response = $this->actingAs($admin)
            ->getJson('/api/v1/admin/users/' . $user->id . '/permissions');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'user_id',
                    'roles',
                    'direct_permissions',
                    'role_permissions',
                    'all_permissions',
                    'permissions_count',
                ],
            ]);
    }

    /** @test */
    public function user_can_view_own_permissions(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $user->syncRoles('author');

        $response = $this->actingAs($user)
            ->getJson('/api/v1/admin/users/' . $user->id . '/permissions');

        $response->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    /** @test */
    public function non_admin_cannot_view_other_users_permissions(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $user->syncRoles('author');
        
        $otherUser = User::factory()->create(['role' => 'user']);
        $otherUser->syncRoles('subscriber');

        $response = $this->actingAs($user)
            ->getJson('/api/v1/admin/users/' . $otherUser->id . '/permissions');

        $response->assertStatus(403);
    }
}
