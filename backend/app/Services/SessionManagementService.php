<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Models\UserDevice;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SessionManagementService
{
    /**
     * Retrieve list of active device sessions and disk telemetry metadata.
     *
     * @return array{data: Collection<int, array<string, mixed>>, meta: array<string, int>}
     */
    public function listActiveSessions(string $currentSessionId, int $limit = 1000): array
    {
        // 1. Auto-remove stale/expired sessions before listing
        UserDevice::purgeStaleSessions();

        // 2. Fetch active devices with user relationship
        $devices = UserDevice::with('user:id,name,email,role,is_active')
            ->orderBy('last_active_at', 'desc')
            ->limit(min($limit, 5000))
            ->get()
            ->map(function ($device) use ($currentSessionId) {
                $isCurrent = ($device->session_id === $currentSessionId);
                return [
                    'id' => $device->id,
                    'user_id' => $device->user_id,
                    'user_name' => $device->user?->name ?? 'User #' . $device->user_id,
                    'user_email' => $device->user?->email ?? '-',
                    'user_role' => $device->user?->role ?? ['user'],
                    'ip_address' => $device->ip_address ?? '127.0.0.1',
                    'user_agent' => $device->user_agent,
                    'device_name' => $device->device_name ?? 'Unknown Device',
                    'device_type' => $device->device_type ?? 'desktop',
                    'platform' => $device->platform ?? 'Windows',
                    'browser' => $device->browser ?? 'Browser',
                    'token_version' => $device->token_version,
                    'is_current' => $isCurrent,
                    'last_active_at' => $device->last_active_at?->toIso8601String(),
                    'expires_at' => $device->expires_at?->toIso8601String(),
                    'is_expired' => $device->expires_at ? $device->expires_at->isPast() : false,
                ];
            });

        $meta = [
            'total_tracked' => $devices->count(),
            'active_users' => $devices->pluck('user_id')->unique()->count(),
            'max_devices_cap' => (int) config('auth.max_concurrent_devices', 3),
            'disk_sessions_count' => UserDevice::countDiskSessions(),
        ];

        return [
            'data' => $devices,
            'meta' => $meta,
        ];
    }

    /**
     * Revoke single device session and physically unlink session file.
     *
     * @return array{user_name: string, device_name: string}
     */
    public function revokeDevice(UserDevice $device): array
    {
        $userName = $device->user?->name ?? 'Pengguna';
        $deviceName = $device->device_name ?? 'Perangkat';

        if ($device->session_id) {
            $sessionFile = storage_path("framework/sessions/{$device->session_id}");
            if (file_exists($sessionFile)) {
                @unlink($sessionFile);
            }
        }

        $device->delete();

        return [
            'user_name' => $userName,
            'device_name' => $deviceName,
        ];
    }

    /**
     * Revoke all sessions for a specific user.
     */
    public function revokeUserSessions(User $targetUser): void
    {
        DB::transaction(function () use ($targetUser) {
            $devices = UserDevice::where('user_id', $targetUser->id)->get();
            foreach ($devices as $device) {
                if ($device->session_id) {
                    $sessionFile = storage_path("framework/sessions/{$device->session_id}");
                    if (file_exists($sessionFile)) {
                        @unlink($sessionFile);
                    }
                }
                $device->delete();
            }

            if (method_exists($targetUser, 'tokens')) {
                $targetUser->tokens()->delete();
            }

            $targetUser->increment('token_version');
        });
    }

    /**
     * Emergency Kill-Switch: Revoke all active sessions system-wide except the current admin session.
     *
     * @return array{revoked_count: int, orphaned_files_purged: int}
     */
    public function revokeAllExceptCurrent(string $currentSessionId, int $currentUserId): array
    {
        $revokedCount = 0;

        DB::transaction(function () use ($currentSessionId, $currentUserId, &$revokedCount) {
            $otherDevices = UserDevice::where('session_id', '!=', $currentSessionId)->get();
            $revokedCount = $otherDevices->count();

            foreach ($otherDevices as $device) {
                if ($device->session_id) {
                    $sessionFile = storage_path("framework/sessions/{$device->session_id}");
                    if (file_exists($sessionFile)) {
                        @unlink($sessionFile);
                    }
                }
                $device->delete();
            }

            try {
                DB::table('personal_access_tokens')
                    ->where('tokenable_id', '!=', $currentUserId)
                    ->delete();
            } catch (\Throwable $e) {
                // Defensive fallback if PAT table absent
            }

            User::where('id', '!=', $currentUserId)->increment('token_version');
        });

        $diskStats = UserDevice::pruneDiskSessions(graceMinutes: 15, force: true);

        return [
            'revoked_count' => $revokedCount,
            'orphaned_files_purged' => (int) ($diskStats['deleted'] ?? 0),
        ];
    }

    /**
     * Purge expired sessions and prune orphaned disk session files.
     *
     * @return array{purged_count: int, disk_stats: array<string, int>, kb_freed: float, remaining_disk_files: int}
     */
    public function cleanupSessions(int $graceMinutes = 30, bool $force = false): array
    {
        $purgedDbCount = UserDevice::purgeStaleSessions(null, false);
        $diskStats = UserDevice::pruneDiskSessions($graceMinutes, $force);
        $kbFreed = round(($diskStats['bytes_freed'] ?? 0) / 1024, 2);

        return [
            'purged_count' => $purgedDbCount,
            'disk_stats' => $diskStats,
            'kb_freed' => $kbFreed,
            'remaining_disk_files' => UserDevice::countDiskSessions(),
        ];
    }
}
