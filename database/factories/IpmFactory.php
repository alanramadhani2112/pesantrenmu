<?php

namespace Database\Factories;

use App\Models\Ipm;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Ipm> */
class IpmFactory extends Factory
{
    protected $model = Ipm::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory()->asPesantren(),
            'nsp_file' => 'test/nsp.pdf',
            'lulus_santri_file' => 'test/lulus-santri.pdf',
            'kurikulum_file' => 'test/kurikulum.pdf',
            'buku_ajar_file' => 'test/buku-ajar.pdf',
        ];
    }
}
