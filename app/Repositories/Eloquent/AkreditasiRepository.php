<?php

namespace App\Repositories\Eloquent;

use App\Models\Akreditasi;
use App\Models\Asesor;
use App\Repositories\Contracts\AkreditasiRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class AkreditasiRepository implements AkreditasiRepositoryInterface
{
    public function getPaginatedAkreditasis(string $statusFilter, ?string $search = null, int $perPage = 10, string $sortField = 'created_at', bool $sortAsc = false): LengthAwarePaginator
    {
        $query = Akreditasi::with(['user.pesantren', 'assessments', 'catatans.user']);

        if ($statusFilter === 'pengajuan') {
            $query->where('status', 6);
        } elseif ($statusFilter === 'assessment') {
            $query->where('status', 5);
        } elseif ($statusFilter === 'visitasi') {
            $query->where('status', '<=', 4);
        }

        if ($search) {
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                    ->orWhereHas('pesantren', function ($q2) use ($search) {
                        $q2->where('nama_pesantren', 'like', '%' . $search . '%');
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
        $query = \App\Models\Assessment::with(['akreditasi.user.pesantren', 'akreditasi.catatans.user', 'akreditasi.assessment1'])
            ->where('asesor_id', $asesorId);

        if ($search) {
            $query->whereHas('akreditasi.user.pesantren', function ($q) use ($search) {
                $q->where('nama_pesantren', 'like', '%' . $search . '%');
            })->orWhereHas('akreditasi.user', function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%');
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
                    $q->whereIn('status', [1, 2, 3]);
                } elseif ($statusFilter === 'siap') {
                    $q->where('status', '>', 3)->whereNotNull('tgl_visitasi');
                } elseif ($statusFilter === 'revisi') {
                    $q->where('status', '>', 3)->whereHas('catatans', function ($cq) {
                        $cq->whereNotNull('perbaikan')->where('perbaikan', '!=', '');
                    });
                } elseif ($statusFilter === 'belum') {
                    $q->where('status', '>', 3)->whereNull('tgl_visitasi')
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

    public function findAssessment(int $id, array $relations = []): ?\App\Models\Assessment
    {
        return \App\Models\Assessment::with($relations)->find($id);
    }

    public function addCatatan(array $data): \App\Models\AkreditasiCatatan
    {
        return \App\Models\AkreditasiCatatan::create($data);
    }

    public function getEdpmData(int $akreditasiId, ?int $asesorId = null): \Illuminate\Support\Collection
    {
        $query = \App\Models\AkreditasiEdpm::where('akreditasi_id', $akreditasiId);
        if ($asesorId) {
            $query->where('asesor_id', $asesorId);
        }
        return $query->get();
    }

    public function getEdpmCatatans(int $akreditasiId, ?int $asesorId = null): \Illuminate\Support\Collection
    {
        $query = \App\Models\AkreditasiEdpmCatatan::where('akreditasi_id', $akreditasiId);
        if ($asesorId) {
            $query->where('asesor_id', $asesorId);
        }
        return $query->get();
    }

    public function saveEdpmEvaluation(array $attributes, array $data): \App\Models\AkreditasiEdpm
    {
        return \App\Models\AkreditasiEdpm::updateOrCreate($attributes, $data);
    }

    public function saveEdpmCatatan(array $attributes, array $data): \App\Models\AkreditasiEdpmCatatan
    {
        return \App\Models\AkreditasiEdpmCatatan::updateOrCreate($attributes, $data);
    }

    public function getPaginatedByUserId(int $userId, ?string $search = null, ?string $periodeFilter = null, ?string $statusFilter = null, int $perPage = 10, string $sortField = 'created_at', bool $sortAsc = false): LengthAwarePaginator
    {
        return \App\Models\Akreditasi::with(['assessments', 'catatans', 'assessment1'])
            ->where('user_id', $userId)
            ->when($periodeFilter, fn($q) => $q->whereYear('created_at', $periodeFilter))
            ->when($statusFilter, fn($q) => $q->where('status', $statusFilter))
            ->when($search, function ($query) use ($search) {
                $query->where('nomor_sk', 'like', '%' . $search . '%')
                    ->orWhere('peringkat', 'like', '%' . $search . '%');
            })
            ->orderBy($sortField, $sortAsc ? 'asc' : 'desc')
            ->paginate($perPage);
    }

    public function getAvailableAsesors(): Collection
    {
        return Asesor::with('user')
            ->whereDoesntHave('assessments', function ($query) {
                $query->whereHas('akreditasi', function ($q) {
                    $q->whereNotIn('status', [1, 2]);
                });
            })
            ->get();
    }
}
