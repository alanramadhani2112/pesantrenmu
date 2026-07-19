<?php

namespace Database\Factories;

use App\Models\Edpm;
use App\Models\MasterEdpmButir;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Edpm> */
class EdpmFactory extends Factory
{
    protected $model = Edpm::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory()->asPesantren(),
            'butir_id' => MasterEdpmButir::factory(),
            'isian' => 'Jawaban EDPM test',
            'link' => 'https://example.test/bukti',
        ];
    }
}
