<?php

namespace App\Http\Controllers\Admin;

use App\Exports\AkreditasiExport;
use App\Http\Controllers\Controller;
use App\Models\Akreditasi;
use App\Services\AkreditasiService;
use App\Services\DeadlineService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Maatwebsite\Excel\Facades\Excel;

class AkreditasiController extends Controller
{
    public function __construct(
        private AkreditasiService $akreditasiService,
        private DeadlineService $deadlineService
    ) {}

    public function index(Request $request)
    {
        abort_unless(auth()->user()->canAccessAdminArea(), 403);

        $statusFilter = $request->filled('statusFilter') ? (string) $request->input('statusFilter') : 'pengajuan';
        $search = $request->input('search', '');
        $perPage = $request->integer('perPage', 10);
        $sortField = $request->input('sortField', 'created_at');
        $sortAsc = filter_var($request->input('sortAsc', 'false'), FILTER_VALIDATE_BOOLEAN);

        if ($statusFilter === 'overdue') {
            $overdueIds = $this->deadlineService->getOverdueAkreditasi()->pluck('id')->toArray();

            $query = Akreditasi::with(['user.pesantren', 'assessments', 'catatans.user', 'assessment1'])
                ->whereIn('id', $overdueIds);

            if ($search) {
                $query->whereHas('user', function ($q) use ($search) {
                    $q->where('name', 'like', '%'.$search.'%')
                        ->orWhereHas('pesantren', function ($q2) use ($search) {
                            $q2->where('nama_pesantren', 'like', '%'.$search.'%');
                        });
                });
            }

            $akreditasis = $query->orderBy($sortField, $sortAsc ? 'asc' : 'desc')
                ->paginate($perPage);
        } else {
            $akreditasis = $this->akreditasiService->getPaginatedAkreditasis(
                $statusFilter, $search, $perPage, $sortField, $sortAsc
            );
        }

        $statusCounts = $this->akreditasiService->getStatusCounts();
        $overdueMap = $this->getOverdueMap();
        $asesors = $this->akreditasiService->getAvailableAsesors();

        $workflowStatus = match ($statusFilter) {
            'pengajuan' => Akreditasi::STATUS_PENGAJUAN,
            'verifikasi' => Akreditasi::STATUS_VERIFIKASI_BERKAS,
            'assessment' => Akreditasi::STATUS_ASSESSMENT,
            'visitasi' => Akreditasi::STATUS_VISITASI,
            'validasi' => Akreditasi::STATUS_VALIDASI_ADMIN,
            'selesai' => Akreditasi::STATUS_SELESAI,
            'ditolak' => Akreditasi::STATUS_DITOLAK,
            'banding' => Akreditasi::STATUS_BANDING,
            default => null,
        };

        return view('admin.akreditasi.index', compact(
            'akreditasis', 'statusCounts', 'overdueMap', 'asesors',
            'statusFilter', 'search', 'perPage', 'sortField', 'sortAsc',
            'workflowStatus'
        ));
    }

    public function catatanModal(Request $request)
    {
        abort_unless(auth()->user()->canAccessAdminArea(), 403);

        $akreditasi = $this->akreditasiService->findAkreditasiById(
            $request->integer('id'),
            ['catatans.user']
        );

        return view('admin.akreditasi._catatan_modal', compact('akreditasi'));
    }

    public function delete(Request $request)
    {
        abort_unless(auth()->user()->canAccessAdminArea(), 403);
        Gate::authorize('akreditasi.delete');

        $request->validate(['id' => 'required|integer|exists:akreditasis,id']);

        if ($this->akreditasiService->deleteAkreditasi($request->integer('id'))) {
            return back()->with('success', 'Pengajuan akreditasi berhasil dihapus.');
        }

        return back()->with('error', 'Gagal menghapus pengajuan akreditasi.');
    }

    public function export(Request $request)
    {
        abort_unless(auth()->user()->canAccessAdminArea(), 403);

        return Excel::download(
            new AkreditasiExport(
                $request->input('statusFilter', 'pengajuan'),
                $request->input('search', ''),
                $request->input('sortField', 'created_at'),
                filter_var($request->input('sortAsc', 'false'), FILTER_VALIDATE_BOOLEAN)
            ),
            'data-akreditasi-'.$request->input('statusFilter', 'pengajuan').'-'.now()->format('Y-m-d').'.xlsx'
        );
    }

    private function getOverdueMap(): array
    {
        $overdueAkreditasi = $this->deadlineService->getOverdueAkreditasi();
        $map = [];
        foreach ($overdueAkreditasi as $akreditasi) {
            $primaryAssessment = $akreditasi->assessments->firstWhere('tipe', 1)
                ?? $akreditasi->assessments->first();
            if ($primaryAssessment) {
                $map[$akreditasi->id] = $this->deadlineService->getDaysOverdue($primaryAssessment);
            }
        }

        return $map;
    }
}
