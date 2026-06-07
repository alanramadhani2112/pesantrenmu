<?php

namespace App\Http\Controllers\Asesor;

use App\Http\Controllers\Controller;
use App\Services\AkreditasiService;
use App\Services\AkreditasiWorkflowService;
use App\Services\AsesorService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AkreditasiController extends Controller
{
    public function __construct(
        private AsesorService $asesorService,
        private AkreditasiService $akreditasiService,
        private AkreditasiWorkflowService $workflowService
    ) {}

    public function index(Request $request)
    {
        abort_unless(auth()->user()->isAsesor(), 403);

        $asesor = auth()->user()->asesor;
        if (! $asesor) {
            abort(403);
        }

        $focus = $request->input('focus', '');
        $search = $request->input('search', '');
        $periodeFilter = $request->input('periodeFilter', '');
        $statusFilter = $request->input('statusFilter', '');
        $perPage = $request->integer('perPage', 10);
        $sortField = $request->input('sortField', 'id');
        $sortAsc = $request->input('sortAsc', 'false') === 'true';

        // Map focus to expected statusFilter
        $focusStatusMap = [
            'review' => 'belum',
            'jadwal' => 'belum',
            'nilai' => 'penilaian',
            'laporan_visitasi' => 'penilaian',
        ];

        $expectedStatus = $focusStatusMap[$focus] ?? null;
        if ($expectedStatus && $statusFilter !== $expectedStatus) {
            return redirect()->route('asesor.akreditasi', [
                'statusFilter' => $expectedStatus,
                'focus' => $focus,
            ]);
        }

        // Derive effective status filter
        $effectiveStatusFilter = match ($focus) {
            'review', 'jadwal' => 'belum',
            'nilai', 'laporan_visitasi' => 'penilaian',
            default => $statusFilter,
        };

        $assessments = $this->asesorService->getPaginatedAssessments(
            $asesor->id,
            $search ?: null,
            $periodeFilter ?: null,
            $effectiveStatusFilter ?: null,
            $perPage,
            $sortField,
            $sortAsc
        );

        // Context based on focus
        $activeFocus = in_array($focus, ['review', 'jadwal', 'nilai', 'laporan_visitasi'], true)
            ? $focus
            : 'tugas';

        $context = match ($activeFocus) {
            'review' => [
                'title' => 'Review Berkas',
                'subtitle' => 'Fokus pada pengajuan yang perlu dicek sebelum visitasi dijadwalkan.',
                'tableTitle' => 'Daftar Review Berkas',
                'tableSubtitle' => 'Buka detail untuk meninjau profil, IPM, SDM, EDPM/IPR, dan catatan berkas pesantren.',
            ],
            'jadwal' => [
                'title' => 'Atur Jadwal Visitasi',
                'subtitle' => 'Fokus pada pengajuan yang belum memiliki jadwal visitasi.',
                'tableTitle' => 'Daftar Penjadwalan Visitasi',
                'tableSubtitle' => 'Gunakan aksi baris untuk menetapkan atau mengubah jadwal visitasi.',
            ],
            'nilai' => [
                'title' => 'Penilaian Visitasi',
                'subtitle' => 'Fokus pada pesantren yang sedang dalam proses penilaian.',
                'tableTitle' => 'Daftar Penilaian',
                'tableSubtitle' => 'Buka detail akreditasi untuk mengisi nilai EDPM.',
            ],
            'laporan_visitasi' => [
                'title' => 'Laporan Visitasi',
                'subtitle' => 'Fokus pada laporan visitasi yang perlu dilengkapi.',
                'tableTitle' => 'Daftar Laporan',
                'tableSubtitle' => 'Lengkapi laporan visitasi setelah penilaian selesai.',
            ],
            default => [
                'title' => 'Tugas Akreditasi',
                'subtitle' => 'Daftar semua penugasan akreditasi untuk Anda.',
                'tableTitle' => 'Daftar Tugas',
                'tableSubtitle' => 'Kelola semua tugas akreditasi dari sini.',
            ],
        };

        return view('asesor.akreditasi', compact(
            'assessments', 'search', 'periodeFilter', 'statusFilter',
            'perPage', 'sortField', 'sortAsc', 'focus', 'activeFocus', 'context'
        ));
    }

    public function showCatatan(int $id)
    {
        abort_unless(auth()->user()->isAsesor(), 403);

        $akreditasi = $this->akreditasiService->findAkreditasiById($id, ['catatans.user']);

        if (! $akreditasi) {
            return response()->json(['error' => 'Data tidak ditemukan'], 404);
        }

        return response()->json([
            'pesantren' => $akreditasi->pesantren->nama ?? $akreditasi->pesantren->user->name ?? '-',
            'catatans' => $akreditasi->catatans->map(fn ($c) => [
                'user' => $c->user->name ?? '-',
                'catatan' => $c->catatan,
                'created_at' => $c->created_at->format('d/m/Y H:i'),
            ]),
        ]);
    }

    public function scheduleVisitasi(Request $request)
    {
        abort_unless(auth()->user()->isAsesor(), 403);

        $request->validate([
            'akreditasi_id' => 'required|integer|exists:akreditasis,id',
            'tanggal_mulai' => 'required|date',
            'tanggal_akhir' => 'required|date|after_or_equal:tanggal_mulai',
            'catatan' => 'nullable|string',
        ]);

        // Validate date range (max 14 days)
        $start = Carbon::parse($request->input('tanggal_mulai'));
        $end = Carbon::parse($request->input('tanggal_akhir'));

        if ($start->diffInDays($end) > 14) {
            return back()->with('error', 'Rentang visitasi maksimal adalah 14 hari.');
        }

        try {
            $this->workflowService->scheduleVisitasi(
                $request->integer('akreditasi_id'),
                auth()->id(),
                [
                    'tanggal_mulai' => $request->input('tanggal_mulai'),
                    'tanggal_akhir' => $request->input('tanggal_akhir'),
                    'catatan_visitasi' => $request->input('catatan', ''),
                ]
            );

            return back()->with('success', 'Jadwal visitasi berhasil ditetapkan.');
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function rejectDocument(Request $request)
    {
        abort_unless(auth()->user()->isAsesor(), 403);

        $request->validate([
            'akreditasi_id' => 'required|integer|exists:akreditasis,id',
            'perbaikan' => 'required|array|min:1',
            'catatan' => 'required|string|min:10',
        ]);

        try {
            $this->workflowService->createDocumentRejection(
                $request->integer('akreditasi_id'),
                auth()->id(),
                $request->input('perbaikan'),
                $request->input('catatan')
            );

            return back()->with('success', 'Penolakan dokumen berhasil dikirim ke pesantren.');
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
