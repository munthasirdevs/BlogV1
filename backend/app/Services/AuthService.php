<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\VerifyEmail as VerifyEmailNotification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Support\Carbon;

class AuthService
{
    /**
     * Default token expiration in minutes (24 hours).
     */
    private const TOKEN_EXPIRATION_MINUTES = 1440;

    /**
     * Remember me token expiration in minutes (30 days).
     */
    private const REMEMBER_TOKEN_EXPIRATION_MINUTES = 43200;

    /**
     * Register a new user.
     *
     * @param array $data
     * @return User
     */
    public function register(array $data): User
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => 'user', // Default role
            'status' => 'active',
            'email_verified_at' => null,
            'preferences' => [
                'notifications' => true,
                'newsletter' => false,
                'theme' => 'light',
            ],
        ]);

        // Send email verification notification
        $user->notify(new VerifyEmailNotification());

        // Fire registered event
        event(new Registered($user));

        return $user;
    }

    /**
     * Login user and create token.
     *
     * @param string $email
     * @param string $password
     * @param bool $remember
     * @return array
     */
    public function login(string $email, string $password, bool $remember = false): array
    {
        $user = User::where('email', $email)->first();

        if (!$user) {
            return [
                'success' => false,
                'message' => 'Invalid credentials',
            ];
        }

        if (!Hash::check($password, $user->password)) {
            return [
                'success' => false,
                'message' => 'Invalid credentials',
            ];
        }

        if ($user->isBanned()) {
            return [
                'success' => false,
                'message' => 'Your account has been banned. Please contact support.',
            ];
        }

        if (!in_array($user->status, ['active', 'pending'])) {
            return [
                'success' => false,
                'message' => 'Your account is not active. Please contact support.',
            ];
        }

        // Delete old tokens to limit token accumulation
        $this->revokeOldTokens($user);

        // Create new token with appropriate expiration
        $tokenName = 'auth_token_' . time() . '_' . Str::random(4);
        $expiresAt = $remember 
            ? Carbon::now()->addMinutes(self::REMEMBER_TOKEN_EXPIRATION_MINUTES)
            : Carbon::now()->addMinutes(self::TOKEN_EXPIRATION_MINUTES);

        $token = $user->createToken($tokenName, ['*'], $expiresAt);

        return [
            'success' => true,
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'expires_in' => $remember ? self::REMEMBER_TOKEN_EXPIRATION_MINUTES * 60 : self::TOKEN_EXPIRATION_MINUTES * 60,
            'user' => $user,
        ];
    }

    /**
     * Logout user by revoking current token.
     *
     * @param User $user
     * @return void
     */
    public function logout(User $user): void
    {
        // Delete current token
        $user->currentAccessToken()?->delete();
    }

    /**
     * Logout user from all devices by revoking all tokens.
     *
     * @param User $user
     * @return void
     */
    public function logoutAllDevices(User $user): void
    {
        $user->tokens()->delete();
    }

    /**
     * Refresh the current token.
     *
     * @param User $user
     * @param bool $remember
     * @return array
     */
    public function refreshToken(User $user, bool $remember = false): array
    {
        // Delete current token
        $user->currentAccessToken()?->delete();

        // Create new token
        $tokenName = 'auth_token_' . time() . '_' . Str::random(4);
        $expiresAt = $remember 
            ? Carbon::now()->addMinutes(self::REMEMBER_TOKEN_EXPIRATION_MINUTES)
            : Carbon::now()->addMinutes(self::TOKEN_EXPIRATION_MINUTES);

        $token = $user->createToken($tokenName, ['*'], $expiresAt);

        return [
            'success' => true,
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'expires_in' => $remember ? self::REMEMBER_TOKEN_EXPIRATION_MINUTES * 60 : self::TOKEN_EXPIRATION_MINUTES * 60,
        ];
    }

    /**
     * Revoke old tokens for a user (keep last 5 tokens max).
     *
     * @param User $user
     * @return void
     */
    private function revokeOldTokens(User $user): void
    {
        $tokens = $user->tokens()
            ->orderBy('created_at', 'desc')
            ->get();

        // Keep only the 5 most recent tokens
        if ($tokens->count() > 5) {
            $tokens->skip(5)->each->delete();
        }
    }

    /**
     * Send password reset link.
     *
     * @param string $email
     * @return array
     */
    public function sendPasswordResetLink(string $email): array
    {
        $user = User::where('email', $email)->first();

        if (!$user) {
            // Don't reveal if email exists or not for security
            return [
                'success' => true,
                'message' => 'If the email exists, a password reset link has been sent.',
            ];
        }

        // Generate token using Password broker
        $token = Password::createToken($user);

        // Send our custom notification
        $user->notify(new \App\Notifications\ResetPassword($token));

        return [
            'success' => true,
            'message' => 'Password reset link has been sent to your email.',
        ];
    }

    /**
     * Reset password with token.
     *
     * @param string $email
     * @param string $password
     * @param string $token
     * @return array
     */
    public function resetPassword(string $email, string $password, string $token): array
    {
        $status = Password::reset(
            [
                'email' => $email,
                'password' => $password,
                'password_confirmation' => $password,
                'token' => $token,
            ],
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                // Revoke all tokens for security
                $user->tokens()->delete();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return [
                'success' => true,
                'message' => 'Password has been reset successfully.',
            ];
        }

        return [
            'success' => false,
            'message' => __($status),
        ];
    }

    /**
     * Verify email address.
     *
     * @param User $user
     * @return array
     */
    public function verifyEmail(User $user): array
    {
        if ($user->hasVerifiedEmail()) {
            return [
                'success' => true,
                'message' => 'Email is already verified.',
            ];
        }

        $user->markEmailAsVerified();

        return [
            'success' => true,
            'message' => 'Email verified successfully.',
        ];
    }

    /**
     * Resend email verification notification.
     *
     * @param User $user
     * @return array
     */
    public function resendVerificationEmail(User $user): array
    {
        if ($user->hasVerifiedEmail()) {
            return [
                'success' => false,
                'message' => 'Email is already verified.',
            ];
        }

        $user->notify(new VerifyEmailNotification());

        return [
            'success' => true,
            'message' => 'Verification email has been sent.',
        ];
    }

    /**
     * Get user by token.
     *
     * @param string $token
     * @return User|null
     */
    public function getUserByToken(string $token): ?User
    {
        $accessToken = PersonalAccessToken::findToken($token);

        if (!$accessToken) {
            return null;
        }

        return $accessToken->tokenable;
    }

    /**
     * Check if token is expired.
     *
     * @param User $user
     * @return bool
     */
    public function isTokenExpired(User $user): bool
    {
        $token = $user->currentAccessToken();

        if (!$token) {
            return true;
        }

        return $token->expires_at && $token->expires_at->isPast();
    }

    /**
     * Get user with their tokens info.
     *
     * @param User $user
     * @return array
     */
    public function getUserTokensInfo(User $user): array
    {
        $tokens = $user->tokens()
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($token) {
                return [
                    'id' => $token->id,
                    'name' => $token->name,
                    'created_at' => $token->created_at->toIso8601String(),
                    'expires_at' => $token->expires_at?->toIso8601String(),
                    'is_expired' => $token->expires_at?->isPast() ?? false,
                ];
            });

        return $tokens->toArray();
    }

    /**
     * Revoke a specific token.
     *
     * @param User $user
     * @param int $tokenId
     * @return bool
     */
    public function revokeToken(User $user, int $tokenId): bool
    {
        return $user->tokens()->where('id', $tokenId)->delete() > 0;
    }
}
