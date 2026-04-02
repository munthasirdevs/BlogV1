<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SessionManagementTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test user can view active sessions.
     */
    public function test_user_can_view_active_sessions(): void
    {
        $user = User::factory()->create();
        
        // Create multiple tokens
        $user->createToken('token-1');
        $user->createToken('token-2');
        $token = $user->createToken('token-3')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/v1/auth/sessions');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'sessions' => [],
                ],
            ])
            ->assertJsonStructure([
                'data' => [
                    'sessions' => [
                        '*' => [
                            'id',
                            'name',
                            'created_at',
                            'expires_at',
                            'is_expired',
                        ],
                    ],
                ],
            ]);
    }

    /**
     * Test user can revoke a session.
     */
    public function test_user_can_revoke_session(): void
    {
        $user = User::factory()->create();
        
        // Create multiple tokens
        $tokenToRevoke = $user->createToken('token-to-revoke')->plainTextToken;
        $currentToken = $user->createToken('current-token')->plainTextToken;

        // Get the token ID to revoke
        $tokenModel = $user->tokens()
            ->where('name', 'like', 'token-to-revoke%')
            ->first();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $currentToken,
        ])->deleteJson('/api/v1/auth/sessions/' . $tokenModel->id);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Session revoked successfully',
            ]);

        // Verify token was revoked
        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => $tokenModel->id,
        ]);
    }

    /**
     * Test user cannot revoke current session.
     */
    public function test_user_cannot_revoke_current_session(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('current-token')->plainTextToken;

        // Get the current token model using the token we just created
        $currentTokenModel = $user->tokens()
            ->where('name', 'like', 'current-token%')
            ->first();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->deleteJson('/api/v1/auth/sessions/' . $currentTokenModel->id);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Cannot revoke the current session',
            ]);
    }

    /**
     * Test revoking non-existent session returns 404.
     */
    public function test_revoking_nonexistent_session_returns_404(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('current-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->deleteJson('/api/v1/auth/sessions/99999');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Session not found',
            ]);
    }

    /**
     * Test unauthenticated user cannot access sessions.
     */
    public function test_unauthenticated_user_cannot_access_sessions(): void
    {
        $response = $this->getJson('/api/v1/auth/sessions');

        $response->assertStatus(401);
    }

    /**
     * Test unauthenticated user cannot revoke sessions.
     */
    public function test_unauthenticated_user_cannot_revoke_sessions(): void
    {
        $response = $this->deleteJson('/api/v1/auth/sessions/1');

        $response->assertStatus(401);
    }

    /**
     * Test token expiration is tracked correctly.
     */
    public function test_token_expiration_is_tracked(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token', ['*'], now()->addMinutes(1))->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/v1/auth/sessions');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'sessions' => [
                        '*' => [
                            'is_expired',
                        ],
                    ],
                ],
            ]);
    }
}
