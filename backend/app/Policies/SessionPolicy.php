<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Models\UserDevice;

class SessionPolicy
{
    /**
     * Determine whether the user can view active device sessions.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->hasPermission('sessions.view');
    }

    /**
     * Determine whether the user can revoke device sessions.
     */
    public function revoke(User $user, ?UserDevice $device = null): bool
    {
        return $user->isAdmin() || $user->hasPermission('sessions.revoke');
    }

    /**
     * Determine whether the user can trigger disk and database session cleanups.
     */
    public function cleanup(User $user): bool
    {
        return $user->isAdmin() || $user->hasPermission('sessions.cleanup');
    }
}
