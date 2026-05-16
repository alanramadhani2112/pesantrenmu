<?php

namespace App\Services;

use App\Repositories\Contracts\AkreditasiRepositoryInterface;
use App\Services\RejectionService;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Models\Assessment;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Notifications\AkreditasiNotification;
use App\Models\User;

class AsesorService
{
    protected $akreditasiRepository;
    protected $asesorRepository;

    public function __construct(
        AkreditasiRepositoryInterface $akreditasiRepository,
        \App\Repositories\Contracts\AsesorRepositoryInterface $asesorRepository
    ) {
        $this->akreditasiRepository = $akreditasiRepository;
        $this->asesorRepository = $asesorRepository;
    }

    public function getProfile(int $userId): \App\Models\Asesor
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

    public function findAssessment(int $id): ?Assessment
    {
        return $this->akreditasiRepository->findAssessment($id, ['akreditasi.user.pesantren', 'akreditasi.catatans.user']);
    }

    public function findAkreditasiByUuid(string $uuid): ?\App\Models\Akreditasi
    {
        return $this->akreditasiRepository->findByUuid($uuid, ['user.pesantren', 'catatans.user', 'assessment1', 'assessment2']);
    }

    public function processVisitasi(int $akreditasiId, int $userId, array $data, string $action): bool
    {
        $isAssignedAsesor1 = Assessment::where('akreditasi_id', $akreditasiId)
            ->whereHas('asesor', fn($q) => $q->where('user_id', $userId))
            ->where('tipe', 1)
            ->exists();

        if (!$isAssignedAsesor1) {
            Log::warning('processVisitasi unauthorized asesor', [
                'user_id' => $userId,
                'akreditasi_id' => $akreditasiId,
            ]);
            return false;
        }

        $akreditasi = $this->akreditasiRepository->find($akreditasiId);
        if (!$akreditasi) return false;

        if ($action === 'tolak') {
            $rejectionService = app(RejectionService::class);
            $result = $rejectionService->createRejection(
                $akreditasiId,
                $userId,
                $data['rejected_items'],
                $data['catatan']
            );
            return $result['success'];
        }

        if ($action === 'accept_perbaikan') {
            $rejectionService = app(RejectionService::class);
            $result = $rejectionService->acceptPerbaikan($akreditasiId, $userId);
            return $result['success'];
        }

        return DB::transaction(function () use ($akreditasi, $akreditasiId, $userId, $data, $action) {
            $asesorUser = User::find($userId);
            $asesorName = $asesorUser->name;
            $admins = User::whereHas('role', function ($q) { $q->where('id', 1); })->get();

            if ($action === 'terima') {
                $this->akreditasiRepository->update($akreditasiId, [
                    'status' => 4, // Visitasi
                    'tgl_visitasi' => $data['tanggal'],
                    'tgl_visitasi_akhir' => $data['tanggal_akhir'],
                ]);

                if (!empty($data['catatan'])) {
                    $this->akreditasiRepository->addCatatan([
                        'akreditasi_id' => $akreditasiId,
                        'user_id' => $userId,
                        'tipe' => 'visitasi',
                        'catatan' => $data['catatan'],
                    ]);
                }

                $rangeStr = \Carbon\Carbon::parse($data['tanggal'])->format('d/m/Y');
                if ($data['tanggal'] != $data['tanggal_akhir']) {
                    $rangeStr .= ' s/d ' . \Carbon\Carbon::parse($data['tanggal_akhir'])->format('d/m/Y');
                }

                // Notify Pesantren
                $akreditasi->user->notify(new AkreditasiNotification(
                    'visitasi_diterima',
                    'Jadwal Visitasi Ditetapkan',
                    "Asesor $asesorName telah menjadwalkan visitasi pada tanggal $rangeStr.",
                    route('pesantren.akreditasi')
                ));

                // Notify Admin
                Notification::send($admins, new AkreditasiNotification(
                    'visitasi_diterima',
                    'Jadwal Visitasi Ditetapkan',
                    "Asesor $asesorName telah menetapkan jadwal visitasi untuk pesantren " . ($akreditasi->user?->pesantren?->nama_pesantren ?? $akreditasi->user->name) . " pada tanggal $rangeStr.",
                    route('admin.akreditasi')
                ));
            } else {
                $this->akreditasiRepository->update($akreditasiId, ['status' => 5]); // Kembali ke Assessment (penjadwalan)

                $this->akreditasiRepository->addCatatan([
                    'akreditasi_id' => $akreditasiId,
                    'user_id' => $userId,
                    'tipe' => 'visitasi',
                    'catatan' => $data['catatan'],
                    'perbaikan' => implode(', ', $data['perbaikan']),
                ]);

                // Notify Pesantren
                $akreditasi->user->notify(new AkreditasiNotification(
                    'visitasi_ditolak',
                    'Pengajuan Visitasi Ditolak',
                    "Asesor $asesorName menolak jadwal visitasi dengan catatan: " . $data['catatan'] . ". Silahkan periksa catatan perbaikan.",
                    route('pesantren.akreditasi')
                ));

                // Notify Admin
                Notification::send($admins, new AkreditasiNotification(
                    'visitasi_ditolak',
                    'Visitasi Ditolak Asesor',
                    "Asesor $asesorName menolak visitasi untuk pesantren " . ($akreditasi->user?->pesantren?->nama_pesantren ?? $akreditasi->user->name) . ".",
                    route('admin.akreditasi')
                ));
            }

            return true;
        });
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

    public function saveAsesorEdpm(int $akreditasiId, int $asesorId, int $asesorTipe, int $pesantrenId, array $data): bool
    {
        foreach ($data['asesorEvaluasis'] as $butirId => $isian) {
            if (empty($isian)) continue;

            $saveData = [
                'pesantren_id' => $pesantrenId,
                'isian' => $isian,
                'catatan' => $data['asesorButirCatatans'][$butirId] ?? null
            ];
            if ($asesorTipe == 1) {
                $saveData['nk'] = !empty($data['asesorNks'][$butirId]) ? $data['asesorNks'][$butirId] : null;
            }
            $this->akreditasiRepository->saveEdpmEvaluation(
                ['akreditasi_id' => $akreditasiId, 'butir_id' => $butirId, 'asesor_id' => $asesorId],
                $saveData
            );
        }

        foreach ($data['asesorCatatans'] as $komponenId => $catatan) {
            $saveData = ['pesantren_id' => $pesantrenId, 'catatan' => $catatan];
            if ($asesorTipe == 1) {
                $saveData['nk'] = !empty($data['asesorCatatanNks'][$komponenId]) ? $data['asesorCatatanNks'][$komponenId] : null;
            }
            $this->akreditasiRepository->saveEdpmCatatan(
                ['akreditasi_id' => $akreditasiId, 'komponen_id' => $komponenId, 'asesor_id' => $asesorId],
                $saveData
            );
        }

        $this->notifyEdpmInput($akreditasiId, $asesorTipe);

        return true;
    }

    protected function notifyEdpmInput(int $akreditasiId, int $asesorTipe): void
    {
        $akreditasi = $this->akreditasiRepository->find($akreditasiId);
        $user = auth()->user();
        $admins = User::whereHas('role', function ($q) { $q->where('id', 1); })->get();
        $pesantrenName = $akreditasi->user?->pesantren?->nama_pesantren ?? $akreditasi->user?->name;

        if ($asesorTipe == 1) {
            $message = 'Asesor 1 (' . $user->name . ') telah mengisi draf nilai NA untuk ' . $pesantrenName;
            Notification::send($admins, new AkreditasiNotification('na1_diisi', 'Nilai NA 1 diisi', $message, route('admin.akreditasi-detail', $akreditasi->uuid)));

            $assessor2 = $akreditasi->assessment2;
            if ($assessor2 && $assessor2->asesor && $assessor2->asesor->user) {
                $assessor2->asesor->user->notify(new AkreditasiNotification('na1_diisi', 'Nilai NA 1 diisi', $message, route('asesor.akreditasi-detail', $akreditasi->uuid)));
            }
        } elseif ($asesorTipe == 2) {
            $message = 'Asesor 2 (' . $user->name . ') telah mengisi nilai NA untuk ' . $pesantrenName;
            Notification::send($admins, new AkreditasiNotification('na2_diisi', 'Nilai NA 2 diisi', $message, route('admin.akreditasi-detail', $akreditasi->uuid)));

            $assessor1 = $akreditasi->assessment1;
            if ($assessor1 && $assessor1->asesor && $assessor1->asesor->user) {
                $assessor1->asesor->user->notify(new AkreditasiNotification('na2_diisi', 'Nilai NA 2 diisi', $message, route('asesor.akreditasi-detail', $akreditasi->uuid)));
            }
        }
    }

    public function finalizeVerification(int $akreditasiId, int $userId): bool
    {
        // Precondition 1: Hanya asesor 1 (ketua) yang ditugaskan boleh finalisasi
        $asesor1Assessment = Assessment::where('akreditasi_id', $akreditasiId)
            ->where('tipe', 1)
            ->whereHas('asesor', fn($q) => $q->where('user_id', $userId))
            ->first();

        if (!$asesor1Assessment) {
            Log::warning('finalizeVerification unauthorized asesor', [
                'user_id' => $userId,
                'akreditasi_id' => $akreditasiId,
            ]);
            return false;
        }

        $akreditasi = $this->akreditasiRepository->find($akreditasiId);
        if (!$akreditasi) return false;

        // Precondition 2: Kelengkapan butir di server (defense-in-depth)
        $totalButir = \App\Models\MasterEdpmButir::count();

        $asesor2Assessment = Assessment::where('akreditasi_id', $akreditasiId)
            ->where('tipe', 2)
            ->first();

        // Asesor 1: NA (isian) dan NK harus terisi untuk semua butir
        $asesor1FilledNa = \App\Models\AkreditasiEdpm::where('akreditasi_id', $akreditasiId)
            ->where('asesor_id', $asesor1Assessment->asesor_id)
            ->whereNotNull('isian')
            ->count();
        $asesor1FilledNk = \App\Models\AkreditasiEdpm::where('akreditasi_id', $akreditasiId)
            ->where('asesor_id', $asesor1Assessment->asesor_id)
            ->whereNotNull('nk')
            ->count();

        if ($asesor1FilledNa < $totalButir || $asesor1FilledNk < $totalButir) {
            Log::warning('finalizeVerification incomplete asesor1 data', [
                'akreditasi_id' => $akreditasiId,
                'total_butir' => $totalButir,
                'asesor1_filled_na' => $asesor1FilledNa,
                'asesor1_filled_nk' => $asesor1FilledNk,
            ]);
            return false;
        }

        // Asesor 2 (bila ditugaskan): NA (isian) harus terisi untuk semua butir
        if ($asesor2Assessment) {
            $asesor2FilledNa = \App\Models\AkreditasiEdpm::where('akreditasi_id', $akreditasiId)
                ->where('asesor_id', $asesor2Assessment->asesor_id)
                ->whereNotNull('isian')
                ->count();

            if ($asesor2FilledNa < $totalButir) {
                Log::warning('finalizeVerification incomplete asesor2 data', [
                    'akreditasi_id' => $akreditasiId,
                    'total_butir' => $totalButir,
                    'asesor2_filled_na' => $asesor2FilledNa,
                ]);
                return false;
            }
        }

        return DB::transaction(function () use ($akreditasi, $userId) {
            $akreditasi->update(['status' => 3]); // Validasi Central

            $user = User::find($userId);
            $admins = User::whereHas('role', function ($q) { $q->where('id', 1); })->get();
            $pesantrenName = $akreditasi->user?->pesantren?->nama_pesantren ?? $akreditasi->user?->name;

            // Notify Admin
            Notification::send($admins, new AkreditasiNotification(
                'assessment_selesai',
                'Pemberitahuan: Assessment Selesai',
                "Assessment untuk $pesantrenName telah diselesaikan oleh $user->name. Menunggu unggahan Laporan Visitasi.",
                route('admin.akreditasi-detail', $akreditasi->uuid)
            ));

            // Notify Assessors
            $asesors = collect();
            if ($akreditasi->assessment1?->asesor?->user) $asesors->push($akreditasi->assessment1->asesor->user);
            if ($akreditasi->assessment2?->asesor?->user) $asesors->push($akreditasi->assessment2->asesor->user);

            Notification::send($asesors->unique('id'), new AkreditasiNotification(
                'input_laporan',
                'Instruksi: Unggah Laporan Visitasi',
                "Assessment untuk $pesantrenName telah diverifikasi. Silakan ketua/anggota segera unggah Laporan Visitasi di tab yang tersedia.",
                route('asesor.akreditasi-detail', $akreditasi->uuid)
            ));

            // Notify Pesantren
            $akreditasi->user->notify(new AkreditasiNotification(
                'validasi',
                'Update Status: Validasi',
                'Assessment telah selesai. Silakan unduh Kartu Kendali di menu dokumen, kemudian unggah kembali di menu akreditasi untuk melanjutkan proses validasi.',
                route('pesantren.akreditasi')
            ));

            return true;
        });
    }

    public function getAkreditasiDetailAsesor(string $uuid, int $userId): array
    {
        $akreditasi = $this->findAkreditasiByUuid($uuid);
        if (!$akreditasi) return [];

        $user = User::with('asesor')->find($userId);
        if (!$user->asesor) return [];

        $currentAssessment = $akreditasi->assessments->where('asesor_id', $user->asesor->id)->first();
        if (!$currentAssessment) return [];

        $asesorTipe = $currentAssessment->tipe;
        $pesantrenUserId = $akreditasi->user_id;

        $pesantren = \App\Models\Pesantren::with('units')->where('user_id', $pesantrenUserId)->first();
        $ipm = \App\Models\Ipm::where('user_id', $pesantrenUserId)->first();
        $sdm = \App\Models\SdmPesantren::where('user_id', $pesantrenUserId)->get()->keyBy('tingkat');
        $komponens = \App\Models\MasterEdpmKomponen::with('butirs')->orderByRaw('COALESCE(ipr, 0) ASC')->orderBy('id', 'ASC')->get();
        $visitasiTemplate = \App\Models\Document::where('type', 'visitasi')->where('status', 1)->first();

        // Pesantren EDPM
        $pEdpms = \App\Models\Edpm::where('user_id', $pesantrenUserId)->get();
        $pEvaluasis = $pEdpms->pluck('isian', 'butir_id');
        $pLinks = $pEdpms->pluck('link', 'butir_id');
        $pCatatans = \App\Models\EdpmCatatan::where('user_id', $pesantrenUserId)->get()->pluck('catatan', 'komponen_id');

        // Assessor EDPM Data
        $evaluationData = $this->getEdpmEvaluationData($akreditasi->id, $user->asesor->id, $asesorTipe);

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
        ];
    }

    public function uploadLaporanVisitasi(int $akreditasiId, int $asesorTipe, string $filePath): bool
    {
        $akreditasi = \App\Models\Akreditasi::find($akreditasiId);
        if (!$akreditasi) return false;

        $field = $asesorTipe == 1 ? 'laporan_visitasi_file' : 'laporan_visitasi_file_2';
        return $akreditasi->update([$field => $filePath]);
    }
}
