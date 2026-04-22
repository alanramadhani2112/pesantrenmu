<?php

namespace App\Repositories\Contracts;

use App\Models\User;
use App\Models\Pesantren;
use Illuminate\Pagination\LengthAwarePaginator;

interface PesantrenRepositoryInterface
{
    public function getPaginatedPesantrens(?string $search = null, ?string $filterStatus = '', ?string $filterAkreditasi = '', int $perPage = 10, string $sortField = 'name', bool $sortAsc = true): LengthAwarePaginator;

    public function findUserByUuid(string $uuid, array $relations = []): ?User;

    public function findPesantren(int $id): ?Pesantren;

    public function updatePesantren(int $id, array $data): bool;

    public function findByUserId(int $userId): ?\App\Models\Pesantren;

    public function updateByUserId(int $userId, array $data): bool;
}
