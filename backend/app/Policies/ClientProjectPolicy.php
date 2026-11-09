<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ClientProject;
use App\Models\User;

class ClientProjectPolicy
{
    /**
     * Determine whether the user can view any projects.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->hasPermission('projects.view');
    }

    /**
     * Determine whether the user can view the project.
     */
    public function view(User $user, ?ClientProject $project = null): bool
    {
        return $user->isAdmin() || $user->hasPermission('projects.view');
    }

    /**
     * Determine whether the user can create projects.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->hasPermission('projects.create');
    }

    /**
     * Determine whether the user can update the project.
     */
    public function update(User $user, ?ClientProject $project = null): bool
    {
        return $user->isAdmin() || $user->hasPermission('projects.edit');
    }

    /**
     * Determine whether the user can delete the project.
     */
    public function delete(User $user, ?ClientProject $project = null): bool
    {
        return $user->isAdmin() || $user->hasPermission('projects.delete');
    }
}
