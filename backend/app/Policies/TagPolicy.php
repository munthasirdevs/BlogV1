<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Tag;

/**
 * Class TagPolicy
 *
 * Defines authorization rules for Tag model operations.
 * 
 * Authorization Matrix:
 * - super-admin: Full access to all tags
 * - admin: Full access to all tags
 * - editor: Can manage tags
 * - moderator: Can view tags only
 * - author: Can view tags only
 * - subscriber: Can view tags only
 *
 * @package App\Policies
 */
class TagPolicy
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
     * Determine if the user can view any tags.
     */
    public function viewAny(?User $user): bool
    {
        // Public can view tags
        return true;
    }

    /**
     * Determine if the user can view a specific tag.
     */
    public function view(?User $user, Tag $tag): bool
    {
        // All tags are publicly viewable
        return true;
    }

    /**
     * Determine if the user can create tags.
     */
    public function create(User $user): bool
    {
        // Only editors and admins can create tags
        return $user->hasRole(['admin', 'editor']);
    }

    /**
     * Determine if the user can update the tag.
     */
    public function update(User $user, Tag $tag): bool
    {
        // Only editors and admins can update tags
        return $user->hasRole(['admin', 'editor']);
    }

    /**
     * Determine if the user can delete the tag.
     */
    public function delete(User $user, Tag $tag): bool
    {
        // Only admins can delete tags
        return $user->hasRole(['admin']);
    }

    /**
     * Determine if the user can delete any tag (global delete permission).
     */
    public function deleteAny(User $user): bool
    {
        return $user->hasRole(['admin']);
    }

    /**
     * Determine if the user can manage tags.
     */
    public function manage(User $user): bool
    {
        return $user->hasRole(['admin', 'editor']);
    }
}
