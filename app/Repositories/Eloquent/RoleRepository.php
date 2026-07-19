<?php

namespace App\Repositories\Eloquent;

use App\Models\Role;
use App\Repositories\Contracts\RoleRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class RoleRepository implements RoleRepositoryInterface
{
    public function getPaginated(?string $search, int $perPage, string $sortField, bool $sortAsc): LengthAwarePaginator
    {
        return Role::query()
            ->when($search, function ($query) use ($search) {
                $query->where('name', 'like', '%'.$search.'%')
                    ->orWhere('parameter', 'like', '%'.$search.'%');
            })
            ->orderBy($sortField, $sortAsc ? 'asc' : 'desc')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function getAll(): Collection
    {
        return Role::all();
    }

    public function find(int $id): ?Role
    {
        return Role::find($id);
    }

    public function create(array $data): Role
    {
        return Role::create($data);
    }

    public function update(int $id, array $data): bool
    {
        $role = $this->find($id);

        return $role ? $role->update($data) : false;
    }

    public function delete(int $id): bool
    {
        $role = $this->find($id);

        return $role ? $role->delete() : false;
    }
}
