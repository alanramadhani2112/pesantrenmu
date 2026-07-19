<?php

namespace Database\Factories;

use App\Models\Akreditasi;
use App\Models\Banding;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Banding> */
class BandingFactory extends Factory
{
    protected $model = Banding::class;

    public function definition(): array
    {
        return [
            'akreditasi_id' => Akreditasi::factory(),
            'user_id' => User::factory()->asPesantren(),
            'reviewer_id' => User::factory()->asAdmin(),
            'status' => 'pending',
            'alasan' => 'Memohon peninjauan ulang hasil akreditasi.',
            'review_deadline' => now()->addDays(7),
        ];
    }
}
