<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\User;
use App\Services\UserManagementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminUserController extends Controller
{
    /**
     * List all users for administration with search and status filtering.
     */
    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', User::class);

        $query = User::select(['id', 'name', 'email', 'role', 'is_active', 'token_version', 'created_at']);

        // Support sanitized search filter against Wildcard DoS
        if ($search = $request->input('search')) {
            $escaped = addcslashes(trim((string) $search), '%_\\');
            $query->where(function ($q) use ($escaped) {
                $q->where('name', 'like', "%{$escaped}%")
                  ->orWhere('email', 'like', "%{$escaped}%");
            });
        }

        // Support optional status / is_active filter
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        } elseif ($status = $request->input('status')) {
            $query->where('is_active', $status === 'active' || $status === '1' || $status === 1);
        }

        // Safe query ceiling limit (default 1000, capped at 5000)
        $limit = min((int) $request->input('limit', 1000), 5000);
        $users = $query->orderBy('id', 'asc')->limit($limit)->get();

        return response()->json([
            'data' => $users,
            'meta' => [
                'total' => User::count(),
                'active_count' => User::where('is_active', true)->count(),
                'limit' => $limit,
            ]
        ], 200);
    }

    /**
     * Create a new user (CRUD - Store).
     */
    public function store(StoreUserRequest $request, UserManagementService $userService): JsonResponse
    {
        $validated = $request->validated();
        $roles = $request->getValidatedRoles();
        $isActive = $request->boolean('is_active', true);

        $user = $userService->createUser($validated, $roles, $isActive);

        return response()->json([
            'message' => 'Pengguna berhasil ditambahkan.',
            'user' => $user
        ], 201);
    }

    /**
     * Update existing user (CRUD - Update).
     */
    public function update(UpdateUserRequest $request, int|string $id, UserManagementService $userService): JsonResponse
    {
        $targetUser = User::findOrFail($id);

        $validated = $request->validated();
        $roles = $request->getValidatedRoles();
        $isActive = $request->has('is_active') ? $request->boolean('is_active') : null;

        $updatedUser = $userService->updateUser($targetUser, $validated, $roles, $isActive);

        return response()->json([
            'message' => 'Data pengguna berhasil diperbarui.',
            'user' => $updatedUser
        ], 200);
    }

    /**
     * Delete user (CRUD - Destroy).
     */
    public function destroy(Request $request, int|string $id, UserManagementService $userService): JsonResponse
    {
        $currentUser = $request->user();
        if ((int) $id === (int) $currentUser?->id) {
            return response()->json(['message' => 'Tidak dapat menghapus akun sendiri.'], 400);
        }

        $targetUser = User::findOrFail($id);
        Gate::authorize('delete', $targetUser);

        $userService->deleteUser($targetUser);

        return response()->json([
            'message' => 'Pengguna berhasil dihapus secara permanen.'
        ], 200);
    }

    /**
     * Toggle user status between active and suspended.
     */
    public function toggleStatus(Request $request, int|string $id, UserManagementService $userService): JsonResponse
    {
        $currentUser = $request->user();
        if ((int) $id === (int) $currentUser?->id) {
            return response()->json(['message' => 'Tidak dapat menonaktifkan akun sendiri.'], 400);
        }

        $targetUser = User::findOrFail($id);
        Gate::authorize('toggleStatus', $targetUser);

        $newStatus = $userService->toggleStatus($targetUser);

        return response()->json([
            'message' => $newStatus ? 'Pengguna berhasil diaktifkan kembali.' : 'Pengguna berhasil ditangguhkan.',
            'user' => $targetUser
        ], 200);
    }

    /**
     * Invalidate all sessions/tokens for a target user immediately.
     */
    public function revokeSessions(Request $request, int|string $id, UserManagementService $userService): JsonResponse
    {
        $targetUser = User::findOrFail($id);
        Gate::authorize('revokeSessions', $targetUser);

        $userService->revokeSessions($targetUser);

        return response()->json([
            'message' => 'Seluruh sesi perangkat pengguna berhasil dicabut secara instan.',
            'user' => $targetUser
        ], 200);
    }

    /**
     * Stream all users as CSV with O(1) memory overhead.
     */
    public function export(Request $request): StreamedResponse
    {
        Gate::authorize('export', User::class);

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="users_export_' . date('Ymd_His') . '.csv"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return new StreamedResponse(function () {
            $handle = fopen('php://output', 'w');

            // UTF-8 BOM for Microsoft Excel compatibility
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, ['ID', 'Nama', 'Email', 'Peran (Roles)', 'Status Akun', 'Token Version', 'Dibuat Pada']);

            User::orderBy('id', 'asc')->chunk(100, function ($users) use ($handle) {
                foreach ($users as $user) {
                    $roles = is_array($user->role) ? implode(', ', $user->role) : (string) $user->role;
                    fputcsv($handle, [
                        $user->id,
                        $user->name,
                        $user->email,
                        $roles,
                        $user->is_active ? 'Aktif' : 'Ditangguhkan',
                        $user->token_version ?? 1,
                        $user->created_at?->format('Y-m-d H:i:s') ?? '-',
                    ]);
                }
            });

            fclose($handle);
        }, 200, $headers);
    }
}
