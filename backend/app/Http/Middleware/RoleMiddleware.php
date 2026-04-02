<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class RoleMiddleware
 *
 * Middleware to check if the authenticated user has the required role(s).
 * Supports multiple roles with OR logic - user needs at least one of the specified roles.
 *
 * Usage:
 * - Route::middleware(['role:admin'])
 * - Route::middleware(['role:admin,editor'])
 * - Route::middleware(['role:admin|editor|moderator'])
 *
 * @package App\Http\Middleware
 */
class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles  The roles to check (comma or pipe separated)
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        // Check if user is authenticated
        if (!$request->user()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated. Please log in to access this resource.',
            ], 401);
        }

        // Flatten roles array (handle both comma and pipe separated)
        $requiredRoles = [];
        foreach ($roles as $role) {
            $requiredRoles = array_merge($requiredRoles, explode('|', $role));
        }
        $requiredRoles = array_map('trim', $requiredRoles);
        $requiredRoles = array_filter($requiredRoles);

        if (empty($requiredRoles)) {
            return response()->json([
                'success' => false,
                'message' => 'No roles specified for authorization check.',
            ], 500);
        }

        // Check if user has any of the required roles
        // Using Spatie's hasRole method which supports multiple roles
        if (!$request->user()->hasRole($requiredRoles)) {
            $userRoles = $request->user()->getRoleNames()->join(', ');

            return response()->json([
                'success' => false,
                'message' => 'Forbidden. Insufficient role permissions.',
                'required_roles' => $requiredRoles,
                'user_roles' => $userRoles ?: 'none',
            ], 403);
        }

        return $next($request);
    }
}
