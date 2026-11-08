<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckUserActive
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if ($user) {
            $sessionId = $request->hasSession() ? $request->session()->getId() : null;

            // 1. Verifikasi status akun aktif
            if (!$user->is_active) {
                if ($sessionId) {
                    \App\Models\UserDevice::where('session_id', $sessionId)->delete();
                    $sessionFile = storage_path("framework/sessions/{$sessionId}");
                    if (file_exists($sessionFile)) {
                        @unlink($sessionFile);
                    }
                }
                Auth::guard('web')->logout();
                $postLogoutId = null;
                if ($request->hasSession()) {
                    $request->session()->invalidate();
                    $postLogoutId = $request->session()->getId();
                }

                if (config('session.driver') === 'file') {
                    app()->terminating(function () use ($sessionId, $postLogoutId) {
                        if ($sessionId) {
                            $f1 = storage_path("framework/sessions/{$sessionId}");
                            if (file_exists($f1)) @unlink($f1);
                        }
                        if ($postLogoutId) {
                            $f2 = storage_path("framework/sessions/{$postLogoutId}");
                            if (file_exists($f2)) @unlink($f2);
                        }
                    });
                }

                return response()->json([
                    'message' => 'Akun dinonaktifkan atau ditangguhkan oleh pengelola sistem.'
                ], 403)
                    ->withCookie(cookie()->forget(env('AUTH_JWT_COOKIE_NAME', 'auth_jwt'), '/', config('session.domain')))
                    ->withCookie(cookie()->forget(env('AUTH_ROLE_COOKIE_NAME', 'user_role'), '/', config('session.domain')))
                    ->withCookie(cookie()->forget(config('session.cookie'), '/', config('session.domain')))
                    ->withCookie(cookie()->forget('XSRF-TOKEN', '/', config('session.domain')));
            }

            // 2. Verifikasi kesesuaian peran akun dengan config('permissions.roles')
            $configuredRoles = array_keys(config('permissions.roles', []));
            $userRoles = (array) $user->role;
            $invalidRoles = array_diff($userRoles, $configuredRoles);

            if (empty($userRoles) || !empty($invalidRoles)) {
                if ($sessionId) {
                    \App\Models\UserDevice::where('session_id', $sessionId)->delete();
                    $sessionFile = storage_path("framework/sessions/{$sessionId}");
                    if (file_exists($sessionFile)) {
                        @unlink($sessionFile);
                    }
                }
                Auth::guard('web')->logout();
                Auth::forgetGuards();
                $postLogoutId = null;
                if ($request->hasSession()) {
                    $request->session()->invalidate();
                    $postLogoutId = $request->session()->getId();
                }

                if (config('session.driver') === 'file') {
                    app()->terminating(function () use ($sessionId, $postLogoutId) {
                        if ($sessionId) {
                            $f1 = storage_path("framework/sessions/{$sessionId}");
                            if (file_exists($f1)) @unlink($f1);
                        }
                        if ($postLogoutId) {
                            $f2 = storage_path("framework/sessions/{$postLogoutId}");
                            if (file_exists($f2)) @unlink($f2);
                        }
                    });
                }

                return response()->json([
                    'message' => 'Akses ditolak: Peran akun tidak valid atau belum dikonfigurasi.'
                ], 403)
                    ->withCookie(cookie()->forget(env('AUTH_JWT_COOKIE_NAME', 'auth_jwt'), '/', config('session.domain')))
                    ->withCookie(cookie()->forget(env('AUTH_ROLE_COOKIE_NAME', 'user_role'), '/', config('session.domain')))
                    ->withCookie(cookie()->forget(config('session.cookie'), '/', config('session.domain')))
                    ->withCookie(cookie()->forget('XSRF-TOKEN', '/', config('session.domain')));
            }

            // 3. Verifikasi token_version (Sesi multi-device / Session Revocation)
            if ($request->hasSession()) {
                $sessionVersion = $request->session()->get('auth_token_version');
                $deviceSessionId = $request->session()->get('device_session_id');

                $userDb = \App\Models\User::where('id', $user->id)->first(['token_version', 'is_active']);
                $currentTokenVersion = (int) ($userDb?->token_version ?? $user->token_version ?? 1);

                $isTokenObsolete = ($sessionVersion !== null && (int) $sessionVersion < $currentTokenVersion);
                $isDeviceRevoked = ($deviceSessionId && !\App\Models\UserDevice::where('id', $deviceSessionId)->exists());

                if ($isTokenObsolete || $isDeviceRevoked) {
                    if ($sessionId) {
                        \App\Models\UserDevice::where('session_id', $sessionId)->delete();
                        $sessionFile = storage_path("framework/sessions/{$sessionId}");
                        if (file_exists($sessionFile)) {
                            @unlink($sessionFile);
                        }
                    }
                    Auth::guard('web')->logout();
                    Auth::forgetGuards();
                    $postRevokeId = null;
                    if ($request->hasSession()) {
                        $request->session()->invalidate();
                        $postRevokeId = $request->session()->getId();
                    }

                    if (config('session.driver') === 'file') {
                        app()->terminating(function () use ($sessionId, $postRevokeId) {
                            if ($sessionId) {
                                $f1 = storage_path("framework/sessions/{$sessionId}");
                                if (file_exists($f1)) @unlink($f1);
                            }
                            if ($postRevokeId) {
                                $f2 = storage_path("framework/sessions/{$postRevokeId}");
                                if (file_exists($f2)) @unlink($f2);
                            }
                        });
                    }

                    return response()->json([
                        'message' => 'Sesi perangkat telah dicabut. Silakan login kembali.'
                    ], 401)
                        ->withCookie(cookie()->forget(env('AUTH_JWT_COOKIE_NAME', 'auth_jwt'), '/', config('session.domain')))
                        ->withCookie(cookie()->forget(env('AUTH_ROLE_COOKIE_NAME', 'user_role'), '/', config('session.domain')))
                        ->withCookie(cookie()->forget(config('session.cookie'), '/', config('session.domain')))
                        ->withCookie(cookie()->forget('XSRF-TOKEN', '/', config('session.domain')));
                }
            }
        }

        return $next($request);
    }
}
