<?php

namespace Database\Seeders;

use App\Models\Akreditasi;
use App\Models\AkreditasiEdpm;
use App\Models\AkreditasiEdpmCatatan;
use App\Models\Asesor;
use App\Models\Assessment;
use App\Models\Banding;
use App\Models\Edpm;
use App\Models\Ipm;
use App\Models\MasterEdpmButir;
use App\Models\MasterEdpmKomponen;
use App\Models\Pesantren;
use App\Models\PesantrenUnit;
use App\Models\Role;
use App\Models\SdmPesantren;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class BusinessFlowTestSeeder extends Seeder
{
    public const PASSWORD = 'password';

    public function run(): void
    {
        $this->call(RoleSeeder::class);
        $this->call(PermissionSeeder::class);
        $this->call(RolePermissionSeeder::class);

        if (MasterEdpmButir::count() < 62) {
            $this->call(MasterEdpmSeeder::class);
        }

        $this->call(DocumentCategorySeeder::class);

        $users = $this->seedUsers();
        $this->seedPesantrenData($users['pesantren'], complete: true, locked: false, name: 'BF Pesantren Utama');
        $this->seedPesantrenData($users['other_pesantren'], complete: true, locked: false, name: 'BF Pesantren Lain');
        $this->seedPesantrenData($users['incomplete_pesantren'], complete: false, locked: false, name: 'BF Pesantren Belum Lengkap');

        $asesor1 = Asesor::where('user_id', $users['asesor1']->id)->firstOrFail();
        $asesor2 = Asesor::where('user_id', $users['asesor2']->id)->firstOrFail();

        $this->seedScenarioAkreditasi($users['pesantren'], $asesor1, $asesor2);
        $this->seedOtherTenantScenario($users['other_pesantren']);
    }

    /** @return array<string, User> */
    private function seedUsers(): array
    {
        return [
            'super_admin' => $this->user('BF Super Admin', 'bf.superadmin@test.local', Role::ID_SUPER_ADMIN),
            'admin' => $this->user('BF Admin', 'bf.admin@test.local', Role::ID_ADMIN),
            'pesantren' => $this->user('BF Pesantren', 'bf.pesantren@test.local', Role::ID_PESANTREN),
            'other_pesantren' => $this->user('BF Pesantren Other', 'bf.pesantren.other@test.local', Role::ID_PESANTREN),
            'incomplete_pesantren' => $this->user('BF Pesantren Incomplete', 'bf.pesantren.incomplete@test.local', Role::ID_PESANTREN),
            'asesor1' => $this->asesorUser('BF Asesor 1', 'bf.asesor1@test.local', active: true),
            'asesor2' => $this->asesorUser('BF Asesor 2', 'bf.asesor2@test.local', active: true),
            'unassigned_asesor' => $this->asesorUser('BF Asesor Unassigned', 'bf.asesor.unassigned@test.local', active: true),
            'inactive_asesor' => $this->asesorUser('BF Asesor Inactive', 'bf.asesor.inactive@test.local', active: false),
        ];
    }

    private function user(string $name, string $email, int $roleId, bool $active = true): User
    {
        return User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make(self::PASSWORD),
                'role_id' => $roleId,
                'status' => $active ? 1 : 0,
                'email_verified_at' => now(),
            ]
        );
    }

    private function asesorUser(string $name, string $email, bool $active): User
    {
        $user = $this->user($name, $email, Role::ID_ASESOR, $active);

        Asesor::updateOrCreate(
            ['user_id' => $user->id],
            [
                'nama_dengan_gelar' => $name.', S.Pd.',
                'nama_tanpa_gelar' => $name,
                'nbm_nia' => 'BF-'.substr(md5($email), 0, 8),
                'layanan_satuan_pendidikan' => ['spm'],
            ]
        );

        return $user;
    }

    private function seedPesantrenData(User $user, bool $complete, bool $locked, string $name): void
    {
        $pesantren = Pesantren::updateOrCreate(
            ['user_id' => $user->id],
            [
                'nama_pesantren' => $name,
                'ns_pesantren' => $complete ? 'BF-NSP-'.$user->id : null,
                'alamat' => $complete ? 'Jl. Business Flow '.$user->id : null,
                'provinsi' => $complete ? 'DI Yogyakarta' : null,
                'kota_kabupaten' => $complete ? 'Yogyakarta' : null,
                'tahun_pendirian' => $complete ? '2010' : null,
                'nama_mudir' => $complete ? 'Mudir BF' : null,
                'layanan_satuan_pendidikan' => $complete ? ['spm'] : [],
                'is_locked' => $locked,
            ]
        );

        PesantrenUnit::updateOrCreate(
            ['pesantren_id' => $pesantren->id, 'unit' => 'spm'],
            ['jumlah_rombel' => 3]
        );

        if (! $complete) {
            return;
        }

        Ipm::updateOrCreate(
            ['user_id' => $user->id],
            [
                'nsp_file' => 'bf/ipm/nsp.pdf',
                'lulus_santri_file' => 'bf/ipm/lulus.pdf',
                'kurikulum_file' => 'bf/ipm/kurikulum.pdf',
                'buku_ajar_file' => 'bf/ipm/buku-ajar.pdf',
            ]
        );

        SdmPesantren::updateOrCreate(
            ['user_id' => $user->id, 'tingkat' => 'spm'],
            [
                'santri_l' => 10,
                'santri_p' => 12,
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
            ]
        );

        foreach (MasterEdpmButir::pluck('id') as $butirId) {
            Edpm::updateOrCreate(
                ['user_id' => $user->id, 'butir_id' => $butirId],
                ['isian' => '3', 'link' => 'https://example.test/bf-edpm']
            );
        }
    }

    private function seedScenarioAkreditasi(User $pesantren, Asesor $asesor1, Asesor $asesor2): void
    {
        foreach ([
            'BF-HAPPY-001' => 6,
            'BF-HAPPY-002' => 5,
            'BF-HAPPY-003' => 4,
            'BF-HAPPY-004' => 3,
            'BF-HAPPY-005' => 2,
            'BF-HAPPY-006' => 1,
            'BF-HAPPY-007' => 0,
            'BF-NEG-003' => 4,
            'BF-NEG-004' => 0,
            'BF-NEG-005' => -1,
            'BF-NEG-006' => -1,
            'BF-NEG-007' => 4,
            'BF-NEG-008' => 5,
            'BF-NEG-009' => 1,
            'BF-NEG-010' => 5,
            'BF-BANDING-001' => -1,
            'BF-BANDING-002' => -2,
        ] as $code => $status) {
            $akreditasi = Akreditasi::updateOrCreate(
                ['catatan' => "[$code] seeded business flow scenario"],
                [
                    'user_id' => $pesantren->id,
                    'status' => $status,
                    'tgl_visitasi' => in_array($status, [3, 2, 1, 0], true) ? now()->subDay()->toDateString() : null,
                    'tgl_visitasi_akhir' => in_array($status, [3, 2, 1, 0], true) ? now()->addDay()->toDateString() : null,
                    'visitasi_confirmed_at' => in_array($status, [2, 1, 0], true) ? now() : null,
                    'laporan_visitasi_asesor1' => in_array($status, [2, 1, 0], true) ? 'bf/laporan/asesor1.pdf' : null,
                    'laporan_visitasi_asesor2' => in_array($status, [2, 1, 0], true) ? 'bf/laporan/asesor2.pdf' : null,
                    'laporan_visitasi_kelompok' => in_array($status, [2, 1, 0], true) ? 'bf/laporan/kelompok.pdf' : null,
                    'kartu_kendali' => in_array($status, [2, 1, 0], true) ? 'bf/kartu/kendali.pdf' : null,
                    'is_nilai_asesor_final' => in_array($status, [1, 0], true),
                    'is_nilai_asesor2_final' => in_array($status, [1, 0], true),
                    'is_nv_final' => $status === 0,
                    'nomor_sk' => $status === 0 ? 'BF/SK/001' : null,
                    'sertifikat_path' => $status === 0 ? 'bf/sertifikat.pdf' : null,
                    'masa_berlaku' => $status === 0 ? now()->toDateString() : null,
                    'masa_berlaku_akhir' => $status === 0 ? now()->addYears(5)->toDateString() : null,
                ]
            );

            if (in_array($status, [4, 3, 2, 1, 0, -1, -2], true)) {
                $this->seedAssessments($akreditasi, $asesor1, $asesor2);
            }

            if (in_array($status, [1, 0], true) || in_array($code, ['BF-NEG-009'], true)) {
                $this->seedFinalScoring($akreditasi, $pesantren, $asesor1, $asesor2, nv: $status === 0);
            }

            if ($code === 'BF-NEG-006') {
                Banding::updateOrCreate(
                    ['akreditasi_id' => $akreditasi->id],
                    ['user_id' => $pesantren->id, 'status' => 'rejected', 'alasan' => str_repeat('Banding sudah ada. ', 4)]
                );
            }

            if ($code === 'BF-BANDING-002') {
                Banding::updateOrCreate(
                    ['akreditasi_id' => $akreditasi->id],
                    ['user_id' => $pesantren->id, 'status' => 'pending', 'alasan' => str_repeat('Alasan banding valid. ', 4)]
                );
            }
        }
    }

    private function seedOtherTenantScenario(User $other): void
    {
        Akreditasi::updateOrCreate(
            ['catatan' => '[BF-NEG-002] other tenant akreditasi'],
            ['user_id' => $other->id, 'status' => 4]
        );
    }

    private function seedAssessments(Akreditasi $akreditasi, Asesor $asesor1, Asesor $asesor2): void
    {
        foreach ([[$asesor1->id, 1], [$asesor2->id, 2]] as [$asesorId, $tipe]) {
            Assessment::updateOrCreate(
                ['akreditasi_id' => $akreditasi->id, 'asesor_id' => $asesorId, 'tipe' => $tipe],
                ['tanggal_mulai' => now()->subDay(), 'tanggal_berakhir' => now()->addDays(30)]
            );
        }
    }

    private function seedFinalScoring(Akreditasi $akreditasi, User $pesantren, Asesor $asesor1, Asesor $asesor2, bool $nv): void
    {
        foreach (MasterEdpmButir::all() as $butir) {
            AkreditasiEdpm::updateOrCreate(
                ['akreditasi_id' => $akreditasi->id, 'asesor_id' => $asesor1->id, 'butir_id' => $butir->id],
                [
                    'pesantren_id' => $pesantren->id,
                    'isian' => 3,
                    'nk' => 3,
                    'nv' => $nv ? 3 : null,
                    'catatan' => 'Catatan BF',
                    'is_final' => true,
                    'delta' => 0,
                ]
            );

            AkreditasiEdpm::updateOrCreate(
                ['akreditasi_id' => $akreditasi->id, 'asesor_id' => $asesor2->id, 'butir_id' => $butir->id],
                [
                    'pesantren_id' => $pesantren->id,
                    'isian' => 3,
                    'is_final' => true,
                ]
            );
        }

        foreach (MasterEdpmKomponen::whereNull('ipr')->take(4)->get() as $komponen) {
            AkreditasiEdpmCatatan::updateOrCreate(
                ['akreditasi_id' => $akreditasi->id, 'asesor_id' => $asesor1->id, 'komponen_id' => $komponen->id],
                ['pesantren_id' => $pesantren->id, 'catatan' => 'Catatan rekomendasi BF', 'rekomendasi' => 'Rekomendasi BF']
            );
        }
    }
}
