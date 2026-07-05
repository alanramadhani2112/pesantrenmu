<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;

class PanduanRedirectController extends Controller
{
    public function __invoke(): RedirectResponse
    {
        $user = auth()->user();

        $route = match (true) {
            $user->isSuperAdmin() => 'panduan.superadmin',
            $user->isAdmin() => 'panduan.admin',
            $user->isAsesor() => 'panduan.asesor',
            $user->isPesantren() => 'panduan.pesantren',
            default => 'dashboard',
        };

        return redirect()->route($route);
    }
}
