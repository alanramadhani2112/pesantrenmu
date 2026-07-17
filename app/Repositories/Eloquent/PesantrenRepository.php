<?php

namespace App\Repositories\Eloquent;

use App\Models\Akreditasi;
use App\Models\Pesantren;
use App\Models\User;
use App\Repositories\Contracts\PesantrenRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class PesantrenRepository implements PesantrenRepositoryInterface
{
    public function getPaginatedPesantrens(?string $search = null, ?string $filterStatus = '', ?string $filterAkreditasi = '', int $perPage = 10, string $sortField = 'name', bool $sortAsc = true): LengthAwarePaginator
    {
        $perPage = min(max($perPage, 5), 50);
        $sortField = in_array($sortField, ['name', 'email', 'status', 'created_at', 'id'], true) ? $sortField : 'name';

        return User::where('role_id', 3)
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%')
                        ->orWhereHas('pesantren', function ($pq) use ($search) {
                            $pq->where('nama_pesantren', 'like', '%'.$search.'%')
                                ->orWhere('ns_pesantren', 'like', '%'.$search.'%');
                        });
                });
            })
            ->when($filterStatus !== '', function ($query) use ($filterStatus) {
                $query->where('status', $filterStatus);
            })
            ->when($filterAkreditasi, function ($query) use ($filterAkreditasi) {
                if ($filterAkreditasi === 'belum') {
                    $query->whereDoesntHave('akreditasis');
                } elseif ($filterAkreditasi === 'proses') {
                    $query->whereHas('akreditasis', function ($q) {
                        $q->whereIn('status', Akreditasi::activeStatuses());
                    });
                } elseif ($filterAkreditasi === 'terakreditasi') {
                    $query->whereHas('akreditasis', function ($q) {
                        $q->where('status', Akreditasi::STATUS_SELESAI);
                    });
                } elseif ($filterAkreditasi === 'ditolak') {
                    $query->whereHas('akreditasis', function ($q) {
                        $q->where('status', Akreditasi::STATUS_DITOLAK);
                    });
                }
            })
            ->with(['pesantren:id,user_id,nama_pesantren,is_locked'])
            ->withCount([
                'akreditasis as akreditasi_aktif_count' => fn ($q) => $q->whereIn('status', Akreditasi::activeStatuses()),
            ])
            ->orderBy($sortField, $sortAsc ? 'asc' : 'desc')
            ->paginate($perPage);
    }

    public function findUserByUuid(string $uuid, array $relations = []): ?User
    {
        return User::where('uuid', $uuid)->with($relations)->first();
    }

    public function findPesantren(int $id): ?Pesantren
    {
        return Pesantren::find($id);
    }

    public function updatePesantren(int $id, array $data): bool
    {
        $pesantren = Pesantren::find($id);

        return $pesantren ? $pesantren->update($data) : false;
    }

    public function findByUserId(int $userId): ?Pesantren
    {
        return Pesantren::where('user_id', $userId)->first();
    }

    public function updateByUserId(int $userId, array $data): bool
    {
        return Pesantren::updateOrCreate(['user_id' => $userId], $data)->exists();
    }
}
