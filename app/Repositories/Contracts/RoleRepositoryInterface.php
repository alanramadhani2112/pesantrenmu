<?php

namespace App\Repositories\Contracts;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface RoleRepositoryInterface
{
    public function getPaginated(?string $search, int $perPage, string $sortField, bool $sortAsc): LengthAwarePaginator;
    public function getAll(): Collection;
    public function find(int $id): ?\App\Models\Role;
    public function create(array $data): \App\Models\Role;
    public function update(int $id, array $data): bool;
    public function delete(int $id): bool;
}
