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
    public const PROFILE_REQUIRED_FIELDS = [
        'nama_pesantren' => 'Nama Pesantren',
        'ns_pesantren' => 'Nomor Statistik Pesantren (NSP)',
        'alamat' => 'Alamat Pesantren',
        'provinsi' => 'Provinsi',
        'kota_kabupaten' => 'Kota / Kabupaten',
        'tahun_pendirian' => 'Tahun Pendirian',
        'nama_mudir' => 'Nama Mudir / Pimpinan',
        'layanan_satuan_pendidikan' => 'Layanan Satuan Pendidikan',
    ];

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
        // PM-8 fix: gunakan lockForUpdate + atomic NOT is_locked untuk mencegah
        // lost-update saat dua admin klik toggle bersamaan.
        return DB::transaction(function () use ($pesantrenId) {
            $pesantren = Pesantren::lockForUpdate()->find($pesantrenId);
            if (! $pesantren) {
                return false;
            }

            $isLocked = ! $pesantren->is_locked;
            $pesantren->update(['is_locked' => $isLocked]);

            if (! $isLocked) {
                // Notify user hanya saat dibuka kunci
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
        });
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
        } else {
            $missingProfileFields = $this->getMissingProfileFields($pesantren);
            if ($missingProfileFields !== []) {
                $missingData[] = 'Profil Pesantren belum lengkap: '.implode(', ', $missingProfileFields);
            }

            // PM-12 fix: wajib ada minimal 1 layanan satuan pendidikan
            $layanan = $pesantren->layanan_satuan_pendidikan;
            if (empty($layanan) || (is_array($layanan) && count($layanan) === 0)) {
                $missingData[] = 'Layanan Satuan Pendidikan belum dipilih';
            }
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

        // 3. Check SDM — PM-12 fix: minimal 1 row SDM dengan setidaknya 1 nilai non-zero
        $sdmRows = \App\Models\SdmPesantren::where('user_id', $userId)->get();
        if ($sdmRows->isEmpty()) {
            $missingData[] = 'Data SDM belum diisi';
        } else {
            $numericFields = [
                'santri_l', 'santri_p',
                'ustadz_dirosah_l', 'ustadz_dirosah_p',
                'ustadz_non_dirosah_l', 'ustadz_non_dirosah_p',
                'pamong_l', 'pamong_p',
                'musyrif_l', 'musyrif_p',
                'tendik_l', 'tendik_p',
            ];
            $hasNonZero = $sdmRows->contains(function ($row) use ($numericFields) {
                foreach ($numericFields as $field) {
                    if ((int) ($row->$field ?? 0) > 0) {
                        return true;
                    }
                }
                return false;
            });
            if (! $hasNonZero) {
                $missingData[] = 'Data SDM masih kosong (semua nilai nol). Isi minimal satu data SDM.';
            }
        }

        // 4. Check EDPM
        // P-9 fix: cache master butir count — it changes only when admin edits master data.
        $totalButirs = \Illuminate\Support\Facades\Cache::rememberForever(
            'master_edpm_butir_count',
            fn () => \App\Models\MasterEdpmButir::count()
        );
        $evaluatedButirs = \App\Models\Edpm::where('user_id', $userId)->count();
        if ($evaluatedButirs < $totalButirs) {
            $missingData[] = "Evaluasi Diri (EDPM) belum lengkap ($evaluatedButirs/$totalButirs butir terisi)";
        }

        return $missingData;
    }

    /**
     * @return list<string>
     */
    public function getMissingProfileFields(Pesantren $pesantren): array
    {
        $missing = [];

        foreach (self::PROFILE_REQUIRED_FIELDS as $field => $label) {
            $value = $pesantren->{$field};

            if (is_array($value) ? $value === [] : blank($value)) {
                $missing[] = $label;
            }
        }

        return $missing;
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

    public function cancelSubmission(int $id, int $userId): bool
    {
        // PM-7 fix: gunakan find + ownership check (bukan findOrFail yang throw 404
        // pada double-click), dan bungkus dalam DB::transaction agar delete +
        // unlock pesantren jadi atomic.
        $akreditasi = \App\Models\Akreditasi::where('user_id', $userId)
            ->where('status', 6)
            ->find($id);

        if (! $akreditasi) {
            return false;
        }

        return DB::transaction(function () use ($akreditasi, $userId) {
            $akreditasi->delete();

            // Unlock pesantren data only when no other active akreditasi remains.
            $hasActive = \App\Models\Akreditasi::where('user_id', $userId)
                ->whereIn('status', [3, 4, 5, 6])
                ->exists();

            if (! $hasActive) {
                $user = User::find($userId);
                $user?->pesantren?->update(['is_locked' => false]);
            }

            return true;
        });
    }

    public function submitAppeals(int $id, int $userId, string $alasan): bool
    {
        // Defense-in-depth length validation
        // L-6 fix: use $trimmed for both min and max bounds (was using $alasan for max).
        $trimmed = trim($alasan);
        if (mb_strlen($trimmed) < 10 || mb_strlen($trimmed) > 1000) {
            return false;
        }

        $akreditasi = \App\Models\Akreditasi::withTrashed()->where('user_id', $userId)->find($id);
        if (! $akreditasi) {
            Log::warning('submitAppeals ownership mismatch or not found', [
                'user_id' => $userId,
                'akreditasi_id' => $id,
            ]);

            return false;
        }

        $bandingService = app(BandingService::class);
        $outcome = $bandingService->submitBanding($id, $userId, $trimmed);

        if (! $outcome['success']) {
            return false;
        }

        // Audit trail: log banding submission for callers still using this wrapper.
        $this->auditTrailService->log(
            akreditasiId: $akreditasi->id,
            actionType: 'banding_submitted',
            metadata: [
                'alasan' => $trimmed,
            ]
        );

        return true;
    }

    public function getProfile(int $userId): Pesantren
    {
        // Audit fix PM-4: use firstOrCreate so the unique index on user_id
        // turns a concurrent double-insert race into a safe retry instead of
        // producing two Pesantren rows for the same user.
        return $this->pesantrenRepository->findByUserId($userId)
            ?? Pesantren::firstOrCreate(
                ['user_id' => $userId],
                ['nama_pesantren' => auth()->user()?->name ?? '']
            );
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

        if ((int) $akreditasi->status !== \App\StateMachine\AkreditasiStateMachine::STATUS_PASCA_VISITASI) {
            return false;
        }

        $result = DB::transaction(function () use ($akreditasi, $filePath) {
            // PM-27 fix: simpan path lama sebelum update, hapus setelah commit sukses.
            $oldPath = $akreditasi->kartu_kendali;
            $akreditasi->update(['kartu_kendali' => $filePath]);

            return ['success' => true, 'old_path' => $oldPath];
        });

        if (! $result['success']) {
            return false;
        }

        // Hapus file lama setelah transaction commit (non-blocking, best-effort)
        if ($result['old_path'] && $result['old_path'] !== $filePath) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($result['old_path']);
        }

        // Dispatch notifications AFTER transaction commits (non-blocking)
        if ($result['success']) {
            $admins = \App\Models\User::whereHas('role', fn ($q) => $q->where('id', 1))->get();
            \Illuminate\Support\Facades\Notification::send($admins, new \App\Notifications\AkreditasiNotification(
                'kartu_kendali_diunggah',
                'Kartu Kendali Diunggah',
                'Pesantren '.($akreditasi->user->pesantren->nama_pesantren ?? $akreditasi->user->name).' telah mengunggah kembali Kartu Kendali.',
                route('admin.akreditasi-detail', $akreditasi->uuid)
            ));
        }

        return (bool) $result['success'];
    }
}
