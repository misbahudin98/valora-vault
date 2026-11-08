<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\Admin\AdminUserController;

Route::prefix('auth')->middleware('throttle:auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
});

// Telemetri Error Klien Frontend (Throttled & Non-Blocking)
Route::post('/telemetry/errors', [\App\Http\Controllers\Api\V1\TelemetryController::class, 'reportError'])->middleware('throttle:60,1');

Route::middleware(['auth:sanctum', 'user.active', 'throttle:api'])->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::post('/auth/logout-other-devices', [AuthController::class, 'logoutOtherDevices']);
    Route::get('/auth/me', [AuthController::class, 'me']);

    // Admin exclusive routes (Constraint 7)
    Route::middleware('role:admin')->prefix('admin')->group(function () {
        Route::get('/stats', fn () => response()->json(['total_users' => \App\Models\User::count()]));

        // Manajemen User Khusus Admin (CRUD Lengkap & Sesi Revocation)
        Route::get('/users', [AdminUserController::class, 'index']);
        Route::get('/users/export', [AdminUserController::class, 'export']);
        Route::post('/users', [AdminUserController::class, 'store']);
        Route::put('/users/{id}', [AdminUserController::class, 'update']);
        Route::delete('/users/{id}', [AdminUserController::class, 'destroy']);
        Route::post('/users/{id}/toggle-status', [AdminUserController::class, 'toggleStatus']);
        Route::post('/users/{id}/revoke-sessions', [AdminUserController::class, 'revokeSessions']);

        // Manajemen Sesi Terpisah Khusus Admin (Constraint 5, 8, 9)
        Route::get('/sessions', [\App\Http\Controllers\Api\V1\Admin\AdminSessionController::class, 'index']);
        Route::get('/sessions/active', [\App\Http\Controllers\Api\V1\Admin\AdminSessionController::class, 'index']);
        Route::delete('/sessions/{id}', [\App\Http\Controllers\Api\V1\Admin\AdminSessionController::class, 'revokeSession']);
        Route::post('/sessions/{id}/revoke', [\App\Http\Controllers\Api\V1\Admin\AdminSessionController::class, 'revokeSession']);
        Route::post('/sessions/user/{id}/revoke', [\App\Http\Controllers\Api\V1\Admin\AdminSessionController::class, 'revokeUserSessions']);
        Route::post('/sessions/revoke-all', [\App\Http\Controllers\Api\V1\Admin\AdminSessionController::class, 'revokeAllSessions']);
        Route::post('/sessions/cleanup', [\App\Http\Controllers\Api\V1\Admin\AdminSessionController::class, 'cleanup']);

        // Status Versi & Pembaruan Sistem (Constraint 8)
        Route::get('/version', function () {
            $lockFile = dirname(base_path()) . DIRECTORY_SEPARATOR . '.installed';
            $installedData = file_exists($lockFile) ? json_decode(file_get_contents($lockFile), true) : null;
            $installedVersion = $installedData['version'] ?? '6.0.0';
            $codebaseVersion = '6.31.0';

            return response()->json([
                'codebase_version' => $codebaseVersion,
                'installed_version' => $installedVersion,
                'is_up_to_date' => version_compare($codebaseVersion, $installedVersion, '<='),
                'installed_at' => $installedData['installed_at'] ?? null,
                'updated_at' => $installedData['updated_at'] ?? null,
                'history' => $installedData['history'] ?? [],
            ]);
        });

        // Pemantauan Sistem, Mode Maintenance & Log Viewer (Backend & Frontend)
        Route::get('/system/status', [\App\Http\Controllers\Api\V1\Admin\AdminSystemController::class, 'status']);
        Route::post('/system/maintenance/toggle', [\App\Http\Controllers\Api\V1\Admin\AdminSystemController::class, 'toggleMaintenance']);
        Route::get('/system/logs', [\App\Http\Controllers\Api\V1\Admin\AdminSystemController::class, 'logs']);
        Route::get('/system/logs/client', [\App\Http\Controllers\Api\V1\Admin\AdminSystemController::class, 'clientLogs']);
        Route::delete('/system/logs', [\App\Http\Controllers\Api\V1\Admin\AdminSystemController::class, 'clearLogs']);

        // Feature Blueprint Studio & Case Study Generator (Spec-to-Code Pipeline)
        Route::get('/studio/modules', [\App\Http\Controllers\Api\V1\Admin\AdminStudioController::class, 'index']);
        Route::post('/studio/generate-spec', [\App\Http\Controllers\Api\V1\Admin\AdminStudioController::class, 'saveSpec']);
    });

    // User & Admin routes
    Route::middleware('role:user,admin')->prefix('user')->group(function () {
        Route::get('/profile', fn (\Illuminate\Http\Request $request) => response()->json(['user' => $request->user()]));
    });
});
