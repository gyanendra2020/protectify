<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SwitchGuardToSanctum
{
    public function handle(Request $request, Closure $next)
    {
        if (!$request->get('sanctum')) {
            config(['auth.defaults.guard' => 'sanctum']);
        }

        return $next($request);
    }
}
