<?php

namespace App\Policies;

use App\Models\User;
use Spatie\Permission\Models\Role;

/**
 * Class UserPolicy
 *
 * Defines authorization rules for User model operations.
 * 
 * Authorization Matrix:
 * - super-admin: Full access to all users, can manage roles including super-admin
 * - admin: Full access to users, can manage roles except super-admin
 * - editor: Can view users only
 * - moderator: Can view users and ban/unban (non-admin) users
 * - author: Can view users only
 * - subscriber: Can view public profiles only
 *
 * @package App\Policies
 */
class UserPolicy
{
    /**
     * Determine if any policy check should pass before specific checks.
     * Super-admins bypass all other checks.
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        // Banned users cannot perform any actions
        if ($user->isBanned()) {
            return false;
        }

        return null;
    }

    /**
     * Determine if the user can view any users.
     */
    public function viewAny(User $user): bool
    {
        // All authenticated users can view user list
        return true;
    }

    /**
     * Determine if the user can view a specific user's profile.
     */
    public function view(User $user, User $targetUser): bool
    {
        // Users can always view their own profile
        if ($user->id === $targetUser->id) {
            return true;
        }

        // Staff can view all user profiles
        if ($user->hasRole(['admin', 'editor', 'moderator'])) {
            return true;
        }

        // Regular users can view public profiles
        return true;
    }

    /**
     * Determine if the user can create users.
     */
    public function create(User $user): bool
    {
        // Only admins can create users
        return $user->hasRole(['admin']);
    }

    /**
     * Determine if the user can update the target user.
     */
    public function update(User $user, User $targetUser): bool
    {
        // Users can update their own profile
        if ($user->id === $targetUser->id) {
            return true;
        }

        // Admins can update any user
        if ($user->hasRole(['admin'])) {
            return true;
        }

        return false;
    }

    /**
     * Determine if the user can delete the target user.
     */
    public function delete(User $user, User $targetUser): bool
    {
        // Users cannot delete themselves through API
        if ($user->id === $targetUser->id) {
            return false;
        }

        // Only admins can delete users
        if ($user->hasRole(['admin'])) {
            // Admins cannot delete super-admins
            if ($targetUser->hasRole('super-admin')) {
                return false;
            }
            return true;
        }

        return false;
    }

    /**
     * Determine if the user can delete any user (global delete permission).
     */
    public function deleteAny(User $user): bool
    {
        return $user->hasRole(['admin']);
    }

    /**
     * Determine if the user can assign roles to the target user.
     * 
     * Security: Prevents privilege escalation
     * - Non-admins cannot assign roles
     * - Admins cannot assign super-admin role
     * - Users cannot assign roles with higher privileges than their own
     */
    public function assignRole(User $user, User $targetUser): bool
    {
        // Only admins can assign roles
        if (!$user->hasRole(['admin'])) {
            return false;
        }

        // Users cannot assign roles to themselves
        if ($user->id === $targetUser->id) {
            return false;
        }

        // Admins cannot assign roles to super-admins
        if ($targetUser->hasRole('super-admin')) {
            return false;
        }

        return true;
    }

    /**
     * Determine if the user can revoke roles from the target user.
     * 
     * Security: Prevents privilege escalation
     * - Non-admins cannot revoke roles
     * - Admins cannot revoke roles from super-admins
     * - Users cannot revoke roles from users with higher privileges
     */
    public function revokeRole(User $user, User $targetUser): bool
    {
        // Only admins can revoke roles
        if (!$user->hasRole(['admin'])) {
            return false;
        }

        // Users cannot revoke roles from themselves
        if ($user->id === $targetUser->id) {
            return false;
        }

        // Admins cannot revoke roles from super-admins
        if ($targetUser->hasRole('super-admin')) {
            return false;
        }

        return true;
    }

    /**
     * Determine if the user can ban the target user.
     */
    public function ban(User $user, User $targetUser): bool
    {
        // Users cannot ban themselves
        if ($user->id === $targetUser->id) {
            return false;
        }

        // Admins can ban any user except super-admins
        if ($user->hasRole(['admin'])) {
            return !$targetUser->hasRole('super-admin');
        }

        // Moderators can ban non-staff users
        if ($user->hasRole(['moderator'])) {
            return !$targetUser->hasRole(['admin', 'editor', 'moderator', 'super-admin']);
        }

        return false;
    }

    /**
     * Determine if the user can unban the target user.
     */
    public function unban(User $user, User $targetUser): bool
    {
        // Admins can unban any user except super-admins
        if ($user->hasRole(['admin'])) {
            return !$targetUser->hasRole('super-admin');
        }

        // Moderators can unban users they can ban
        if ($user->hasRole(['moderator'])) {
            return !$targetUser->hasRole(['admin', 'editor', 'moderator', 'super-admin']);
        }

        return false;
    }

    /**
     * Determine if the user can manage users (general management permission).
     */
    public function manage(User $user): bool
    {
        return $user->hasRole(['admin']);
    }

    /**
     * Determine if the user can manage roles (assign/revoke roles).
     */
    public function manageRoles(User $user): bool
    {
        return $user->hasRole(['admin']);
    }

    /**
     * Determine if the acting user can manage the target user's roles.
     * This is a more specific check for role management UI.
     */
    public function manageUserRoles(User $user, User $targetUser): bool
    {
        // Cannot manage own roles through this endpoint
        if ($user->id === $targetUser->id) {
            return false;
        }

        // Super-admins can manage anyone's roles
        if ($user->hasRole('super-admin')) {
            return true;
        }

        // Admins can manage roles of non-super-admins
        if ($user->hasRole('admin')) {
            return !$targetUser->hasRole('super-admin');
        }

        return false;
    }
}
