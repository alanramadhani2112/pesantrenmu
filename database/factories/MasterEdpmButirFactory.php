<?php

namespace Database\Factories;

use App\Models\MasterEdpmButir;
use App\Models\MasterEdpmKomponen;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<MasterEdpmButir> */
class MasterEdpmButirFactory extends Factory
{
    protected $model = MasterEdpmButir::class;

    public function definition(): array
    {
        return [
            'komponen_id' => MasterEdpmKomponen::factory(),
            'no_sk' => 'SK-'.$this->faker->numberBetween(1, 99),
            'nomor_butir' => (string) $this->faker->unique()->numberBetween(1, 999),
            'butir_pernyataan' => $this->faker->sentence(),
        ];
    }
}
