<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class PermissionMiddleware
 *
 * Middleware to check if the authenticated user has the required permission(s).
 * Supports multiple permissions with OR or AND logic.
 *
 * Usage:
 * - Route::middleware(['permission:create-post'])
 * - Route::middleware(['permission:create-post|edit-post']) - OR logic (any permission)
 * - Route::middleware(['permission:create-post,&edit-post']) - AND logic (all permissions)
 *
 * @package App\Http\Middleware
 */
class PermissionMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$permissions  The permissions to check
     */
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        // Check if user is authenticated
        if (!$request->user()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated. Please log in to access this resource.',
            ], 401);
        }

        // Flatten permissions array
        $requiredPermissions = [];
        foreach ($permissions as $permission) {
            $requiredPermissions = array_merge($requiredPermissions, explode('|', $permission));
        }
        $requiredPermissions = array_map('trim', $requiredPermissions);
        $requiredPermissions = array_filter($requiredPermissions);

        if (empty($requiredPermissions)) {
            return response()->json([
                'success' => false,
                'message' => 'No permissions specified for authorization check.',
            ], 500);
        }

        // Check for AND logic (permissions separated by comma after pipe expansion)
        $permissionGroups = [];
        $andPermissions = [];
        
        foreach ($requiredPermissions as $permission) {
            if (strpos($permission, ',') !== false) {
                // AND logic - split by comma
                $andPermissions = array_map('trim', explode(',', $permission));
            } else {
                $permissionGroups[] = $permission;
            }
        }

        // If we have AND permissions, user must have ALL of them
        if (!empty($andPermissions)) {
            foreach ($andPermissions as $permission) {
                if (!$request->user()->can($permission)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Forbidden. Insufficient permissions.',
                        'required_permission' => $permission,
                        'logic' => 'AND - all permissions required',
                    ], 403);
                }
            }
            return $next($request);
        }

        // OR logic - user needs at least one of the permissions
        // Spatie's hasPermissionTo supports checking multiple permissions
        $hasAnyPermission = false;
        foreach ($permissionGroups as $permission) {
            if ($request->user()->can($permission)) {
                $hasAnyPermission = true;
                break;
            }
        }

        if (!$hasAnyPermission) {
            $userPermissions = $request->user()->getPermissionNames()->join(', ');

            return response()->json([
                'success' => false,
                'message' => 'Forbidden. Insufficient permissions.',
                'required_permissions' => $permissionGroups,
                'user_permissions' => $userPermissions ?: 'none',
                'logic' => 'OR - any permission sufficient',
            ], 403);
        }

        return $next($request);
    }
}
