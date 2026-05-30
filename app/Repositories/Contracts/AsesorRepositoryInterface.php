<?php

namespace App\Repositories\Contracts;

use App\Models\Asesor;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

interface AsesorRepositoryInterface
{
    public function getPaginatedAsesors(array $filters = [], int $perPage = 10, string $sortField = 'name', bool $sortAsc = true): LengthAwarePaginator;

    public function findByUuid(string $uuid): ?User;

    public function toggleStatus(int $id): bool;

    public function findByUserId(int $userId): ?Asesor;

    public function updateByUserId(int $userId, array $data): bool;

    public function firstOrCreate(array $attributes, array $values = []): Asesor;
}
