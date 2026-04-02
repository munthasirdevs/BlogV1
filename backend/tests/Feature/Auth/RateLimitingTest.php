<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RateLimitingTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test registration is rate limited.
     */
    public function test_registration_is_rate_limited(): void
    {
        // Make 3 successful requests (rate limit)
        for ($i = 0; $i < 3; $i++) {
            $this->postJson('/api/v1/auth/register', [
                'name' => 'Test User ' . $i,
                'email' => 'test' . $i . '@example.com',
                'password' => 'Password123',
                'password_confirmation' => 'Password123',
            ]);
        }

        // 4th request should be rate limited
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Test User 4',
            'email' => 'test4@example.com',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ]);

        $response->assertStatus(429);
    }

    /**
     * Test login is rate limited.
     */
    public function test_login_is_rate_limited(): void
    {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('Password123'),
        ]);

        // Make 5 failed login attempts (rate limit)
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/auth/login', [
                'email' => 'test@example.com',
                'password' => 'WrongPassword',
            ]);
        }

        // 6th request should be rate limited
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'WrongPassword',
        ]);

        $response->assertStatus(429);
    }

    /**
     * Test forgot password is rate limited.
     */
    public function test_forgot_password_is_rate_limited(): void
    {
        User::factory()->create(['email' => 'test@example.com']);

        // Make 5 requests (rate limit)
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

    /**
     * Test reset password is rate limited.
     */
    public function test_reset_password_is_rate_limited(): void
    {
        // Make 5 requests (rate limit)
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/auth/reset-password', [
                'token' => 'test-token',
                'email' => 'test@example.com',
                'password' => 'Password123',
                'password_confirmation' => 'Password123',
            ]);
        }

        // 6th request should be rate limited
        $response = $this->postJson('/api/v1/auth/reset-password', [
            'token' => 'test-token',
            'email' => 'test@example.com',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ]);

        $response->assertStatus(429);
    }

    /**
     * Test verify email is rate limited.
     */
    public function test_verify_email_is_rate_limited(): void
    {
        // Make 10 requests (rate limit)
        for ($i = 0; $i < 10; $i++) {
            $this->postJson('/api/v1/auth/verify-email', [
                'id' => 1,
                'token' => 'test-token',
            ]);
        }

        // 11th request should be rate limited
        $response = $this->postJson('/api/v1/auth/verify-email', [
            'id' => 1,
            'token' => 'test-token',
        ]);

        $response->assertStatus(429);
    }

    /**
     * Test resend verification is rate limited.
     */
    public function test_resend_verification_is_rate_limited(): void
    {
        User::factory()->create(['email' => 'test@example.com']);

        // Make 3 requests (rate limit)
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

    /**
     * Test rate limit response includes retry header.
     */
    public function test_rate_limit_response_includes_retry_header(): void
    {
        // Make requests until rate limited
        for ($i = 0; $i < 6; $i++) {
            $response = $this->postJson('/api/v1/auth/login', [
                'email' => 'test@example.com',
                'password' => 'wrong',
            ]);
        }

        $response->assertStatus(429);
        
        // Check for Retry-After header
        $response->assertHeader('Retry-After');
    }
}
