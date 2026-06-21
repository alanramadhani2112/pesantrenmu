<?php

namespace App\Http\Controllers\Asesor;

use App\Http\Controllers\Controller;
use App\Models\Akreditasi;
use App\Models\AkreditasiEdpm;
use App\Services\AkreditasiDocumentService;
use App\Services\AkreditasiService;
use App\Services\AkreditasiWorkflowService;
use App\Services\AsesorService;
use App\Services\AssessorScoringService;
use App\Services\RejectionService;
use App\StateMachine\AkreditasiStateMachine;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

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

        Gate::authorize('view', $akreditasi);

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

    public function show(string $uuid, Request $request)
    {
        $user = auth()->user();
        abort_unless($user->isAsesor(), 403);

        $data = $this->asesorService->getAkreditasiDetailAsesor($uuid, $user->id);
        if (empty($data)) {
            abort(404);
        }

        $akreditasi = $data['akreditasi'];
        Gate::authorize('view', $akreditasi);

        $asesorTipe = $data['asesorTipe'];
        $pesantren = $data['pesantren'];
        $ipm = $data['ipm'];
        $sdm = $data['sdm'];
        $komponens = $data['komponens'];
        $visitasiTemplate = $data['visitasiTemplate'];

        $levels = [];
        if ($pesantren && $pesantren->relationLoaded('units')) {
            $levels = $pesantren->units->pluck('unit')->toArray();
        }

        // Active tab from URL
        $activeTab = $request->input('tab', 'profil');

        // Tab access restrictions based on status
        if (in_array((int) $akreditasi->status, [
            AkreditasiStateMachine::STATUS_ASSESSMENT,
            AkreditasiStateMachine::STATUS_VERIFIKASI_BERKAS,
        ], true) && $activeTab === 'laporan_visitasi') {
            $activeTab = 'profil';
        }
        if (! in_array((int) $akreditasi->status, [
            AkreditasiStateMachine::STATUS_PASCA_VISITASI,
            AkreditasiStateMachine::STATUS_VALIDASI_ADMIN,
            AkreditasiStateMachine::STATUS_SELESAI,
        ], true) && $activeTab === 'instrumen') {
            $activeTab = 'profil';
        }

        // Pesantren EDPM
        $pesantrenEvaluasis = $data['pesantren_edpm']['evaluasis'];
        $pesantrenLinks = $data['pesantren_edpm']['links'];
        $pesantrenCatatans = $data['pesantren_edpm']['catatans'];

        foreach ($komponens as $komponen) {
            if (! isset($pesantrenCatatans[$komponen->id])) {
                $pesantrenCatatans[$komponen->id] = '-';
            }
        }

        // Assessor EDPM
        $asesorEvaluasis = $data['evaluation']['asesorEvaluasis'];
        $asesorNks = $data['evaluation']['asesorNks'];
        $asesorButirCatatans = $data['evaluation']['asesorButirCatatans'];
        $asesorCatatans = $data['evaluation']['asesorCatatans'];
        $asesorCatatanNks = $data['evaluation']['asesorCatatanNks'];
        $otherAsesorEvaluasis = $data['evaluation']['otherAsesorEvaluasis'];
        $otherAsesorButirCatatans = $data['evaluation']['otherAsesorButirCatatans'];
        $otherAsesorCatatans = $data['evaluation']['otherAsesorCatatans'];

        // Rejection data (Asesor 1 only)
        $rejectionStatus = [];
        $selectableItems = [];
        if ($asesorTipe == 1) {
            $rejectionService = app(RejectionService::class);
            $rejectionStatus = $rejectionService->getRejectionStatus($akreditasi->id);
            $selectableItems = $rejectionService->getSelectableItems($akreditasi->id);
        }

        // Scoring progress (only after visitasi confirmed)
        $asesor1NaProgress = null;
        $asesor1NkProgress = null;
        $asesor2NaProgress = null;
        $nilaiKetuaFinalComplete = false;
        $nilaiAnggotaFinalComplete = false;
        $nilaiKelompokUnlocked = false;

        if ((int) $akreditasi->status === AkreditasiStateMachine::STATUS_PASCA_VISITASI) {
            $progress = $data['progress'] ?? [];
            $asesor1NaProgress = $progress['asesor1_na'] ?? null;
            $asesor1NkProgress = $progress['asesor1_nk'] ?? null;
            $asesor2NaProgress = $progress['asesor2_na'] ?? null;

            // Scoring gate state
            $ketuaUserId = $akreditasi->assessment1?->asesor?->user_id;
            $anggotaUserId = $akreditasi->assessment2?->asesor?->user_id;
            if ($ketuaUserId && $anggotaUserId) {
                $scoringService = app(AssessorScoringService::class);
                $nilaiKetuaFinalComplete = $scoringService->allNA1Final($akreditasi->id, $ketuaUserId);
                $nilaiAnggotaFinalComplete = $scoringService->allNA2Final($akreditasi->id, $anggotaUserId);
                $nilaiKelompokUnlocked = $nilaiKetuaFinalComplete && $nilaiAnggotaFinalComplete;
            }
        }

        // Final status for each butir
        $asesorFinalStatus = [];
        $currentAsesorId = $akreditasi->{'assessment'.$asesorTipe}?->asesor_id ?? null;
        if ($currentAsesorId) {
            $asesorFinalStatus = AkreditasiEdpm::where('akreditasi_id', $akreditasi->id)
                ->where('asesor_id', $currentAsesorId)
                ->where('is_final', true)
                ->pluck('is_final', 'butir_id')
                ->toArray();
        }

        // Scoring progress cards
        $scoringProgressCards = [];
        if ((int) $akreditasi->status === AkreditasiStateMachine::STATUS_PASCA_VISITASI
            && ($asesor1NaProgress || $asesor2NaProgress)) {
            $makeCard = fn ($progress, $label, $class = 'col-lg-4') => $progress ? [
                'label' => $label,
                'class' => $class,
                'completed' => $progress['completed'] ?? 0,
                'total' => $progress['total'] ?? 0,
                'percentage' => ($progress['total'] ?? 0) > 0 ? round((($progress['completed'] ?? 0) / $progress['total']) * 100) : 0,
            ] : null;

            $scoringProgressCards = $asesorTipe == 1
                ? array_values(array_filter([
                    $makeCard($asesor1NaProgress, 'Nilai Ketua'),
                    $makeCard($asesor1NkProgress, 'Nilai Kelompok'),
                    $makeCard($asesor2NaProgress, 'Nilai Anggota'),
                ]))
                : array_values(array_filter([
                    $makeCard($asesor2NaProgress, 'Nilai Anggota', 'col-lg-6'),
                    $makeCard($asesor1NaProgress, 'Nilai Ketua', 'col-lg-6'),
                ]));
        }

        // Can confirm visitasi
        $canConfirmVisitasi = false;
        if ((int) $akreditasi->status === AkreditasiStateMachine::STATUS_VISITASI && $asesorTipe == 1) {
            $canConfirmVisitasi = $akreditasi->tgl_visitasi
                && Carbon::today()->gte(Carbon::parse($akreditasi->tgl_visitasi));
        }

        // Status variant for badge
        $statusVariant = match ((int) $akreditasi->status) {
            AkreditasiStateMachine::STATUS_ASSESSMENT => 'info',
            AkreditasiStateMachine::STATUS_VERIFIKASI_BERKAS => 'warning',
            AkreditasiStateMachine::STATUS_VISITASI => 'primary',
            AkreditasiStateMachine::STATUS_PASCA_VISITASI => 'secondary',
            AkreditasiStateMachine::STATUS_VALIDASI_ADMIN => 'dark',
            AkreditasiStateMachine::STATUS_SELESAI => 'success',
            default => 'light',
        };

        // Can submit document rejection
        $canSubmitDocumentRejection = $asesorTipe == 1
            && in_array((int) $akreditasi->status, [
                AkreditasiStateMachine::STATUS_VERIFIKASI_BERKAS,
                AkreditasiStateMachine::STATUS_ASSESSMENT,
            ], true);

        // Can schedule visitasi
        $canScheduleVisitasi = $asesorTipe == 1
            && (int) $akreditasi->status === AkreditasiStateMachine::STATUS_VERIFIKASI_BERKAS;

        // IPM items for profil tab
        $ipmItems = [];
        if ($ipm) {
            $ipmItems = [
                ['label' => 'Tahun Berdiri', 'value' => $ipm->tahun_berdiri ?? '-'],
                ['label' => 'Luas Tanah', 'value' => $ipm->luas_tanah ? $ipm->luas_tanah.' m²' : '-'],
                ['label' => 'Status Tanah', 'value' => $ipm->status_tanah ?? '-'],
            ];
        }

        // Dokumen utama & sekunder
        $dokumenUtama = $akreditasi->dokumens()->where('kategori', 'utama')->get();
        $dokumenSekunder = $akreditasi->dokumens()->where('kategori', 'sekunder')->get();

        return view('asesor.akreditasi-detail', compact(
            'akreditasi', 'pesantren', 'ipm', 'sdm', 'levels', 'komponens',
            'visitasiTemplate', 'asesorTipe', 'activeTab', 'statusVariant',
            'pesantrenEvaluasis', 'pesantrenLinks', 'pesantrenCatatans',
            'asesorEvaluasis', 'asesorNks', 'asesorButirCatatans', 'asesorCatatans',
            'asesorCatatanNks', 'otherAsesorEvaluasis', 'otherAsesorButirCatatans',
            'otherAsesorCatatans', 'rejectionStatus', 'selectableItems',
            'asesor1NaProgress', 'asesor1NkProgress', 'asesor2NaProgress',
            'nilaiKetuaFinalComplete', 'nilaiAnggotaFinalComplete', 'nilaiKelompokUnlocked',
            'asesorFinalStatus', 'scoringProgressCards', 'canConfirmVisitasi',
            'canSubmitDocumentRejection', 'canScheduleVisitasi',
            'ipmItems', 'dokumenUtama', 'dokumenSekunder'
        ));
    }

    public function saveEdpm(Request $request)
    {
        abort_unless(auth()->user()->isAsesor(), 403);

        $akreditasi = Akreditasi::findOrFail($request->integer('akreditasi_id'));
        Gate::authorize('update', $akreditasi);

        if ((int) $akreditasi->status !== AkreditasiStateMachine::STATUS_PASCA_VISITASI) {
            return back()->with('error', 'Nilai asesor hanya dapat diisi setelah visitasi dikonfirmasi selesai.');
        }

        $isFinal = $request->boolean('is_final');
        $asesorId = auth()->user()->asesor->id;
        $asesorTipe = $akreditasi->assessment1?->asesor_id === $asesorId ? 1 : 2;

        try {
            $this->asesorService->saveAsesorEdpm($akreditasi->id, $asesorId, $asesorTipe, $akreditasi->user_id, [
                'asesorEvaluasis' => $request->input('asesorEvaluasis', []),
                'asesorNks' => $request->input('asesorNks', []),
                'asesorButirCatatans' => $request->input('asesorButirCatatans', []),
                'asesorCatatans' => $request->input('asesorCatatans', []),
                'asesorCatatanNks' => $request->input('asesorCatatanNks', []),
            ], $isFinal);

            return back()->with('success', $isFinal ? 'Penilaian berhasil difinalisasi.' : 'Penilaian berhasil disimpan.');
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function acceptPerbaikan(Request $request)
    {
        abort_unless(auth()->user()->isAsesor(), 403);

        $akreditasi = Akreditasi::findOrFail($request->integer('akreditasi_id'));
        Gate::authorize('update', $akreditasi);

        $rejectionService = app(RejectionService::class);
        $result = $rejectionService->acceptPerbaikan($akreditasi->id, auth()->id());

        return back()->with(
            $result['success'] ? 'success' : 'error',
            $result['success'] ? 'Perbaikan diterima. Proses visitasi dapat dilanjutkan.' : 'Gagal menerima perbaikan.'
        );
    }

    public function confirmVisitasiSelesai(Request $request)
    {
        abort_unless(auth()->user()->isAsesor(), 403);

        $akreditasi = Akreditasi::findOrFail($request->integer('akreditasi_id'));
        Gate::authorize('update', $akreditasi);

        try {
            $this->workflowService->confirmVisitasiSelesai($akreditasi->id, auth()->id());

            return back()->with('success', 'Visitasi dikonfirmasi selesai. Tahap penilaian pasca visitasi dimulai.');
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function finalizeScoring(Request $request)
    {
        abort_unless(auth()->user()->isAsesor(), 403);

        $akreditasi = Akreditasi::findOrFail($request->integer('akreditasi_id'));
        Gate::authorize('update', $akreditasi);

        try {
            $this->workflowService->finalizeAssessorScoring($akreditasi->id, auth()->id());

            return back()->with('success', 'Penilaian difinalisasi. Akreditasi masuk tahap Validasi Admin.');
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function saveNaValue(Request $request)
    {
        abort_unless(auth()->user()->isAsesor(), 403);

        $request->validate([
            'akreditasi_id' => 'required|integer',
            'butir_id' => 'required|integer',
            'value' => 'required|integer|between:1,4',
            'is_final' => 'required|boolean',
        ]);

        $akreditasi = Akreditasi::findOrFail($request->integer('akreditasi_id'));
        Gate::authorize('update', $akreditasi);

        try {
            $scoringService = app(AssessorScoringService::class);
            $scoringService->saveNA($akreditasi->id, auth()->id(), $request->integer('butir_id'), $request->integer('value'), $request->boolean('is_final'));

            return response()->json(['success' => true, 'message' => $request->boolean('is_final') ? 'Nilai dikunci sebagai Final.' : 'Nilai disimpan.']);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function saveNkValue(Request $request)
    {
        abort_unless(auth()->user()->isAsesor(), 403);

        $request->validate([
            'akreditasi_id' => 'required|integer',
            'butir_id' => 'required|integer',
            'value' => 'required|integer|between:1,4',
            'is_final' => 'required|boolean',
        ]);

        $akreditasi = Akreditasi::findOrFail($request->integer('akreditasi_id'));
        Gate::authorize('update', $akreditasi);

        try {
            $scoringService = app(AssessorScoringService::class);
            $asesor2UserId = $akreditasi->assessment2?->asesor?->user_id ?? 0;
            $scoringService->saveNK($akreditasi->id, auth()->id(), $asesor2UserId, $request->integer('butir_id'), $request->integer('value'), $request->boolean('is_final'));

            return response()->json(['success' => true, 'message' => $request->boolean('is_final') ? 'NK dikunci sebagai Final.' : 'NK disimpan.']);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function uploadLaporanIndividu(Request $request)
    {
        abort_unless(auth()->user()->isAsesor(), 403);

        $request->validate([
            'akreditasi_id' => 'required|integer',
            'laporan_individu_file' => 'required|file|mimes:pdf,docx|max:5120',
        ]);

        $akreditasi = Akreditasi::findOrFail($request->integer('akreditasi_id'));
        Gate::authorize('update', $akreditasi);

        if ((int) $akreditasi->status !== AkreditasiStateMachine::STATUS_PASCA_VISITASI) {
            return back()->with('error', 'Upload hanya diperbolehkan pada tahap pasca visitasi.');
        }

        try {
            $docService = app(AkreditasiDocumentService::class);
            $docService->uploadLaporanIndividuForAsesor($akreditasi->id, auth()->id(), $request->file('laporan_individu_file'));

            return back()->with('success', 'Laporan individu berhasil diunggah.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Upload gagal: '.$e->getMessage());
        }
    }

    public function uploadLaporanKelompok(Request $request)
    {
        abort_unless(auth()->user()->isAsesor(), 403);

        $request->validate([
            'akreditasi_id' => 'required|integer',
            'laporan_kelompok_file' => 'required|file|mimes:pdf,docx|max:5120',
        ]);

        $akreditasi = Akreditasi::findOrFail($request->integer('akreditasi_id'));
        Gate::authorize('update', $akreditasi);

        $asesorId = auth()->user()->asesor->id;
        $asesorTipe = $akreditasi->assessment1?->asesor_id === $asesorId ? 1 : 2;

        if ($asesorTipe !== 1) {
            return back()->with('error', 'Hanya Asesor 1 yang dapat mengunggah laporan kelompok.');
        }
        if ((int) $akreditasi->status !== AkreditasiStateMachine::STATUS_PASCA_VISITASI) {
            return back()->with('error', 'Upload hanya diperbolehkan pada tahap pasca visitasi.');
        }

        try {
            $docService = app(AkreditasiDocumentService::class);
            $docService->uploadLaporanKelompokForAsesor1($akreditasi->id, auth()->id(), $request->file('laporan_kelompok_file'));

            return back()->with('success', 'Laporan kelompok berhasil diunggah.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Upload gagal: '.$e->getMessage());
        }
    }
}
