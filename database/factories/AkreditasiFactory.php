<?php

namespace Database\Factories;

use App\Models\Akreditasi;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Akreditasi> */
class AkreditasiFactory extends Factory
{
    protected $model = Akreditasi::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory()->asPesantren(),
            'uuid' => (string) Str::uuid(),
            'catatan' => null,
            'status' => Akreditasi::STATUS_PENGAJUAN,
        ];
    }

    public function status(int $status): static
    {
        return $this->state(fn (array $attributes) => ['status' => $status]);
    }

    public function selesai(): static
    {
        return $this->status(Akreditasi::STATUS_SELESAI)->state(fn (array $attributes) => [
            'nilai' => 90,
            'peringkat' => 'A',
            'nomor_sk' => 'SK-'.$this->faker->unique()->numerify('####'),
        ]);
    }
}
