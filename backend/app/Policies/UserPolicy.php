<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Determine whether the user can view any users.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->hasPermission('users.view');
    }

    /**
     * Determine whether the user can view the specific user.
     */
    public function view(User $user, User $model): bool
    {
        return $user->isAdmin() || $user->hasPermission('users.view') || $user->id === $model->id;
    }

    /**
     * Determine whether the user can create users.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->hasPermission('users.create');
    }

    /**
     * Determine whether the user can update the user.
     */
    public function update(User $user, User $model): bool
    {
        return $user->isAdmin() || $user->hasPermission('users.edit');
    }

    /**
     * Determine whether the user can delete the user.
     */
    public function delete(User $user, User $model): bool
    {
        return $user->isAdmin() || $user->hasPermission('users.delete');
    }

    /**
     * Determine whether the user can toggle the status of the user.
     */
    public function toggleStatus(User $user, User $model): bool
    {
        return $user->isAdmin() || $user->hasPermission('users.toggle_status');
    }

    /**
     * Determine whether the user can revoke all sessions of the user.
     */
    public function revokeSessions(User $user, User $model): bool
    {
        return $user->isAdmin() || $user->hasPermission('users.revoke_sessions');
    }

    /**
     * Determine whether the user can export users to CSV.
     */
    public function export(User $user): bool
    {
        return $user->isAdmin() || $user->hasPermission('reports.export');
    }
}
