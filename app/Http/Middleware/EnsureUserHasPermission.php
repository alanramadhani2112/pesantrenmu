<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasPermission
{
    /**
     * Pastikan user yang sedang login memiliki permission yang diminta.
     *
     * Middleware ini menerima parameter permission key (e.g. `permission:akreditasi.approve`)
     * dan mengecek via `$user->hasPermission($permission)`. Jika user belum login,
     * abort 401. Jika user tidak memiliki permission, abort 403.
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(401);
        }

        if (! $user->hasPermission($permission)) {
            abort(403);
        }

        return $next($request);
    }
}
