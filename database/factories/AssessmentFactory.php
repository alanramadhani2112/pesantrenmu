<?php

namespace Database\Factories;

use App\Models\Akreditasi;
use App\Models\Asesor;
use App\Models\Assessment;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Assessment> */
class AssessmentFactory extends Factory
{
    protected $model = Assessment::class;

    public function definition(): array
    {
        return [
            'akreditasi_id' => Akreditasi::factory(),
            'asesor_id' => Asesor::factory(),
            'tipe' => 1,
            'tanggal_mulai' => now(),
            'tanggal_berakhir' => now()->addDays(7),
        ];
    }

    public function tipe(int $tipe): static
    {
        return $this->state(fn (array $attributes) => ['tipe' => $tipe]);
    }
}
