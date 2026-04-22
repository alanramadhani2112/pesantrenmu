<?php

namespace App\Repositories\Eloquent;

use App\Models\Ipm;
use App\Repositories\Contracts\IpmRepositoryInterface;

class IpmRepository implements IpmRepositoryInterface
{
    public function findByUserId(int $userId): ?Ipm
    {
        return Ipm::where('user_id', $userId)->first();
    }

    public function updateByUserId(int $userId, array $data): bool
    {
        return Ipm::updateOrCreate(['user_id' => $userId], $data)->exists();
    }
}
