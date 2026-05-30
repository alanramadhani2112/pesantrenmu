<?php

namespace App\Repositories\Contracts;

use App\Models\Role;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface RoleRepositoryInterface
{
    public function getPaginated(?string $search, int $perPage, string $sortField, bool $sortAsc): LengthAwarePaginator;

    public function getAll(): Collection;

    public function find(int $id): ?Role;

    public function create(array $data): Role;

    public function update(int $id, array $data): bool;

    public function delete(int $id): bool;
}
