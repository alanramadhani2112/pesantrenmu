<?php

namespace App\Services;

use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Support\Facades\Hash;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Models\User;

class UserService
{
    protected $userRepository;

    public function __construct(UserRepositoryInterface $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function getPaginatedAccounts(int $roleId, ?string $search = null, int $perPage = 10, string $sortField = 'id', bool $sortAsc = false): LengthAwarePaginator
    {
        return $this->userRepository->getPaginatedByCategory($roleId, $search, $perPage, $sortField, $sortAsc);
    }

    public function getCountByRole(int $roleId): int
    {
        return $this->userRepository->getCountByRole($roleId);
    }

    public function findUser(int $id): ?User
    {
        return $this->userRepository->find($id);
    }

    public function saveAccount(array $data, ?int $id = null): void
    {
        $payload = [
            'name' => $data['name'],
            'email' => $data['email'],
            'role_id' => $data['role_id'],
            'status' => $data['status'] ? 1 : 0,
        ];

        if (!empty($data['password'])) {
            $payload['password'] = Hash::make($data['password']);
        }

        // Persist sso_sync_role when editing
        if (isset($data['sso_sync_role'])) {
            $payload['sso_sync_role'] = (bool) $data['sso_sync_role'];
        }

        if ($id) {
            $this->userRepository->update($id, $payload);
        } else {
            $user = $this->userRepository->create($payload);

            // Auto-create asesor profile when role is Asesor (role_id = 2)
            // so the user immediately appears in asesor assignment dropdowns.
            if ((int) $data['role_id'] === 2) {
                \App\Models\Asesor::firstOrCreate(
                    ['user_id' => $user->id],
                    [
                        'nama_dengan_gelar' => $data['name'],
                        'nama_tanpa_gelar'  => $data['name'],
                    ]
                );
            }
        }
    }

    public function deleteAccount(int $id): bool
    {
        if ($id == auth()->id()) {
            return false;
        }
        return $this->userRepository->delete($id);
    }

    public function toggleAccountStatus(int $id): bool
    {
        return $this->userRepository->toggleStatus($id);
    }
}
