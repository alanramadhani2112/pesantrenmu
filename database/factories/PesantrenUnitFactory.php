<?php

namespace Database\Factories;

use App\Models\Pesantren;
use App\Models\PesantrenUnit;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PesantrenUnit> */
class PesantrenUnitFactory extends Factory
{
    protected $model = PesantrenUnit::class;

    public function definition(): array
    {
        return [
            'pesantren_id' => Pesantren::factory(),
            'unit' => 'SPM',
            'jumlah_rombel' => 1,
        ];
    }
}
