<?php

namespace App\Repositories\Eloquent;

use App\Models\Akreditasi;
use App\Models\Asesor;
use App\Models\User;
use App\Repositories\Contracts\AsesorRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class AsesorRepository implements AsesorRepositoryInterface
{
    public function getPaginatedAsesors(array $filters = [], int $perPage = 10, string $sortField = 'name', bool $sortAsc = true): LengthAwarePaginator
    {
        $query = User::where('role_id', 2)
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where('name', 'like', '%'.$search.'%');
            })
            ->when(($filters['status'] ?? '') !== '', function ($query) use ($filters) {
                $query->where('status', $filters['status']);
            })
            ->when($filters['peran'] ?? null, function ($query, $peran) {
                $query->whereHas('asesor.assessments', function ($q) use ($peran) {
                    $q->where('tipe', $peran);
                });
            })
            ->when($filters['penugasan'] ?? null, function ($query, $penugasan) {
                if ($penugasan === 'bertugas') {
                    $query->whereHas('asesor.assessments.akreditasi', function ($q) {
                        $q->whereIn('status', Akreditasi::activeStatuses());
                    });
                } elseif ($penugasan === 'bebas') {
                    $query->whereDoesntHave('asesor.assessments', function ($q) {
                        $q->whereHas('akreditasi', function ($sq) {
                            $sq->whereIn('status', Akreditasi::activeStatuses());
                        });
                    });
                }
            });

        return $query->with([
            'asesor:id,user_id,nama_dengan_gelar',
            'asesor.assessments' => function ($query) {
                $query
                    ->latest()
                    ->with([
                        'akreditasi:id,user_id,status,uuid',
                        'akreditasi.user:id,name',
                        'akreditasi.user.pesantren:id,user_id,nama_pesantren',
                    ]);
            },
        ])
            ->orderBy($sortField, $sortAsc ? 'asc' : 'desc')
            ->paginate($perPage);
    }

    public function findByUuid(string $uuid): ?User
    {
        return User::where('uuid', $uuid)->with('asesor')->first();
    }

    public function toggleStatus(int $id): bool
    {
        $user = User::find($id);
        if ($user) {
            $user->status = $user->status == 1 ? 0 : 1;

            return $user->save();
        }

        return false;
    }

    public function findByUserId(int $userId): ?Asesor
    {
        return Asesor::where('user_id', $userId)->first();
    }

    public function updateByUserId(int $userId, array $data): bool
    {
        $asesor = $this->findByUserId($userId);
        if ($asesor) {
            return $asesor->update($data);
        }

        return false;
    }

    public function firstOrCreate(array $attributes, array $values = []): Asesor
    {
        return Asesor::firstOrCreate($attributes, $values);
    }
}
