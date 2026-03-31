<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\NewAccessToken;

class AuthService
{
    /**
     * Register a new user.
     */
    public function register(array $data): User
    {
        return User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);
    }

    /**
     * Login user and create token.
     */
    public function login(string $email, string $password, bool $remember = false): array
    {
        $user = User::where('email', $email)->first();

        if (!$user || !Hash::check($password, $user->password)) {
            return [
                'success' => false,
                'message' => 'Invalid credentials',
            ];
        }

        if ($user->isBanned()) {
            return [
                'success' => false,
                'message' => 'Your account has been banned',
            ];
        }

        // Delete old tokens
        $user->tokens()->delete();

        // Create new token
        $tokenName = 'auth_token_' . time();
        $expiresAt = $remember ? now()->addDays(30) : now()->addDay();
        
        $token = $user->createToken($tokenName, ['*'], $expiresAt);

        return [
            'success' => true,
            'token' => $token->plainTextToken,
            'user' => $user,
        ];
    }

    /**
     * Logout user.
     */
    public function logout(User $user): void
    {
        // Delete current token
        $user->currentAccessToken()?->delete();
    }

    /**
     * Get user by token.
     */
    public function getUserByToken(string $token): ?User
    {
        $token = \Laravel\Sanctum\PersonalAccessToken::findToken($token);
        
        if (!$token) {
            return null;
        }

        return $token->tokenable;
    }
}
