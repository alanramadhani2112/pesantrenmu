<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Pastikan user yang sedang login memiliki role yang diizinkan.
     *
     * Middleware ini menjadi lapis pertama defense-in-depth authorization
     * (lihat design.md Stream 3.1). Bila user belum login atau role-nya
     * tidak cocok dengan parameter `$role`, request di-abort dengan 403.
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(403);
        }

        // Super admin bypasses all role gates (god mode).
        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        if ($user->role?->name !== $role) {
            abort(403);
        }

        return $next($request);
    }
}
