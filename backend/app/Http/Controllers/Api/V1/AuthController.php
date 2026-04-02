<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\PasswordResetRequest;
use App\Http\Requests\Auth\VerifyEmailRequest;
use App\Http\Requests\Auth\ResendVerificationRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\URL;
use Illuminate\Auth\Events\Verified;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(
        private AuthService $authService
    ) {}

    /**
     * Register a new user.
     *
     * @param RegisterRequest $request
     * @return JsonResponse
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = $this->authService->register($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Registration successful. Please verify your email.',
            'data' => [
                'user' => new UserResource($user),
            ],
        ], 201);
    }

    /**
     * Login user and issue token.
     *
     * @param LoginRequest $request
     * @return JsonResponse
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->login(
            $request->email,
            $request->password,
            $request->boolean('remember')
        );

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
            ], 401);
        }

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'data' => [
                'token' => $result['token'],
                'token_type' => $result['token_type'],
                'expires_in' => $result['expires_in'],
                'user' => new UserResource($result['user']),
            ],
        ]);
    }

    /**
     * Logout user (revoke current token).
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully',
        ]);
    }

    /**
     * Logout from all devices (revoke all tokens).
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function logoutAll(Request $request): JsonResponse
    {
        $this->authService->logoutAllDevices($request->user());

        return response()->json([
            'success' => true,
            'message' => 'Logged out from all devices successfully',
        ]);
    }

    /**
     * Refresh authentication token.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function refresh(Request $request): JsonResponse
    {
        $result = $this->authService->refreshToken(
            $request->user(),
            $request->boolean('remember')
        );

        return response()->json([
            'success' => true,
            'message' => 'Token refreshed successfully',
            'data' => [
                'token' => $result['token'],
                'token_type' => $result['token_type'],
                'expires_in' => $result['expires_in'],
            ],
        ]);
    }

    /**
     * Get current authenticated user.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load(['posts', 'comments']);

        return response()->json([
            'success' => true,
            'data' => new UserResource($user),
        ]);
    }

    /**
     * Send password reset link to email.
     *
     * @param ForgotPasswordRequest $request
     * @return JsonResponse
     */
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $result = $this->authService->sendPasswordResetLink($request->email);

        $statusCode = $result['success'] ? 200 : 400;

        return response()->json([
            'success' => $result['success'],
            'message' => $result['message'],
        ], $statusCode);
    }

    /**
     * Reset password with token.
     *
     * @param PasswordResetRequest $request
     * @return JsonResponse
     */
    public function resetPassword(PasswordResetRequest $request): JsonResponse
    {
        $result = $this->authService->resetPassword(
            $request->email,
            $request->password,
            $request->token
        );

        $statusCode = $result['success'] ? 200 : 400;

        return response()->json([
            'success' => $result['success'],
            'message' => $result['message'],
        ], $statusCode);
    }

    /**
     * Verify email address with signed URL.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function verify(Request $request): JsonResponse
    {
        if (!$request->hasValidSignature()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired verification link',
            ], 403);
        }

        $user = User::find($request->route('id'));

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ], 404);
        }

        // Verify the hash matches the user's email
        $hash = sha1($user->getEmailForVerification());
        if (!hash_equals($hash, (string) $request->route('hash'))) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired verification link',
            ], 403);
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'success' => true,
                'message' => 'Email is already verified',
            ]);
        }

        $user->markEmailAsVerified();

        event(new Verified($user));

        return response()->json([
            'success' => true,
            'message' => 'Email verified successfully',
        ]);
    }

    /**
     * Verify email with token (alternative method).
     *
     * @param VerifyEmailRequest $request
     * @return JsonResponse
     */
    public function verifyEmail(VerifyEmailRequest $request): JsonResponse
    {
        $user = User::find($request->id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ], 404);
        }

        // Verify the token matches (in production, use signed URLs instead)
        $expectedHash = sha1($user->getEmailForVerification());
        
        if ($request->token !== $expectedHash) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid verification token',
            ], 403);
        }

        $result = $this->authService->verifyEmail($user);

        $statusCode = $result['success'] ? 200 : 400;

        return response()->json([
            'success' => $result['success'],
            'message' => $result['message'],
        ], $statusCode);
    }

    /**
     * Resend email verification notification.
     *
     * @param ResendVerificationRequest $request
     * @return JsonResponse
     */
    public function resendVerification(ResendVerificationRequest $request): JsonResponse
    {
        $user = User::where('email', $request->email)->first();

        $result = $this->authService->resendVerificationEmail($user);

        $statusCode = $result['success'] ? 200 : 400;

        return response()->json([
            'success' => $result['success'],
            'message' => $result['message'],
        ], $statusCode);
    }

    /**
     * Resend verification for authenticated user.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function resendVerificationAuthenticated(Request $request): JsonResponse
    {
        $result = $this->authService->resendVerificationEmail($request->user());

        $statusCode = $result['success'] ? 200 : 400;

        return response()->json([
            'success' => $result['success'],
            'message' => $result['message'],
        ], $statusCode);
    }

    /**
     * Get active sessions/tokens for user.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function sessions(Request $request): JsonResponse
    {
        $tokens = $this->authService->getUserTokensInfo($request->user());

        return response()->json([
            'success' => true,
            'data' => [
                'sessions' => $tokens,
            ],
        ]);
    }

    /**
     * Revoke a specific session/token.
     *
     * @param Request $request
     * @param int $tokenId
     * @return JsonResponse
     */
    public function revokeSession(Request $request, int $tokenId): JsonResponse
    {
        $user = $request->user();
        $currentTokenId = $user->currentAccessToken()->id;

        // Prevent revoking the current session
        if ($tokenId === $currentTokenId) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot revoke the current session',
            ], 400);
        }

        $revoked = $this->authService->revokeToken($user, $tokenId);

        if (!$revoked) {
            return response()->json([
                'success' => false,
                'message' => 'Session not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Session revoked successfully',
        ]);
    }
}
