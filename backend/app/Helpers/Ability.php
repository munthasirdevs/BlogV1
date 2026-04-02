<?php

namespace App\Helpers;

use App\Models\User;
use Illuminate\Support\Facades\Cache;

/**
 * Class Ability
 *
 * Helper class for complex permission and ability checks.
 * Provides cached permission checks for improved performance.
 *
 * Usage:
 *   Ability::hasPermission($user, 'create-post')
 *   Ability::hasRole($user, 'admin')
 *   Ability::canEditPost($user, $post)
 *   Ability::canManageUsers($user)
 *
 * @package App\Helpers
 */
class Ability
{
    /**
     * Cache prefix for ability checks.
     */
    const CACHE_PREFIX = 'ability:';

    /**
     * Cache TTL in seconds (5 minutes).
     */
    const CACHE_TTL = 300;

    /**
     * Check if user has a specific permission.
     * Results are cached for performance.
     *
     * @param  User  $user
     * @param  string  $permission
     * @return bool
     */
    public static function hasPermission(User $user, string $permission): bool
    {
        $cacheKey = self::CACHE_PREFIX . "user:{$user->id}:permission:{$permission}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($user, $permission) {
            return $user->can($permission);
        });
    }

    /**
     * Check if user has any of the specified permissions.
     *
     * @param  User  $user
     * @param  array  $permissions
     * @return bool
     */
    public static function hasAnyPermission(User $user, array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if (self::hasPermission($user, $permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if user has all of the specified permissions.
     *
     * @param  User  $user
     * @param  array  $permissions
     * @return bool
     */
    public static function hasAllPermissions(User $user, array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if (!self::hasPermission($user, $permission)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if user has a specific role.
     *
     * @param  User  $user
     * @param  string  $role
     * @return bool
     */
    public static function hasRole(User $user, string $role): bool
    {
        return $user->role === $role;
    }

    /**
     * Check if user has any of the specified roles.
     *
     * @param  User  $user
     * @param  array  $roles
     * @return bool
     */
    public static function hasAnyRole(User $user, array $roles): bool
    {
        return in_array($user->role, $roles);
    }

    /**
     * Check if user has all of the specified roles.
     *
     * @param  User  $user
     * @param  array  $roles
     * @return bool
     */
    public static function hasAllRoles(User $user, array $roles): bool
    {
        foreach ($roles as $role) {
            if (!self::hasRole($user, $role)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if user can edit a post.
     * Authors can edit own posts, editors/admins can edit any.
     *
     * @param  User  $user
     * @param  mixed  $post  Post model or post user_id
     * @return bool
     */
    public static function canEditPost(User $user, $post): bool
    {
        $postUserId = is_object($post) ? $post->user_id : $post;

        // Own posts
        if ($user->id === $postUserId) {
            return true;
        }

        // Editors and admins can edit any post
        return self::hasAnyRole($user, ['admin', 'editor']);
    }

    /**
     * Check if user can delete a post.
     * Authors can delete own posts, admins can delete any.
     *
     * @param  User  $user
     * @param  mixed  $post  Post model or post user_id
     * @return bool
     */
    public static function canDeletePost(User $user, $post): bool
    {
        $postUserId = is_object($post) ? $post->user_id : $post;

        // Own posts
        if ($user->id === $postUserId) {
            return true;
        }

        // Admins can delete any post
        return self::hasRole($user, 'admin');
    }

    /**
     * Check if user can publish a post.
     * Only editors and admins can publish.
     *
     * @param  User  $user
     * @return bool
     */
    public static function canPublishPost(User $user): bool
    {
        return self::hasAnyRole($user, ['admin', 'editor']);
    }

    /**
     * Check if user can feature a post.
     * Only admins can feature posts.
     *
     * @param  User  $user
     * @return bool
     */
    public static function canFeaturePost(User $user): bool
    {
        return self::hasRole($user, 'admin');
    }

    /**
     * Check if user can manage comments.
     * Moderators and admins can manage comments.
     *
     * @param  User  $user
     * @return bool
     */
    public static function canManageComments(User $user): bool
    {
        return self::hasAnyRole($user, ['admin', 'moderator']);
    }

    /**
     * Check if user can delete a comment.
     * Users can delete own comments, moderators/admins can delete any.
     *
     * @param  User  $user
     * @param  mixed  $comment  Comment model or comment user_id
     * @return bool
     */
    public static function canDeleteComment(User $user, $comment): bool
    {
        $commentUserId = is_object($comment) ? $comment->user_id : $comment;

        // Own comments
        if ($user->id === $commentUserId) {
            return true;
        }

        // Moderators and admins can delete any comment
        return self::hasAnyRole($user, ['admin', 'moderator']);
    }

    /**
     * Check if user can approve a comment.
     * Only moderators and admins can approve.
     *
     * @param  User  $user
     * @return bool
     */
    public static function canApproveComment(User $user): bool
    {
        return self::hasAnyRole($user, ['admin', 'moderator']);
    }

    /**
     * Check if user can manage users.
     * Only admins can manage users.
     *
     * @param  User  $user
     * @return bool
     */
    public static function canManageUsers(User $user): bool
    {
        return self::hasRole($user, 'admin');
    }

    /**
     * Check if user can assign roles.
     * Only admins can assign roles (with restrictions).
     *
     * @param  User  $user
     * @return bool
     */
    public static function canAssignRoles(User $user): bool
    {
        return self::hasRole($user, 'admin');
    }

    /**
     * Check if user can ban other users.
     * Admins can ban anyone except super-admins.
     * Moderators can ban non-staff users.
     *
     * @param  User  $user
     * @param  User  $targetUser
     * @return bool
     */
    public static function canBanUser(User $user, User $targetUser): bool
    {
        // Cannot ban self
        if ($user->id === $targetUser->id) {
            return false;
        }

        // Admins can ban anyone except super-admins
        if (self::hasRole($user, 'admin')) {
            return !self::hasRole($targetUser, 'super-admin');
        }

        // Moderators can ban non-staff
        if (self::hasRole($user, 'moderator')) {
            return !self::hasAnyRole($targetUser, ['admin', 'editor', 'moderator', 'super-admin']);
        }

        return false;
    }

    /**
     * Check if user can access admin panel.
     * Staff roles can access admin panel.
     *
     * @param  User  $user
     * @return bool
     */
    public static function canAccessAdminPanel(User $user): bool
    {
        return self::hasAnyRole($user, ['admin', 'editor', 'moderator']);
    }

    /**
     * Check if user can view analytics.
     * Staff roles can view analytics.
     *
     * @param  User  $user
     * @return bool
     */
    public static function canViewAnalytics(User $user): bool
    {
        return self::hasAnyRole($user, ['admin', 'editor', 'subscriber']);
    }

    /**
     * Check if user can manage settings.
     * Only admins can manage settings.
     *
     * @param  User  $user
     * @return bool
     */
    public static function canManageSettings(User $user): bool
    {
        return self::hasRole($user, 'admin');
    }

    /**
     * Check if user can manage categories.
     * Editors and admins can manage categories.
     *
     * @param  User  $user
     * @return bool
     */
    public static function canManageCategories(User $user): bool
    {
        return self::hasAnyRole($user, ['admin', 'editor']);
    }

    /**
     * Check if user can manage tags.
     * Editors and admins can manage tags.
     *
     * @param  User  $user
     * @return bool
     */
    public static function canManageTags(User $user): bool
    {
        return self::hasAnyRole($user, ['admin', 'editor']);
    }

    /**
     * Check if user can manage media.
     * Editors and admins can manage media library.
     *
     * @param  User  $user
     * @return bool
     */
    public static function canManageMedia(User $user): bool
    {
        return self::hasAnyRole($user, ['admin', 'editor']);
    }

    /**
     * Invalidate cached abilities for a user.
     * Call this after role/permission changes.
     *
     * @param  User  $user
     * @return void
     */
    public static function invalidateCache(User $user): void
    {
        // Clear all ability cache for this user
        Cache::forget(self::CACHE_PREFIX . "user:{$user->id}");
        
        // Also clear Spatie's permission cache
        $user->forgetCachedPermissions();
    }

    /**
     * Get all permissions for a user (cached).
     *
     * @param  User  $user
     * @return \Illuminate\Support\Collection
     */
    public static function getUserPermissions(User $user): \Illuminate\Support\Collection
    {
        $cacheKey = self::CACHE_PREFIX . "user:{$user->id}:all_permissions";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($user) {
            return $user->getAllPermissions();
        });
    }

    /**
     * Get all roles for a user (cached).
     *
     * @param  User  $user
     * @return \Illuminate\Support\Collection
     */
    public static function getUserRoles(User $user): \Illuminate\Support\Collection
    {
        $cacheKey = self::CACHE_PREFIX . "user:{$user->id}:all_roles";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($user) {
            return $user->roles;
        });
    }
}
