<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Str;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next, ...$roles)
    {
        if (!auth()->check() || !auth()->user()->role) {
            abort(404);
        }

        $roles = array_map(function ($role) {
            return Str::upper($role);
        }, $roles);

        if (!in_array(auth()->user()->role, $roles)) {
            abort(404);
        }

        return $next($request);
    }
}
