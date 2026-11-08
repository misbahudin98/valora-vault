<?php

declare(strict_types=1);
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;

class EnsureRole {
    public function handle(Request $request, Closure $next, string ...$roles) {
        if (! $request->user() || ! $request->user()->hasRole(...$roles)) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }
        return $next($request);
    }
}
