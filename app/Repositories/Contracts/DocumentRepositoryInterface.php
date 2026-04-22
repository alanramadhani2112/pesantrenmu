<?php

namespace App\Repositories\Contracts;

use App\Models\Document;
use Illuminate\Pagination\LengthAwarePaginator;

interface DocumentRepositoryInterface
{
    public function getPaginatedDocuments(?string $search = null, int $perPage = 10, string $sortField = 'created_at', bool $sortAsc = false): LengthAwarePaginator;

    public function find(int $id): ?Document;

    public function create(array $data): Document;

    public function update(int $id, array $data): bool;

    public function delete(int $id): bool;
}
