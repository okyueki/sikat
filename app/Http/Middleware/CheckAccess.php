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

        $level = Auth::user()->level;
        $map = config('access.map', []);
        $allowedLevels = $map[$ability] ?? null;

        // If ability is not configured, deny by default (safer).
        if (!is_array($allowedLevels)) {
            abort(403, 'Unauthorized access.');
        }

        if (in_array($level, $allowedLevels, true)) {
            return $next($request);
        }

        abort(403, 'Unauthorized access.');
    }
}

