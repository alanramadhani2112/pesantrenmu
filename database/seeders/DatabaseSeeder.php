<?php

namespace Database\Seeders;

use App\Models\Akreditasi;
use App\Models\Asesor;
use App\Models\Edpm;
use App\Models\Ipm;
use App\Models\MasterEdpmButir;
use App\Models\Pesantren;
use App\Models\PesantrenUnit;
use App\Models\SdmPesantren;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RoleSeeder::class);

        if (MasterEdpmButir::count() === 0) {
            $this->call(MasterEdpmSeeder::class);
        }

        $this->seedAdmin();
        $this->seedPesantren();
        $this->seedAsesor();
    }

    private function seedAdmin(): User
    {
        return User::updateOrCreate(
            ['email' => 'admin@spm.test'],
            [
                'name' => 'Admin SPM',
                'password' => Hash::make('password'),
                'role_id' => 1,
                'status' => 1,
                'email_verified_at' => now(),
            ]
        );
    }

    private function seedPesantren(): User
    {
        $user = User::updateOrCreate(
            ['email' => 'pesantren@spm.test'],
            [
                'name' => 'Pesantren Demo',
                'password' => Hash::make('password'),
                'role_id' => 3,
                'status' => 1,
                'email_verified_at' => now(),
            ]
        );

        $pesantren = Pesantren::updateOrCreate(
            ['user_id' => $user->id],
            [
                'nama_pesantren' => 'Pesantren Demo Muhammadiyah',
                'ns_pesantren' => 'NSP-DEMO-001',
                'alamat' => 'Jl. Pendidikan No. 1',
                'kota_kabupaten' => 'Yogyakarta',
                'provinsi' => 'DI Yogyakarta',
                'tahun_pendirian' => '2010',
                'nama_mudir' => 'KH. Ahmad Demo',
                'jenjang_pendidikan_mudir' => 'S2',
                'telp_pesantren' => '0274-000000',
                'hp_wa' => '081234567890',
                'email_pesantren' => 'pesantren@spm.test',
                'persyarikatan' => 'Muhammadiyah',
                'visi' => 'Menjadi pesantren unggul dan berkemajuan.',
                'misi' => 'Menyelenggarakan pendidikan pesantren yang bermutu.',
                'layanan_satuan_pendidikan' => ['spm', 'mts', 'ma'],
                'luas_tanah' => '5000',
                'luas_bangunan' => '2500',
                'is_locked' => false,
            ]
        );

        foreach ([
            ['unit' => 'spm', 'jumlah_rombel' => 6],
            ['unit' => 'mts', 'jumlah_rombel' => 9],
            ['unit' => 'ma', 'jumlah_rombel' => 6],
        ] as $unit) {
            PesantrenUnit::updateOrCreate(
                ['pesantren_id' => $pesantren->id, 'unit' => $unit['unit']],
                ['jumlah_rombel' => $unit['jumlah_rombel']]
            );
        }

        Ipm::updateOrCreate(
            ['user_id' => $user->id],
            [
                'nsp_file' => 'demo/ipm/nsp.pdf',
                'lulus_santri_file' => 'demo/ipm/lulus-santri.pdf',
                'kurikulum_file' => 'demo/ipm/kurikulum.pdf',
                'buku_ajar_file' => 'demo/ipm/buku-ajar.pdf',
            ]
        );

        SdmPesantren::updateOrCreate(
            ['user_id' => $user->id, 'tingkat' => 'spm'],
            [
                'santri_l' => 120,
                'santri_p' => 110,
                'ustadz_dirosah_l' => 12,
                'ustadz_dirosah_p' => 8,
                'ustadz_non_dirosah_l' => 5,
                'ustadz_non_dirosah_p' => 4,
                'pamong_l' => 3,
                'pamong_p' => 2,
                'musyrif_l' => 5,
                'musyrif_p' => 5,
                'tendik_l' => 4,
                'tendik_p' => 3,
            ]
        );

        foreach (MasterEdpmButir::query()->pluck('id') as $butirId) {
            Edpm::updateOrCreate(
                ['user_id' => $user->id, 'butir_id' => $butirId],
                ['isian' => '4', 'link' => 'https://example.test/bukti-edpm']
            );
        }

        Akreditasi::firstOrCreate(
            ['user_id' => $user->id, 'parent' => null],
            ['status' => 6]
        );

        return $user;
    }

    private function seedAsesor(): User
    {
        $user = User::updateOrCreate(
            ['email' => 'asesor@spm.test'],
            [
                'name' => 'Asesor Demo',
                'password' => Hash::make('password'),
                'role_id' => 2,
                'status' => 1,
                'email_verified_at' => now(),
            ]
        );

        Asesor::updateOrCreate(
            ['user_id' => $user->id],
            [
                'nama_dengan_gelar' => 'Dr. Asesor Demo, M.Pd.',
                'nama_tanpa_gelar' => 'Asesor Demo',
                'nbm_nia' => 'NBM-DEMO-001',
                'nomor_induk_asesor_pm' => 'APM-DEMO-001',
                'whatsapp' => '081298765432',
                'nik' => '3400000000000001',
                'tempat_lahir' => 'Yogyakarta',
                'tanggal_lahir' => '1985-01-01',
                'unit_kerja' => 'Majelis Dikdasmen PWM',
                'jabatan_utama' => 'Asesor',
                'jenis_kelamin' => 'L',
                'alamat_kantor' => 'Jl. Kantor PWM No. 1',
                'alamat_rumah' => 'Jl. Rumah No. 1',
                'provinsi' => 'DI Yogyakarta',
                'kota_kabupaten' => 'Yogyakarta',
                'status_perkawinan' => 'Menikah',
                'profesi' => 'Dosen',
                'pendidikan_terakhir' => 'S3',
                'telp_kantor' => '0274-111111',
                'tahun_terbit_sertifikat' => '2024',
                'email_pribadi' => 'asesor@spm.test',
                'layanan_satuan_pendidikan' => ['spm', 'mts', 'ma'],
                'riwayat_pendidikan' => [],
                'pengalaman_pelatihan' => [],
                'pengalaman_bekerja' => [],
                'pengalaman_berorganisasi' => [],
                'karya_publikasi' => [],
            ]
        );

        return $user;
    }
}
