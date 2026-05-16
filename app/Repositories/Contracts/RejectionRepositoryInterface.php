<?php

namespace App\Repositories\Contracts;

use App\Models\AkreditasiRejection;
use Illuminate\Support\Collection;

interface RejectionRepositoryInterface
{
    public function create(array $data): AkreditasiRejection;

    public function findActiveByAkreditasi(int $akreditasiId): ?AkreditasiRejection;

    public function getByAkreditasi(int $akreditasiId): Collection;

    public function countByAkreditasi(int $akreditasiId): int;

    public function update(int $id, array $data): bool;
}
