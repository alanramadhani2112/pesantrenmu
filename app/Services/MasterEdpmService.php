<?php

namespace App\Services;

use App\Repositories\Contracts\MasterEdpmRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
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
        return Cache::remember('master_edpm_komponens_butirs', now()->addHours(24), function () {
            return $this->masterEdpmRepository->getAllKomponensWithButirs();
        });
    }

    /**
     * Bust the master data cache after any mutation.
     */
    public function flushCache(): void
    {
        Cache::forget('master_edpm_komponens_butirs');
    }

    public function saveKomponen(array $data, ?int $id = null): void
    {
        if ($id) {
            $this->masterEdpmRepository->updateKomponen($id, $data);
        } else {
            $this->masterEdpmRepository->createKomponen($data);
        }
        $this->flushCache();
    }

    public function deleteKomponen(int $id): bool
    {
        $result = $this->masterEdpmRepository->deleteKomponen($id);
        $this->flushCache();
        return $result;
    }

    public function saveButir(array $data, ?int $id = null): void
    {
        if ($id) {
            $this->masterEdpmRepository->updateButir($id, $data);
        } else {
            $this->masterEdpmRepository->createButir($data);
        }
        $this->flushCache();
    }

    public function deleteButir(int $id): bool
    {
        $result = $this->masterEdpmRepository->deleteButir($id);
        $this->flushCache();
        return $result;
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
