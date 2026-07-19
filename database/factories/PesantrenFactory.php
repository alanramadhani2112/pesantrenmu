<?php

namespace Database\Factories;

use App\Models\Pesantren;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Pesantren> */
class PesantrenFactory extends Factory
{
    protected $model = Pesantren::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory()->asPesantren(),
            'nama_pesantren' => 'Pesantren '.$this->faker->company(),
            'nspp' => $this->faker->unique()->numerify('############'),
            'ns_pesantren' => $this->faker->unique()->numerify('############'),
            'alamat' => $this->faker->address(),
            'kota_kabupaten' => $this->faker->city(),
            'kabupaten' => $this->faker->city(),
            'kabupaten_kode' => $this->faker->numerify('##.##'),
            'kecamatan' => $this->faker->citySuffix(),
            'kelurahan' => $this->faker->streetName(),
            'provinsi' => $this->faker->state(),
            'provinsi_kode' => $this->faker->numerify('##'),
            'tahun_pendirian' => (string) $this->faker->numberBetween(1980, 2020),
            'nama_mudir' => $this->faker->name(),
            'jenjang_pendidikan_mudir' => 'S2',
            'telp_pesantren' => $this->faker->phoneNumber(),
            'hp_wa' => $this->faker->phoneNumber(),
            'email_pesantren' => $this->faker->safeEmail(),
            'persyarikatan' => 'Muhammadiyah',
            'visi' => 'Menjadi pesantren unggul.',
            'misi' => 'Mendidik santri berakhlak.',
            'layanan_satuan_pendidikan' => ['SPM'],
            'luas_tanah' => '1000',
            'luas_bangunan' => '500',
            'is_locked' => false,
        ];
    }

    public function complete(): static
    {
        return $this->state(fn (array $attributes) => [
            'sertifikat_nsp' => 'test/sertifikat-nsp.pdf',
            'dok_profil' => 'test/profil.pdf',
            'dok_nsp' => 'test/nsp.pdf',
            'dok_renstra' => 'test/renstra.pdf',
            'dok_rk_anggaran' => 'test/rk-anggaran.pdf',
            'dok_kurikulum' => 'test/kurikulum.pdf',
            'dok_silabus_rpp' => 'test/silabus-rpp.pdf',
            'dok_kepengasuhan' => 'test/kepengasuhan.pdf',
            'dok_peraturan_kepegawaian' => 'test/kepegawaian.pdf',
            'dok_sarpras' => 'test/sarpras.pdf',
            'dok_laporan_tahunan' => 'test/laporan-tahunan.pdf',
            'dok_sop' => 'test/sop.pdf',
        ]);
    }

    public function locked(): static
    {
        return $this->state(fn (array $attributes) => ['is_locked' => true]);
    }
}
