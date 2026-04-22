<?php

namespace App\Repositories\Contracts;

interface IpmRepositoryInterface
{
    public function findByUserId(int $userId): ?\App\Models\Ipm;
    public function updateByUserId(int $userId, array $data): bool;
}
