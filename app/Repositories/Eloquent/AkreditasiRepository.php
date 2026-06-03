<?php

namespace App\Repositories\Eloquent;

use App\Models\Akreditasi;
use App\Models\AkreditasiCatatan;
use App\Models\AkreditasiEdpm;
use App\Models\AkreditasiEdpmCatatan;
use App\Models\Asesor;
use App\Models\Assessment;
use App\Repositories\Contracts\AkreditasiRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class AkreditasiRepository implements AkreditasiRepositoryInterface
{
    public function getPaginatedAkreditasis(string $statusFilter, ?string $search = null, int $perPage = 10, string $sortField = 'created_at', bool $sortAsc = false): LengthAwarePaginator
    {
        // P-3 fix: eager-load assessment1 + assessment2 directly instead of bare
        // 'assessments' — the blade reads $item->assessment1 (hasOne tipe=1) which
        // was a separate lazy query per row when only 'assessments' was loaded.
        $query = Akreditasi::with([
            'user:id,name,email,uuid',
            'user.pesantren:id,user_id,nama_pesantren,is_locked',
            'assessment1:id,akreditasi_id,asesor_id,tipe,tanggal_mulai,tanggal_berakhir',
            'assessment2:id,akreditasi_id,asesor_id,tipe,tanggal_mulai,tanggal_berakhir',
            'catatans:id,akreditasi_id,user_id,tipe,perbaikan,created_at',
            'catatans.user:id,name',
        ]);

        match ($statusFilter) {
            'pengajuan' => $query->where('status', 6),
            'verifikasi' => $query->where('status', 5),
            'assessment' => $query->where('status', 4),
            'visitasi' => $query->whereIn('status', [3, 2]),
            'validasi' => $query->where('status', 1),
            'selesai' => $query->where('status', 0),
            'ditolak' => $query->where('status', -1),
            'banding' => $query->where('status', -2),
            default => null, // 'all' or empty — no filter
        };

        if ($search) {
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', '%'.$search.'%')
                    ->orWhereHas('pesantren', function ($q2) use ($search) {
                        $q2->where('nama_pesantren', 'like', '%'.$search.'%');
                    });
            });
        }

        return $query->orderBy($sortField, $sortAsc ? 'asc' : 'desc')
            ->paginate($perPage);
    }

    public function getCountByStatus(int|array $status): int
    {
        if (is_array($status)) {
            return Akreditasi::whereIn('status', $status)->count();
        }

        return Akreditasi::where('status', $status)->count();
    }

    public function findByUuid(string $uuid, array $relations = []): ?Akreditasi
    {
        return Akreditasi::with($relations)->where('uuid', $uuid)->first();
    }

    public function find(int $id, array $relations = []): ?Akreditasi
    {
        return Akreditasi::with($relations)->find($id);
    }

    public function delete(int $id): bool
    {
        $akreditasi = $this->find($id);

        return $akreditasi ? $akreditasi->delete() : false;
    }

    public function update(int $id, array $data): bool
    {
        $akreditasi = $this->find($id);

        return $akreditasi ? $akreditasi->update($data) : false;
    }

    public function getAssessmentsByAsesor(int $asesorId, ?string $search = null, ?string $periodeFilter = null, ?string $statusFilter = null, int $perPage = 10, string $sortField = 'id', bool $sortAsc = false): LengthAwarePaginator
    {
        $query = Assessment::with(['akreditasi.user.pesantren', 'akreditasi.catatans.user', 'akreditasi.assessment1'])
            ->where('asesor_id', $asesorId);

        if ($search) {
            $query->whereHas('akreditasi.user.pesantren', function ($q) use ($search) {
                $q->where('nama_pesantren', 'like', '%'.$search.'%');
            })->orWhereHas('akreditasi.user', function ($q) use ($search) {
                $q->where('name', 'like', '%'.$search.'%');
            });
        }

        if ($periodeFilter) {
            $query->whereHas('akreditasi', function ($q) use ($periodeFilter) {
                $q->whereYear('created_at', $periodeFilter);
            });
        }

        if ($statusFilter) {
            $query->whereHas('akreditasi', function ($q) use ($statusFilter) {
                if ($statusFilter === 'selesai') {
                    $q->where('status', Akreditasi::STATUS_SELESAI);
                } elseif ($statusFilter === 'siap') {
                    $q->where('status', Akreditasi::STATUS_VISITASI);
                } elseif ($statusFilter === 'penilaian') {
                    $q->where('status', Akreditasi::STATUS_PASCA_VISITASI);
                } elseif ($statusFilter === 'revisi') {
                    $q->where('status', Akreditasi::STATUS_ASSESSMENT)->whereHas('catatans', function ($cq) {
                        $cq->whereNotNull('perbaikan')->where('perbaikan', '!=', '');
                    });
                } elseif ($statusFilter === 'belum') {
                    $q->where('status', Akreditasi::STATUS_ASSESSMENT)->whereNull('tgl_visitasi')
                        ->whereDoesntHave('catatans', function ($cq) {
                            $cq->whereNotNull('perbaikan')->where('perbaikan', '!=', '');
                        });
                } else {
                    $q->where('status', $statusFilter);
                }
            });
        }

        return $query->orderBy($sortField, $sortAsc ? 'asc' : 'desc')
            ->paginate($perPage);
    }

    public function findAssessment(int $id, array $relations = []): ?Assessment
    {
        return Assessment::with($relations)->find($id);
    }

    public function addCatatan(array $data): AkreditasiCatatan
    {
        return AkreditasiCatatan::create($data);
    }

    public function getEdpmData(int $akreditasiId, ?int $asesorId = null): Collection
    {
        $query = AkreditasiEdpm::where('akreditasi_id', $akreditasiId);
        if ($asesorId) {
            $query->where('asesor_id', $asesorId);
        }

        return $query->get();
    }

    public function getEdpmCatatans(int $akreditasiId, ?int $asesorId = null): Collection
    {
        $query = AkreditasiEdpmCatatan::where('akreditasi_id', $akreditasiId);
        if ($asesorId) {
            $query->where('asesor_id', $asesorId);
        }

        return $query->get();
    }

    public function saveEdpmEvaluation(array $attributes, array $data): AkreditasiEdpm
    {
        return AkreditasiEdpm::updateOrCreate($attributes, $data);
    }

    public function saveEdpmCatatan(array $attributes, array $data): AkreditasiEdpmCatatan
    {
        return AkreditasiEdpmCatatan::updateOrCreate($attributes, $data);
    }

    public function getPaginatedByUserId(int $userId, ?string $search = null, ?string $periodeFilter = null, ?string $statusFilter = null, ?string $tahapanFilter = null, int $perPage = 10, string $sortField = 'created_at', bool $sortAsc = false): LengthAwarePaginator
    {
        $tahapanStatusMap = [
            'visitasi' => Akreditasi::STATUS_VISITASI,
        ];

        return Akreditasi::with(['assessments', 'catatans', 'assessment1', 'bandings'])
            ->where('user_id', $userId)
            ->when($periodeFilter, fn ($q) => $q->whereYear('created_at', $periodeFilter))
            ->when($statusFilter !== null && $statusFilter !== '', function ($query) use ($statusFilter) {
                if ($statusFilter === 'hasil_akhir') {
                    $query->whereIn('status', [
                        Akreditasi::STATUS_SELESAI,
                        Akreditasi::STATUS_BANDING,
                    ]);

                    return;
                }

                $query->where('status', $statusFilter);
            })
            ->when($tahapanFilter !== null && $tahapanFilter !== '', function ($query) use ($tahapanFilter, $tahapanStatusMap) {
                $query->where('status', $tahapanStatusMap[$tahapanFilter] ?? $tahapanFilter);
            })
            ->when($search, function ($query) use ($search) {
                $query->where('nomor_sk', 'like', '%'.$search.'%')
                    ->orWhere('peringkat', 'like', '%'.$search.'%');
            })
            ->orderBy($sortField, $sortAsc ? 'asc' : 'desc')
            ->paginate($perPage);
    }

    public function latestForUser(int $userId): ?Akreditasi
    {
        return Akreditasi::query()
            ->where('user_id', $userId)
            ->latest()
            ->first();
    }

    public function getAvailableAsesors(): Collection
    {
        return Asesor::with('user')
            ->whereDoesntHave('assessments', function ($query) {
                $query->whereHas('akreditasi', function ($q) {
                    // Exclude asesors currently assigned to active akreditasi (status 1-6)
                    $q->whereIn('status', [6, 5, 4, 3, 2, 1]);
                });
            })
            ->get();
    }
}
