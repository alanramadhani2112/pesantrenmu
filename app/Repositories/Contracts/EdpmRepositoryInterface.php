<?php

namespace App\Repositories\Contracts;

use Illuminate\Support\Collection;

interface EdpmRepositoryInterface
{
    public function getKomponens(): Collection;
    public function getExistingEdpms(int $userId): Collection;
    public function getExistingCatatans(int $userId): Collection;
    public function saveEdpm(array $attributes, array $data): \App\Models\Edpm;
    public function saveCatatan(array $attributes, array $data): \App\Models\EdpmCatatan;
}
