<?php

namespace App\Http\Controllers\Admin;

use App\Exports\AsesorExport;
use App\Http\Controllers\Controller;
use App\Services\AsesorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Maatwebsite\Excel\Facades\Excel;

class AsesorController extends Controller
{
    public function __construct(private AsesorService $asesorService) {}

    public function index(Request $request)
    {
        abort_unless(auth()->user()->canAccessAdminArea(), 403);

        $search = $request->input('search', '');
        $filterPeran = $request->input('filterPeran', '');
        $filterPenugasan = $request->input('filterPenugasan', '');
        $filterStatus = $request->input('filterStatus', '');
        $perPage = $request->integer('perPage', 10);
        $sortField = $request->input('sortField', 'name');
        $sortAsc = filter_var($request->input('sortAsc', 'true'), FILTER_VALIDATE_BOOLEAN);

        $asesors = $this->asesorService->getPaginatedAsesors(
            compact('search', 'filterStatus', 'filterPeran', 'filterPenugasan'),
            $perPage,
            $sortField,
            $sortAsc
        );

        return view('admin.asesor.index', compact(
            'asesors', 'search', 'filterPeran', 'filterPenugasan',
            'filterStatus', 'perPage', 'sortField', 'sortAsc'
        ));
    }

    public function show(string $uuid)
    {
        abort_unless(auth()->user()->canAccessAdminArea(), 403);

        $user = $this->asesorService->findAsesor($uuid);

        if (! $user) {
            abort(404);
        }

        $asesor = $user->asesor;

        return view('admin.asesor.detail', compact('user', 'asesor'));
    }

    public function toggleStatus(Request $request)
    {
        abort_unless(auth()->user()->canAccessAdminArea(), 403);
        Gate::authorize('asesor.manage');

        $request->validate(['user_id' => 'required|integer|exists:users,id']);

        if ($this->asesorService->toggleStatus($request->integer('user_id'))) {
            return back()->with('success', 'Status asesor berhasil diperbarui.');
        }

        return back()->with('error', 'Gagal memperbarui status asesor.');
    }

    public function export(Request $request)
    {
        abort_unless(auth()->user()->canAccessAdminArea(), 403);

        $search = $request->input('search', '');
        $filterPeran = $request->input('filterPeran', '');
        $filterPenugasan = $request->input('filterPenugasan', '');
        $filterStatus = $request->input('filterStatus', '');
        $sortField = $request->input('sortField', 'name');
        $sortAsc = filter_var($request->input('sortAsc', 'true'), FILTER_VALIDATE_BOOLEAN);

        return Excel::download(
            new AsesorExport($search, $filterPeran, $filterPenugasan, $filterStatus, $sortField, $sortAsc),
            'data-asesor-'.now()->format('Y-m-d').'.xlsx'
        );
    }
}
