<?php

namespace Tests\Feature;

use App\Models\Akreditasi;
use App\Models\Assessment;
use App\Models\Asesor;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AkreditasiTableFilterRegressionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_admin_akreditasi_status_filters_include_terminal_states(): void
    {
        $admin = $this->user(Role::ID_ADMIN, 'Admin Filter');

        foreach ($this->adminStatusCases() as $token => [$status, $visible, $hidden]) {
            $visibleAkreditasi = $this->createAkreditasi($visible, $status);
            $hiddenStatus = $status === Akreditasi::STATUS_PENGAJUAN
                ? Akreditasi::STATUS_SELESAI
                : Akreditasi::STATUS_PENGAJUAN;
            $hiddenAkreditasi = $this->createAkreditasi($hidden, $hiddenStatus);

            $response = $this->actingAs($admin)->get('/admin/akreditasi?statusFilter='.$token);

            $response->assertOk()
                ->assertSee($visible)
                ->assertDontSee($hidden)
                ->assertSee('value="selesai"', false)
                ->assertSee('value="ditolak"', false)
                ->assertSee('value="banding"', false);

            $visibleAkreditasi->delete();
            $hiddenAkreditasi->delete();
        }
    }

    public function test_pesantren_tahapan_filters_match_visible_options(): void
    {
        $pesantren = $this->user(Role::ID_PESANTREN, 'Pesantren Filter');

        $pengajuan = $this->createAkreditasi('Tahap Pengajuan', Akreditasi::STATUS_PENGAJUAN, $pesantren);
        $verifikasi = $this->createAkreditasi('Tahap Verifikasi', Akreditasi::STATUS_VERIFIKASI_BERKAS, $pesantren);
        $visitasi = $this->createAkreditasi('Tahap Visitasi', Akreditasi::STATUS_VISITASI, $pesantren);
        $pasca = $this->createAkreditasi('Tahap Pasca', Akreditasi::STATUS_PASCA_VISITASI, $pesantren);
        $selesai = $this->createAkreditasi('Tahap Selesai', Akreditasi::STATUS_SELESAI, $pesantren);
        $banding = $this->createAkreditasi('Tahap Banding', Akreditasi::STATUS_BANDING, $pesantren);

        $this->actingAs($pesantren)
            ->get('/pesantren/akreditasi?tahapanFilter=pengajuan')
            ->assertOk()
            ->assertSee($this->rowId($pengajuan), false)
            ->assertDontSee($this->rowId($verifikasi), false);

        $this->actingAs($pesantren)
            ->get('/pesantren/akreditasi?tahapanFilter=verifikasi')
            ->assertOk()
            ->assertSee($this->rowId($verifikasi), false)
            ->assertDontSee($this->rowId($pengajuan), false);

        $this->actingAs($pesantren)
            ->get('/pesantren/akreditasi?tahapanFilter=visitasi')
            ->assertOk()
            ->assertSee($this->rowId($visitasi), false)
            ->assertSee($this->rowId($pasca), false)
            ->assertDontSee($this->rowId($selesai), false);

        $this->actingAs($pesantren)
            ->get('/pesantren/akreditasi?tahapanFilter=penilaian')
            ->assertOk()
            ->assertSee($this->rowId($pasca), false)
            ->assertDontSee($this->rowId($visitasi), false);

        $this->actingAs($pesantren)
            ->get('/pesantren/akreditasi?tahapanFilter=hasil')
            ->assertOk()
            ->assertSee($this->rowId($selesai), false)
            ->assertSee($this->rowId($banding), false)
            ->assertDontSee($this->rowId($pengajuan), false);
    }

    public function test_pesantren_hasil_akhir_status_filter_is_token_not_persisted_status(): void
    {
        $pesantren = $this->user(Role::ID_PESANTREN, 'Pesantren Hasil');

        $selesai = $this->createAkreditasi('Hasil Selesai', Akreditasi::STATUS_SELESAI, $pesantren);
        $banding = $this->createAkreditasi('Hasil Banding', Akreditasi::STATUS_BANDING, $pesantren);
        $pengajuan = $this->createAkreditasi('Masih Pengajuan', Akreditasi::STATUS_PENGAJUAN, $pesantren);

        $this->actingAs($pesantren)
            ->get('/pesantren/akreditasi?statusFilter=hasil_akhir')
            ->assertOk()
            ->assertSee($this->rowId($selesai), false)
            ->assertSee($this->rowId($banding), false)
            ->assertDontSee($this->rowId($pengajuan), false);
    }

    public function test_asesor_focus_filter_is_preserved_in_filter_form(): void
    {
        $asesor = $this->assignedAsesor(Akreditasi::STATUS_PASCA_VISITASI, 'Pesantren Fokus');

        $this->actingAs($asesor->user)
            ->get('/asesor/akreditasi?statusFilter=2&focus=nilai')
            ->assertOk()
            ->assertSee('name="focus" value="nilai"', false)
            ->assertSee('data-akreditasi-context="tugas"', false)
            ->assertSee('Pesantren Fokus');
    }

    public function test_table_sort_links_and_per_page_controls_reset_page(): void
    {
        $admin = $this->user(Role::ID_ADMIN, 'Admin Page Reset');
        $this->createAkreditasi('Pesantren Reset', Akreditasi::STATUS_PENGAJUAN);

        $this->actingAs($admin)
            ->get('/admin/akreditasi?statusFilter=pengajuan&page=9')
            ->assertOk()
            ->assertSee('page=1', false)
            ->assertSee("p.value='1'", false);
    }

    /**
     * @return array<string, array{0: int, 1: string, 2: string}>
     */
    private function adminStatusCases(): array
    {
        return [
            'pengajuan' => [Akreditasi::STATUS_PENGAJUAN, 'Admin Pengajuan', 'Admin Verifikasi'],
            'verifikasi' => [Akreditasi::STATUS_VERIFIKASI_BERKAS, 'Admin Verifikasi', 'Admin Assessment'],
            'assessment' => [Akreditasi::STATUS_ASSESSMENT, 'Admin Assessment', 'Admin Visitasi'],
            'visitasi' => [Akreditasi::STATUS_VISITASI, 'Admin Visitasi', 'Admin Validasi'],
            'validasi' => [Akreditasi::STATUS_VALIDASI_ADMIN, 'Admin Validasi', 'Admin Selesai'],
            'selesai' => [Akreditasi::STATUS_SELESAI, 'Admin Selesai', 'Admin Ditolak'],
            'ditolak' => [Akreditasi::STATUS_DITOLAK, 'Admin Ditolak', 'Admin Banding'],
            'banding' => [Akreditasi::STATUS_BANDING, 'Admin Banding', 'Admin Pengajuan Lain'],
        ];
    }

    private function user(int $roleId, string $name): User
    {
        return User::factory()->create([
            'name' => $name,
            'role_id' => $roleId,
        ]);
    }

    private function createAkreditasi(string $name, int $status, ?User $user = null): Akreditasi
    {
        $user ??= $this->user(Role::ID_PESANTREN, $name);
        $user->update(['name' => $name]);

        return Akreditasi::create([
            'user_id' => $user->id,
            'status' => $status,
        ]);
    }

    private function rowId(Akreditasi $akreditasi): string
    {
        return '<td class="fw-semibold">'.$akreditasi->id.'</td>';
    }

    private function assignedAsesor(int $status, string $pesantrenName): Asesor
    {
        $asesorUser = $this->user(Role::ID_ASESOR, 'Asesor Fokus');
        $asesor = Asesor::create([
            'user_id' => $asesorUser->id,
            'nama_dengan_gelar' => 'Dr. Asesor Fokus',
            'nama_tanpa_gelar' => 'Asesor Fokus',
        ]);
        $akreditasi = $this->createAkreditasi($pesantrenName, $status);

        Assessment::create([
            'akreditasi_id' => $akreditasi->id,
            'asesor_id' => $asesor->id,
            'tipe' => 1,
            'tanggal_mulai' => now(),
            'tanggal_berakhir' => now()->addDay(),
        ]);

        return $asesor->load('user');
    }
}
