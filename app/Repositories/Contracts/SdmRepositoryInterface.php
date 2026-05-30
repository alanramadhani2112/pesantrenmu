<?php

namespace App\Repositories\Contracts;

use App\Models\SdmPesantren;
use Illuminate\Support\Collection;

interface SdmRepositoryInterface
{
    public function getExistingSdm(int $userId): Collection;

    public function updateOrUpdateSdm(int $userId, string $tingkat, array $data): SdmPesantren;
}
