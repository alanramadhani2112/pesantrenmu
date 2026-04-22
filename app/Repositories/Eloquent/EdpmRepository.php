<?php

namespace App\Repositories\Eloquent;

use App\Models\Edpm;
use App\Models\EdpmCatatan;
use App\Models\MasterEdpmKomponen;
use App\Repositories\Contracts\EdpmRepositoryInterface;
use Illuminate\Support\Collection;

class EdpmRepository implements EdpmRepositoryInterface
{
    public function getKomponens(): Collection
    {
        return MasterEdpmKomponen::with('butirs')->orderByRaw('COALESCE(ipr, 0) ASC')->orderBy('id', 'ASC')->get();
    }

    public function getExistingEdpms(int $userId): Collection
    {
        return Edpm::where('user_id', $userId)->get()->keyBy('butir_id');
    }

    public function getExistingCatatans(int $userId): Collection
    {
        return EdpmCatatan::where('user_id', $userId)->get()->pluck('catatan', 'komponen_id');
    }

    public function saveEdpm(array $attributes, array $data): Edpm
    {
        return Edpm::updateOrCreate($attributes, $data);
    }

    public function saveCatatan(array $attributes, array $data): EdpmCatatan
    {
        return EdpmCatatan::updateOrCreate($attributes, $data);
    }
}
