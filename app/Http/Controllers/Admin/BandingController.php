<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\BandingService;
use Illuminate\Http\Request;

class BandingController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(auth()->user()->canAccessAdminArea(), 403);

        $statusFilter = $request->input('statusFilter', 'all');
        $search = $request->input('search', '');
        $perPage = min(max($request->integer('perPage', 10), 5), 50);

        $bandingService = app(BandingService::class);
        $bandings = $bandingService->getPaginatedBandings($statusFilter, $search, $perPage);
        $pendingCount = $bandingService->getPendingCount();

        return view('admin.banding.index', compact(
            'bandings', 'pendingCount', 'statusFilter', 'search', 'perPage'
        ));
    }
}
