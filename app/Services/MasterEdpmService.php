<?php

namespace App\Services;

use App\Repositories\Contracts\MasterEdpmRepositoryInterface;
use Illuminate\Support\Collection;
use App\Models\MasterEdpmKomponen;
use App\Models\MasterEdpmButir;

class MasterEdpmService
{
    protected $masterEdpmRepository;

    public function __construct(MasterEdpmRepositoryInterface $masterEdpmRepository)
    {
        $this->masterEdpmRepository = $masterEdpmRepository;
    }

    public function getKomponensData(): Collection
    {
        return $this->masterEdpmRepository->getAllKomponensWithButirs();
    }

    public function saveKomponen(array $data, ?int $id = null): void
    {
        if ($id) {
            $this->masterEdpmRepository->updateKomponen($id, $data);
        } else {
            $this->masterEdpmRepository->createKomponen($data);
        }
    }

    public function deleteKomponen(int $id): bool
    {
        return $this->masterEdpmRepository->deleteKomponen($id);
    }

    public function saveButir(array $data, ?int $id = null): void
    {
        if ($id) {
            $this->masterEdpmRepository->updateButir($id, $data);
        } else {
            $this->masterEdpmRepository->createButir($data);
        }
    }

    public function deleteButir(int $id): bool
    {
        return $this->masterEdpmRepository->deleteButir($id);
    }

    public function findKomponen(int $id): ?MasterEdpmKomponen
    {
        return $this->masterEdpmRepository->findKomponen($id);
    }

    public function findButir(int $id): ?MasterEdpmButir
    {
        return $this->masterEdpmRepository->findButir($id);
    }
}
