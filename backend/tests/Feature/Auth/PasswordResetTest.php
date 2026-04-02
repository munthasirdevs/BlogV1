<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\ResetPassword;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test user can request password reset link.
     */
    public function test_user_can_request_password_reset_link(): void
    {
        Notification::fake();

        $user = User::factory()->create(['email' => 'test@example.com']);

        $response = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'test@example.com',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Password reset link has been sent to your email.',
            ]);

        // Verify reset notification was sent
        Notification::assertSentTo($user, ResetPassword::class);
    }

    /**
     * Test password reset request with non-existent email.
     */
    public function test_password_reset_request_with_nonexistent_email(): void
    {
        Notification::fake();

        $response = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'nonexistent@example.com',
        ]);

        // Should not reveal if email exists
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'If the email exists, a password reset link has been sent.',
            ]);

        // Verify no notification was sent
        Notification::assertNothingSent();
    }

    /**
     * Test password reset request with invalid email format.
     */
    public function test_password_reset_request_with_invalid_email(): void
    {
        $response = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'invalid-email',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('email');
    }

    /**
     * Test user can reset password with valid token.
     */
    public function test_user_can_reset_password_with_valid_token(): void
    {
        Event::fake([PasswordReset::class]);
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('OldPassword123'),
        ]);

        // Request password reset
        $token = Password::createToken($user);

        $response = $this->postJson('/api/v1/auth/reset-password', [
            'token' => $token,
            'email' => 'test@example.com',
            'password' => 'NewPassword123',
            'password_confirmation' => 'NewPassword123',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Password has been reset successfully.',
            ]);

        // Verify password was updated
        $this->assertTrue(Hash::check('NewPassword123', $user->fresh()->password));

        // Verify PasswordReset event was dispatched
        Event::assertDispatched(PasswordReset::class, function ($event) use ($user) {
            return $event->user->id === $user->id;
        });
    }

    /**
     * Test password reset fails with invalid token.
     */
    public function test_password_reset_fails_with_invalid_token(): void
    {
        $user = User::factory()->create(['email' => 'test@example.com']);

        $response = $this->postJson('/api/v1/auth/reset-password', [
            'token' => 'invalid-token',
            'email' => 'test@example.com',
            'password' => 'NewPassword123',
            'password_confirmation' => 'NewPassword123',
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'This password reset token is invalid.',
            ]);
    }

    /**
     * Test password reset fails with weak new password.
     */
    public function test_password_reset_fails_with_weak_password(): void
    {
        $user = User::factory()->create(['email' => 'test@example.com']);
        $token = Password::createToken($user);

        $response = $this->postJson('/api/v1/auth/reset-password', [
            'token' => $token,
            'email' => 'test@example.com',
            'password' => 'weak',
            'password_confirmation' => 'weak',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('password');
    }

    /**
     * Test password reset fails with mismatched passwords.
     */
    public function test_password_reset_fails_with_mismatched_passwords(): void
    {
        $user = User::factory()->create(['email' => 'test@example.com']);
        $token = Password::createToken($user);

        $response = $this->postJson('/api/v1/auth/reset-password', [
            'token' => $token,
            'email' => 'test@example.com',
            'password' => 'NewPassword123',
            'password_confirmation' => 'DifferentPassword123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('password');
    }

    /**
     * Test password reset fails with non-existent email.
     */
    public function test_password_reset_fails_with_nonexistent_email(): void
    {
        $response = $this->postJson('/api/v1/auth/reset-password', [
            'token' => 'some-token',
            'email' => 'nonexistent@example.com',
            'password' => 'NewPassword123',
            'password_confirmation' => 'NewPassword123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('email');
    }

    /**
     * Test password reset revokes all tokens.
     */
    public function test_password_reset_revokes_all_tokens(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('OldPassword123'),
        ]);

        // Create some tokens
        $user->createToken('token-1');
        $user->createToken('token-2');

        $this->assertEquals(2, $user->tokens()->count());

        // Request password reset
        $token = Password::createToken($user);

        $response = $this->postJson('/api/v1/auth/reset-password', [
            'token' => $token,
            'email' => 'test@example.com',
            'password' => 'NewPassword123',
            'password_confirmation' => 'NewPassword123',
        ]);

        $response->assertStatus(200);

        // Verify all tokens were revoked
        $this->assertEquals(0, $user->fresh()->tokens()->count());
    }

    /**
     * Test rate limiting on password reset requests.
     */
    public function test_password_reset_is_rate_limited(): void
    {
        User::factory()->create(['email' => 'test@example.com']);

        // Make 5 successful requests (rate limit)
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/auth/forgot-password', [
                'email' => 'test@example.com',
            ]);
        }

        // 6th request should be rate limited
        $response = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'test@example.com',
        ]);

        $response->assertStatus(429);
    }
}
