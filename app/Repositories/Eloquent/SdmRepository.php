<?php

namespace App\Repositories\Eloquent;

use App\Models\SdmPesantren;
use App\Repositories\Contracts\SdmRepositoryInterface;
use Illuminate\Support\Collection;

class SdmRepository implements SdmRepositoryInterface
{
    public function getExistingSdm(int $userId): Collection
    {
        return SdmPesantren::where('user_id', $userId)->get()->keyBy('tingkat');
    }

    public function updateOrUpdateSdm(int $userId, string $tingkat, array $data): SdmPesantren
    {
        return SdmPesantren::updateOrCreate(
            ['user_id' => $userId, 'tingkat' => $tingkat],
            $data
        );
    }
}
