<?php

namespace Tests\Feature\Authorization;

use App\Models\User;
use Spatie\Permission\PermissionRegistrar;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Class UserPolicyTest
 *
 * Tests for UserPolicy authorization rules including role assignment.
 */
class UserPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Clear cached permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    /** @test */
    public function admin_can_assign_roles(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->syncRoles('admin');
        
        $user = User::factory()->create(['role' => 'user']);
        $user->syncRoles('subscriber');

        $this->assertTrue($admin->can('assignRole', $user));
    }

    /** @test */
    public function user_cannot_assign_roles_to_self(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->syncRoles('admin');

        $this->assertFalse($admin->can('assignRole', $admin));
    }

    /** @test */
    public function admin_cannot_assign_roles_to_super_admin(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->syncRoles('admin');
        
        $superAdmin = User::factory()->create(['role' => 'super-admin']);
        $superAdmin->syncRoles('super-admin');

        $this->assertFalse($admin->can('assignRole', $superAdmin));
    }

    /** @test */
    public function admin_cannot_revoke_roles_from_super_admin(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->syncRoles('admin');
        
        $superAdmin = User::factory()->create(['role' => 'super-admin']);
        $superAdmin->syncRoles('super-admin');

        $this->assertFalse($admin->can('revokeRole', $superAdmin));
    }

    /** @test */
    public function admin_can_ban_non_admin_user(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->syncRoles('admin');
        
        $user = User::factory()->create(['role' => 'user']);
        $user->syncRoles('subscriber');

        $this->assertTrue($admin->can('ban', $user));
    }

    /** @test */
    public function admin_cannot_ban_super_admin(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->syncRoles('admin');
        
        $superAdmin = User::factory()->create(['role' => 'super-admin']);
        $superAdmin->syncRoles('super-admin');

        $this->assertFalse($admin->can('ban', $superAdmin));
    }

    /** @test */
    public function moderator_can_ban_regular_user(): void
    {
        $moderator = User::factory()->create(['role' => 'moderator']);
        $moderator->syncRoles('moderator');
        
        $user = User::factory()->create(['role' => 'user']);
        $user->syncRoles('subscriber');

        $this->assertTrue($moderator->can('ban', $user));
    }

    /** @test */
    public function moderator_cannot_ban_staff(): void
    {
        $moderator = User::factory()->create(['role' => 'moderator']);
        $moderator->syncRoles('moderator');
        
        $editor = User::factory()->create(['role' => 'editor']);
        $editor->syncRoles('editor');

        $this->assertFalse($moderator->can('ban', $editor));
    }

    /** @test */
    public function user_can_view_own_profile(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $user->syncRoles('subscriber');

        $this->assertTrue($user->can('view', $user));
    }

    /** @test */
    public function admin_can_view_any_user(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->syncRoles('admin');
        
        $user = User::factory()->create(['role' => 'user']);
        $user->syncRoles('subscriber');

        $this->assertTrue($admin->can('view', $user));
    }

    /** @test */
    public function user_cannot_delete_self(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $user->syncRoles('subscriber');

        $this->assertFalse($user->can('delete', $user));
    }

    /** @test */
    public function admin_can_delete_non_admin_user(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->syncRoles('admin');
        
        $user = User::factory()->create(['role' => 'user']);
        $user->syncRoles('subscriber');

        $this->assertTrue($admin->can('delete', $user));
    }

    /** @test */
    public function admin_cannot_delete_super_admin(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->syncRoles('admin');
        
        $superAdmin = User::factory()->create(['role' => 'super-admin']);
        $superAdmin->syncRoles('super-admin');

        $this->assertFalse($admin->can('delete', $superAdmin));
    }
}
