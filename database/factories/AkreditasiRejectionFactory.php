<?php

namespace Database\Factories;

use App\Models\Akreditasi;
use App\Models\AkreditasiRejection;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AkreditasiRejection> */
class AkreditasiRejectionFactory extends Factory
{
    protected $model = AkreditasiRejection::class;

    public function definition(): array
    {
        return [
            'akreditasi_id' => Akreditasi::factory(),
            'user_id' => User::factory()->asAdmin(),
            'type' => 'admin_verifikasi',
            'items' => ['dokumen'],
            'categories' => ['profil'],
            'explanation' => 'Dokumen perlu diperbaiki.',
            'rejection_number' => 'RJ-'.$this->faker->unique()->numerify('####'),
            'perbaikan_deadline' => now()->addDays(7),
            'status' => 'active',
        ];
    }
}
