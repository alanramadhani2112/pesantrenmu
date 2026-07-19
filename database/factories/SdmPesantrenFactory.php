<?php

namespace Database\Factories;

use App\Models\PesantrenUnit;
use App\Models\SdmPesantren;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<SdmPesantren> */
class SdmPesantrenFactory extends Factory
{
    protected $model = SdmPesantren::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory()->asPesantren(),
            'pesantren_unit_id' => PesantrenUnit::factory(),
            'tingkat' => 'SPM',
            'santri_l' => 10,
            'santri_p' => 10,
            'ustadz_dirosah_l' => 2,
            'ustadz_dirosah_p' => 2,
            'ustadz_non_dirosah_l' => 1,
            'ustadz_non_dirosah_p' => 1,
            'pamong_l' => 1,
            'pamong_p' => 1,
            'musyrif_l' => 1,
            'musyrif_p' => 1,
            'tendik_l' => 1,
            'tendik_p' => 1,
        ];
    }
}
