<?php

namespace Tests\Feature\Services;

use App\Services\UserService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Class UserServiceTest
 *
 * Tests for the UserService class.
 */
class UserServiceTest extends TestCase
{
    use RefreshDatabase;

    protected UserService $userService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->userService = new UserService();
    }

    public function test_create_user(): void
    {
        $data = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
        ];

        $user = $this->userService->createUser($data);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('Test User', $user->name);
        $this->assertEquals('test@example.com', $user->email);
        $this->assertTrue(Hash::check('SecurePass123!', $user->password));
        $this->assertEquals('user', $user->role);
        $this->assertEquals('active', $user->status);
    }

    public function test_update_user(): void
    {
        $user = User::create([
            'name' => 'Original Name',
            'email' => 'original@example.com',
            'password' => Hash::make('password'),
            'role' => 'user',
        ]);

        $updated = $this->userService->updateUser($user->id, [
            'name' => 'Updated Name',
            'bio' => 'Test bio',
        ]);

        $this->assertEquals('Updated Name', $updated->name);
        $this->assertEquals('Test bio', $updated->bio);
        $this->assertEquals('original@example.com', $updated->email);
    }

    public function test_update_password(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('oldpassword'),
        ]);

        $updated = $this->userService->updatePassword($user->id, 'NewSecurePass123!');

        $this->assertTrue(Hash::check('NewSecurePass123!', $updated->password));
    }

    public function test_assign_role(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'role' => 'user',
        ]);

        $updated = $this->userService->assignRole($user, 'editor');

        $this->assertEquals('editor', $updated->role);
        $this->assertTrue($updated->hasRole('editor'));
    }

    public function test_ban_user(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'status' => 'active',
        ]);

        $banned = $this->userService->banUser($user->id, 'Test reason');

        $this->assertEquals('banned', $banned->status);
        $this->assertTrue($banned->isBanned());
    }

    public function test_unban_user(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'status' => 'banned',
        ]);

        $unbanned = $this->userService->unbanUser($user->id);

        $this->assertEquals('active', $unbanned->status);
        $this->assertTrue($unbanned->isActive());
    }

    public function test_find_by_email(): void
    {
        User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
        ]);

        $user = $this->userService->findByEmail('test@example.com');

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('test@example.com', $user->email);
    }

    public function test_find_by_email_returns_null_when_not_found(): void
    {
        $user = $this->userService->findByEmail('nonexistent@example.com');

        $this->assertNull($user);
    }

    public function test_get_paginated_users(): void
    {
        User::factory()->count(20)->create();

        $paginated = $this->userService->getPaginatedUsers([], 10);

        $this->assertEquals(10, $paginated->perPage());
        $this->assertGreaterThanOrEqual(20, $paginated->total());
    }

    public function test_get_paginated_users_with_filters(): void
    {
        User::factory()->create(['role' => 'admin', 'status' => 'active']);
        User::factory()->create(['role' => 'user', 'status' => 'active']);
        User::factory()->create(['role' => 'user', 'status' => 'banned']);

        $paginated = $this->userService->getPaginatedUsers([
            'role' => 'user',
            'status' => 'active',
        ], 10);

        $this->assertEquals(1, $paginated->total());
        $this->assertEquals('user', $paginated->first()->role);
    }

    public function test_search_users(): void
    {
        User::factory()->create(['name' => 'John Doe', 'email' => 'john@example.com']);
        User::factory()->create(['name' => 'Jane Smith', 'email' => 'jane@example.com']);

        $users = $this->userService->searchUsers('John');

        $this->assertCount(1, $users);
        $this->assertEquals('John Doe', $users->first()->name);
    }

    public function test_is_email_available(): void
    {
        User::create([
            'name' => 'Test User',
            'email' => 'taken@example.com',
            'password' => Hash::make('password'),
        ]);

        $this->assertFalse($this->userService->isEmailAvailable('taken@example.com'));
        $this->assertTrue($this->userService->isEmailAvailable('available@example.com'));
    }

    public function test_is_email_available_excludes_current_user(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
        ]);

        $this->assertTrue($this->userService->isEmailAvailable('test@example.com', $user->id));
    }

    public function test_delete_user(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
        ]);

        $result = $this->userService->deleteUser($user->id);

        $this->assertTrue($result);
        $this->assertSoftDeleted('users', ['id' => $user->id]);
    }

    public function test_cannot_delete_last_admin(): void
    {
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot delete the last admin user');

        $this->userService->deleteUser($admin->id);
    }
}
