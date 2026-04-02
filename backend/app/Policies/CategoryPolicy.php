<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Category;

/**
 * Class CategoryPolicy
 *
 * Defines authorization rules for Category model operations.
 * 
 * Authorization Matrix:
 * - super-admin: Full access to all categories
 * - admin: Full access to all categories
 * - editor: Can manage categories
 * - moderator: Can view categories only
 * - author: Can view categories only
 * - subscriber: Can view categories only
 *
 * @package App\Policies
 */
class CategoryPolicy
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
     * Determine if the user can view any categories.
     */
    public function viewAny(?User $user): bool
    {
        // Public can view categories
        return true;
    }

    /**
     * Determine if the user can view a specific category.
     */
    public function view(?User $user, Category $category): bool
    {
        // All categories are publicly viewable
        return true;
    }

    /**
     * Determine if the user can create categories.
     */
    public function create(User $user): bool
    {
        // Only editors and admins can create categories
        return $user->hasRole(['admin', 'editor']);
    }

    /**
     * Determine if the user can update the category.
     */
    public function update(User $user, Category $category): bool
    {
        // Only editors and admins can update categories
        return $user->hasRole(['admin', 'editor']);
    }

    /**
     * Determine if the user can delete the category.
     */
    public function delete(User $user, Category $category): bool
    {
        // Only admins can delete categories
        return $user->hasRole(['admin']);
    }

    /**
     * Determine if the user can delete any category (global delete permission).
     */
    public function deleteAny(User $user): bool
    {
        return $user->hasRole(['admin']);
    }

    /**
     * Determine if the user can manage categories.
     */
    public function manage(User $user): bool
    {
        return $user->hasRole(['admin', 'editor']);
    }
}
