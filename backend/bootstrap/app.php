<?php
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\Request;
use App\Http\Middleware\SanitizeInput;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\EnsureRole;
use App\Http\Middleware\EnsurePermission;

use App\Http\Middleware\CheckUserActive;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        apiPrefix: 'api/v1',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->statefulApi();
        $middleware->validateCsrfTokens(except: [
            'api/v1/auth/login',
        ]);
        $middleware->preventRequestsDuringMaintenance(except: [
            'api/v1/admin/system/*',
        ]);
        $middleware->encryptCookies(except: ['auth_jwt', 'user_role']);
        $middleware->append(SanitizeInput::class);
        $middleware->append(SecurityHeaders::class);
        $middleware->append(CheckUserActive::class);
        $middleware->alias([
            'role' => EnsureRole::class,
            'permission' => EnsurePermission::class,
            'user.active' => CheckUserActive::class,
        ]);
    })
    ->withExceptions(function (\Illuminate\Foundation\Configuration\Exceptions $exceptions) {
        $exceptions->shouldRenderJsonWhen(fn (Request $request, Throwable $e) => $request->is('api/*') || $request->is('sanctum/*') || $request->expectsJson());

        $exceptions->render(function (Throwable $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                // Biarkan validasi dan otentikasi standar Laravel diproses normal
                if ($e instanceof \Illuminate\Validation\ValidationException ||
                    $e instanceof \Illuminate\Auth\AuthenticationException) {
                    return null;
                }

                $traceId = (string) \Illuminate\Support\Str::uuid();
                $statusCode = method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500;
                if ($statusCode < 100 || $statusCode > 599) {
                    $statusCode = 500;
                }

                \Illuminate\Support\Facades\Log::error("[API Exception] Trace: {$traceId} | " . $e->getMessage(), [
                    'trace_id' => $traceId,
                    'url' => $request->fullUrl(),
                    'method' => $request->method(),
                    'ip' => $request->ip(),
                    'user_id' => $request->user()?->id,
                    'exception' => get_class($e),
                    'file' => $e->getFile() . ':' . $e->getLine(),
                ]);

                $message = config('app.debug') || $statusCode < 500
                    ? $e->getMessage()
                    : 'Terjadi kesalahan sistem internal. Silakan hubungi administrator dengan menyebutkan ID pelacak.';

                return response()->json([
                    'status' => 'error',
                    'message' => $message ?: 'Terjadi kesalahan sistem.',
                    'trace_id' => $traceId,
                    'code' => $statusCode,
                ], $statusCode);
            }
        });
    })
    ->booted(function () {
        RateLimiter::for('api', fn (Request $r) => Limit::perMinute(app()->environment('local', 'testing') ? 300 : 60)->by($r->user()?->id ?: $r->ip()));
        RateLimiter::for('auth', fn (Request $r) => Limit::perMinute(app()->environment('local', 'testing') ? 60 : 5)->by($r->ip()));
    })->create();
