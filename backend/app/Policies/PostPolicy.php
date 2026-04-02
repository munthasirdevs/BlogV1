<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Post;

/**
 * Class PostPolicy
 *
 * Defines authorization rules for Post model operations.
 *
 * Authorization Matrix:
 * - super-admin: Full access to all posts
 * - admin: Full access to all posts
 * - editor: Can edit/publish/feature any post, delete own posts
 * - moderator: No post management permissions
 * - author: Can manage own posts only
 * - subscriber: No post management permissions
 *
 * @package App\Policies
 */
class PostPolicy
{
    /**
     * Determine if any policy check should pass before specific checks.
     * Super-admins bypass all other checks.
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->role === 'super-admin') {
            return true;
        }

        // Banned users cannot perform any actions
        if ($user->isBanned()) {
            return false;
        }

        return null;
    }

    /**
     * Determine if the user can view any posts in the list.
     */
    public function viewAny(?User $user): bool
    {
        // Public can view published posts
        return true;
    }

    /**
     * Determine if the user can view a specific post.
     */
    public function view(?User $user, Post $post): bool
    {
        // Published posts are visible to everyone
        if ($post->isPublished()) {
            return true;
        }

        // Draft posts require authentication
        if (!$user) {
            return false;
        }

        // Authors can view their own drafts
        if ($user->id === $post->user_id) {
            return true;
        }

        // Editors and admins can view all drafts
        if (in_array($user->role, ['admin', 'editor'])) {
            return true;
        }

        return false;
    }

    /**
     * Determine if the user can create posts.
     */
    public function create(User $user): bool
    {
        // All authenticated users can create posts (authors are users with posts)
        return true;
    }

    /**
     * Determine if the user can update the post.
     */
    public function update(User $user, Post $post): bool
    {
        // Own posts can be edited
        if ($user->id === $post->user_id) {
            return true;
        }

        // Editors and admins can edit any post
        return in_array($user->role, ['admin', 'editor']);
    }

    /**
     * Determine if the user can delete the post.
     */
    public function delete(User $user, Post $post): bool
    {
        // Own posts can be deleted (authors can delete their own)
        if ($user->id === $post->user_id) {
            return true;
        }

        // Admins can delete any post
        return $user->role === 'admin';
    }

    /**
     * Determine if the user can delete any post (global delete permission).
     */
    public function deleteAny(User $user): bool
    {
        return in_array($user->role, ['admin', 'super-admin']);
    }

    /**
     * Determine if the user can publish the post.
     */
    public function publish(User $user, Post $post): bool
    {
        // Only editors and admins can publish
        return in_array($user->role, ['admin', 'editor']);
    }

    /**
     * Determine if the user can unpublish the post.
     */
    public function unpublish(User $user, Post $post): bool
    {
        // Only editors and admins can unpublish
        return in_array($user->role, ['admin', 'editor']);
    }

    /**
     * Determine if the user can feature the post.
     */
    public function feature(User $user, Post $post): bool
    {
        // Only admins can feature posts
        return $user->role === 'admin';
    }

    /**
     * Determine if the user can unfeature the post.
     */
    public function unfeature(User $user, Post $post): bool
    {
        // Only admins can unfeature posts
        return $user->role === 'admin';
    }

    /**
     * Determine if the user can restore a soft-deleted post.
     */
    public function restore(User $user, Post $post): bool
    {
        // Only admins can restore deleted posts
        return $user->role === 'admin';
    }

    /**
     * Determine if the user can permanently delete a post.
     */
    public function forceDelete(User $user, Post $post): bool
    {
        // Only admins can permanently delete
        return $user->role === 'admin';
    }
}
