<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasPermission
{
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $user = $request->user();
        abort_unless($user && collect($permissions)->contains(fn ($permission) => $user->hasPermission($permission)), 403, 'Anda tidak memiliki hak akses ke modul ini.');

        return $next($request);
    }
}
