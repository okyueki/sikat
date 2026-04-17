<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckAccess
{
    public function handle(Request $request, Closure $next, string $ability)
    {
        if (!Auth::check()) {
            abort(403, 'Unauthorized access.');
        }

        // Ability harus ada di config access.map (bukan config('access.map.'.$x) — titik di nama ability bukan nested key).
        $levels = config('access.map', [])[$ability] ?? null;
        if (! is_array($levels)) {
            abort(403, 'Unauthorized access.');
        }

        if (Auth::user()->can($ability)) {
            return $next($request);
        }

        abort(403, 'Unauthorized access.');
    }
}

