<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

class SystemPolicy
{
    /**
     * Determine whether the user can manage system settings and maintenance mode.
     */
    public function manage(User $user): bool
    {
        return $user->isAdmin() || $user->hasPermission('settings.manage');
    }

    /**
     * Determine whether the user can view system logs and telemetry.
     */
    public function viewLogs(User $user): bool
    {
        return $user->isAdmin() || $user->hasPermission('settings.manage') || $user->hasPermission('reports.view');
    }
}
