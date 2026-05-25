<?php

namespace Tests\Feature;

use App\Exports\PesantrenExport;
use App\Models\Akreditasi;
use App\Models\Asesor;
use App\Models\Assessment;
use App\Models\Pesantren;
use App\Models\User;
use App\Repositories\Eloquent\AsesorRepository;
use App\Repositories\Eloquent\PesantrenRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BusinessStatusMappingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('roles')->insert([
            ['id' => 1, 'name' => 'Admin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => 'Asesor', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'Pesantren', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function test_pesantren_repository_uses_lp2m_canonical_status_groups(): void
    {
        $completedUser = $this->createPesantrenWithAkreditasi('Pesantren Selesai', 0);
        $rejectedUser = $this->createPesantrenWithAkreditasi('Pesantren Ditolak', -1);
        $validasiUser = $this->createPesantrenWithAkreditasi('Pesantren Validasi', 1);
        $pascaVisitasiUser = $this->createPesantrenWithAkreditasi('Pesantren Pasca', 2);

        $repository = app(PesantrenRepository::class);

        $terakreditasiIds = $repository
            ->getPaginatedPesantrens(filterAkreditasi: 'terakreditasi', perPage: 20)
            ->getCollection()
            ->pluck('id')
            ->all();

        $ditolakIds = $repository
            ->getPaginatedPesantrens(filterAkreditasi: 'ditolak', perPage: 20)
            ->getCollection()
            ->pluck('id')
            ->all();

        $prosesIds = $repository
            ->getPaginatedPesantrens(filterAkreditasi: 'proses', perPage: 20)
            ->getCollection()
            ->pluck('id')
            ->all();

        $this->assertSame([$completedUser->id], $terakreditasiIds);
        $this->assertSame([$rejectedUser->id], $ditolakIds);
        $this->assertContains($validasiUser->id, $prosesIds);
        $this->assertContains($pascaVisitasiUser->id, $prosesIds);
    }

    public function test_pesantren_active_count_treats_status_1_and_2_as_active_workflow(): void
    {
        $user = $this->createPesantrenUser('Pesantren Aktif');

        Akreditasi::create(['user_id' => $user->id, 'status' => 1]);
        Akreditasi::create(['user_id' => $user->id, 'status' => 2]);
        Akreditasi::create(['user_id' => $user->id, 'status' => 0]);
        Akreditasi::create(['user_id' => $user->id, 'status' => -1]);

        $row = app(PesantrenRepository::class)
            ->getPaginatedPesantrens(perPage: 20)
            ->getCollection()
            ->firstWhere('id', $user->id);

        $this->assertSame(2, (int) $row->akreditasi_aktif_count);
    }

    public function test_asesor_repository_treats_status_1_and_2_as_active_assignment(): void
    {
        $busyUser = $this->createAsesorWithAssignment('Asesor Aktif', 2);
        $freeUser = $this->createAsesorWithAssignment('Asesor Selesai', 0);

        $repository = app(AsesorRepository::class);

        $busyIds = $repository
            ->getPaginatedAsesors(['penugasan' => 'bertugas'], 20)
            ->getCollection()
            ->pluck('id')
            ->all();

        $freeIds = $repository
            ->getPaginatedAsesors(['penugasan' => 'bebas'], 20)
            ->getCollection()
            ->pluck('id')
            ->all();

        $this->assertContains($busyUser->id, $busyIds);
        $this->assertNotContains($busyUser->id, $freeIds);
        $this->assertContains($freeUser->id, $freeIds);
    }

    public function test_pesantren_export_uses_canonical_finished_and_rejected_labels(): void
    {
        $completedUser = $this->createPesantrenWithAkreditasi('Export Selesai', 0);
        $rejectedUser = $this->createPesantrenWithAkreditasi('Export Ditolak', -1);
        $activeUser = $this->createPesantrenWithAkreditasi('Export Validasi', 1);

        $export = new PesantrenExport();

        $this->assertSame('Terakreditasi', $export->map($completedUser->load(['pesantren', 'akreditasis']))[4]);
        $this->assertSame('Ditolak', $export->map($rejectedUser->load(['pesantren', 'akreditasis']))[4]);
        $this->assertSame('Proses', $export->map($activeUser->load(['pesantren', 'akreditasis']))[4]);
    }

    private function createPesantrenWithAkreditasi(string $name, int $status): User
    {
        $user = $this->createPesantrenUser($name);
        Akreditasi::create(['user_id' => $user->id, 'status' => $status]);

        return $user;
    }

    private function createPesantrenUser(string $name): User
    {
        $user = User::factory()->create([
            'name' => $name,
            'email' => str($name)->slug()->append('@example.test')->toString(),
            'role_id' => 3,
        ]);

        Pesantren::create([
            'user_id' => $user->id,
            'nama_pesantren' => $name,
            'ns_pesantren' => 'NSP-' . $user->id,
        ]);

        return $user;
    }

    private function createAsesorWithAssignment(string $name, int $akreditasiStatus): User
    {
        $user = User::factory()->create([
            'name' => $name,
            'email' => str($name)->slug()->append('@example.test')->toString(),
            'role_id' => 2,
        ]);

        $asesor = Asesor::create([
            'user_id' => $user->id,
            'nama_dengan_gelar' => $name,
            'nama_tanpa_gelar' => $name,
        ]);

        $pesantren = $this->createPesantrenWithAkreditasi('Tugas ' . $name, $akreditasiStatus);
        $akreditasi = $pesantren->akreditasis()->latest()->first();

        Assessment::create([
            'akreditasi_id' => $akreditasi->id,
            'asesor_id' => $asesor->id,
            'tipe' => 1,
            'tanggal_mulai' => now(),
            'tanggal_berakhir' => now()->addDays(30),
        ]);

        return $user;
    }
}
