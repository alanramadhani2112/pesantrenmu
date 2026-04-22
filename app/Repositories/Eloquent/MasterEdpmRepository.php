<?php

namespace App\Repositories\Eloquent;

use App\Models\MasterEdpmButir;
use App\Models\MasterEdpmKomponen;
use App\Repositories\Contracts\MasterEdpmRepositoryInterface;
use Illuminate\Support\Collection;

class MasterEdpmRepository implements MasterEdpmRepositoryInterface
{
    public function getAllKomponensWithButirs(): Collection
    {
        return MasterEdpmKomponen::with('butirs')
            ->orderByRaw('COALESCE(ipr, 0) ASC')
            ->orderBy('id', 'ASC')
            ->get();
    }

    public function findKomponen(int $id): ?MasterEdpmKomponen
    {
        return MasterEdpmKomponen::find($id);
    }

    public function createKomponen(array $data): MasterEdpmKomponen
    {
        return MasterEdpmKomponen::create($data);
    }

    public function updateKomponen(int $id, array $data): bool
    {
        $komponen = $this->findKomponen($id);
        return $komponen ? $komponen->update($data) : false;
    }

    public function deleteKomponen(int $id): bool
    {
        $komponen = $this->findKomponen($id);
        return $komponen ? $komponen->delete() : false;
    }

    public function findButir(int $id): ?MasterEdpmButir
    {
        return MasterEdpmButir::find($id);
    }

    public function createButir(array $data): MasterEdpmButir
    {
        return MasterEdpmButir::create($data);
    }

    public function updateButir(int $id, array $data): bool
    {
        $butir = $this->findButir($id);
        return $butir ? $butir->update($data) : false;
    }

    public function deleteButir(int $id): bool
    {
        $butir = $this->findButir($id);
        return $butir ? $butir->delete() : false;
    }
}
