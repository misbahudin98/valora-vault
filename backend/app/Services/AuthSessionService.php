<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Models\UserDevice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Cookie;

class AuthSessionService
{
    /**
     * Validate that user is active and has configured roles.
     *
     * @throws ValidationException
     */
    public function validateUserEligibility(User $user, Request $request): void
    {
        // 1. Verify user is active
        if (!$user->is_active) {
            Auth::guard('web')->logout();
            if ($request->hasSession()) {
                $request->session()->invalidate();
            }
            throw ValidationException::withMessages([
                'email' => ['Akun Anda telah ditangguhkan atau dinonaktifkan oleh administrator.']
            ]);
        }

        // 2. Verify roles match config('permissions.roles')
        $configuredRoles = array_keys(config('permissions.roles', []));
        $userRoles = (array) $user->role;
        $invalidRoles = array_diff($userRoles, $configuredRoles);

        if (empty($userRoles) || !empty($invalidRoles)) {
            Auth::guard('web')->logout();
            Auth::forgetGuards();
            if ($request->hasSession()) {
                $request->session()->invalidate();
            }
            throw ValidationException::withMessages([
                'email' => ['Akses ditolak: Akun Anda memiliki peran yang tidak valid atau hak akses belum dikonfigurasi. Silakan hubungi pengelola sistem.']
            ]);
        }
    }

    /**
     * Cycle session and purge old unauthenticated session file.
     *
     * @return array{has_session: bool, old_session_id: ?string, session_id: ?string}
     */
    public function cycleSession(Request $request): array
    {
        $hasSession = $request->hasSession();
        $oldSessionId = $hasSession ? $request->session()->getId() : null;

        if ($hasSession) {
            $request->session()->regenerate(true);
        }

        $sessionId = $hasSession ? $request->session()->getId() : (string) \Illuminate\Support\Str::uuid();

        if ($oldSessionId && $oldSessionId !== $sessionId && config('session.driver') === 'file') {
            $oldSessionFile = storage_path("framework/sessions/{$oldSessionId}");
            if (file_exists($oldSessionFile)) {
                @unlink($oldSessionFile);
            }
        }

        return [
            'has_session' => $hasSession,
            'old_session_id' => $oldSessionId,
            'session_id' => $sessionId,
        ];
    }

    /**
     * Register active device session in database and enforce concurrency limit.
     *
     * @return array{device: UserDevice, max_devices: int}
     */
    public function registerDevice(User $user, Request $request, ?string $sessionId): array
    {
        // 1. Purge stale/expired sessions
        UserDevice::purgeStaleSessions($user->id);

        // 2. Enforce maximum concurrent devices cap
        $maxDevices = (int) env('AUTH_MAX_CONCURRENT_DEVICES', 3);
        $activeCount = UserDevice::where('user_id', $user->id)->count();

        if ($activeCount >= $maxDevices) {
            $oldestSessions = UserDevice::where('user_id', $user->id)
                ->orderBy('created_at', 'asc')
                ->limit($activeCount - $maxDevices + 1)
                ->get();

            foreach ($oldestSessions as $oldSession) {
                if ($oldSession->session_id) {
                    $sessionFile = storage_path("framework/sessions/{$oldSession->session_id}");
                    if (file_exists($sessionFile)) {
                        @unlink($sessionFile);
                    }
                }
                $oldSession->delete();
            }
        }

        // 3. Register current device session
        $lifetimeMinutes = (int) env('AUTH_TOKEN_TTL_MINUTES', config('session.lifetime', 120));
        $deviceMeta = UserDevice::parseDeviceMetadata($request->userAgent());

        $device = UserDevice::create([
            'user_id' => $user->id,
            'session_id' => $sessionId,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'device_name' => $deviceMeta['device_name'],
            'device_type' => $deviceMeta['device_type'],
            'platform' => $deviceMeta['platform'],
            'browser' => $deviceMeta['browser'],
            'token_version' => (int) ($user->token_version ?? 1),
            'last_active_at' => now(),
            'expires_at' => now()->addMinutes($lifetimeMinutes),
        ]);

        return [
            'device' => $device,
            'max_devices' => $maxDevices,
        ];
    }

    /**
     * Generate signed JWT and associated cookies.
     *
     * @return array{jwt: string, jwt_cookie: Cookie, role_cookie: Cookie}
     */
    public function createAuthCookies(User $user, int $lifetimeMinutes): array
    {
        $jwtCookieName = env('AUTH_JWT_COOKIE_NAME', 'auth_jwt');
        $roleCookieName = env('AUTH_ROLE_COOKIE_NAME', 'user_role');
        $ttlSeconds = $lifetimeMinutes * 60;

        $jwt = JwtService::createToken([
            'sub' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'roles' => (array) $user->role,
            'permissions' => $user->getPermissions(),
            'ver' => (int) ($user->token_version ?? 1),
        ], $ttlSeconds);

        $jwtCookie = cookie(
            $jwtCookieName,
            $jwt,
            $lifetimeMinutes,
            '/',
            config('session.domain'),
            config('session.secure', false),
            false,
            false,
            'lax'
        );

        $primaryRole = is_array($user->role) ? ($user->role[0] ?? 'user') : (string) $user->role;
        $roleCookie = cookie(
            $roleCookieName,
            $primaryRole,
            $lifetimeMinutes,
            '/',
            config('session.domain'),
            config('session.secure', false),
            false,
            false,
            'lax'
        );

        return [
            'jwt' => $jwt,
            'jwt_cookie' => $jwtCookie,
            'role_cookie' => $roleCookie,
        ];
    }

    /**
     * Terminate active session and physically delete session files on server disk.
     */
    public function terminateCurrentSession(Request $request): void
    {
        $hasSession = $request->hasSession();
        $sessionId = $hasSession ? $request->session()->getId() : (string) \Illuminate\Support\Str::uuid();

        if ($sessionId) {
            UserDevice::where('session_id', $sessionId)->delete();
        }

        $user = $request->user();
        if ($user) {
            $token = $user->currentAccessToken();
            if ($token instanceof \Laravel\Sanctum\PersonalAccessToken) {
                $token->delete();
            }
        }

        Auth::guard('web')->logout();
        Auth::forgetGuards();

        $postLogoutSessionId = null;
        if ($hasSession) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            $postLogoutSessionId = $request->session()->getId();
        }

        if (config('session.driver') === 'file') {
            if ($sessionId) {
                $sessionFile = storage_path("framework/sessions/{$sessionId}");
                if (file_exists($sessionFile)) {
                    @unlink($sessionFile);
                }
            }

            app()->terminating(function () use ($sessionId, $postLogoutSessionId) {
                if ($sessionId) {
                    $f1 = storage_path("framework/sessions/{$sessionId}");
                    if (file_exists($f1)) {
                        @unlink($f1);
                    }
                }
                if ($postLogoutSessionId) {
                    $f2 = storage_path("framework/sessions/{$postLogoutSessionId}");
                    if (file_exists($f2)) {
                        @unlink($f2);
                    }
                }
            });
        }
    }

    /**
     * Log out all other devices for the authenticated user.
     */
    public function terminateOtherDevices(User $user, ?string $currentSessionId, bool $hasSession, Request $request): int
    {
        $query = UserDevice::where('user_id', $user->id);

        if ($currentSessionId) {
            $query->where('session_id', '!=', $currentSessionId);
        } elseif ($hasSession && $request->session()->has('device_session_id')) {
            $query->where('id', '!=', $request->session()->get('device_session_id'));
        }

        $otherDevices = $query->get();

        foreach ($otherDevices as $device) {
            if ($device->session_id) {
                $sessionFile = storage_path("framework/sessions/{$device->session_id}");
                if (file_exists($sessionFile)) {
                    @unlink($sessionFile);
                }
            }
            $device->delete();
        }

        $user->increment('token_version');
        if ($hasSession) {
            $request->session()->put('auth_token_version', $user->token_version);
        }

        if ($currentSessionId) {
            UserDevice::where('session_id', $currentSessionId)->update([
                'token_version' => $user->token_version
            ]);
        } elseif ($hasSession && $request->session()->has('device_session_id')) {
            UserDevice::where('id', $request->session()->get('device_session_id'))->update([
                'token_version' => $user->token_version
            ]);
        }

        return (int) $user->token_version;
    }
}
