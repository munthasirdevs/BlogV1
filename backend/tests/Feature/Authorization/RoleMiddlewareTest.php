<?php

namespace Tests\Feature\Authorization;

use App\Models\User;
use App\Models\Post;
use App\Models\Comment;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Class RoleMiddlewareTest
 *
 * Tests for the RoleMiddleware authorization checks.
 */
class RoleMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Clear cached permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    /** @test */
    public function unauthenticated_user_cannot_access_role_protected_route(): void
    {
        $response = $this->getJson('/api/v1/admin/dashboard');

        $response->assertStatus(401);
    }

    /** @test */
    public function user_without_required_role_gets_forbidden(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $user->syncRoles('subscriber');

        $response = $this->actingAs($user)
            ->getJson('/api/v1/admin/dashboard');

        $response->assertStatus(403)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Forbidden. Insufficient role permissions.');
    }

    /** @test */
    public function user_with_required_role_can_access(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->syncRoles('admin');

        $response = $this->actingAs($admin)
            ->getJson('/api/v1/admin/dashboard');

        $response->assertStatus(200);
    }

    /** @test */
    public function user_with_any_of_multiple_roles_can_access(): void
    {
        $editor = User::factory()->create(['role' => 'editor']);
        $editor->syncRoles('editor');

        // Editor should be able to access admin routes with role:admin,editor middleware
        $response = $this->actingAs($editor)
            ->getJson('/api/v1/admin/dashboard');

        $response->assertStatus(200);
    }
}
