<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\AgencyProfile;
use App\Models\User;

class AgencyProfilePolicy
{
    /**
     * Determine whether the user can view the agency profile.
     */
    public function viewAny(?User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the agency profile.
     */
    public function view(?User $user, ?AgencyProfile $profile = null): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the agency profile.
     */
    public function update(User $user, ?AgencyProfile $profile = null): bool
    {
        return $user->isAdmin();
    }
}
