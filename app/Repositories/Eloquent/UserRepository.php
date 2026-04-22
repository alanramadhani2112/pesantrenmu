<?php

namespace App\Repositories\Eloquent;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class UserRepository implements UserRepositoryInterface
{
    public function getPaginatedByCategory(int $roleId, ?string $search = null, int $perPage = 10, string $sortField = 'id', bool $sortAsc = false): LengthAwarePaginator
    {
        return User::with('role')
            ->where('role_id', $roleId)
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', '%' . $search . '%')
                        ->orWhere('email', 'like', '%' . $search . '%');
                });
            })
            ->orderBy($sortField, $sortAsc ? 'asc' : 'desc')
            ->paginate($perPage);
    }

    public function getCountByRole(int $roleId): int
    {
        return User::where('role_id', $roleId)->count();
    }

    public function find(int $id): ?User
    {
        return User::find($id);
    }

    public function create(array $data): User
    {
        return User::create($data);
    }

    public function update(int $id, array $data): bool
    {
        $user = $this->find($id);
        if ($user) {
            return $user->update($data);
        }
        return false;
    }

    public function delete(int $id): bool
    {
        $user = $this->find($id);
        if ($user) {
            return $user->delete();
        }
        return false;
    }

    public function toggleStatus(int $id): bool
    {
        $user = $this->find($id);
        if ($user) {
            $user->status = $user->status == 1 ? 0 : 1;
            return $user->save();
        }
        return false;
    }
}
