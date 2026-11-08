<?php

declare(strict_types=1);
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;

class SanitizeInput {
    public function handle(Request $request, Closure $next) {
        $input = $request->all();
        $except = ['password', 'password_confirmation', 'current_password', 'new_password'];
        array_walk_recursive($input, function (&$val, $key) use ($except) {
            if (is_string($val) && !in_array($key, $except, true)) {
                $val = htmlspecialchars(strip_tags($val), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            }
        });
        $request->merge($input);
        return $next($request);
    }
}
