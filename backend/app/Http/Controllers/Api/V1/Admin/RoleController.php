<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;

/**
 * Class RoleController
 *
 * Controller for managing roles and permissions.
 * Provides endpoints to list roles, permissions, and role-permission relationships.
 *
 * @package App\Http\Controllers\Api\V1\Admin
 */
class RoleController extends Controller
{
    /**
     * Display a listing of all roles.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        // Authorize
        Gate::authorize('manageRoles', User::class);

        $roles = Role::with('permissions')
            ->orderBy('name')
            ->get()
            ->map(function ($role) {
                return [
                    'id' => $role->id,
                    'name' => $role->name,
                    'guard_name' => $role->guard_name,
                    'permissions_count' => $role->permissions->count(),
                    'permissions' => $role->permissions->pluck('name'),
                    'created_at' => $role->created_at?->toIso8601String(),
                    'updated_at' => $role->updated_at?->toIso8601String(),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $roles,
            'meta' => [
                'total' => $roles->count(),
            ],
        ]);
    }

    /**
     * Display a listing of all permissions.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function permissions(Request $request): JsonResponse
    {
        // Authorize
        Gate::authorize('manageRoles', User::class);

        $permissions = Permission::with('roles')
            ->orderBy('name')
            ->get()
            ->map(function ($permission) {
                return [
                    'id' => $permission->id,
                    'name' => $permission->name,
                    'guard_name' => $permission->guard_name,
                    'roles_count' => $permission->roles->count(),
                    'roles' => $permission->roles->pluck('name'),
                    'created_at' => $permission->created_at?->toIso8601String(),
                    'updated_at' => $permission->updated_at?->toIso8601String(),
                ];
            });

        // Group permissions by category
        $groupedPermissions = [
            'posts' => $permissions->filter(fn($p) => str_contains($p['name'], 'post'))->values(),
            'comments' => $permissions->filter(fn($p) => str_contains($p['name'], 'comment'))->values(),
            'users' => $permissions->filter(fn($p) => str_contains($p['name'], 'user') || str_contains($p['name'], 'role') || str_contains($p['name'], 'ban'))->values(),
            'settings' => $permissions->filter(fn($p) => str_contains($p['name'], 'setting') || str_contains($p['name'], 'category') || str_contains($p['name'], 'tag'))->values(),
            'system' => $permissions->filter(fn($p) => str_contains($p['name'], 'admin') || str_contains($p['name'], 'analytics') || str_contains($p['name'], 'media'))->values(),
        ];

        return response()->json([
            'success' => true,
            'data' => $permissions,
            'grouped' => $groupedPermissions,
            'meta' => [
                'total' => $permissions->count(),
            ],
        ]);
    }

    /**
     * Display the permissions for a specific role.
     *
     * @param  string  $roleName
     * @return JsonResponse
     */
    public function showRolePermissions(string $roleName): JsonResponse
    {
        // Authorize
        Gate::authorize('manageRoles', User::class);

        $role = Role::with('permissions')->findByName($roleName, 'sanctum');

        if (!$role) {
            return response()->json([
                'success' => false,
                'message' => "Role '{$roleName}' not found.",
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $role->id,
                'name' => $role->name,
                'guard_name' => $role->guard_name,
                'permissions' => $role->permissions->map(fn($p) => [
                    'id' => $p->id,
                    'name' => $p->name,
                ]),
                'permissions_count' => $role->permissions->count(),
            ],
        ]);
    }

    /**
     * Assign permissions to a role.
     *
     * @param  Request  $request
     * @param  string  $roleName
     * @return JsonResponse
     */
    public function assignPermissions(Request $request, string $roleName): JsonResponse
    {
        // Authorize
        Gate::authorize('manageRoles', User::class);

        $request->validate([
            'permissions' => 'required|array|min:1',
            'permissions.*' => 'required|string|exists:permissions,name',
        ]);

        $role = Role::findByName($roleName, 'sanctum');

        if (!$role) {
            return response()->json([
                'success' => false,
                'message' => "Role '{$roleName}' not found.",
            ], 404);
        }

        // Prevent modifying super-admin role (security)
        if ($role->name === 'super-admin') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot modify super-admin role permissions.',
            ], 403);
        }

        $permissions = $request->input('permissions');
        $role->syncPermissions($permissions);

        // Clear cached permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        return response()->json([
            'success' => true,
            'message' => "Permissions assigned to role '{$roleName}' successfully.",
            'data' => [
                'role' => $role->name,
                'permissions' => $role->permissions->pluck('name'),
            ],
        ]);
    }

    /**
     * Remove permissions from a role.
     *
     * @param  Request  $request
     * @param  string  $roleName
     * @return JsonResponse
     */
    public function removePermissions(Request $request, string $roleName): JsonResponse
    {
        // Authorize
        Gate::authorize('manageRoles', User::class);

        $request->validate([
            'permissions' => 'required|array|min:1',
            'permissions.*' => 'required|string|exists:permissions,name',
        ]);

        $role = Role::findByName($roleName, 'sanctum');

        if (!$role) {
            return response()->json([
                'success' => false,
                'message' => "Role '{$roleName}' not found.",
            ], 404);
        }

        // Prevent modifying super-admin role (security)
        if ($role->name === 'super-admin') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot modify super-admin role permissions.',
            ], 403);
        }

        $permissions = $request->input('permissions');
        
        foreach ($permissions as $permission) {
            $role->revokePermissionTo($permission);
        }

        // Clear cached permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        return response()->json([
            'success' => true,
            'message' => "Permissions removed from role '{$roleName}' successfully.",
            'data' => [
                'role' => $role->name,
                'permissions' => $role->permissions->pluck('name'),
            ],
        ]);
    }

    /**
     * Create a new role.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        // Authorize
        Gate::authorize('manageRoles', User::class);

        $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
            'permissions' => 'array',
            'permissions.*' => 'string|exists:permissions,name',
        ]);

        $role = Role::create([
            'name' => $request->name,
            'guard_name' => 'sanctum',
        ]);

        if ($request->has('permissions')) {
            $role->syncPermissions($request->permissions);
        }

        // Clear cached permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        return response()->json([
            'success' => true,
            'message' => "Role '{$role->name}' created successfully.",
            'data' => [
                'id' => $role->id,
                'name' => $role->name,
                'guard_name' => $role->guard_name,
            ],
        ], 201);
    }

    /**
     * Delete a role.
     *
     * @param  string  $roleName
     * @return JsonResponse
     */
    public function destroy(string $roleName): JsonResponse
    {
        // Authorize
        Gate::authorize('manageRoles', User::class);

        $role = Role::findByName($roleName, 'sanctum');

        if (!$role) {
            return response()->json([
                'success' => false,
                'message' => "Role '{$roleName}' not found.",
            ], 404);
        }

        // Prevent deleting system roles (security)
        $protectedRoles = ['super-admin', 'admin'];
        if (in_array($role->name, $protectedRoles)) {
            return response()->json([
                'success' => false,
                'message' => "Cannot delete protected role '{$roleName}'.",
            ], 403);
        }

        // Check if any users have this role
        $userCount = DB::table('model_has_roles')
            ->where('role_id', $role->id)
            ->count();

        if ($userCount > 0) {
            return response()->json([
                'success' => false,
                'message' => "Cannot delete role '{$roleName}' because {$userCount} user(s) still have this role.",
                'users_count' => $userCount,
            ], 400);
        }

        $roleName = $role->name;
        $role->delete();

        // Clear cached permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        return response()->json([
            'success' => true,
            'message' => "Role '{$roleName}' deleted successfully.",
        ]);
    }
}
