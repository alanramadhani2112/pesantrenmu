<?php

namespace Tests\Feature\BusinessFlow;

use App\Models\Akreditasi;
use App\Models\AkreditasiEdpm;
use App\Models\AkreditasiEdpmCatatan;
use App\Models\Asesor;
use App\Models\Assessment;
use App\Models\Edpm;
use App\Models\Ipm;
use App\Models\MasterEdpmButir;
use App\Models\MasterEdpmKomponen;
use App\Models\Pesantren;
use App\Models\PesantrenUnit;
use App\Models\SdmPesantren;
use App\Models\User;
use Database\Seeders\BusinessFlowTestSeeder;
use Database\Seeders\DocumentCategorySeeder;
use Database\Seeders\MasterEdpmSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Notification;

trait BusinessFlowTestHelpers
{
    protected function seedBusinessFlowBase(): void
    {
        Notification::fake();
        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);
        $this->seed(RolePermissionSeeder::class);
        $this->seed(MasterEdpmSeeder::class);
        $this->seed(DocumentCategorySeeder::class);
    }

    protected function seedBusinessFlowFixtures(): void
    {
        Notification::fake();
        $this->seed(BusinessFlowTestSeeder::class);
    }

    protected function bfUser(string $email): User
    {
        return User::where('email', $email)->firstOrFail();
    }

    protected function createUser(string $email, int $roleId, string $name = 'BF User', int $status = 1): User
    {
        return User::factory()->create([
            'name' => $name,
            'email' => $email,
            'role_id' => $roleId,
            'status' => $status,
            'email_verified_at' => now(),
        ]);
    }

    protected function createAsesorUser(string $email, string $name = 'BF Asesor', int $status = 1): User
    {
        $user = $this->createUser($email, 2, $name, $status);
        Asesor::create([
            'user_id' => $user->id,
            'nama_dengan_gelar' => $name.', S.Pd.',
            'nama_tanpa_gelar' => $name,
            'layanan_satuan_pendidikan' => ['spm'],
        ]);

        return $user;
    }

    protected function createCompletePesantrenUser(string $email = 'bf.dynamic.pesantren@test.local'): User
    {
        $user = $this->createUser($email, 3, 'BF Dynamic Pesantren');
        $this->completePesantrenData($user);

        return $user;
    }

    protected function completePesantrenData(User $user): void
    {
        $pesantren = Pesantren::create([
            'user_id' => $user->id,
            'nama_pesantren' => 'BF Pesantren '.$user->id,
            'ns_pesantren' => 'BF-NSP-'.$user->id,
            'alamat' => 'Jl. BF '.$user->id,
            'provinsi_kode' => '34',
            'kota_kabupaten' => 'Yogyakarta',
            'tahun_pendirian' => '2010',
            'nama_mudir' => 'Mudir BF',
            'layanan_satuan_pendidikan' => ['spm'],
            'is_locked' => false,
        ]);

        PesantrenUnit::create(['pesantren_id' => $pesantren->id, 'unit' => 'spm', 'jumlah_rombel' => 3]);

        Ipm::create([
            'user_id' => $user->id,
            'nsp_file' => 'bf/ipm/nsp.pdf',
            'lulus_santri_file' => 'bf/ipm/lulus.pdf',
            'kurikulum_file' => 'bf/ipm/kurikulum.pdf',
            'buku_ajar_file' => 'bf/ipm/buku-ajar.pdf',
        ]);

        SdmPesantren::create([
            'user_id' => $user->id,
            'tingkat' => 'spm',
            'santri_l' => 10,
            'santri_p' => 12,
            'ustadz_dirosah_l' => 2,
            'ustadz_dirosah_p' => 2,
        ]);

        foreach (MasterEdpmButir::pluck('id') as $butirId) {
            Edpm::create(['user_id' => $user->id, 'butir_id' => $butirId, 'isian' => '3']);
        }
    }

    protected function createIncompletePesantrenUser(string $email = 'bf.incomplete.dynamic@test.local'): User
    {
        $user = $this->createUser($email, 3, 'BF Incomplete Pesantren');
        Pesantren::create(['user_id' => $user->id, 'nama_pesantren' => 'Incomplete BF', 'is_locked' => false]);

        return $user;
    }

    protected function assignAsesors(Akreditasi $akreditasi, User $asesor1User, User $asesor2User): void
    {
        $asesor1 = Asesor::where('user_id', $asesor1User->id)->firstOrFail();
        $asesor2 = Asesor::where('user_id', $asesor2User->id)->firstOrFail();

        Assessment::create(['akreditasi_id' => $akreditasi->id, 'asesor_id' => $asesor1->id, 'tipe' => 1, 'tanggal_mulai' => now(), 'tanggal_berakhir' => now()->addDays(30)]);
        Assessment::create(['akreditasi_id' => $akreditasi->id, 'asesor_id' => $asesor2->id, 'tipe' => 2, 'tanggal_mulai' => now(), 'tanggal_berakhir' => now()->addDays(30)]);
    }

    protected function seedCompleteScoring(Akreditasi $akreditasi, User $asesor1User, User $asesor2User, bool $withNv = false): void
    {
        $asesor1 = Asesor::where('user_id', $asesor1User->id)->firstOrFail();
        $asesor2 = Asesor::where('user_id', $asesor2User->id)->firstOrFail();

        foreach (MasterEdpmButir::all() as $butir) {
            AkreditasiEdpm::create([
                'akreditasi_id' => $akreditasi->id,
                'pesantren_id' => $akreditasi->user_id,
                'asesor_id' => $asesor1->id,
                'butir_id' => $butir->id,
                'isian' => 3,
                'nk' => 3,
                'nv' => $withNv ? 3 : null,
                'catatan' => 'Catatan BF',
                'is_final' => true,
                'delta' => 0,
            ]);

            AkreditasiEdpm::create([
                'akreditasi_id' => $akreditasi->id,
                'pesantren_id' => $akreditasi->user_id,
                'asesor_id' => $asesor2->id,
                'butir_id' => $butir->id,
                'isian' => 3,
                'is_final' => true,
            ]);
        }

        foreach (MasterEdpmKomponen::whereNull('ipr')->take(4)->get() as $komponen) {
            AkreditasiEdpmCatatan::create([
                'akreditasi_id' => $akreditasi->id,
                'pesantren_id' => $akreditasi->user_id,
                'asesor_id' => $asesor1->id,
                'komponen_id' => $komponen->id,
                'catatan' => 'Catatan rekomendasi BF',
                'rekomendasi' => 'Rekomendasi BF',
            ]);
        }
    }

    protected function createAkreditasi(User $pesantren, int $status, string $code): Akreditasi
    {
        return Akreditasi::create([
            'user_id' => $pesantren->id,
            'status' => $status,
            'catatan' => "[$code] dynamic test scenario",
        ]);
    }

    protected function assertNoStatusChange(Akreditasi $akreditasi, int $oldStatus): void
    {
        $this->assertSame($oldStatus, (int) $akreditasi->fresh()->status);
    }

    protected function assertNoTransitionAudit(Akreditasi $akreditasi, int $toStatus): void
    {
        $this->assertDatabaseMissing('akreditasi_audit_logs', [
            'akreditasi_id' => $akreditasi->id,
            'action_type' => 'status_changed',
            'new_value' => (string) $toStatus,
        ]);
    }
}
