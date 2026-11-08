<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CleanupSessionRequest;
use App\Models\User;
use App\Models\UserDevice;
use App\Services\SessionManagementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AdminSessionController extends Controller
{
    /**
     * List all truly active device sessions with device info, IP, User Agent, and expiration.
     */
    public function index(Request $request, SessionManagementService $sessionService): JsonResponse
    {
        Gate::authorize('viewAny', UserDevice::class);

        $currentSessionId = $request->hasSession() ? $request->session()->getId() : '';
        $result = $sessionService->listActiveSessions($currentSessionId);

        return response()->json($result, 200);
    }

    /**
     * Revoke a specific single device session.
     */
    public function revokeSession(Request $request, int|string $id, SessionManagementService $sessionService): JsonResponse
    {
        $device = UserDevice::with('user')->findOrFail($id);
        Gate::authorize('revoke', $device);

        $info = $sessionService->revokeDevice($device);

        return response()->json([
            'message' => "Sesi perangkat {$info['device_name']} untuk {$info['user_name']} berhasil dicabut.",
        ], 200);
    }

    /**
     * Revoke all sessions for a specific user.
     */
    public function revokeUserSessions(Request $request, int|string $id, SessionManagementService $sessionService): JsonResponse
    {
        Gate::authorize('revoke', UserDevice::class);

        $targetUser = User::findOrFail($id);
        $sessionService->revokeUserSessions($targetUser);

        return response()->json([
            'message' => "Seluruh sesi perangkat untuk {$targetUser->name} berhasil dicabut.",
            'user' => $targetUser,
        ], 200);
    }

    /**
     * Emergency Kill-Switch: Revoke all active sessions system-wide except the current admin session.
     */
    public function revokeAllSessions(Request $request, SessionManagementService $sessionService): JsonResponse
    {
        Gate::authorize('revoke', UserDevice::class);

        $currentSessionId = $request->hasSession() ? $request->session()->getId() : '';
        $currentUserId = (int) $request->user()->id;

        $stats = $sessionService->revokeAllExceptCurrent($currentSessionId, $currentUserId);

        return response()->json([
            'message' => 'Seluruh sesi perangkat lain di sistem berhasil diputus secara instan.',
            'revoked_count' => $stats['revoked_count'],
            'orphaned_files_purged' => $stats['orphaned_files_purged'],
        ], 200);
    }

    /**
     * Explicit auto-cleanup endpoint to purge expired or invalid sessions.
     */
    public function cleanup(CleanupSessionRequest $request, SessionManagementService $sessionService): JsonResponse
    {
        $cleanup = $sessionService->cleanupSessions(
            $request->getGraceMinutes(),
            $request->isForce()
        );

        return response()->json([
            'message' => "Pembersihan selesai: {$cleanup['purged_count']} sesi database kadaluarsa dan {$cleanup['disk_stats']['deleted']} file sesi di server berhasil dibersihkan ({$cleanup['kb_freed']} KB ruang disk dipulihkan).",
            'purged_count' => $cleanup['purged_count'],
            'disk_stats' => $cleanup['disk_stats'],
            'remaining_disk_files' => $cleanup['remaining_disk_files'],
        ], 200);
    }
}
