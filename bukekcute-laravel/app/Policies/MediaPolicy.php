<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Media;

class MediaPolicy
{
    /**
     * Determine if user can upload media
     */
    public function upload(User $user): bool
    {
        return true; // All authenticated users can upload
    }

    /**
     * Determine if user can view media
     */
    public function view(User $user, Media $media): bool
    {
        return true; // All authenticated users can view (files should be public or use separate view policy)
    }

    /**
     * Determine if user can update media (e.g., mark as featured)
     */
    public function update(User $user, Media $media): bool
    {
        // Only admin or the media owner's admin can update
        return $user->is_admin ?? false;
    }

    /**
     * Determine if user can delete media
     */
    public function delete(User $user, Media $media): bool
    {
        // Only admin or the media owner's admin can delete
        return $user->is_admin ?? false;
    }

    /**
     * Determine if user can restore deleted media
     */
    public function restore(User $user, Media $media): bool
    {
        return $user->is_admin ?? false;
    }

    /**
     * Determine if user can permanently delete media
     */
    public function forceDelete(User $user, Media $media): bool
    {
        return $user->is_admin ?? false;
    }
}
