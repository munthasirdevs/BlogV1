<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Comment;

/**
 * Class CommentPolicy
 *
 * Defines authorization rules for Comment model operations.
 * 
 * Authorization Matrix:
 * - super-admin: Full access to all comments
 * - admin: Full access to all comments
 * - editor: Can edit/delete any comment
 * - moderator: Can approve/reject/delete any comment
 * - author: Can manage own comments
 * - subscriber: Can manage own comments
 *
 * @package App\Policies
 */
class CommentPolicy
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
     * Determine if the user can view any comments.
     */
    public function viewAny(?User $user): bool
    {
        // Public can view approved comments
        return true;
    }

    /**
     * Determine if the user can view a specific comment.
     */
    public function view(?User $user, Comment $comment): bool
    {
        // Approved comments are visible to everyone
        if ($comment->isApproved()) {
            return true;
        }

        // Pending comments require authentication
        if (!$user) {
            return false;
        }

        // Comment author can view their own pending comments
        if ($user->id === $comment->user_id) {
            return true;
        }

        // Moderators, editors, and admins can view all comments
        if ($user->hasRole(['admin', 'editor', 'moderator'])) {
            return true;
        }

        // Post author can view comments on their post
        if ($comment->post && $comment->post->user_id === $user->id) {
            return true;
        }

        return false;
    }

    /**
     * Determine if the user can create comments.
     */
    public function create(User $user): bool
    {
        // All authenticated active users can create comments
        return $user->isActive();
    }

    /**
     * Determine if the user can update the comment.
     */
    public function update(User $user, Comment $comment): bool
    {
        // Own comments can be edited
        if ($user->id === $comment->user_id) {
            return true;
        }

        // Editors, moderators, and admins can edit any comment
        if ($user->hasRole(['admin', 'editor', 'moderator'])) {
            return true;
        }

        return false;
    }

    /**
     * Determine if the user can delete the comment.
     */
    public function delete(User $user, Comment $comment): bool
    {
        // Own comments can be deleted
        if ($user->id === $comment->user_id) {
            return true;
        }

        // Moderators and admins can delete any comment
        if ($user->hasRole(['admin', 'moderator'])) {
            return true;
        }

        // Editors can delete comments
        if ($user->hasRole(['editor'])) {
            return true;
        }

        return false;
    }

    /**
     * Determine if the user can delete any comment (global delete permission).
     */
    public function deleteAny(User $user): bool
    {
        return $user->hasRole(['admin', 'moderator']);
    }

    /**
     * Determine if the user can approve the comment.
     */
    public function approve(User $user, Comment $comment): bool
    {
        // Only moderators and admins can approve comments
        return $user->hasRole(['admin', 'moderator']);
    }

    /**
     * Determine if the user can reject the comment.
     */
    public function reject(User $user, Comment $comment): bool
    {
        // Only moderators and admins can reject comments
        return $user->hasRole(['admin', 'moderator']);
    }

    /**
     * Determine if the user can moderate comments (bulk actions).
     */
    public function moderate(User $user): bool
    {
        return $user->hasRole(['admin', 'moderator']);
    }

    /**
     * Determine if the user can restore a soft-deleted comment.
     */
    public function restore(User $user, Comment $comment): bool
    {
        // Only admins and moderators can restore deleted comments
        return $user->hasRole(['admin', 'moderator']);
    }

    /**
     * Determine if the user can permanently delete a comment.
     */
    public function forceDelete(User $user, Comment $comment): bool
    {
        // Only admins can permanently delete
        return $user->hasRole(['admin']);
    }
}
