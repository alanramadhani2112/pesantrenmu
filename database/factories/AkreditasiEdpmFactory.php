<?php

namespace Database\Factories;

use App\Models\Akreditasi;
use App\Models\AkreditasiEdpm;
use App\Models\Asesor;
use App\Models\MasterEdpmButir;
use App\Models\Pesantren;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AkreditasiEdpm> */
class AkreditasiEdpmFactory extends Factory
{
    protected $model = AkreditasiEdpm::class;

    public function definition(): array
    {
        return [
            'akreditasi_id' => Akreditasi::factory(),
            'pesantren_id' => Pesantren::factory(),
            'asesor_id' => Asesor::factory(),
            'butir_id' => MasterEdpmButir::factory(),
            'isian' => 'Jawaban asesor',
            'nk' => 4,
            'nv' => 4,
            'catatan' => null,
            'is_final' => false,
            'delta' => 0,
        ];
    }
}
