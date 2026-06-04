<?php

namespace App\Services;

use App\Models\Akreditasi;
use App\Models\AkreditasiEdpm;
use App\Models\AkreditasiEdpmCatatan;
use App\Repositories\Contracts\AkreditasiRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class AkreditasiService
{
    protected $akreditasiRepository;

    protected AuditTrailService $auditTrailService;

    public function __construct(AkreditasiRepositoryInterface $akreditasiRepository, AuditTrailService $auditTrailService)
    {
        $this->akreditasiRepository = $akreditasiRepository;
        $this->auditTrailService = $auditTrailService;
    }

    /**
     * Get paginated akreditasi list with optional status filter and search.
     */
    public function getPaginatedAkreditasis(string $statusFilter, ?string $search = null, int $perPage = 10, string $sortField = 'created_at', bool $sortAsc = false): LengthAwarePaginator
    {
        return $this->akreditasiRepository->getPaginatedAkreditasis($statusFilter, $search, $perPage, $sortField, $sortAsc);
    }

    /**
     * Get count of akreditasi per status for dashboard widgets.
     *
     * @return array{pengajuan: int, verifikasi: int, assessment: int, visitasi: int, pasca_visitasi: int, validasi: int, overdue: int}
     */
    public function getStatusCounts(): array
    {
        $deadlineService = app(DeadlineService::class);

        return [
            'pengajuan' => $this->akreditasiRepository->getCountByStatus(6),   // Pengajuan
            'verifikasi' => $this->akreditasiRepository->getCountByStatus(5),   // Verifikasi Berkas
            'assessment' => $this->akreditasiRepository->getCountByStatus(4),   // Review Asesor
            'visitasi' => $this->akreditasiRepository->getCountByStatus(3),    // Visitasi
            'pasca_visitasi' => $this->akreditasiRepository->getCountByStatus(2), // Penilaian Pasca Visitasi
            'validasi' => $this->akreditasiRepository->getCountByStatus(1),   // Validasi Admin
            'overdue' => $deadlineService->getOverdueCount(),
        ];
    }

    /**
     * Find akreditasi by UUID with optional eager-loaded relations.
     */
    public function findAkreditasi(string $uuid, array $relations = []): ?Akreditasi
    {
        return $this->akreditasiRepository->findByUuid($uuid, $relations);
    }

    public function findAkreditasiById(int $id, array $relations = []): ?Akreditasi
    {
        return $this->akreditasiRepository->find($id, $relations);
    }

    /**
     * Soft-delete an akreditasi. Returns false if status=0 (Selesai) and force=false.
     *
     * @param  bool  $force  Skip the status=0 guard and delete regardless.
     */
    public function deleteAkreditasi(int $id, bool $force = false): bool
    {
        if (! $force) {
            $akreditasi = $this->akreditasiRepository->find($id);
            if ($akreditasi && $akreditasi->status === 0) {
                return false; // Tolak hapus akreditasi yang sudah Selesai kecuali force=true
            }
        }

        return $this->akreditasiRepository->delete($id);
    }

    public function getAvailableAsesors(): Collection
    {
        return $this->akreditasiRepository->getAvailableAsesors();
    }

    public function getAsesorEdpmData(int $akreditasiId, int $asesorId): array
    {
        $edpms = AkreditasiEdpm::where('akreditasi_id', $akreditasiId)->where('asesor_id', $asesorId)->get();
        $catatansModels = AkreditasiEdpmCatatan::where('akreditasi_id', $akreditasiId)->where('asesor_id', $asesorId)->get();

        return [
            'evaluasis' => $edpms->pluck('isian', 'butir_id'),
            'nks' => $edpms->pluck('nk', 'butir_id'),
            'nvs' => $edpms->pluck('nv', 'butir_id'),
            'butirCatatans' => $edpms->pluck('catatan', 'butir_id'),
            'catatans' => $catatansModels->pluck('catatan', 'komponen_id'),
            'catatanNks' => $catatansModels->pluck('nk', 'komponen_id'),
        ];
    }

    public function updateAdminNv(int $akreditasiId, int $asesor1Id, array $nvs): void
    {
        foreach ($nvs as $butirId => $nv) {
            AkreditasiEdpm::where('akreditasi_id', $akreditasiId)
                ->where('butir_id', $butirId)
                ->where('asesor_id', $asesor1Id)
                ->update(['nv' => $nv]);
        }
    }
}
