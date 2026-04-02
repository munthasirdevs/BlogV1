<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Helpers\Ability;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    /**
     * List all users.
     */
    public function index(Request $request): JsonResponse
    {
        $query = User::query();

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('email', 'LIKE', "%{$search}%");
            });
        }

        // Filter by role
        if ($request->has('role')) {
            $query->where('role', $request->role);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Sorting
        $sortField = $request->get('sort', 'created_at');
        $sortOrder = $request->get('order', 'desc');
        $query->orderBy($sortField, $sortOrder);

        $users = $query->paginate(20);

        return response()->json([
            'success' => true,
            'data' => UserResource::collection($users),
            'meta' => [
                'current_page' => $users->currentPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
                'total_pages' => $users->lastPage(),
            ],
        ]);
    }

    /**
     * Get single user.
     */
    public function show(User $user): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => new UserResource($user->load(['posts', 'comments'])),
        ]);
    }

    /**
     * Update user.
     */
    public function update(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'required', 'email', 'unique:users,email,' . $user->id],
            'role' => ['sometimes', 'required', 'in:user,admin'],
            'status' => ['sometimes', 'required', 'in:active,banned'],
            'bio' => ['nullable', 'string', 'max:500'],
            'password' => ['nullable', 'confirmed', Password::defaults()],
        ]);

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        $user->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'User updated successfully',
            'data' => new UserResource($user),
        ]);
    }

    /**
     * Delete user.
     */
    public function destroy(User $user): JsonResponse
    {
        // Prevent deleting self
        if ($user->id === request()->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete your own account',
            ], 400);
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'User deleted successfully',
        ], 204);
    }

    /**
     * Assign roles to a user.
     *
     * POST /api/v1/admin/users/{id}/roles
     *
     * @param  Request  $request
     * @param  User  $user
     * @return JsonResponse
     */
    public function assignRoles(Request $request, User $user): JsonResponse
    {
        $actingUser = $request->user();

        // Authorize using policy
        Gate::authorize('assignRole', [$actingUser, $user]);

        $request->validate([
            'roles' => 'required|array|min:1',
            'roles.*' => 'required|string|exists:roles,name',
        ]);

        $roles = $request->input('roles');

        // Security: Prevent assigning super-admin role
        if (in_array('super-admin', $roles)) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot assign super-admin role. This action is restricted.',
            ], 403);
        }

        // Security: Ensure user always has at least one role
        $currentRoles = $user->getRoleNames();
        $newRoles = array_unique(array_merge($currentRoles->toArray(), $roles));

        if (empty($newRoles)) {
            return response()->json([
                'success' => false,
                'message' => 'User must have at least one role.',
            ], 400);
        }

        // Assign roles using Spatie
        $user->syncRoles($newRoles);

        // Update legacy role column for backward compatibility
        $primaryRole = reset($newRoles);
        $user->update(['role' => $primaryRole]);

        // Clear cached permissions
        Ability::invalidateCache($user);
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        return response()->json([
            'success' => true,
            'message' => 'Roles assigned successfully.',
            'data' => [
                'user_id' => $user->id,
                'roles' => $user->getRoleNames(),
            ],
        ]);
    }

    /**
     * Revoke roles from a user.
     *
     * DELETE /api/v1/admin/users/{id}/roles/{role}
     *
     * @param  Request  $request
     * @param  User  $user
     * @param  string  $roleName
     * @return JsonResponse
     */
    public function revokeRole(Request $request, User $user, string $roleName): JsonResponse
    {
        $actingUser = $request->user();

        // Authorize using policy
        Gate::authorize('revokeRole', [$actingUser, $user]);

        // Security: Prevent revoking roles from super-admins
        if ($user->hasRole('super-admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot modify roles for super-admin users.',
            ], 403);
        }

        // Security: Prevent revoking super-admin role
        if ($roleName === 'super-admin') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot revoke super-admin role.',
            ], 403);
        }

        // Check if role exists
        $role = Role::findByName($roleName, 'sanctum');
        if (!$role) {
            return response()->json([
                'success' => false,
                'message' => "Role '{$roleName}' not found.",
            ], 404);
        }

        // Check if user has this role
        if (!$user->hasRole($roleName)) {
            return response()->json([
                'success' => false,
                'message' => "User does not have role '{$roleName}'.",
            ], 400);
        }

        // Security: Ensure user always has at least one role
        $currentRoles = $user->getRoleNames();
        if ($currentRoles->count() <= 1) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot revoke last role. User must have at least one role.',
            ], 400);
        }

        // Revoke role
        $user->removeRole($roleName);

        // Update legacy role column if needed
        $remainingRoles = $user->getRoleNames();
        if (!$remainingRoles->contains($user->role)) {
            $primaryRole = $remainingRoles->first();
            $user->update(['role' => $primaryRole]);
        }

        // Clear cached permissions
        Ability::invalidateCache($user);
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        return response()->json([
            'success' => true,
            'message' => "Role '{$roleName}' revoked successfully.",
            'data' => [
                'user_id' => $user->id,
                'roles' => $user->getRoleNames(),
            ],
        ]);
    }

    /**
     * Get user permissions.
     *
     * GET /api/v1/admin/users/{id}/permissions
     *
     * @param  Request  $request
     * @param  User  $user
     * @return JsonResponse
     */
    public function permissions(Request $request, User $user): JsonResponse
    {
        $actingUser = $request->user();

        // Users can view their own permissions
        // Admins can view anyone's permissions
        if ($actingUser->id !== $user->id && !$actingUser->hasRole(['admin', 'super-admin'])) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden. Cannot view other users\' permissions.',
            ], 403);
        }

        $directPermissions = $user->getDirectPermissions();
        $rolePermissions = $user->getPermissionsViaRoles();
        $allPermissions = $user->getAllPermissions();

        return response()->json([
            'success' => true,
            'data' => [
                'user_id' => $user->id,
                'roles' => $user->getRoleNames(),
                'direct_permissions' => $directPermissions->pluck('name'),
                'role_permissions' => $rolePermissions->pluck('name'),
                'all_permissions' => $allPermissions->pluck('name'),
                'permissions_count' => $allPermissions->count(),
            ],
        ]);
    }

    /**
     * Ban a user.
     *
     * POST /api/v1/admin/users/{id}/ban
     *
     * @param  Request  $request
     * @param  User  $user
     * @return JsonResponse
     */
    public function ban(Request $request, User $user): JsonResponse
    {
        $actingUser = $request->user();

        // Authorize using policy
        Gate::authorize('ban', [$actingUser, $user]);

        $request->validate([
            'reason' => 'nullable|string|max:500',
            'duration' => 'nullable|integer|min:1', // in hours, null = permanent
        ]);

        // Security: Prevent banning super-admins
        if ($user->hasRole('super-admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot ban super-admin users.',
            ], 403);
        }

        $user->update([
            'status' => 'banned',
        ]);

        // Log the ban action (could be extended with audit trail)
        \Log::info('User banned', [
            'banned_user_id' => $user->id,
            'banned_user_email' => $user->email,
            'banned_by' => $actingUser->id,
            'reason' => $request->reason,
            'duration' => $request->duration,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'User banned successfully.',
            'data' => [
                'user_id' => $user->id,
                'status' => $user->status,
            ],
        ]);
    }

    /**
     * Unban a user.
     *
     * POST /api/v1/admin/users/{id}/unban
     *
     * @param  Request  $request
     * @param  User  $user
     * @return JsonResponse
     */
    public function unban(Request $request, User $user): JsonResponse
    {
        $actingUser = $request->user();

        // Authorize using policy
        Gate::authorize('unban', [$actingUser, $user]);

        if ($user->status !== 'banned') {
            return response()->json([
                'success' => false,
                'message' => 'User is not banned.',
            ], 400);
        }

        $user->update([
            'status' => 'active',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'User unbanned successfully.',
            'data' => [
                'user_id' => $user->id,
                'status' => $user->status,
            ],
        ]);
    }
}
