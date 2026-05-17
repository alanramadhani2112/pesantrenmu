<?php

namespace App\Services;

use App\Models\Pesantren;
use App\Models\User;
use App\Repositories\Contracts\PesantrenRepositoryInterface;
use App\Services\AuditTrailService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PesantrenService
{
    protected $pesantrenRepository;

    protected $akreditasiRepository;

    protected $edpmRepository;

    protected $ipmRepository;

    protected $sdmRepository;

    protected AuditTrailService $auditTrailService;

    public function __construct(
        PesantrenRepositoryInterface $pesantrenRepository,
        \App\Repositories\Contracts\AkreditasiRepositoryInterface $akreditasiRepository,
        \App\Repositories\Contracts\EdpmRepositoryInterface $edpmRepository,
        \App\Repositories\Contracts\IpmRepositoryInterface $ipmRepository,
        \App\Repositories\Contracts\SdmRepositoryInterface $sdmRepository,
        AuditTrailService $auditTrailService
    ) {
        $this->pesantrenRepository = $pesantrenRepository;
        $this->akreditasiRepository = $akreditasiRepository;
        $this->edpmRepository = $edpmRepository;
        $this->ipmRepository = $ipmRepository;
        $this->sdmRepository = $sdmRepository;
        $this->auditTrailService = $auditTrailService;
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
            $isLocked = ! $pesantren->is_locked;
            $this->pesantrenRepository->updatePesantren($pesantrenId, ['is_locked' => $isLocked]);

            if (! $isLocked) {
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
        if (! $pesantren) {
            $missingData[] = 'Profil Pesantren belum diisi';
        } elseif (empty($pesantren->nama_pesantren)) {
            $missingData[] = 'Nama Pesantren di Profil belum diisi';
        }

        // 2. Check IPM
        $ipm = \App\Models\Ipm::where('user_id', $userId)->first();
        if (! $ipm) {
            $missingData[] = 'Data IPM belum diisi';
        } else {
            if (! $ipm->nsp_file) {
                $missingData[] = 'Dokumen NSP di IPM belum diunggah';
            }
            if (! $ipm->lulus_santri_file) {
                $missingData[] = 'Dokumen Kelulusan Santri di IPM belum diunggah';
            }
            if (! $ipm->kurikulum_file) {
                $missingData[] = 'Dokumen Kurikulum di IPM belum diunggah';
            }
            if (! $ipm->buku_ajar_file) {
                $missingData[] = 'Dokumen Buku Ajar di IPM belum diunggah';
            }
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
        if (! empty($this->checkDataCompleteness($userId))) {
            return null;
        }

        if (\App\Models\Akreditasi::where('user_id', $userId)->whereIn('status', [3, 4, 5, 6])->exists()) {
            return null;
        }

        if ($parentId && \App\Models\Akreditasi::where('parent', $parentId)->exists()) {
            return null;
        }

        if ($parentId) {
            $resubmissionService = app(ResubmissionService::class);
            $eligibility = $resubmissionService->checkResubmissionEligibility($parentId);

            if (! $eligibility['allowed']) {
                Log::info('Resubmission blocked', [
                    'user_id' => $userId,
                    'akreditasi_id' => $parentId,
                    'reason' => $eligibility['error_code'],
                    'chain_count' => $eligibility['error_data']['count'] ?? null,
                    'limit' => $eligibility['error_data']['limit'] ?? config('akreditasi.resubmission_limit'),
                ]);

                return null;
            }
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
            $admins = User::whereHas('role', fn ($q) => $q->where('id', 1))->get();
            \Illuminate\Support\Facades\Notification::send($admins, new \App\Notifications\AkreditasiNotification(
                'pengajuan',
                'Pengajuan Akreditasi Baru',
                'Pesantren '.($user->pesantren->nama_pesantren ?? $user->name).' telah membuat pengajuan akreditasi baru.',
                route('admin.akreditasi')
            ));
        }

        return $akreditasi;
    }

    public function deleteSubmission(int $id, int $userId): bool
    {
        $akreditasi = \App\Models\Akreditasi::find($id);
        if (! $akreditasi) {
            return false;
        }

        if ($akreditasi->user_id !== $userId) {
            Log::warning('deleteSubmission ownership mismatch', [
                'user_id' => $userId,
                'akreditasi_id' => $id,
                'owner_id' => $akreditasi->user_id,
            ]);

            return false;
        }

        if ((int) $akreditasi->status !== 6) {
            return false;
        }

        // Reject if a child resubmission already references this akreditasi.
        if (\App\Models\Akreditasi::where('parent', $id)->exists()) {
            return false;
        }

        return DB::transaction(function () use ($akreditasi, $userId) {
            $akreditasi->delete();

            // Unlock pesantren data only when no other active akreditasi remains.
            $hasOtherActive = \App\Models\Akreditasi::where('user_id', $userId)
                ->whereIn('status', [3, 4, 5, 6])
                ->exists();

            if (! $hasOtherActive) {
                $user = User::find($userId);
                $user?->pesantren?->update(['is_locked' => false]);
            }

            return true;
        });
    }

    public function cancelSubmission(int $id, int $userId): void
    {
        $akreditasi = \App\Models\Akreditasi::where('user_id', $userId)->where('status', 6)->findOrFail($id);
        $akreditasi->delete();

        // Unlock Data if no more active
        $hasActive = \App\Models\Akreditasi::where('user_id', $userId)->whereIn('status', [3, 4, 5, 6])->exists();
        if (! $hasActive) {
            $user = User::find($userId);
            if ($user && $user->pesantren) {
                $user->pesantren->update(['is_locked' => false]);
            }
        }
    }

    public function submitAppeals(int $id, int $userId, string $alasan): bool
    {
        // Defense-in-depth length validation
        $trimmed = trim($alasan);
        if (mb_strlen($trimmed) < 10 || mb_strlen($alasan) > 1000) {
            return false;
        }

        $akreditasi = \App\Models\Akreditasi::where('user_id', $userId)->find($id);
        if (! $akreditasi) {
            Log::warning('submitAppeals ownership mismatch or not found', [
                'user_id' => $userId,
                'akreditasi_id' => $id,
            ]);

            return false;
        }

        if ((int) $akreditasi->status !== 2) {
            return false;
        }

        if (! $akreditasi->assessments()->exists()) {
            return false;
        }

        // Check banding eligibility before proceeding
        $bandingService = app(BandingService::class);
        $eligibility = $bandingService->checkBandingEligibility($id);

        if (! $eligibility['allowed']) {
            return false;
        }

        $result = DB::transaction(function () use ($akreditasi, $userId, $alasan) {
            $akreditasi->update(['status' => 3, 'catatan' => $alasan]);

            // Create banding record
            $bandingService = app(BandingService::class);
            $bandingService->createBanding($akreditasi->id, $userId, $alasan);

            // Audit trail: log banding submission
            $this->auditTrailService->log(
                akreditasiId: $akreditasi->id,
                actionType: 'banding_submitted',
                metadata: [
                    'alasan' => $alasan,
                ]
            );

            return true;
        });

        // Dispatch notifications AFTER transaction commits (non-blocking)
        if ($result) {
            $admins = User::whereHas('role', fn ($q) => $q->where('id', 1))->get();
            $user = User::find($userId);
            \Illuminate\Support\Facades\Notification::send($admins, new \App\Notifications\AkreditasiNotification(
                'banding',
                'Pengajuan Banding Baru',
                'Pesantren '.($user->pesantren->nama_pesantren ?? $user->name).' telah mengajukan banding akreditasi.',
                route('admin.akreditasi')
            ));
        }

        return (bool) $result;
    }

    public function getProfile(int $userId): Pesantren
    {
        return $this->pesantrenRepository->findByUserId($userId) ?? Pesantren::create(['user_id' => $userId, 'nama_pesantren' => auth()->user()->name]);
    }

    /**
     * Update profil pesantren + sinkronisasi unit layanan.
     *
     * Production hardening (audit fix PM-1..PM-4):
     * Pesantren update + units delete + units upsert dibungkus DB::transaction.
     * Tanpa ini, kalau salah satu unit upsert error setelah units lain sudah
     * dihapus, profil akan tersimpan dengan unit kosong/parsial — data ghost.
     */
    public function updateProfile(int $userId, array $data, array $units = []): bool
    {
        $pesantren = $this->getProfile($userId);
        if ($pesantren->is_locked) {
            // Check if 'profil' section is unlocked via rejection
            $rejectionService = app(\App\Services\RejectionService::class);
            $akreditasi = \App\Models\Akreditasi::where('user_id', $userId)->where('status', 5)->latest()->first();
            if (! $akreditasi || ! $rejectionService->isSectionUnlocked($akreditasi->id, 'profil')) {
                return false;
            }
        }

        return DB::transaction(function () use ($pesantren, $data, $units) {
            $pesantren->update($data);

            // Save Units Data — always sync (including full clear when nothing selected)
            $selectedUnits = array_column($units, 'unit');

            if (empty($selectedUnits)) {
                // No layanan selected: remove all unit rows
                $pesantren->units()->delete();
            } else {
                // Delete units not in selected list
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
        });
    }

    public function getEdpmData(int $userId): array
    {
        return [
            'komponens' => $this->edpmRepository->getKomponens(),
            'existingEdpms' => $this->edpmRepository->getExistingEdpms($userId),
            'existingCatatans' => $this->edpmRepository->getExistingCatatans($userId),
        ];
    }

    /**
     * Production hardening (audit fix PM-1..PM-4):
     * Bulk save EDPM dibungkus DB::transaction supaya saveEdpm + saveCatatan
     * jadi all-or-nothing. Tanpa ini, kalau saveCatatan kelima gagal, empat
     * butir sebelumnya sudah commit dan data jadi parsial.
     */
    public function saveEdpmEvaluation(int $userId, array $evaluasis, array $links, array $catatans): bool
    {
        $pesantren = $this->getProfile($userId);
        if ($pesantren->is_locked) {
            // Check if any EDPM butir is unlocked via rejection
            $rejectionService = app(\App\Services\RejectionService::class);
            $akreditasi = \App\Models\Akreditasi::where('user_id', $userId)->where('status', 5)->latest()->first();
            if (! $akreditasi) {
                return false;
            }

            return DB::transaction(function () use ($userId, $evaluasis, $links, $catatans, $rejectionService, $akreditasi) {
                // Filter to only allow unlocked butir entries
                $allIds = array_unique(array_merge(array_keys($evaluasis), array_keys($links)));
                $hasUnlocked = false;
                foreach ($allIds as $butirId) {
                    if ($rejectionService->isSectionUnlocked($akreditasi->id, 'edpm.butir.'.$butirId)) {
                        $hasUnlocked = true;
                        $isian = $evaluasis[$butirId] ?? null;
                        $link = $links[$butirId] ?? null;
                        $this->edpmRepository->saveEdpm(
                            ['user_id' => $userId, 'butir_id' => $butirId],
                            ['isian' => $isian === '' ? null : $isian, 'link' => $link === '' ? null : $link]
                        );
                    }
                }

                // Save catatans for komponen that have at least one unlocked butir
                foreach ($catatans as $komponenId => $catatan) {
                    $this->edpmRepository->saveCatatan(
                        ['user_id' => $userId, 'komponen_id' => $komponenId],
                        ['catatan' => $catatan]
                    );
                }

                return $hasUnlocked;
            });
        }

        return DB::transaction(function () use ($userId, $evaluasis, $links, $catatans) {
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
        });
    }

    /**
     * Production hardening (audit fix PM-1..PM-4):
     * Draft EDPM dibungkus DB::transaction untuk konsistensi sama dengan
     * saveEdpmEvaluation. Penting saat user save draft di akhir sesi sambil
     * koneksi tidak stabil — partial save bikin data jadi misleading.
     */
    public function saveEdpmDraft(int $userId, array $evaluasis, array $links, array $catatans): bool
    {
        $pesantren = $this->getProfile($userId);
        if ($pesantren->is_locked) {
            // Check if any EDPM butir is unlocked via rejection
            $rejectionService = app(\App\Services\RejectionService::class);
            $akreditasi = \App\Models\Akreditasi::where('user_id', $userId)->where('status', 5)->latest()->first();
            if (! $akreditasi) {
                return false;
            }

            return DB::transaction(function () use ($userId, $evaluasis, $links, $catatans, $rejectionService, $akreditasi) {
                // Filter to only allow unlocked butir entries
                $allIds = array_unique(array_merge(array_keys($evaluasis), array_keys($links)));
                $hasUnlocked = false;
                foreach ($allIds as $butirId) {
                    if ($rejectionService->isSectionUnlocked($akreditasi->id, 'edpm.butir.'.$butirId)) {
                        $hasUnlocked = true;
                        $isian = $evaluasis[$butirId] ?? null;
                        $link = $links[$butirId] ?? null;

                        if (($isian !== '' && $isian !== null) || ($link !== '' && $link !== null)) {
                            $this->edpmRepository->saveEdpm(
                                ['user_id' => $userId, 'butir_id' => $butirId],
                                ['isian' => $isian === '' ? null : $isian, 'link' => $link === '' ? null : $link]
                            );
                        }
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

                return $hasUnlocked;
            });
        }

        return DB::transaction(function () use ($userId, $evaluasis, $links, $catatans) {
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
        });
    }

    public function getIpm(int $userId): \App\Models\Ipm
    {
        return $this->ipmRepository->findByUserId($userId) ?? \App\Models\Ipm::create(['user_id' => $userId]);
    }

    public function updateIpm(int $userId, array $data): bool
    {
        $pesantren = $this->getProfile($userId);
        if ($pesantren->is_locked) {
            // Check if any IPM sub-item is unlocked via rejection
            $rejectionService = app(\App\Services\RejectionService::class);
            $akreditasi = \App\Models\Akreditasi::where('user_id', $userId)->where('status', 5)->latest()->first();
            if (! $akreditasi) {
                return false;
            }

            // Filter data to only allow unlocked sub-items
            $ipmSectionMap = [
                'nsp_file' => 'ipm.nsp',
                'kurikulum_file' => 'ipm.kurikulum',
                'buku_ajar_file' => 'ipm.buku_ajar',
                'lulus_santri_file' => 'ipm.lulus_santri',
            ];

            $allowedData = [];
            foreach ($data as $key => $value) {
                $section = $ipmSectionMap[$key] ?? null;
                if ($section && $rejectionService->isSectionUnlocked($akreditasi->id, $section)) {
                    $allowedData[$key] = $value;
                }
            }

            if (empty($allowedData)) {
                return false;
            }

            return $this->ipmRepository->updateByUserId($userId, $allowedData);
        }

        return $this->ipmRepository->updateByUserId($userId, $data);
    }

    public function getSdm(int $userId): Collection
    {
        return $this->sdmRepository->getExistingSdm($userId);
    }

    public function updateSdm(int $userId, string $tingkat, array $data): bool
    {
        $pesantren = $this->getProfile($userId);
        if ($pesantren->is_locked) {
            // Check if 'sdm' section is unlocked via rejection
            $rejectionService = app(\App\Services\RejectionService::class);
            $akreditasi = \App\Models\Akreditasi::where('user_id', $userId)->where('status', 5)->latest()->first();
            if (! $akreditasi || ! $rejectionService->isSectionUnlocked($akreditasi->id, 'sdm')) {
                return false;
            }
        }

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
                    ->pluck('catatan', 'komponen_id'),
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

    public function uploadKartuKendali(int $akreditasiId, int $userId, string $filePath): bool
    {
        $akreditasi = \App\Models\Akreditasi::find($akreditasiId);
        if (! $akreditasi) {
            return false;
        }

        if ($akreditasi->user_id !== $userId) {
            Log::warning('uploadKartuKendali ownership mismatch', [
                'user_id' => $userId,
                'akreditasi_id' => $akreditasiId,
                'owner_id' => $akreditasi->user_id,
            ]);

            return false;
        }

        if ($akreditasi->status != 3) {
            return false;
        }

        $result = DB::transaction(function () use ($akreditasi, $filePath) {
            $akreditasi->update(['kartu_kendali' => $filePath]);

            return true;
        });

        // Dispatch notifications AFTER transaction commits (non-blocking)
        if ($result) {
            $admins = \App\Models\User::whereHas('role', fn ($q) => $q->where('id', 1))->get();
            \Illuminate\Support\Facades\Notification::send($admins, new \App\Notifications\AkreditasiNotification(
                'kartu_kendali_diunggah',
                'Kartu Kendali Diunggah',
                'Pesantren '.($akreditasi->user->pesantren->nama_pesantren ?? $akreditasi->user->name).' telah mengunggah kembali Kartu Kendali.',
                route('admin.akreditasi-detail', $akreditasi->uuid)
            ));
        }

        return (bool) $result;
    }
}
