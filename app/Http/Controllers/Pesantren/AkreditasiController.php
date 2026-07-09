<?php

namespace App\Http\Controllers\Pesantren;

use App\Http\Controllers\Controller;
use App\Models\Akreditasi;
use App\Services\AkreditasiWorkflowService;
use App\Services\PesantrenService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AkreditasiController extends Controller
{
    public function __construct(
        private PesantrenService $pesantrenService,
        private AkreditasiWorkflowService $workflowService
    ) {}

    public function index(Request $request)
    {
        abort_unless(auth()->user()->isPesantren(), 403);

        $focus = $request->input('focus', '');
        $search = $request->input('search', '');
        $periodeFilter = $request->input('periodeFilter', '');
        $statusFilter = $request->input('statusFilter', '');
        $tahapanFilter = $request->input('tahapanFilter', '');
        $perPage = $request->integer('perPage', 10);
        $sortField = $request->input('sortField', 'created_at');
        $sortAsc = $request->input('sortAsc', 'false') === 'true';

        $effectiveStatusFilter = match ($focus) {
            'perbaikan' => '-1',
            'kartu_kendali' => '2',
            'hasil', 'sertifikat', 'banding' => 'hasil_akhir',
            default => $statusFilter,
        };

        $akreditasis = $this->pesantrenService->getAkreditasis(
            Auth::id(),
            $search ?: null,
            $periodeFilter ?: null,
            $effectiveStatusFilter ?: null,
            $tahapanFilter ?: null,
            $perPage,
            $sortField,
            $sortAsc
        );

        $completeness = $this->pesantrenService->checkDataCompleteness(Auth::id());

        return view('pesantren.akreditasi', compact(
            'akreditasis', 'search', 'periodeFilter', 'statusFilter',
            'tahapanFilter', 'focus', 'perPage', 'sortField', 'sortAsc', 'completeness'
        ));
    }

    public function create(Request $request)
    {
        abort_unless(auth()->user()->isPesantren(), 403);

        try {
            $this->workflowService->submitPengajuan(Auth::id());

            return back()->with('success', 'Pengajuan akreditasi berhasil dibuat. Data profil telah dikunci.');
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function delete(Request $request)
    {
        abort_unless(auth()->user()->isPesantren(), 403);

        $request->validate(['id' => 'required|integer']);

        $success = $this->pesantrenService->deleteSubmission($request->integer('id'), Auth::id());

        if ($success) {
            return back()->with('success', 'Pengajuan akreditasi berhasil dihapus. Data profil telah dibuka kunci.');
        }

        return back()->with('error', 'Tidak dapat menghapus pengajuan ini. Pastikan status masih Pengajuan dan Anda adalah pemiliknya.');
    }

    public function cancel(Request $request)
    {
        abort_unless(auth()->user()->isPesantren(), 403);

        $request->validate(['id' => 'required|integer']);

        $result = $this->pesantrenService->cancelSubmission($request->integer('id'), Auth::id());

        if ($result) {
            return back()->with('success', 'Pengajuan akreditasi telah berhasil dibatalkan.');
        }

        return back()->with('error', 'Pengajuan tidak dapat dibatalkan. Mungkin sudah dibatalkan sebelumnya.');
    }

    public function banding(Request $request)
    {
        abort_unless(auth()->user()->isPesantren(), 403);

        $request->validate([
            'id' => 'required|integer',
            'alasan' => 'required|string|min:50',
        ], [
            'alasan.required' => 'Alasan banding wajib diisi.',
            'alasan.min' => 'Alasan banding minimal 50 karakter.',
        ]);

        $result = $this->pesantrenService->submitAppeals(
            $request->integer('id'),
            Auth::id(),
            $request->input('alasan')
        );

        if ($result) {
            return back()->with('success', 'Banding berhasil diajukan.');
        }

        return back()->with('error', 'Gagal mengajukan banding.');
    }

    public function showCatatan(int $id)
    {
        abort_unless(auth()->user()->isPesantren(), 403);

        $akreditasi = Akreditasi::with(['catatans.user'])
            ->where('user_id', Auth::id())
            ->findOrFail($id);

        return response()->json([
            'catatans' => $akreditasi->catatans->map(fn ($c) => [
                'user_name' => $c->user?->name ?? 'System',
                'catatan' => $c->catatan,
                'created_at' => $c->created_at->format('d M Y H:i'),
            ]),
        ]);
    }
}
