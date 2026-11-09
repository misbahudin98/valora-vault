<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class MasterBillingRatePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view master rates.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin') || $user->hasPermission('billing.view');
    }

    /**
     * Determine whether the user can create master rates.
     */
    public function create(User $user): bool
    {
        return $user->hasRole('admin') || $user->hasPermission('billing.manage');
    }

    /**
     * Determine whether the user can update master rates.
     */
    public function update(User $user): bool
    {
        return $user->hasRole('admin') || $user->hasPermission('billing.manage');
    }

    /**
     * Determine whether the user can delete master rates.
     */
    public function delete(User $user): bool
    {
        return $user->hasRole('admin') || $user->hasPermission('billing.manage');
    }

    /**
     * Determine whether the user can manage client billings and payments.
     */
    public function manage(User $user): bool
    {
        return $user->hasRole('admin') || $user->hasPermission('billing.manage');
    }
}
