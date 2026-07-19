<?php

namespace Database\Factories;

use App\Models\Asesor;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Asesor> */
class AsesorFactory extends Factory
{
    protected $model = Asesor::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory()->asAsesor(),
            'nama_dengan_gelar' => $this->faker->name().' M.Pd.',
            'nama_tanpa_gelar' => $this->faker->name(),
            'nbm_nia' => $this->faker->unique()->numerify('########'),
            'nomor_induk_asesor_pm' => $this->faker->unique()->numerify('A####'),
            'whatsapp' => $this->faker->phoneNumber(),
            'nik' => $this->faker->unique()->numerify('################'),
            'tempat_lahir' => $this->faker->city(),
            'tanggal_lahir' => $this->faker->date(),
            'unit_kerja' => 'Pesantren Muhammadiyah',
            'jabatan_utama' => 'Asesor',
            'jenis_kelamin' => 'L',
            'alamat_kantor' => $this->faker->address(),
            'alamat_rumah' => $this->faker->address(),
            'email_pribadi' => $this->faker->safeEmail(),
            'layanan_satuan_pendidikan' => ['SPM'],
            'rombel_spm' => 1,
            'luas_tanah' => '1000',
            'luas_bangunan' => '500',
            'riwayat_pendidikan' => [],
            'pengalaman_pelatihan' => [],
            'pengalaman_bekerja' => [],
            'pengalaman_berorganisasi' => [],
            'karya_publikasi' => [],
        ];
    }
}
