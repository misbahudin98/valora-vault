<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\AuthSessionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Authenticate credentials, rotate session, register device, and issue signed JWT.
     */
    public function login(LoginRequest $request, AuthSessionService $authService): JsonResponse
    {
        $credentials = $request->credentials();

        if (!Auth::attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages(['email' => ['Kredensial tidak valid.']]);
        }

        $user = $request->user();

        // Guard against inactive or improperly configured roles
        $authService->validateUserEligibility($user, $request);

        // Cycle session and purge old unauthenticated session file on disk
        $sessionData = $authService->cycleSession($request);
        $sessionId = $sessionData['session_id'];

        if ($sessionData['has_session']) {
            $request->session()->put('auth_token_version', (int) ($user->token_version ?? 1));
        }

        // Register active device session & enforce concurrency limit
        $deviceData = $authService->registerDevice($user, $request, $sessionId);

        if ($sessionData['has_session']) {
            $request->session()->put('device_session_id', $deviceData['device']->id);
            $request->session()->put('auth_token_version', (int) ($user->token_version ?? 1));
        }

        // Generate signed JWT token and client-readable cookies
        $lifetimeMinutes = (int) env('AUTH_TOKEN_TTL_MINUTES', config('session.lifetime', 120));
        $cookies = $authService->createAuthCookies($user, $lifetimeMinutes);

        return response()->json([
            'message' => 'Authenticated.',
            'user' => $user,
            'token' => $cookies['jwt'],
            'max_devices' => $deviceData['max_devices'],
        ], 200)
            ->withCookie($cookies['jwt_cookie'])
            ->withCookie($cookies['role_cookie']);
    }

    /**
     * Terminate current session and wipe device session files.
     */
    public function logout(Request $request, AuthSessionService $authService): Response
    {
        $authService->terminateCurrentSession($request);

        $jwtCookieName = env('AUTH_JWT_COOKIE_NAME', 'auth_jwt');
        $roleCookieName = env('AUTH_ROLE_COOKIE_NAME', 'user_role');

        return response()->noContent()
            ->withCookie(cookie()->forget($jwtCookieName, '/', config('session.domain')))
            ->withCookie(cookie()->forget($roleCookieName, '/', config('session.domain')))
            ->withCookie(cookie()->forget(config('session.cookie'), '/', config('session.domain')))
            ->withCookie(cookie()->forget('XSRF-TOKEN', '/', config('session.domain')));
    }

    /**
     * Terminate all other sessions across different devices for the current user.
     */
    public function logoutOtherDevices(Request $request, AuthSessionService $authService): JsonResponse
    {
        $user = $request->user();
        $hasSession = $request->hasSession();
        $currentSessionId = $hasSession ? $request->session()->getId() : null;

        $newVersion = $authService->terminateOtherDevices($user, $currentSessionId, $hasSession, $request);

        return response()->json([
            'message' => 'Seluruh sesi perangkat lain berhasil dikeluarkan.',
            'token_version' => $newVersion,
        ], 200);
    }

    /**
     * Return authenticated user profile and roles matrix.
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json(['data' => $request->user()], 200);
    }
}
