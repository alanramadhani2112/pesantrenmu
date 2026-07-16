<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\ConflictException;
use App\Exceptions\ImmutableValueException;
use App\Exceptions\StaleStateException;
use App\Http\Controllers\Controller;
use App\Models\AkreditasiEdpm;
use App\Models\Asesor;
use App\Models\Ipm;
use App\Notifications\AkreditasiNotification;
use App\Services\AkreditasiService;
use App\Services\AkreditasiWorkflowService;
use App\Services\AssessorScoringService;
use App\Services\AuditTrailService;
use App\Services\DeadlineService;
use App\Services\PesantrenService;
use App\Services\ProgressTracker;
use App\Services\RejectionService;
use App\Services\ScoreCalculationService;
use App\StateMachine\AkreditasiStateMachine;
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
        $pesantren = $akreditasi->user?->pesantren;
        $pesantren?->loadMissing('units');
        $isLocked = (bool) ($pesantren?->is_locked ?? false);
        $ipm = Ipm::where('user_id', $userId)->first();
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

        if ($akreditasi->status == AkreditasiStateMachine::STATUS_PASCA_VISITASI) {
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
                'name' => $asesor->nama_tanpa_gelar ?? ($asesor->user?->name ?? 'Asesor #'.$asesor->user_id),
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

        $showFinalDecision = (int) $akreditasi->status === AkreditasiStateMachine::STATUS_VALIDASI_ADMIN
            && (bool) $akreditasi->is_nv_final;

        // Compute scoring progress cards (replaces adminScoringProgressCards())
        $scoringProgressCards = $this->buildScoringProgressCards(
            $asesor1NaProgress, $asesor1NkProgress, $asesor2NaProgress
        );
        $scoringBlockers = array_values(array_map(
            fn (array $card): string => 'Menunggu '.$card['label'],
            array_filter(
                $scoringProgressCards,
                fn (array $card): bool => (float) ($card['progress']['percentage'] ?? 0) < 100.0
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
            'isLocked', 'fields', 'sdmTotals',
            'scoringProgressCards', 'scoringBlockers', 'scoreSummary', 'showFinalDecision',
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
        $ikScores = [];
        $totalSkorIpr = 0.0;
        $pendingCount = 0;
        $iprRowCount = 0;

        foreach ($komponens ?? [] as $komponen) {
            $isIpr = ! is_null($komponen->ipr);
            $butirs = $komponen->butirs ?? collect();
            $nvValues = [];
            $requiredCount = 0;

            foreach ($butirs as $butir) {
                $raw = $adminNvs[$butir->id] ?? null;
                $nkValue = is_array($raw) ? ($raw['nk'] ?? null) : null;
                $nvValue = is_array($raw) ? ($raw['nv'] ?? null) : $raw;

                if (blank($nkValue)) {
                    continue;
                }

                $requiredCount++;
                if (blank($nvValue)) {
                    $pendingCount++;

                    continue;
                }

                $nvValues[] = (int) $nvValue;
            }

            $complete = $requiredCount > 0 && count($nvValues) === $requiredCount;
            $bobot = $isIpr ? 100 : (ScoreCalculationService::KOMPONEN_CONFIG[$komponen->nama]['bobot'] ?? 0);
            $factor = $isIpr ? 100 : $bobot;
            $score = 0.0;

            if ($complete) {
                $score = $isIpr
                    ? $this->scoreCalculationService->calculateSkorIPR($nvValues)
                    : (array_key_exists($komponen->nama, ScoreCalculationService::KOMPONEN_CONFIG)
                        ? $this->scoreCalculationService->calculateSkorKomponen($nvValues, $komponen->nama)
                        : round((array_sum($nvValues) / (max(1, count($butirs)) * ScoreCalculationService::SCORE_MAX)) * $factor, 2));
            }

            if ($isIpr) {
                $totalSkorIpr += $score;
                $iprRowCount++;
            } else {
                $ikScores[] = $score;
            }

            $rows[] = [
                'name' => $komponen->nama,
                'nama' => $komponen->nama,
                'cmaks' => $isIpr ? ScoreCalculationService::IPR_CMAKS : ((ScoreCalculationService::KOMPONEN_CONFIG[$komponen->nama]['butir_count'] ?? count($butirs)) * ScoreCalculationService::SCORE_MAX),
                'ci' => array_sum($nvValues),
                'bk' => $bobot,
                'factor' => $factor,
                'score' => $complete ? $score : '-',
                'complete' => $complete,
                'is_ipr' => $isIpr,
                'total_score' => null,
                'total_rowspan' => null,
            ];
        }

        $totalSkorIk = $this->scoreCalculationService->calculateTotalSkorIK($ikScores);
        $totalIpr = round($totalSkorIpr, 2);
        $isPending = $pendingCount > 0;
        $finalScore = $isPending ? 0.0 : $this->scoreCalculationService->calculateNilaiAkhir($totalSkorIk, $totalIpr);
        $peringkat = $isPending ? 'Pending' : $this->scoreCalculationService->determinePeringkat($finalScore);

        return [
            'isIpr' => $iprRowCount > 0,
            'isPending' => $isPending,
            'pendingCount' => $pendingCount,
            'iprScore' => $isPending ? '-' : $totalIpr,
            'komponenDetails' => array_values(array_filter($rows, fn (array $row): bool => ! $row['is_ipr'])),
            'finalScore' => $finalScore,
            'predicate' => $peringkat,
            'rows' => $rows,
            'result' => [
                'nilai_akreditasi' => $peringkat,
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
        if (! $akreditasi) {
            abort(404);
        }

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
        if (! $akreditasi) {
            abort(404);
        }

        Gate::authorize('akreditasi.approve');

        if ((int) $akreditasi->status !== 1) {
            return back()->withInput()->with('error', 'Data tidak dapat diubah karena status bukan Validasi Admin.');
        }

        if (empty($akreditasi->laporan_visitasi_asesor1)) {
            return back()->withInput()->with('error', 'Nilai NV belum dapat disimpan karena Laporan Visitasi Ketua Kelompok belum diunggah.');
        }

        $request->validate([
            'adminNvs' => 'required|array',
            'nvReasons' => 'nullable|array',
            'nvReasons.*' => 'nullable|string|max:2000',
        ]);

        $adminId = Auth::id();
        $errors = [];
        $reasons = $request->input('nvReasons', []);

        foreach ($request->input('adminNvs', []) as $butirId => $nvValue) {
            if (! empty($nvValue) && is_numeric($nvValue) && $nvValue >= 1 && $nvValue <= 4) {
                $reason = isset($reasons[$butirId]) && is_string($reasons[$butirId]) ? trim($reasons[$butirId]) : null;
                if ($reason === '') {
                    $reason = null;
                }

                try {
                    $this->scoringService->saveNV($akreditasi->id, $adminId, (int) $butirId, (int) $nvValue, false, $reason);
                } catch (\Throwable $e) {
                    $errors[] = "Butir #{$butirId}: ".$e->getMessage();
                }
            }
        }

        if (! empty($errors)) {
            return back()->withInput()->with('warning', implode('; ', array_slice($errors, 0, 3)));
        }

        return back()->withInput()->with('success', 'Nilai Verifikasi berhasil disimpan.');
    }

    public function finalizeAllNv(Request $request, string $uuid)
    {
        abort_unless(auth()->user()->canAccessAdminArea(), 403);

        $akreditasi = $this->akreditasiService->findAkreditasi($uuid);
        if (! $akreditasi) {
            abort(404);
        }

        Gate::authorize('akreditasi.approve');

        if ((int) $akreditasi->status !== 1) {
            return back()->withInput()->with('error', 'Finalisasi NV hanya dapat dilakukan saat status Validasi Admin.');
        }

        if (empty($akreditasi->laporan_visitasi_asesor1)) {
            return back()->withInput()->with('error', 'NV belum dapat difinalisasi karena Laporan Visitasi Ketua Kelompok belum diunggah.');
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
                        } catch (ImmutableValueException $e) {
                            $finalizedCount++;
                        }
                    }
                }
            });
        } catch (\DomainException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        if ($finalizedCount === 0) {
            return back()->withInput()->with('error', 'Tidak ada NV valid yang dikirim untuk difinalisasi.');
        }

        $requiredNvQuery = AkreditasiEdpm::where('akreditasi_id', $akreditasi->id)
            ->whereNotNull('nk');

        $expectedFinalCount = (clone $requiredNvQuery)->count();

        $actualFinalCount = (clone $requiredNvQuery)
            ->whereNotNull('nv')
            ->where('is_final', true)
            ->count();

        if ($actualFinalCount < $expectedFinalCount) {
            return back()->withInput()->with('error', "Finalisasi NV belum lengkap ({$actualFinalCount}/{$expectedFinalCount} butir). Lengkapi alasan perubahan NV dan semua nilai final terlebih dahulu.");
        }

        $akreditasi->update(['is_nv_final' => true]);

        return back()->with('success', "Semua NV ({$finalizedCount} butir) berhasil difinalisasi dan dikunci.");
    }

    public function openForReview(Request $request, string $uuid)
    {
        abort_unless(auth()->user()->canAccessAdminArea(), 403);

        $akreditasi = $this->akreditasiService->findAkreditasi($uuid);
        if (! $akreditasi) {
            abort(404);
        }

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
        if (! $akreditasi) {
            abort(404);
        }

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
        } catch (StaleStateException $e) {
            return back()->with('error', 'Akreditasi telah dimodifikasi oleh pengguna lain. Silakan muat ulang halaman.');
        }
    }

    public function rejectBerkas(Request $request, string $uuid)
    {
        abort_unless(auth()->user()->canAccessAdminArea(), 403);

        $akreditasi = $this->akreditasiService->findAkreditasi($uuid);
        if (! $akreditasi) {
            abort(404);
        }

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
        if (! $akreditasi) {
            abort(404);
        }

        Gate::authorize('finalize', $akreditasi);

        if ((int) $akreditasi->status !== 1) {
            return back()->withInput($request->except('sertifikat_file'))->with('warning', 'Akreditasi tidak berada pada status Validasi Admin.');
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
        } catch (ConflictException $e) {
            $this->cleanupStoredCertificate($sertifikatPath);

            return back()->withInput($request->except('sertifikat_file'))->with('error', 'Akreditasi telah dimodifikasi oleh pengguna lain. Silakan muat ulang halaman.');
        } catch (\DomainException $e) {
            $this->cleanupStoredCertificate($sertifikatPath);

            return back()->withInput($request->except('sertifikat_file'))->with('error', $e->getMessage());
        } catch (StaleStateException $e) {
            $this->cleanupStoredCertificate($sertifikatPath);

            return back()->withInput($request->except('sertifikat_file'))->with('error', 'Akreditasi telah dimodifikasi oleh pengguna lain. Silakan muat ulang halaman.');
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
        if (! $akreditasi) {
            abort(404);
        }

        Gate::authorize('finalize', $akreditasi);

        if ((int) $akreditasi->status !== 1) {
            return back()->withInput()->with('warning', 'Akreditasi tidak berada pada status Validasi Admin.');
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
                ->map(fn ($c) => ($c['category'] ?? '').': '.($c['explanation'] ?? ''))
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
            return back()->withInput()->with('error', $e->getMessage());
        } catch (StaleStateException $e) {
            return back()->withInput()->with('error', 'Akreditasi telah dimodifikasi oleh pengguna lain. Silakan muat ulang halaman.');
        }
    }

    public function toggleLock(Request $request, string $uuid)
    {
        abort_unless(auth()->user()->canAccessAdminArea(), 403);

        $akreditasi = $this->akreditasiService->findAkreditasi($uuid);
        if (! $akreditasi) {
            abort(404);
        }

        Gate::authorize('pesantren.lock');

        $akreditasi->loadMissing('user.pesantren');
        $pesantren = $akreditasi->user?->pesantren;
        if ($pesantren) {
            $prevLocked = $pesantren->is_locked;
            $pesantren->is_locked = ! $pesantren->is_locked;
            $pesantren->save();

            $status = $pesantren->is_locked ? 'terkunci' : 'terbuka';

            if ($prevLocked && ! $pesantren->is_locked) {
                $akreditasi->user->notify(new AkreditasiNotification(
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
        if (! $akreditasi) {
            abort(404);
        }

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
            return back()->with('error', 'Gagal mengganti asesor: '.$e->getMessage());
        }
    }
}
