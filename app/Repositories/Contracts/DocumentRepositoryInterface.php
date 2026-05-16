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

    /**
     * Active documents visible to the given role, optionally filtered by category slug.
     *
     * @param  string|null  $role  one of 'admin', 'asesor', 'pesantren', null
     * @param  string|null  $categorySlug  the document_categories.slug, or 'all' / null for unfiltered
     */
    public function getActiveForRole(
        ?string $role,
        ?string $categorySlug = null,
        ?string $search = null,
        int $perPage = 10
    ): LengthAwarePaginator;
}
