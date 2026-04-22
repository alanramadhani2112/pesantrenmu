<?php

namespace App\Repositories\Contracts;

use Illuminate\Support\Collection;
use App\Models\MasterEdpmKomponen;
use App\Models\MasterEdpmButir;

interface MasterEdpmRepositoryInterface
{
    public function getAllKomponensWithButirs(): Collection;

    public function findKomponen(int $id): ?MasterEdpmKomponen;

    public function createKomponen(array $data): MasterEdpmKomponen;

    public function updateKomponen(int $id, array $data): bool;

    public function deleteKomponen(int $id): bool;

    public function findButir(int $id): ?MasterEdpmButir;

    public function createButir(array $data): MasterEdpmButir;

    public function updateButir(int $id, array $data): bool;

    public function deleteButir(int $id): bool;
}
