<?php

namespace App\Services;

use App\Repositories\Contracts\PesantrenRepositoryInterface;
use App\Models\User;
use App\Models\Pesantren;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class PesantrenService
{
    protected $pesantrenRepository;
    protected $akreditasiRepository;
    protected $edpmRepository;
    protected $ipmRepository;
    protected $sdmRepository;

    public function __construct(
        PesantrenRepositoryInterface $pesantrenRepository,
        \App\Repositories\Contracts\AkreditasiRepositoryInterface $akreditasiRepository,
        \App\Repositories\Contracts\EdpmRepositoryInterface $edpmRepository,
        \App\Repositories\Contracts\IpmRepositoryInterface $ipmRepository,
        \App\Repositories\Contracts\SdmRepositoryInterface $sdmRepository
    ) {
        $this->pesantrenRepository = $pesantrenRepository;
        $this->akreditasiRepository = $akreditasiRepository;
        $this->edpmRepository = $edpmRepository;
        $this->ipmRepository = $ipmRepository;
        $this->sdmRepository = $sdmRepository;
    }

    public function getPaginatedData(?string $search = null, ?string $filterStatus = '', ?string $filterAkreditasi = '', int $perPage = 10, string $sortField = 'name', bool $sortAsc = true): LengthAwarePaginator
    {
        return $this->pesantrenRepository->getPaginatedPesantrens($search, $filterStatus, $filterAkreditasi, $perPage, $sortField, $sortAsc);
    }

    public function findUserDetail(string $uuid): ?User
    {
        return $this->pesantrenRepository->findUserByUuid($uuid, ['pesantren', 'pesantren.units', 'akreditasis']);
    }

    public function toggleDataLock(int $pesantrenId): bool
    {
        $pesantren = $this->pesantrenRepository->findPesantren($pesantrenId);
        if ($pesantren) {
            $isLocked = !$pesantren->is_locked;
            $this->pesantrenRepository->updatePesantren($pesantrenId, ['is_locked' => $isLocked]);

            if (!$isLocked) {
                // Notify user
                $user = User::find($pesantren->user_id);
                if ($user) {
                    $user->notify(new \App\Notifications\AkreditasiNotification(
                        'buka_kunci',
                        'Akses Data Dibuka',
                        'Administrator telah membuka kunci data Anda. Anda sekarang dapat memperbarui profil dan dokumen.',
                        route('pesantren.profile')
                    ));
                }
            }
            return true;
        }
        return false;
    }

    public function getAkreditasis(int $userId, ?string $search = null, ?string $periodeFilter = null, ?string $statusFilter = null, int $perPage = 10, string $sortField = 'created_at', bool $sortAsc = false): LengthAwarePaginator
    {
        return $this->akreditasiRepository->getPaginatedByUserId($userId, $search, $periodeFilter, $statusFilter, $perPage, $sortField, $sortAsc);
    }

    public function checkDataCompleteness(int $userId): array
    {
        $missingData = [];

        // 1. Check Profil
        $pesantren = \App\Models\Pesantren::where('user_id', $userId)->first();
        if (!$pesantren) {
            $missingData[] = 'Profil Pesantren belum diisi';
        } else if (empty($pesantren->nama_pesantren)) {
            $missingData[] = 'Nama Pesantren di Profil belum diisi';
        }

        // 2. Check IPM
        $ipm = \App\Models\Ipm::where('user_id', $userId)->first();
        if (!$ipm) {
            $missingData[] = 'Data IPM belum diisi';
        } else {
            if (!$ipm->nsp_file) $missingData[] = 'Dokumen NSP di IPM belum diunggah';
            if (!$ipm->lulus_santri_file) $missingData[] = 'Dokumen Kelulusan Santri di IPM belum diunggah';
            if (!$ipm->kurikulum_file) $missingData[] = 'Dokumen Kurikulum di IPM belum diunggah';
            if (!$ipm->buku_ajar_file) $missingData[] = 'Dokumen Buku Ajar di IPM belum diunggah';
        }

        // 3. Check SDM
        if (\App\Models\SdmPesantren::where('user_id', $userId)->count() === 0) {
            $missingData[] = 'Data SDM belum diisi';
        }

        // 4. Check EDPM
        $totalButirs = \App\Models\MasterEdpmButir::count();
        $evaluatedButirs = \App\Models\Edpm::where('user_id', $userId)->count();
        if ($evaluatedButirs < $totalButirs) {
            $missingData[] = "Evaluasi Diri (EDPM) belum lengkap ($evaluatedButirs/$totalButirs butir terisi)";
        }

        return $missingData;
    }

    public function createSubmission(int $userId, ?int $parentId = null): ?\App\Models\Akreditasi
    {
        // Check if already resubmitted
        if ($parentId && \App\Models\Akreditasi::where('parent', $parentId)->exists()) {
            return null;
        }

        $akreditasi = \App\Models\Akreditasi::create([
            'user_id' => $userId,
            'status' => 6, // Pengajuan
            'parent' => $parentId,
        ]);

        // Lock Data
        $user = User::find($userId);
        if ($user && $user->pesantren) {
            $user->pesantren->update(['is_locked' => true]);
            
            // Notify Admin
            $admins = User::whereHas('role', fn($q) => $q->where('id', 1))->get();
            \Illuminate\Support\Facades\Notification::send($admins, new \App\Notifications\AkreditasiNotification(
                'pengajuan',
                'Pengajuan Akreditasi Baru',
                'Pesantren ' . ($user->pesantren->nama_pesantren ?? $user->name) . ' telah membuat pengajuan akreditasi baru.',
                route('admin.akreditasi')
            ));
        }

        return $akreditasi;
    }

    public function deleteSubmission(int $id, int $userId): void
    {
        $akreditasi = \App\Models\Akreditasi::where('user_id', $userId)->findOrFail($id);
        $akreditasi->delete();

        // Unlock Data
        $user = User::find($userId);
        if ($user && $user->pesantren) {
            $user->pesantren->update(['is_locked' => false]);
        }
    }

    public function cancelSubmission(int $id, int $userId): void
    {
        $akreditasi = \App\Models\Akreditasi::where('user_id', $userId)->where('status', 6)->findOrFail($id);
        $akreditasi->delete();

        // Unlock Data if no more active
        $hasActive = \App\Models\Akreditasi::where('user_id', $userId)->whereIn('status', [3, 4, 5, 6])->exists();
        if (!$hasActive) {
            $user = User::find($userId);
            if ($user && $user->pesantren) {
                $user->pesantren->update(['is_locked' => false]);
            }
        }
    }

    public function submitAppeals(int $id, int $userId, string $alasan): bool
    {
        $akreditasi = \App\Models\Akreditasi::where('user_id', $userId)->findOrFail($id);

        if ($akreditasi->status == 2 && $akreditasi->assessments()->exists()) {
            $akreditasi->update(['status' => 3, 'catatan' => $alasan]);

            // Notify Admin
            $admins = User::whereHas('role', fn($q) => $q->where('id', 1))->get();
            $user = User::find($userId);
            \Illuminate\Support\Facades\Notification::send($admins, new \App\Notifications\AkreditasiNotification(
                'banding',
                'Pengajuan Banding Baru',
                'Pesantren ' . ($user->pesantren->nama_pesantren ?? $user->name) . ' telah mengajukan banding akreditasi.',
                route('admin.akreditasi')
            ));
            return true;
        }
        return false;
    }

    public function getProfile(int $userId): Pesantren
    {
        return $this->pesantrenRepository->findByUserId($userId) ?? Pesantren::create(['user_id' => $userId, 'nama_pesantren' => auth()->user()->name]);
    }

    public function updateProfile(int $userId, array $data, array $units = []): bool
    {
        $pesantren = $this->getProfile($userId);
        if ($pesantren->is_locked) return false;

        $pesantren->update($data);

        // Save Units Data
        if (!empty($units)) {
            // Delete units not in selected list
            $selectedUnits = array_column($units, 'unit');
            $pesantren->units()->whereNotIn('unit', $selectedUnits)->delete();

            // Update or create selected units
            foreach ($units as $unit) {
                $pesantren->units()->updateOrCreate(
                    ['unit' => $unit['unit']],
                    ['jumlah_rombel' => $unit['jumlah_rombel']]
                );
            }
        }

        return true;
    }

    public function getEdpmData(int $userId): array
    {
        return [
            'komponens' => $this->edpmRepository->getKomponens(),
            'existingEdpms' => $this->edpmRepository->getExistingEdpms($userId),
            'existingCatatans' => $this->edpmRepository->getExistingCatatans($userId),
        ];
    }

    public function saveEdpmEvaluation(int $userId, array $evaluasis, array $links, array $catatans): bool
    {
        $pesantren = $this->getProfile($userId);
        if ($pesantren->is_locked) return false;

        $allIds = array_unique(array_merge(array_keys($evaluasis), array_keys($links)));
        foreach ($allIds as $butirId) {
            $isian = $evaluasis[$butirId] ?? null;
            $link = $links[$butirId] ?? null;
            $this->edpmRepository->saveEdpm(
                ['user_id' => $userId, 'butir_id' => $butirId],
                ['isian' => $isian === '' ? null : $isian, 'link' => $link === '' ? null : $link]
            );
        }

        foreach ($catatans as $komponenId => $catatan) {
            $this->edpmRepository->saveCatatan(
                ['user_id' => $userId, 'komponen_id' => $komponenId],
                ['catatan' => $catatan]
            );
        }

        return true;
    }

    public function saveEdpmDraft(int $userId, array $evaluasis, array $links, array $catatans): bool
    {
        $pesantren = $this->getProfile($userId);
        if ($pesantren->is_locked) return false;

        $allIds = array_unique(array_merge(array_keys($evaluasis), array_keys($links)));
        foreach ($allIds as $butirId) {
            $isian = $evaluasis[$butirId] ?? null;
            $link = $links[$butirId] ?? null;

            if (($isian !== '' && $isian !== null) || ($link !== '' && $link !== null)) {
                $this->edpmRepository->saveEdpm(
                    ['user_id' => $userId, 'butir_id' => $butirId],
                    ['isian' => $isian === '' ? null : $isian, 'link' => $link === '' ? null : $link]
                );
            }
        }

        foreach ($catatans as $komponenId => $catatan) {
            if ($catatan !== '' && $catatan !== null) {
                $this->edpmRepository->saveCatatan(
                    ['user_id' => $userId, 'komponen_id' => $komponenId],
                    ['catatan' => $catatan]
                );
            }
        }

        return true;
    }

    public function getIpm(int $userId): \App\Models\Ipm
    {
        return $this->ipmRepository->findByUserId($userId) ?? \App\Models\Ipm::create(['user_id' => $userId]);
    }

    public function updateIpm(int $userId, array $data): bool
    {
        $pesantren = $this->getProfile($userId);
        if ($pesantren->is_locked) return false;

        return $this->ipmRepository->updateByUserId($userId, $data);
    }

    public function getSdm(int $userId): Collection
    {
        return $this->sdmRepository->getExistingSdm($userId);
    }

    public function updateSdm(int $userId, string $tingkat, array $data): bool
    {
        $pesantren = $this->getProfile($userId);
        if ($pesantren->is_locked) return false;

        $this->sdmRepository->updateOrUpdateSdm($userId, $tingkat, $data);
        return true;
    }

    public function getAkreditasiDetail(string $uuid, int $userId): array
    {
        $akreditasi = \App\Models\Akreditasi::with(['assessments.asesor.user', 'assessment1', 'assessment2'])
            ->where('uuid', $uuid)
            ->where('user_id', $userId)
            ->firstOrFail();

        $pesantren = \App\Models\Pesantren::with('units')->where('user_id', $userId)->first();
        $ipm = \App\Models\Ipm::where('user_id', $userId)->first();
        $sdm = \App\Models\SdmPesantren::where('user_id', $userId)->get()->keyBy('tingkat');
        $komponens = \App\Models\MasterEdpmKomponen::with('butirs')->orderByRaw('COALESCE(ipr, 0) ASC')->orderBy('id', 'ASC')->get();
        $visitasiTemplate = \App\Models\Document::where('type', 'visitasi')->where('status', 1)->first();

        // Pesantren EDPM
        $pEdpms = \App\Models\Edpm::where('user_id', $userId)->get();
        $pEvaluasis = $pEdpms->pluck('isian', 'butir_id');
        $pLinks = $pEdpms->pluck('link', 'butir_id');
        $pCatatans = \App\Models\EdpmCatatan::where('user_id', $userId)->get()->pluck('catatan', 'komponen_id');

        // Assessor 1 Data
        $asesor1Data = [];
        $asesor1Id = $akreditasi->assessment1->asesor_id ?? null;
        if ($asesor1Id) {
            $a1Edpms = \App\Models\AkreditasiEdpm::where('akreditasi_id', $akreditasi->id)->where('asesor_id', $asesor1Id)->get();
            $asesor1Data = [
                'evaluasis' => $a1Edpms->pluck('isian', 'butir_id'),
                'nks' => $a1Edpms->pluck('nk', 'butir_id'),
                'nvs' => $a1Edpms->pluck('nv', 'butir_id'),
                'butir_catatans' => $a1Edpms->pluck('catatan', 'butir_id'),
                'catatans' => \App\Models\AkreditasiEdpmCatatan::where('akreditasi_id', $akreditasi->id)
                    ->where('asesor_id', $asesor1Id)
                    ->get()
                    ->pluck('catatan', 'komponen_id')
            ];
        }

        // Assessor 2 Data
        $asesor2Data = [];
        $asesor2Id = $akreditasi->assessment2->asesor_id ?? null;
        if ($asesor2Id) {
            $asesor2Data['evaluasis'] = \App\Models\AkreditasiEdpm::where('akreditasi_id', $akreditasi->id)
                ->where('asesor_id', $asesor2Id)
                ->get()
                ->pluck('isian', 'butir_id');
        }

        return [
            'akreditasi' => $akreditasi,
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
            'asesor1' => $asesor1Data,
            'asesor2' => $asesor2Data,
        ];
    }

    public function uploadKartuKendali(int $akreditasiId, string $filePath): bool
    {
        $akreditasi = \App\Models\Akreditasi::findOrFail($akreditasiId);
        if ($akreditasi->status != 3) return false;

        $akreditasi->update(['kartu_kendali' => $filePath]);

        // Notify Admin
        $admins = \App\Models\User::whereHas('role', fn($q) => $q->where('id', 1))->get();
        \Illuminate\Support\Facades\Notification::send($admins, new \App\Notifications\AkreditasiNotification(
            'kartu_kendali_diunggah',
            'Kartu Kendali Diunggah',
            'Pesantren ' . ($akreditasi->user->pesantren->nama_pesantren ?? $akreditasi->user->name) . ' telah mengunggah kembali Kartu Kendali.',
            route('admin.akreditasi-detail', $akreditasi->uuid)
        ));

        return true;
    }
}
