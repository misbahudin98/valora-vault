<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsurePermission
{
    /**
     * Handle an incoming request.
     * Checks if the authenticated user possesses any of the required permissions.
     */
    public function handle(Request $request, Closure $next, string ...$permissions)
    {
        $user = $request->user();

        if (!$user || !$user->hasPermission(...$permissions)) {
            return response()->json([
                'message' => 'Forbidden: Hak akses tidak memadai untuk tindakan ini.',
                'required_permissions' => $permissions,
            ], 403);
        }

        return $next($request);
    }
}
