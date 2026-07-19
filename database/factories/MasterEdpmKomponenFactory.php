<?php

namespace Database\Factories;

use App\Models\MasterEdpmKomponen;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<MasterEdpmKomponen> */
class MasterEdpmKomponenFactory extends Factory
{
    protected $model = MasterEdpmKomponen::class;

    public function definition(): array
    {
        return [
            'nama' => 'Komponen '.$this->faker->unique()->word(),
            'ipr' => $this->faker->numberBetween(1, 4),
        ];
    }
}
