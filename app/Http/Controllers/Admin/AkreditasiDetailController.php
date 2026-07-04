<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Akreditasi;
use App\Models\Asesor;
use App\Services\AkreditasiService;
use App\Services\AkreditasiWorkflowService;
use App\Services\AuditTrailService;
use App\Services\AssessorScoringService;
use App\Services\DeadlineService;
use App\Services\PesantrenService;
use App\Services\ProgressTracker;
use App\Services\RejectionService;
use App\Services\ScoreCalculationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class AkreditasiDetailController extends Controller
{
    public function __construct(
        private AkreditasiService $akreditasiService,
        private PesantrenService $pesantrenService,
        private DeadlineService $deadlineService,
        private RejectionService $rejectionService,
        private ProgressTracker $progressTracker,
        private AssessorScoringService $scoringService,
        private AkreditasiWorkflowService $workflowService,
        private ScoreCalculationService $scoreCalculationService,
    ) {}

    public function show(string $uuid)
    {
        abort_unless(auth()->user()->canAccessAdminArea(), 403);

        $akreditasi = $this->akreditasiService->findAkreditasi($uuid, [
            'user.pesantren', 'assessments.asesor.user', 'assessment1', 'assessment2',
        ]);

        if (! $akreditasi) {
            abort(404);
        }

        Gate::authorize('view', $akreditasi);

        $userId = $akreditasi->user_id;
        $pesantren = $this->pesantrenService->getProfile($userId);
        $ipm = $this->pesantrenService->getIpm($userId);
        $sdm = $this->pesantrenService->getSdm($userId)->keyBy('tingkat');

        $levels = [];
        if ($pesantren && $pesantren->relationLoaded('units')) {
            $levels = $pesantren->units->pluck('unit')->toArray();
        }

        $pEdpmData = $this->pesantrenService->getEdpmData($userId);
        $komponens = $pEdpmData['komponens'];

        $pesantrenEvaluasis = $pEdpmData['existingEdpms']->pluck('isian', 'butir_id');
        $pesantrenLinks = $pEdpmData['existingEdpms']->pluck('link', 'butir_id');
        $pesantrenCatatans = $pEdpmData['existingCatatans'];

        $asesor1Evaluasis = [];
        $asesor1Catatans = [];
        $asesor1Nks = [];
        $asesor1CatatanNks = [];
        $asesor1ButirCatatans = [];
        $asesor1Nvs = [];

        $asesor1Id = $akreditasi->assessment1->asesor_id ?? null;
        if ($asesor1Id) {
            $a1Data = $this->akreditasiService->getAsesorEdpmData($akreditasi->id, $asesor1Id);
            $asesor1Evaluasis = $a1Data['evaluasis'];
            $asesor1Nks = $a1Data['nks'];
            $asesor1Nvs = $a1Data['nvs'];
            $asesor1ButirCatatans = $a1Data['butirCatatans'];
            $asesor1Catatans = $a1Data['catatans'];
            $asesor1CatatanNks = $a1Data['catatanNks'];
        }

        $asesor2Evaluasis = [];
        $asesor2Catatans = [];
        $asesor2ButirCatatans = [];

        $asesor2Id = $akreditasi->assessment2->asesor_id ?? null;
        if ($asesor2Id) {
            $a2Data = $this->akreditasiService->getAsesorEdpmData($akreditasi->id, $asesor2Id);
            $asesor2Evaluasis = $a2Data['evaluasis'];
            $asesor2Catatans = $a2Data['catatans'];
            $asesor2ButirCatatans = $a2Data['butirCatatans'];
        }

        // Build adminNvs array from existing data
        $adminNvs = [];
        foreach ($komponens as $komponen) {
            foreach ($komponen->butirs as $butir) {
                $adminNvs[$butir->id] = ['nv' => $asesor1Nvs[$butir->id] ?? '', 'nk' => $asesor1Nks[$butir->id] ?? ''];
                $asesor1ButirCatatans[$butir->id] = $asesor1ButirCatatans[$butir->id] ?? '';
                $asesor2Evaluasis[$butir->id] = $asesor2Evaluasis[$butir->id] ?? '';
                $asesor2ButirCatatans[$butir->id] = $asesor2ButirCatatans[$butir->id] ?? '';
            }
            $asesor1CatatanNks[$komponen->id] = $asesor1CatatanNks[$komponen->id] ?? '';
        }

        $rejectionStatus = $this->rejectionService->getRejectionStatus($akreditasi->id);

        $asesor1NaProgress = null;
        $asesor1NkProgress = null;
        $asesor2NaProgress = null;

        if ($akreditasi->status == \App\StateMachine\AkreditasiStateMachine::STATUS_PASCA_VISITASI) {
            $progress = $this->progressTracker->getAkreditasiProgress($akreditasi->id);
            $asesor1NaProgress = $progress['asesor1_na'];
            $asesor1NkProgress = $progress['asesor1_nk'];
            $asesor2NaProgress = $progress['asesor2_na'];
        }

        $isOverdue = false;
        $availableAsesorsForReassignment = [];
        $primaryAssessment = $akreditasi->assessments->firstWhere('tipe', 1)
            ?? $akreditasi->assessments->first();
        if ($primaryAssessment) {
            $isOverdue = $this->deadlineService->isOverdue($primaryAssessment);
            if ($isOverdue) {
                $availableAsesorsForReassignment = $this->deadlineService
                    ->getAvailableAsesorsForReassignment($primaryAssessment)
                    ->toArray();
            }
        }

        $asesorsForAssignment = Asesor::query()
            ->with('user:id,name')
            ->get()
            ->map(fn (Asesor $asesor): array => [
                'user_id' => $asesor->user_id,
                'name' => $asesor->nama_tanpa_gelar ?? ($asesor->user?->name ?? 'Asesor #' . $asesor->user_id),
            ])
            ->all();

        $fields = [
            'santri_l', 'santri_p', 'ustadz_dirosah_l', 'ustadz_dirosah_p',
            'ustadz_non_dirosah_l', 'ustadz_non_dirosah_p', 'pamong_l', 'pamong_p',
            'musyrif_l', 'musyrif_p', 'tendik_l', 'tendik_p',
        ];

        // Compute SDM totals
        $sdmTotals = [];
        foreach ($fields as $field) {
            $sdmTotals[$field] = 0;
            foreach ($levels as $level) {
                $sdmTotals[$field] += (int) ($sdm[$level]->$field ?? 0);
            }
        }

        // Compute scoring progress cards (replaces adminScoringProgressCards())
        $scoringProgressCards = $this->buildScoringProgressCards(
            $asesor1NaProgress, $asesor1NkProgress, $asesor2NaProgress
        );
        $scoringBlockers = array_values(array_map(
            fn(array $card): string => 'Menunggu ' . $card['label'],
            array_filter(
                $scoringProgressCards,
                fn(array $card): bool => (float) ($card['progress']['percentage'] ?? 0) < 100.0
            )
        ));

        // Compute score summary (replaces adminScoreSummaryViewData())
        $scoreSummary = $this->buildScoreSummary($komponens, $adminNvs);

        // Audit Trail data — loaded only when tab is active
        $auditLogs = collect();
        $auditActors = collect();
        $auditActionTypes = AuditTrailService::ALLOWED_ACTION_TYPES;
        $auditFilterActionType = request()->query('audit_action_type', '');
        $auditFilterUserId = request()->query('audit_user_id', '');
        $auditFilterDateFrom = request()->query('audit_date_from', '');
        $auditFilterDateTo = request()->query('audit_date_to', '');

        if (request()->query('tab') === 'audit_trail') {
            $auditService = app(AuditTrailService::class);
            $auditFilters = [];
            if ($auditFilterActionType !== '') {
                $auditFilters['action_type'] = $auditFilterActionType;
            }
            if ($auditFilterUserId !== '') {
                $auditFilters['user_id'] = (int) $auditFilterUserId;
            }
            if ($auditFilterDateFrom !== '') {
                $auditFilters['date_from'] = $auditFilterDateFrom;
            }
            if ($auditFilterDateTo !== '') {
                $auditFilters['date_to'] = $auditFilterDateTo;
            }
            $auditLogs = $auditService->getTimeline($akreditasi->id, $auditFilters, 15);

            $auditActors = DB::table('akreditasi_audit_logs')
                ->join('users', 'akreditasi_audit_logs.user_id', '=', 'users.id')
                ->where('akreditasi_audit_logs.akreditasi_id', $akreditasi->id)
                ->select('users.id', 'users.name')
                ->distinct()
                ->orderBy('users.name')
                ->get();
        }

        // Build activeTab from query string (optional override)
        $activeTab = request()->query('tab', request()->query('activeTab', 'profil'));

        return view('admin.akreditasi.detail', compact(
            'akreditasi', 'pesantren', 'ipm', 'sdm', 'komponens', 'levels',
            'pesantrenEvaluasis', 'pesantrenCatatans', 'pesantrenLinks',
            'asesor1Evaluasis', 'asesor1Catatans', 'asesor1Nks', 'asesor1CatatanNks',
            'asesor1ButirCatatans', 'asesor1Nvs',
            'asesor2Evaluasis', 'asesor2Catatans', 'asesor2ButirCatatans',
            'adminNvs', 'rejectionStatus',
            'asesor1NaProgress', 'asesor1NkProgress', 'asesor2NaProgress',
            'isOverdue', 'availableAsesorsForReassignment', 'asesorsForAssignment',
            'fields', 'sdmTotals',
            'scoringProgressCards', 'scoringBlockers', 'scoreSummary',
            'auditLogs', 'auditActors', 'auditActionTypes',
            'auditFilterActionType', 'auditFilterUserId', 'auditFilterDateFrom', 'auditFilterDateTo',
            'activeTab'
        ));
    }

    private function buildScoringProgressCards(
        ?array $asesor1NaProgress,
        ?array $asesor1NkProgress,
        ?array $asesor2NaProgress
    ): array {
        $cards = [
            ['progress' => $asesor1NaProgress, 'label' => 'Nilai Ketua'],
            ['progress' => $asesor1NkProgress, 'label' => 'Nilai Kelompok'],
            ['progress' => $asesor2NaProgress, 'label' => 'Nilai Anggota'],
        ];

        return array_values(array_filter(array_map(function (array $card): ?array {
            if (empty($card['progress'])) {
                return null;
            }
            $card['color'] = $this->progressTracker->getColorClass((float) ($card['progress']['percentage'] ?? 0));
            return $card;
        }, $cards)));
    }

    private function buildScoreSummary($komponens, array $adminNvs): array
    {
        $rows = [];
        $totalSkorIk = 0.0;
        $totalSkorIpr = 0.0;
        $ikRowCount = 0;
        $iprRowCount = 0;

        foreach ($komponens ?? [] as $komponen) {
            $isIpr = ! is_null($komponen->ipr);
            $butirs = $komponen->butirs ?? collect();
            $cmaks = count($butirs) * ScoreCalculationService::SCORE_MAX;
            $ci = 0;

            foreach ($butirs as $butir) {
                $raw = $adminNvs[$butir->id] ?? 0;
                $ci += (int) (is_array($raw) ? ($raw['nv'] ?? 0) : $raw);
            }

            $bobot = $isIpr
                ? 97
                : (ScoreCalculationService::KOMPONEN_CONFIG[$komponen->nama]['bobot'] ?? 0);
            $factor = $isIpr ? 100 : $bobot;
            $score = $cmaks > 0 ? round(($ci / $cmaks) * $factor) : 0;

            if ($isIpr) {
                $totalSkorIpr += $score;
                $iprRowCount++;
            } else {
                $totalSkorIk += $score;
                $ikRowCount++;
            }

            $rows[] = [
                'name' => $komponen->nama,
                'cmaks' => $cmaks,
                'ci' => $ci,
                'bk' => $bobot,
                'score' => $score,
                'is_ipr' => $isIpr,
                'total_score' => null,
                'total_rowspan' => null,
            ];
        }

        // Annotate rowspan for total score display
        $ikRow = null;
        foreach ($rows as $i => &$row) {
            if (! $row['is_ipr'] && $ikRow === null) {
                $ikRow = &$rows[$i];
                $ikRow['total_score'] = round($totalSkorIk, 1);
                $ikRow['total_rowspan'] = $ikRowCount;
            }
        }

        $totalIpr = round($totalSkorIpr, 1);
        $finalScore = round($totalSkorIk + $totalSkorIpr, 1);

        $nilaiAkreditasi = match (true) {
            $finalScore >= 86 => 'A (Unggul)',
            $finalScore >= 71 => 'B (Baik)',
            $finalScore >= 56 => 'C (Cukup Baik)',
            default => 'D (Kurang)',
        };
        $peringkat = match (true) {
            $finalScore >= 86 => 'A - Unggul',
            $finalScore >= 71 => 'B - Baik',
            $finalScore >= 56 => 'C - Cukup',
            default => 'D - Kurang',
        };

        return [
            'rows' => $rows,
            'result' => [
                'nilai_akreditasi' => $nilaiAkreditasi,
                'peringkat' => $peringkat,
                'total_skor_ik' => $totalSkorIk,
                'total_skor_ipr' => $totalIpr,
                'final_score' => $finalScore,
            ],
        ];
    }

    public function rescheduleVisitasi(Request $request, string $uuid)
    {
        abort_unless(auth()->user()->canAccessAdminArea(), 403);

        $akreditasi = $this->akreditasiService->findAkreditasi($uuid);
        if (! $akreditasi) abort(404);

        Gate::authorize('akreditasi.approve');

        $validated = $request->validate([
            'tgl_visitasi' => 'required|date',
            'tgl_visitasi_akhir' => 'required|date|after_or_equal:tgl_visitasi',
        ]);

        try {
            $asesor1UserId = $akreditasi->assessment1?->asesor?->user_id;
            if (! $asesor1UserId) {
                return back()->with('error', 'Ketua Kelompok tidak ditemukan.');
            }

            $this->workflowService->rescheduleVisitasi($akreditasi->id, $asesor1UserId, [
                'tanggal_mulai' => $validated['tgl_visitasi'],
                'tanggal_akhir' => $validated['tgl_visitasi_akhir'],
                'catatan_visitasi' => $akreditasi->catatan_visitasi ?? '',
            ]);

            return back()->with('success', 'Jadwal Visitasi berhasil diperbarui.');
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function saveAdminNv(Request $request, string $uuid)
    {
        abort_unless(auth()->user()->canAccessAdminArea(), 403);

        $akreditasi = $this->akreditasiService->findAkreditasi($uuid);
        if (! $akreditasi) abort(404);

        Gate::authorize('akreditasi.approve');

        if ((int) $akreditasi->status !== 1) {
            return back()->with('error', 'Data tidak dapat diubah karena status bukan Validasi Admin.');
        }

        if (empty($akreditasi->laporan_visitasi_asesor1)) {
            return back()->with('error', 'Nilai NV belum dapat disimpan karena Laporan Visitasi Ketua Kelompok belum diunggah.');
        }

        $request->validate([
            'adminNvs' => 'required|array',
            'nvReason' => 'nullable|string|max:2000',
        ]);

        $adminId = Auth::id();
        $errors = [];
        foreach ($request->input('adminNvs', []) as $butirId => $nvValue) {
            if (! empty($nvValue) && is_numeric($nvValue) && $nvValue >= 1 && $nvValue <= 4) {
                try {
                    $this->scoringService->saveNV($akreditasi->id, $adminId, (int) $butirId, (int) $nvValue, false);
                } catch (\Throwable $e) {
                    $errors[] = "Butir #{$butirId}: " . $e->getMessage();
                }
            }
        }

        if (! empty($errors)) {
            return back()->with('warning', implode('; ', array_slice($errors, 0, 3)));
        }

        return back()->with('success', 'Nilai Verifikasi berhasil disimpan.');
    }

    public function finalizeAllNv(Request $request, string $uuid)
    {
        abort_unless(auth()->user()->canAccessAdminArea(), 403);

        $akreditasi = $this->akreditasiService->findAkreditasi($uuid);
        if (! $akreditasi) abort(404);

        Gate::authorize('akreditasi.approve');

        if ((int) $akreditasi->status !== 1) {
            return back()->with('error', 'Finalisasi NV hanya dapat dilakukan saat status Validasi Admin.');
        }

        if (empty($akreditasi->laporan_visitasi_asesor1)) {
            return back()->with('error', 'NV belum dapat difinalisasi karena Laporan Visitasi Ketua Kelompok belum diunggah.');
        }

        $request->validate([
            'adminNvs' => 'required|array',
            'nvReasons' => 'nullable|array',
            'nvReasons.*' => 'nullable|string|max:2000',
        ]);

        $adminId = Auth::id();
        $finalizedCount = 0;
        $reasons = $request->input('nvReasons', []);

        try {
            DB::transaction(function () use ($request, $akreditasi, $adminId, $reasons, &$finalizedCount): void {
                foreach ($request->input('adminNvs', []) as $butirId => $nvValue) {
                    if (is_numeric($nvValue) && $nvValue >= 1 && $nvValue <= 4) {
                        $reason = isset($reasons[$butirId]) && is_string($reasons[$butirId]) ? trim($reasons[$butirId]) : null;
                        if ($reason === '') {
                            $reason = null;
                        }

                        try {
                            $this->scoringService->saveNV($akreditasi->id, $adminId, (int) $butirId, (int) $nvValue, true, $reason);
                            $finalizedCount++;
                        } catch (\App\Exceptions\ImmutableValueException $e) {
                            $finalizedCount++;
                        }
                    }
                }
            });
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        if ($finalizedCount === 0) {
            return back()->with('error', 'Tidak ada NV valid yang dikirim untuk difinalisasi.');
        }

        $expectedFinalCount = \App\Models\AkreditasiEdpm::where('akreditasi_id', $akreditasi->id)
            ->whereNotNull('nk')
            ->count();

        $actualFinalCount = \App\Models\AkreditasiEdpm::where('akreditasi_id', $akreditasi->id)
            ->whereNotNull('nv')
            ->where('is_final', true)
            ->count();

        if ($actualFinalCount < $expectedFinalCount) {
            return back()->with('error', "Finalisasi NV belum lengkap ({$actualFinalCount}/{$expectedFinalCount} butir). Lengkapi alasan perubahan NV dan semua nilai final terlebih dahulu.");
        }

        $akreditasi->update(['is_nv_final' => true]);

        return back()->with('success', "Semua NV ({$finalizedCount} butir) berhasil difinalisasi dan dikunci.");
    }

    public function openForReview(Request $request, string $uuid)
    {
        abort_unless(auth()->user()->canAccessAdminArea(), 403);

        $akreditasi = $this->akreditasiService->findAkreditasi($uuid);
        if (! $akreditasi) abort(404);

        Gate::authorize('akreditasi.approve');

        try {
            $this->workflowService->openForReview($akreditasi->id, Auth::id());
            return back()->with('success', 'Pengajuan dibuka untuk verifikasi berkas.');
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function approveBerkas(Request $request, string $uuid)
    {
        abort_unless(auth()->user()->canAccessAdminArea(), 403);

        $akreditasi = $this->akreditasiService->findAkreditasi($uuid);
        if (! $akreditasi) abort(404);

        Gate::authorize('akreditasi.approve');

        $validated = $request->validate([
            'asesor1Id' => 'required|integer|exists:users,id',
            'asesor2Id' => 'required|integer|exists:users,id|different:asesor1Id',
        ], [
            'asesor1Id.required' => 'Ketua Kelompok wajib dipilih.',
            'asesor2Id.required' => 'Anggota Kelompok wajib dipilih.',
            'asesor2Id.different' => 'Ketua Kelompok dan Anggota Kelompok harus berbeda.',
        ]);

        try {
            $this->workflowService->approveBerkas(
                $akreditasi->id,
                Auth::id(),
                (int) $validated['asesor1Id'],
                (int) $validated['asesor2Id'],
                $akreditasi->updated_at->toISOString()
            );
            return back()->with('success', 'Berkas disetujui. Asesor telah ditugaskan.');
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\App\Exceptions\StaleStateException $e) {
            return back()->with('error', 'Akreditasi telah dimodifikasi oleh pengguna lain. Silakan muat ulang halaman.');
        }
    }

    public function rejectBerkas(Request $request, string $uuid)
    {
        abort_unless(auth()->user()->canAccessAdminArea(), 403);

        $akreditasi = $this->akreditasiService->findAkreditasi($uuid);
        if (! $akreditasi) abort(404);

        Gate::authorize('akreditasi.approve');

        $validated = $request->validate([
            'berkasRejectionSections' => 'required|array|min:1',
            'berkasRejectionCatatan' => 'required|string|min:10|max:2000',
        ], [
            'berkasRejectionSections.required' => 'Pilih minimal satu bagian yang ditolak.',
            'berkasRejectionCatatan.required' => 'Catatan penolakan wajib diisi.',
            'berkasRejectionCatatan.min' => 'Catatan minimal 10 karakter.',
        ]);

        try {
            $this->workflowService->rejectBerkas($akreditasi->id, Auth::id(), [
                'sections' => $validated['berkasRejectionSections'],
                'catatan' => $validated['berkasRejectionCatatan'],
            ]);

            return redirect()->route('admin.akreditasi')->with('success', 'Berkas ditolak.');
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function approve(Request $request, string $uuid)
    {
        abort_unless(auth()->user()->canAccessAdminArea(), 403);

        $akreditasi = $this->akreditasiService->findAkreditasi($uuid);
        if (! $akreditasi) abort(404);

        Gate::authorize('finalize', $akreditasi);

        if ((int) $akreditasi->status !== 1) {
            return back()->with('warning', 'Akreditasi tidak berada pada status Validasi Admin.');
        }

        $validated = $request->validate([
            'nomor_sk' => 'required|string|max:100',
            'sertifikat_file' => 'required|file|mimes:pdf|max:10240',
            'masa_berlaku' => 'required|date',
            'masa_berlaku_akhir' => 'required|date|after:masa_berlaku',
        ]);

        $sertifikatPath = null;

        try {
            $sertifikatPath = $request->file('sertifikat_file')->store('akreditasi/sertifikat', 'public');

            $this->workflowService->issueSK($akreditasi->id, Auth::id(), [
                'nomor_sk' => $validated['nomor_sk'],
                'masa_berlaku' => $validated['masa_berlaku'],
                'masa_berlaku_akhir' => $validated['masa_berlaku_akhir'],
                'sertifikat_path' => $sertifikatPath,
                'catatan_rekomendasi_admin' => $request->input('catatan_admin', ''),
            ], $akreditasi->updated_at->toISOString());

            return redirect()->route('admin.akreditasi')->with('success', 'SK Akreditasi berhasil diterbitkan.');
        } catch (\App\Exceptions\ConflictException $e) {
            $this->cleanupStoredCertificate($sertifikatPath);
            return back()->with('error', 'Akreditasi telah dimodifikasi oleh pengguna lain. Silakan muat ulang halaman.');
        } catch (\DomainException $e) {
            $this->cleanupStoredCertificate($sertifikatPath);
            return back()->with('error', $e->getMessage());
        } catch (\App\Exceptions\StaleStateException $e) {
            $this->cleanupStoredCertificate($sertifikatPath);
            return back()->with('error', 'Akreditasi telah dimodifikasi oleh pengguna lain. Silakan muat ulang halaman.');
        }
    }

    private function cleanupStoredCertificate(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    public function reject(Request $request, string $uuid)
    {
        abort_unless(auth()->user()->canAccessAdminArea(), 403);

        $akreditasi = $this->akreditasiService->findAkreditasi($uuid);
        if (! $akreditasi) abort(404);

        Gate::authorize('finalize', $akreditasi);

        if ((int) $akreditasi->status !== 1) {
            return back()->with('warning', 'Akreditasi tidak berada pada status Validasi Admin.');
        }

        $validated = $request->validate([
            'rejectionCategories' => 'required|array|min:1',
            'rejectionCategories.*.category' => 'required|string',
            'rejectionCategories.*.explanation' => 'required|string|min:10|max:2000',
        ], [
            'rejectionCategories.required' => 'Pilih minimal satu kategori penolakan.',
            'rejectionCategories.*.explanation.required' => 'Penjelasan wajib diisi untuk setiap kategori.',
            'rejectionCategories.*.explanation.min' => 'Penjelasan minimal 10 karakter.',
        ]);

        try {
            $reason = collect($validated['rejectionCategories'])
                ->map(fn($c) => ($c['category'] ?? '') . ': ' . ($c['explanation'] ?? ''))
                ->implode('; ');

            $this->workflowService->rejectAtValidasi(
                $akreditasi->id,
                Auth::id(),
                $reason,
                $akreditasi->updated_at->toISOString(),
                $validated['rejectionCategories']
            );

            return redirect()->route('admin.akreditasi')->with('success', 'Akreditasi telah ditolak.');
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\App\Exceptions\StaleStateException $e) {
            return back()->with('error', 'Akreditasi telah dimodifikasi oleh pengguna lain. Silakan muat ulang halaman.');
        }
    }

    public function toggleLock(Request $request, string $uuid)
    {
        abort_unless(auth()->user()->canAccessAdminArea(), 403);

        $akreditasi = $this->akreditasiService->findAkreditasi($uuid);
        if (! $akreditasi) abort(404);

        Gate::authorize('pesantren.lock');

        $pesantren = $this->pesantrenService->getProfile($akreditasi->user_id);
        if ($pesantren) {
            $prevLocked = $pesantren->is_locked;
            $pesantren->is_locked = ! $pesantren->is_locked;
            $pesantren->save();

            $status = $pesantren->is_locked ? 'terkunci' : 'terbuka';

            if ($prevLocked && ! $pesantren->is_locked) {
                $akreditasi->user->notify(new \App\Notifications\AkreditasiNotification(
                    'buka_kunci',
                    'Akses Data Dibuka',
                    'Administrator telah membuka kunci data Anda. Anda sekarang dapat memperbarui profil dan dokumen.',
                    route('pesantren.profile')
                ));
            }

            return back()->with('success', "Akses data pesantren berhasil diubah menjadi $status.");
        }

        return back()->with('error', 'Data pesantren tidak ditemukan.');
    }

    public function reassignAsesor(Request $request, string $uuid)
    {
        abort_unless(auth()->user()->canAccessAdminArea(), 403);

        $akreditasi = $this->akreditasiService->findAkreditasi($uuid);
        if (! $akreditasi) abort(404);

        Gate::authorize('asesor.assign');

        $validated = $request->validate([
            'reassignAsesorId' => 'required|integer|exists:asesors,id',
        ], [
            'reassignAsesorId.required' => 'Pilih asesor pengganti.',
            'reassignAsesorId.exists' => 'Asesor tidak ditemukan.',
        ]);

        $primaryAssessment = $akreditasi->assessments->firstWhere('tipe', 1)
            ?? $akreditasi->assessments->first();

        if (! $primaryAssessment) {
            return back()->with('error', 'Penugasan asesor tidak ditemukan.');
        }

        try {
            $this->deadlineService->reassignAsesor($primaryAssessment, (int) $validated['reassignAsesorId']);
            return back()->with('success', 'Asesor berhasil diganti. Deadline baru telah ditetapkan.');
        } catch (\DomainException $e) {
            return back()->with('error', 'Gagal mengganti asesor: ' . $e->getMessage());
        }
    }
}



