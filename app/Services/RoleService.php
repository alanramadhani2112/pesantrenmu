<?php

namespace App\Services;

use App\Models\Role;
use App\Repositories\Contracts\RoleRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class RoleService
{
    protected $roleRepository;

    public function __construct(RoleRepositoryInterface $roleRepository)
    {
        $this->roleRepository = $roleRepository;
    }

    public function getPaginatedRoles(?string $search, int $perPage, string $sortField, bool $sortAsc): LengthAwarePaginator
    {
        return $this->roleRepository->getPaginated($search, $perPage, $sortField, $sortAsc);
    }

    public function getAllRoles(): Collection
    {
        return $this->roleRepository->getAll();
    }

    public function findRole(int $id): ?Role
    {
        return $this->roleRepository->find($id);
    }

    public function saveRole(array $data, ?int $id = null): Role|bool
    {
        if ($id) {
            return $this->roleRepository->update($id, $data);
        }

        return $this->roleRepository->create($data);
    }

    public function deleteRole(int $id): bool
    {
        return $this->roleRepository->delete($id);
    }
}
