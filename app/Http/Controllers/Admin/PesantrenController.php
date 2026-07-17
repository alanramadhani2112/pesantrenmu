<?php

namespace App\Http\Controllers\Admin;

use App\Exports\PesantrenExport;
use App\Http\Controllers\Controller;
use App\Models\Pesantren;
use App\Services\PesantrenService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Maatwebsite\Excel\Facades\Excel;

class PesantrenController extends Controller
{
    public function __construct(private PesantrenService $pesantrenService) {}

    public function index(Request $request)
    {
        abort_unless(auth()->user()->canAccessAdminArea(), 403);

        $search = $request->input('search', '');
        $filterStatus = $request->input('filterStatus', '');
        $filterAkreditasi = $request->input('filterAkreditasi', '');
        $perPage = min(max($request->integer('perPage', 10), 5), 50);
        $sortField = $this->sortField((string) $request->input('sortField', 'name'));
        $sortAsc = filter_var($request->input('sortAsc', 'true'), FILTER_VALIDATE_BOOLEAN);

        $pesantrens = $this->pesantrenService->getPaginatedData(
            $search,
            $filterStatus,
            $filterAkreditasi,
            $perPage,
            $sortField,
            $sortAsc
        );

        return view('admin.pesantren.index', compact(
            'pesantrens', 'search', 'filterStatus', 'filterAkreditasi',
            'perPage', 'sortField', 'sortAsc'
        ));
    }

    public function show(string $uuid)
    {
        abort_unless(auth()->user()->canAccessAdminArea(), 403);

        $user = $this->pesantrenService->findUserDetail($uuid);

        if (! $user) {
            abort(404);
        }

        $pesantren = $user->pesantren;

        return view('admin.pesantren.detail', compact('user', 'pesantren'));
    }

    public function toggleLock(Request $request)
    {
        abort_unless(auth()->user()->canAccessAdminArea(), 403);
        Gate::authorize('pesantren.lock');

        $request->validate(['pesantren_id' => 'required|integer|exists:pesantrens,id']);

        if ($this->pesantrenService->toggleDataLock($request->integer('pesantren_id'))) {
            $pesantren = $request->user()->pesantren ?? Pesantren::find($request->integer('pesantren_id'));
            $status = $pesantren?->refresh()->is_locked ? 'terkunci' : 'terbuka';

            return back()->with('success', "Akses data pesantren berhasil diubah menjadi {$status}.");
        }

        return back()->with('error', 'Gagal mengubah status kunci data pesantren.');
    }

    public function export(Request $request)
    {
        abort_unless(auth()->user()->canAccessAdminArea(), 403);

        $search = $request->input('search', '');
        $filterStatus = $request->input('filterStatus', '');
        $filterAkreditasi = $request->input('filterAkreditasi', '');
        $sortField = $this->sortField((string) $request->input('sortField', 'name'));
        $sortAsc = filter_var($request->input('sortAsc', 'true'), FILTER_VALIDATE_BOOLEAN);

        return Excel::download(
            new PesantrenExport($search, $filterStatus, $filterAkreditasi, $sortField, $sortAsc),
            'data-pesantren-'.now()->format('Y-m-d').'.xlsx'
        );
    }

    private function sortField(string $sortField): string
    {
        return in_array($sortField, ['name', 'email', 'status', 'created_at', 'id'], true) ? $sortField : 'name';
    }
}
