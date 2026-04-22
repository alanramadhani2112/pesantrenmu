<?php

namespace App\Repositories\Contracts;

use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

interface UserRepositoryInterface
{
    public function getPaginatedByCategory(int $roleId, ?string $search = null, int $perPage = 10, string $sortField = 'id', bool $sortAsc = false): LengthAwarePaginator;

    public function getCountByRole(int $roleId): int;

    public function find(int $id): ?User;

    public function create(array $data): User;

    public function update(int $id, array $data): bool;

    public function delete(int $id): bool;

    public function toggleStatus(int $id): bool;
}
