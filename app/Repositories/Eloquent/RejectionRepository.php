<?php

namespace App\Repositories\Eloquent;

use App\Models\AkreditasiRejection;
use App\Repositories\Contracts\RejectionRepositoryInterface;
use Illuminate\Support\Collection;

class RejectionRepository implements RejectionRepositoryInterface
{
    public function create(array $data): AkreditasiRejection
    {
        return AkreditasiRejection::create($data);
    }

    public function findActiveByAkreditasi(int $akreditasiId): ?AkreditasiRejection
    {
        return AkreditasiRejection::where('akreditasi_id', $akreditasiId)
            ->where('type', 'asesor')
            ->where('status', 'pending')
            ->latest()
            ->first();
    }

    public function getByAkreditasi(int $akreditasiId): Collection
    {
        return AkreditasiRejection::where('akreditasi_id', $akreditasiId)
            ->orderBy('created_at', 'asc')
            ->get();
    }

    public function countByAkreditasi(int $akreditasiId): int
    {
        return AkreditasiRejection::where('akreditasi_id', $akreditasiId)
            ->where('type', 'asesor')
            ->count();
    }

    public function update(int $id, array $data): bool
    {
        $rejection = AkreditasiRejection::find($id);

        return $rejection ? $rejection->update($data) : false;
    }
}
