<?php

namespace App\Repositories\Contracts;

use App\Models\Akreditasi;
use App\Models\AkreditasiCatatan;
use App\Models\AkreditasiEdpm;
use App\Models\AkreditasiEdpmCatatan;
use App\Models\Assessment;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface AkreditasiRepositoryInterface
{
    public function getPaginatedAkreditasis(string $statusFilter, ?string $search = null, int $perPage = 10, string $sortField = 'created_at', bool $sortAsc = false): LengthAwarePaginator;

    public function getCountByStatus(int|array $status): int;

    public function findByUuid(string $uuid, array $relations = []): ?Akreditasi;

    public function find(int $id, array $relations = []): ?Akreditasi;

    public function delete(int $id): bool;

    public function update(int $id, array $data): bool;

    public function getAssessmentsByAsesor(int $asesorId, ?string $search = null, ?string $periodeFilter = null, ?string $statusFilter = null, int $perPage = 10, string $sortField = 'id', bool $sortAsc = false): LengthAwarePaginator;

    public function findAssessment(int $id, array $relations = []): ?Assessment;

    public function addCatatan(array $data): AkreditasiCatatan;

    public function getEdpmData(int $akreditasiId, ?int $asesorId = null): Collection;

    public function getEdpmCatatans(int $akreditasiId, ?int $asesorId = null): Collection;

    public function saveEdpmEvaluation(array $attributes, array $data): AkreditasiEdpm;

    public function saveEdpmCatatan(array $attributes, array $data): AkreditasiEdpmCatatan;

    public function getPaginatedByUserId(int $userId, ?string $search = null, ?string $periodeFilter = null, ?string $statusFilter = null, int $perPage = 10, string $sortField = 'created_at', bool $sortAsc = false): LengthAwarePaginator;

    public function latestForUser(int $userId): ?Akreditasi;

    public function getAvailableAsesors(): Collection;
}
