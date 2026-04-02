<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Media;

/**
 * Class MediaPolicy
 *
 * Defines authorization rules for Media model operations.
 * 
 * Authorization Matrix:
 * - super-admin: Full access to all media
 * - admin: Full access to all media
 * - editor: Can upload and manage all media
 * - moderator: Can view all media
 * - author: Can upload and manage own media
 * - subscriber: Can only view media
 *
 * @package App\Policies
 */
class MediaPolicy
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
     * Determine if the user can view any media.
     */
    public function viewAny(?User $user): bool
    {
        // Public can view media
        return true;
    }

    /**
     * Determine if the user can view a specific media file.
     */
    public function view(?User $user, Media $media): bool
    {
        // All media is publicly viewable
        return true;
    }

    /**
     * Determine if the user can create/upload media.
     */
    public function create(User $user): bool
    {
        // Authors, editors, and admins can upload media
        return $user->hasRole(['author', 'editor', 'admin']);
    }

    /**
     * Determine if the user can update the media.
     */
    public function update(User $user, Media $media): bool
    {
        // Own media can be updated
        if ($user->id === $media->uploader_id) {
            return true;
        }

        // Editors and admins can update any media
        if ($user->hasRole(['admin', 'editor'])) {
            return true;
        }

        return false;
    }

    /**
     * Determine if the user can delete the media.
     */
    public function delete(User $user, Media $media): bool
    {
        // Own media can be deleted
        if ($user->id === $media->uploader_id) {
            return true;
        }

        // Admins can delete any media
        if ($user->hasRole(['admin'])) {
            return true;
        }

        // Editors can delete media
        if ($user->hasRole(['editor'])) {
            return true;
        }

        return false;
    }

    /**
     * Determine if the user can delete any media (global delete permission).
     */
    public function deleteAny(User $user): bool
    {
        return $user->hasRole(['admin']);
    }

    /**
     * Determine if the user can manage media library.
     */
    public function manage(User $user): bool
    {
        return $user->hasRole(['admin', 'editor']);
    }
}
