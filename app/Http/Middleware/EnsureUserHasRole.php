<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $role = $request->user()?->role?->name;

        abort_unless($role && in_array($role, $roles, true), 403, 'Anda tidak memiliki akses ke modul ini.');

        return $next($request);
    }
}
