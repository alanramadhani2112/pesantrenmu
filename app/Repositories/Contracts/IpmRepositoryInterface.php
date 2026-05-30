<?php

namespace App\Repositories\Contracts;

use App\Models\Ipm;

interface IpmRepositoryInterface
{
    public function findByUserId(int $userId): ?Ipm;

    public function updateByUserId(int $userId, array $data): bool;
}
