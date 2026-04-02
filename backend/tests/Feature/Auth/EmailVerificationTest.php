<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\VerifyEmail;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test email verification with valid signed URL.
     */
    public function test_email_can_be_verified_with_signed_url(): void
    {
        Event::fake([Verified::class]);

        $user = User::factory()->create([
            'email' => 'test@example.com',
            'email_verified_at' => null,
        ]);

        // Generate signed verification URL
        $url = URL::temporarySignedRoute(
            'auth.verify',
            Carbon::now()->addMinutes(60),
            [
                'id' => $user->id,
                'hash' => sha1($user->email),
            ]
        );

        $response = $this->getJson($url);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Email verified successfully',
            ]);

        // Verify email was marked as verified
        $this->assertNotNull($user->fresh()->email_verified_at);

        // Verify Verified event was dispatched
        Event::assertDispatched(Verified::class, function ($event) use ($user) {
            return $event->user->id === $user->id;
        });
    }

    /**
     * Test email verification with expired signed URL.
     */
    public function test_email_verification_fails_with_expired_url(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'email_verified_at' => null,
        ]);

        // Generate expired signed verification URL
        $url = URL::temporarySignedRoute(
            'auth.verify',
            Carbon::now()->subMinutes(60),
            [
                'id' => $user->id,
                'hash' => sha1($user->email),
            ]
        );

        $response = $this->getJson($url);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Invalid or expired verification link',
            ]);

        // Verify email was NOT marked as verified
        $this->assertNull($user->fresh()->email_verified_at);
    }

    /**
     * Test email verification with invalid hash.
     */
    public function test_email_verification_fails_with_invalid_hash(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'email_verified_at' => null,
        ]);

        // Generate signed verification URL with wrong hash
        $url = URL::temporarySignedRoute(
            'auth.verify',
            Carbon::now()->addMinutes(60),
            [
                'id' => $user->id,
                'hash' => sha1('wrong@email.com'),
            ]
        );

        $response = $this->getJson($url);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Invalid or expired verification link',
            ]);

        // Verify email was NOT marked as verified
        $this->assertNull($user->fresh()->email_verified_at);
    }

    /**
     * Test email verification with non-existent user.
     */
    public function test_email_verification_fails_with_nonexistent_user(): void
    {
        $url = URL::temporarySignedRoute(
            'auth.verify',
            Carbon::now()->addMinutes(60),
            [
                'id' => 99999,
                'hash' => sha1('test@example.com'),
            ]
        );

        $response = $this->getJson($url);

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'User not found',
            ]);
    }

    /**
     * Test already verified email returns success message.
     */
    public function test_already_verified_email_returns_success(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'email_verified_at' => Carbon::now(),
        ]);

        $url = URL::temporarySignedRoute(
            'auth.verify',
            Carbon::now()->addMinutes(60),
            [
                'id' => $user->id,
                'hash' => sha1($user->email),
            ]
        );

        $response = $this->getJson($url);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Email is already verified',
            ]);
    }

    /**
     * Test user can resend verification email.
     */
    public function test_user_can_resend_verification_email(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'test@example.com',
            'email_verified_at' => null,
        ]);

        $response = $this->postJson('/api/v1/auth/resend-verification', [
            'email' => 'test@example.com',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Verification email has been sent.',
            ]);

        // Verify notification was sent
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    /**
     * Test resend verification fails for already verified email.
     */
    public function test_resend_verification_fails_for_verified_email(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'test@example.com',
            'email_verified_at' => Carbon::now(),
        ]);

        $response = $this->postJson('/api/v1/auth/resend-verification', [
            'email' => 'test@example.com',
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Email is already verified.',
            ]);

        // Verify no notification was sent
        Notification::assertNothingSent();
    }

    /**
     * Test resend verification fails with non-existent email.
     */
    public function test_resend_verification_fails_with_nonexistent_email(): void
    {
        $response = $this->postJson('/api/v1/auth/resend-verification', [
            'email' => 'nonexistent@example.com',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('email');
    }

    /**
     * Test authenticated user can resend verification email.
     */
    public function test_authenticated_user_can_resend_verification(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'test@example.com',
            'email_verified_at' => null,
        ]);

        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/v1/auth/verification-notification');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Verification email has been sent.',
            ]);

        // Verify notification was sent
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    /**
     * Test email verification with token-based method.
     */
    public function test_email_can_be_verified_with_token(): void
    {
        Event::fake([Verified::class]);

        $user = User::factory()->create([
            'email' => 'test@example.com',
            'email_verified_at' => null,
        ]);

        $response = $this->postJson('/api/v1/auth/verify-email', [
            'id' => $user->id,
            'token' => sha1($user->email),
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Email verified successfully.',
            ]);

        // Verify email was marked as verified
        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    /**
     * Test email verification fails with invalid token.
     */
    public function test_email_verification_fails_with_invalid_token(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'email_verified_at' => null,
        ]);

        $response = $this->postJson('/api/v1/auth/verify-email', [
            'id' => $user->id,
            'token' => 'invalid-token',
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Invalid verification token',
            ]);

        // Verify email was NOT marked as verified
        $this->assertNull($user->fresh()->email_verified_at);
    }

    /**
     * Test rate limiting on resend verification.
     */
    public function test_resend_verification_is_rate_limited(): void
    {
        User::factory()->create(['email' => 'test@example.com']);

        // Make 3 successful requests (rate limit)
        for ($i = 0; $i < 3; $i++) {
            $this->postJson('/api/v1/auth/resend-verification', [
                'email' => 'test@example.com',
            ]);
        }

        // 4th request should be rate limited
        $response = $this->postJson('/api/v1/auth/resend-verification', [
            'email' => 'test@example.com',
        ]);

        $response->assertStatus(429);
    }
}
