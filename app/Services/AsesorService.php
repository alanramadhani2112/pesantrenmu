<?php

namespace App\Services;

use App\Models\Akreditasi;
use App\Models\Asesor;
use App\Models\Assessment;
use App\Models\Document;
use App\Models\Edpm;
use App\Models\EdpmCatatan;
use App\Models\Ipm;
use App\Models\MasterEdpmKomponen;
use App\Models\Pesantren;
use App\Models\SdmPesantren;
use App\Models\User;
use App\Notifications\AkreditasiNotification;
use App\Repositories\Contracts\AkreditasiRepositoryInterface;
use App\Repositories\Contracts\AsesorRepositoryInterface;
use App\StateMachine\AkreditasiStateMachine;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Notification;

class AsesorService
{
    protected $akreditasiRepository;

    protected $asesorRepository;

    protected ProgressTracker $progressTracker;

    public function __construct(
        AkreditasiRepositoryInterface $akreditasiRepository,
        AsesorRepositoryInterface $asesorRepository,
        ProgressTracker $progressTracker
    ) {
        $this->akreditasiRepository = $akreditasiRepository;
        $this->asesorRepository = $asesorRepository;
        $this->progressTracker = $progressTracker;
    }

    public function getProfile(int $userId): Asesor
    {
        return $this->asesorRepository->firstOrCreate(
            ['user_id' => $userId],
            [
                'nama_dengan_gelar' => User::find($userId)->name,
                'nama_tanpa_gelar' => User::find($userId)->name,
            ]
        );
    }

    public function updateProfile(int $userId, array $data): bool
    {
        return $this->asesorRepository->updateByUserId($userId, $data);
    }

    public function getPaginatedAsesors(array $filters = [], int $perPage = 10, string $sortField = 'name', bool $sortAsc = true): LengthAwarePaginator
    {
        return $this->asesorRepository->getPaginatedAsesors($filters, $perPage, $sortField, $sortAsc);
    }

    public function toggleStatus(int $userId): bool
    {
        return $this->asesorRepository->toggleStatus($userId);
    }

    public function findAsesor(string $uuid): ?User
    {
        return $this->asesorRepository->findByUuid($uuid);
    }

    public function getPaginatedAssessments(int $asesorId, ?string $search = null, ?string $periodeFilter = null, ?string $statusFilter = null, int $perPage = 10, string $sortField = 'id', bool $sortAsc = false): LengthAwarePaginator
    {
        return $this->akreditasiRepository->getAssessmentsByAsesor($asesorId, $search, $periodeFilter, $statusFilter, $perPage, $sortField, $sortAsc);
    }

    public function getAssessmentSummary(int $asesorId, ?string $search = null, ?string $periodeFilter = null, ?string $statusFilter = null): array
    {
        return $this->akreditasiRepository->getAssessmentSummaryByAsesor($asesorId, $search, $periodeFilter, $statusFilter);
    }
    public function findAssessment(int $id): ?Assessment
    {
        return $this->akreditasiRepository->findAssessment($id, ['akreditasi.user.pesantren', 'akreditasi.catatans.user']);
    }

    public function findAkreditasiByUuid(string $uuid): ?Akreditasi
    {
        return $this->akreditasiRepository->findByUuid($uuid, ['user.pesantren', 'catatans.user', 'assessment1', 'assessment2']);
    }

    public function getEdpmEvaluationData(int $akreditasiId, int $asesorId, int $asesorTipe): array
    {
        $evaluationData = [
            'asesorEvaluasis' => [],
            'asesorNks' => [],
            'asesorButirCatatans' => [],
            'asesorCatatans' => [],
            'asesorCatatanNks' => [],
            'otherAsesorEvaluasis' => [],
            'otherAsesorButirCatatans' => [],
            'otherAsesorCatatans' => [],
        ];

        // Load current assessor's evaluations
        $aEdpms = $this->akreditasiRepository->getEdpmData($akreditasiId, $asesorId);
        $evaluationData['asesorEvaluasis'] = $aEdpms->pluck('isian', 'butir_id')->toArray();
        $evaluationData['asesorNks'] = $aEdpms->pluck('nk', 'butir_id')->toArray();
        $evaluationData['asesorButirCatatans'] = $aEdpms->pluck('catatan', 'butir_id')->toArray();

        $aCatatansModels = $this->akreditasiRepository->getEdpmCatatans($akreditasiId, $asesorId);
        $evaluationData['asesorCatatans'] = $aCatatansModels->pluck('catatan', 'komponen_id')->toArray();
        $evaluationData['asesorCatatanNks'] = $aCatatansModels->pluck('nk', 'komponen_id')->toArray();

        // Load the other assessor's data if current is Asesor 1
        if ($asesorTipe == 1) {
            $akreditasi = $this->akreditasiRepository->find($akreditasiId);
            $otherAssessment = $akreditasi->assessments->where('tipe', 2)->first();
            if ($otherAssessment) {
                $oEdpms = $this->akreditasiRepository->getEdpmData($akreditasiId, $otherAssessment->asesor_id);
                $evaluationData['otherAsesorEvaluasis'] = $oEdpms->pluck('isian', 'butir_id')->toArray();
                $evaluationData['otherAsesorButirCatatans'] = $oEdpms->pluck('catatan', 'butir_id')->toArray();
                $evaluationData['otherAsesorCatatans'] = $this->akreditasiRepository->getEdpmCatatans($akreditasiId, $otherAssessment->asesor_id)->pluck('catatan', 'komponen_id')->toArray();
            }
        }

        return $evaluationData;
    }

    public function saveAsesorEdpm(int $akreditasiId, int $asesorId, int $asesorTipe, int $pesantrenId, array $data, bool $isFinal = false): bool
    {
        $akreditasi = $this->akreditasiRepository->find($akreditasiId);
        if (! $akreditasi) {
            throw new \DomainException("Akreditasi #{$akreditasiId} tidak ditemukan.");
        }

        if ((int) $akreditasi->status !== AkreditasiStateMachine::STATUS_PASCA_VISITASI) {
            throw new \DomainException('Nilai asesor hanya dapat diisi setelah visitasi dikonfirmasi selesai.');
        }

        if ($asesorTipe == 1 && $this->containsNilaiKelompok($data['asesorNks'] ?? [])) {
            $this->assertNilaiKelompokInputIsOpen($akreditasiId);
        }

        foreach ($data['asesorEvaluasis'] as $butirId => $isian) {
            if (empty($isian)) {
                continue;
            }

            $saveData = [
                'pesantren_id' => $pesantrenId,
                'isian' => $isian,
                'catatan' => $data['asesorButirCatatans'][$butirId] ?? null,
                'is_final' => $isFinal,
            ];
            if ($asesorTipe == 1) {
                $saveData['nk'] = ! empty($data['asesorNks'][$butirId]) ? $data['asesorNks'][$butirId] : null;
            }
            $this->akreditasiRepository->saveEdpmEvaluation(
                ['akreditasi_id' => $akreditasiId, 'butir_id' => $butirId, 'asesor_id' => $asesorId],
                $saveData
            );
        }

        foreach ($data['asesorCatatans'] as $komponenId => $catatan) {
            $saveData = ['pesantren_id' => $pesantrenId, 'catatan' => $catatan];
            if ($asesorTipe == 1) {
                $saveData['nk'] = ! empty($data['asesorCatatanNks'][$komponenId]) ? $data['asesorCatatanNks'][$komponenId] : null;
            }
            $this->akreditasiRepository->saveEdpmCatatan(
                ['akreditasi_id' => $akreditasiId, 'komponen_id' => $komponenId, 'asesor_id' => $asesorId],
                $saveData
            );
        }

        $this->notifyEdpmInput($akreditasiId, $asesorTipe);

        return true;
    }

    private function containsNilaiKelompok(array $nilaiKelompok): bool
    {
        foreach ($nilaiKelompok as $value) {
            if ($value !== null && $value !== '') {
                return true;
            }
        }

        return false;
    }

    private function assertNilaiKelompokInputIsOpen(int $akreditasiId): void
    {
        $ketua = Assessment::where('akreditasi_id', $akreditasiId)
            ->where('tipe', 1)
            ->with('asesor')
            ->first();

        $anggota = Assessment::where('akreditasi_id', $akreditasiId)
            ->where('tipe', 2)
            ->with('asesor')
            ->first();

        if (! $ketua?->asesor?->user_id || ! $anggota?->asesor?->user_id) {
            throw new \DomainException('Nilai Kelompok belum dapat diisi karena pasangan Ketua dan Anggota Kelompok belum lengkap.');
        }

        $scoringService = app(AssessorScoringService::class);

        if (! $scoringService->allNA1Final($akreditasiId, $ketua->asesor->user_id)
            || ! $scoringService->allNA2Final($akreditasiId, $anggota->asesor->user_id)) {
            throw new \DomainException('Nilai Kelompok baru dapat diisi setelah Nilai Ketua dan Nilai Anggota disubmit final seluruhnya.');
        }
    }

    protected function notifyEdpmInput(int $akreditasiId, int $asesorTipe): void
    {
        $akreditasi = $this->akreditasiRepository->find($akreditasiId);
        $user = auth()->user();
        $admins = User::whereHas('role', function ($q) {
            $q->where('id', 1);
        })->get();
        $pesantrenName = $akreditasi->user?->pesantren?->nama_pesantren ?? $akreditasi->user?->name;

        if ($asesorTipe == 1) {
            $message = 'Ketua Kelompok ('.$user->name.') telah mengisi draf Nilai Ketua untuk '.$pesantrenName;
            Notification::send($admins, new AkreditasiNotification('na1_diisi', 'Nilai Ketua diisi', $message, route('admin.akreditasi-detail', $akreditasi->uuid)));

            $assessor2 = $akreditasi->assessment2;
            if ($assessor2 && $assessor2->asesor && $assessor2->asesor->user) {
                $assessor2->asesor->user->notify(new AkreditasiNotification('na1_diisi', 'Nilai Ketua diisi', $message, route('asesor.akreditasi-detail', $akreditasi->uuid)));
            }
        } elseif ($asesorTipe == 2) {
            $message = 'Anggota Kelompok ('.$user->name.') telah mengisi Nilai Anggota untuk '.$pesantrenName;
            Notification::send($admins, new AkreditasiNotification('na2_diisi', 'Nilai Anggota diisi', $message, route('admin.akreditasi-detail', $akreditasi->uuid)));

            $assessor1 = $akreditasi->assessment1;
            if ($assessor1 && $assessor1->asesor && $assessor1->asesor->user) {
                $assessor1->asesor->user->notify(new AkreditasiNotification('na2_diisi', 'Nilai Anggota diisi', $message, route('asesor.akreditasi-detail', $akreditasi->uuid)));
            }
        }
    }

    public function getAkreditasiDetailAsesor(string $uuid, int $userId): array
    {
        $akreditasi = $this->findAkreditasiByUuid($uuid);
        if (! $akreditasi) {
            return [];
        }

        $user = User::with('asesor')->find($userId);
        if (! $user->asesor) {
            return [];
        }

        $currentAssessment = $akreditasi->assessments->where('asesor_id', $user->asesor->id)->first();
        if (! $currentAssessment) {
            return [];
        }

        $asesorTipe = $currentAssessment->tipe;
        $pesantrenUserId = $akreditasi->user_id;

        $pesantren = Pesantren::with('units')->where('user_id', $pesantrenUserId)->first();
        $ipm = Ipm::where('user_id', $pesantrenUserId)->first();
        $sdm = SdmPesantren::where('user_id', $pesantrenUserId)->get()->keyBy('tingkat');
        $komponens = MasterEdpmKomponen::with('butirs')->orderByRaw('COALESCE(ipr, 0) ASC')->orderBy('id', 'ASC')->get();
        $visitasiTemplate = Document::where('type', 'visitasi')->where('status', 1)->first();

        // Pesantren EDPM
        $pEdpms = Edpm::where('user_id', $pesantrenUserId)->get();
        $pEvaluasis = $pEdpms->pluck('isian', 'butir_id');
        $pLinks = $pEdpms->pluck('link', 'butir_id');
        $pCatatans = EdpmCatatan::where('user_id', $pesantrenUserId)->get()->pluck('catatan', 'komponen_id');

        // Assessor EDPM Data
        $evaluationData = $this->getEdpmEvaluationData($akreditasi->id, $user->asesor->id, $asesorTipe);

        $progress = [];
        if ((int) $akreditasi->status === AkreditasiStateMachine::STATUS_PASCA_VISITASI) {
            $progress = $this->progressTracker->getAkreditasiProgress($akreditasi->id);
        }

        return [
            'akreditasi' => $akreditasi,
            'asesorTipe' => $asesorTipe,
            'pesantren' => $pesantren,
            'ipm' => $ipm,
            'sdm' => $sdm,
            'komponens' => $komponens,
            'visitasiTemplate' => $visitasiTemplate,
            'pesantren_edpm' => [
                'evaluasis' => $pEvaluasis,
                'links' => $pLinks,
                'catatans' => $pCatatans,
            ],
            'evaluation' => $evaluationData,
            'progress' => $progress,
        ];
    }
}


