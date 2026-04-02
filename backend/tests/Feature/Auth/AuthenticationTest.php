<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\VerifyEmail;
use App\Notifications\ResetPassword;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test user registration with valid data.
     */
    public function test_user_can_register(): void
    {
        Event::fake([Registered::class]);
        Notification::fake();

        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Registration successful. Please verify your email.',
            ])
            ->assertJsonPath('data.user.name', 'Test User')
            ->assertJsonPath('data.user.email', 'test@example.com');

        // Verify user was created in database
        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'role' => 'user',
        ]);

        // Verify email verification notification was sent
        $user = User::where('email', 'test@example.com')->first();
        Notification::assertSentTo($user, VerifyEmail::class);

        // Verify registered event was dispatched
        Event::assertDispatched(Registered::class, function ($event) use ($user) {
            return $event->user->id === $user->id;
        });
    }

    /**
     * Test registration fails with weak password.
     */
    public function test_registration_fails_with_weak_password(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'weak',
            'password_confirmation' => 'weak',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('password');
    }

    /**
     * Test registration fails with password missing numbers.
     */
    public function test_registration_fails_with_password_missing_numbers(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'PasswordNoNumbers',
            'password_confirmation' => 'PasswordNoNumbers',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('password');
    }

    /**
     * Test registration fails with password missing uppercase.
     */
    public function test_registration_fails_with_password_missing_uppercase(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('password');
    }

    /**
     * Test registration fails with duplicate email.
     */
    public function test_registration_fails_with_duplicate_email(): void
    {
        User::factory()->create(['email' => 'test@example.com']);

        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('email');
    }

    /**
     * Test registration fails with invalid email format.
     */
    public function test_registration_fails_with_invalid_email(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Test User',
            'email' => 'invalid-email',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('email');
    }

    /**
     * Test registration fails with missing name.
     */
    public function test_registration_fails_with_missing_name(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'email' => 'test@example.com',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('name');
    }

    /**
     * Test user login with valid credentials.
     */
    public function test_user_can_login(): void
    {
        $password = 'Password123';
        $user = User::factory()->create([
            'email' => 'login_' . uniqid() . '@example.com',
            'password' => Hash::make($password),
            'email_verified_at' => null,
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => $password,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Login successful',
                'data' => [
                    'token_type' => 'Bearer',
                ],
            ])
            ->assertJsonPath('data.user.email', $user->email);

        // Verify token was created
        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_type' => User::class,
            'tokenable_id' => $user->id,
        ]);
    }

    /**
     * Test user login with remember me.
     */
    public function test_user_can_login_with_remember_me(): void
    {
        $password = 'Password123';
        $user = User::factory()->create([
            'email' => 'remember_' . uniqid() . '@example.com',
            'password' => Hash::make($password),
            'email_verified_at' => null,
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => $password,
            'remember' => true,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'token_type' => 'Bearer',
                ],
            ]);
        
        // Verify expires_in is 30 days in seconds (30 * 24 * 60 * 60 = 2592000)
        $responseData = $response->json('data.expires_in');
        $this->assertEquals(2592000, $responseData);
    }

    /**
     * Test login fails with invalid credentials.
     */
    public function test_login_fails_with_invalid_credentials(): void
    {
        User::factory()->create(['email' => 'test@example.com']);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'WrongPassword123',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Invalid credentials',
            ]);
    }

    /**
     * Test login fails with non-existent email.
     */
    public function test_login_fails_with_nonexistent_email(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'Password123',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Invalid credentials',
            ]);
    }

    /**
     * Test login fails for banned user.
     */
    public function test_login_fails_for_banned_user(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('Password123'),
            'status' => 'banned',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'Password123',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Your account has been banned. Please contact support.',
            ]);
    }

    /**
     * Test authenticated user can logout.
     */
    public function test_user_can_logout(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/v1/auth/logout');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Logged out successfully',
            ]);

        // Verify token count is 0 (token was revoked)
        $this->assertEquals(0, $user->fresh()->tokens()->count());
    }

    /**
     * Test user can logout from all devices.
     */
    public function test_user_can_logout_from_all_devices(): void
    {
        $user = User::factory()->create();
        
        // Create multiple tokens
        $user->createToken('token-1')->plainTextToken;
        $user->createToken('token-2')->plainTextToken;
        $token = $user->createToken('token-3')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/v1/auth/logout-all');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Logged out from all devices successfully',
            ]);

        // Verify all tokens were revoked
        $this->assertEquals(0, $user->tokens()->count());
    }

    /**
     * Test user can refresh token.
     */
    public function test_user_can_refresh_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/v1/auth/refresh');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Token refreshed successfully',
            ])
            ->assertJsonStructure([
                'data' => [
                    'token',
                    'token_type',
                    'expires_in',
                ],
            ]);
    }

    /**
     * Test authenticated user can get their profile.
     */
    public function test_user_can_get_profile(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/v1/auth/me');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $user->id,
                    'email' => $user->email,
                    'name' => $user->name,
                ],
            ]);
    }

    /**
     * Test unauthenticated user cannot access protected routes.
     */
    public function test_unauthenticated_user_cannot_access_protected_routes(): void
    {
        $response = $this->getJson('/api/v1/auth/me');

        $response->assertStatus(401);
    }
}
