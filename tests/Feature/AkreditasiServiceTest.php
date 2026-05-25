<?php

namespace Tests\Feature;

use App\Models\Akreditasi;
use App\Models\Asesor;
use App\Models\Pesantren;
use App\Models\User;
use App\Services\AkreditasiService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Coverage: AkreditasiService — getPaginatedAkreditasis, getStatusCounts,
 * findAkreditasi, findAkreditasiById, deleteAkreditasi, getAvailableAsesors.
 */
class AkreditasiServiceTest extends TestCase
{
    use RefreshDatabase;

    protected AkreditasiService $service;
    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->admin = User::factory()->create(['role_id' => 1]);
        $this->actingAs($this->admin);
        $this->service = app(AkreditasiService::class);
    }

    private function makePesantrenUser(): User
    {
        $user = User::factory()->create(['role_id' => 3]);
        Pesantren::create(['user_id' => $user->id, 'nama_pesantren' => 'Pesantren Test']);
        return $user;
    }

    private function makeAkreditasi(User $user, int $status = 6): Akreditasi
    {
        return Akreditasi::create(['user_id' => $user->id, 'status' => $status]);
    }

    // ─── getPaginatedAkreditasis ──────────────────────────────────────────────

    public function test_get_paginated_returns_paginator(): void
    {
        $user = $this->makePesantrenUser();
        $this->makeAkreditasi($user, 6);

        $result = $this->service->getPaginatedAkreditasis('pengajuan');

        $this->assertSame(1, $result->total());
    }

    public function test_get_paginated_filters_by_status(): void
    {
        $user = $this->makePesantrenUser();
        $this->makeAkreditasi($user, 6);
        $this->makeAkreditasi($user, 5);

        $pengajuan = $this->service->getPaginatedAkreditasis('pengajuan');
        $this->assertSame(1, $pengajuan->total());

        $all = $this->service->getPaginatedAkreditasis('all');
        $this->assertSame(2, $all->total());
    }

    public function test_get_paginated_searches_by_pesantren_name(): void
    {
        $user1 = $this->makePesantrenUser();
        $user1->pesantren->update(['nama_pesantren' => 'Al-Hidayah Bandung']);
        $this->makeAkreditasi($user1, 6);

        $user2 = $this->makePesantrenUser();
        $user2->pesantren->update(['nama_pesantren' => 'An-Nur Surabaya']);
        $this->makeAkreditasi($user2, 6);

        $result = $this->service->getPaginatedAkreditasis('all', 'Bandung');
        $this->assertSame(1, $result->total());
    }

    // ─── getStatusCounts ─────────────────────────────────────────────────────

    public function test_get_status_counts_returns_all_keys(): void
    {
        $counts = $this->service->getStatusCounts();

        $this->assertArrayHasKey('pengajuan', $counts);
        $this->assertArrayHasKey('verifikasi', $counts);
        $this->assertArrayHasKey('assessment', $counts);
        $this->assertArrayHasKey('visitasi', $counts);
        $this->assertArrayHasKey('validasi', $counts);
        $this->assertArrayHasKey('overdue', $counts);
    }

    public function test_get_status_counts_reflects_actual_data(): void
    {
        $user = $this->makePesantrenUser();
        $this->makeAkreditasi($user, 6); // pengajuan
        $this->makeAkreditasi($user, 5); // verifikasi

        $counts = $this->service->getStatusCounts();

        $this->assertSame(1, $counts['pengajuan']);
        $this->assertSame(1, $counts['verifikasi']);
    }

    // ─── findAkreditasi / findAkreditasiById ─────────────────────────────────

    public function test_find_akreditasi_by_uuid_returns_model(): void
    {
        $user = $this->makePesantrenUser();
        $akreditasi = $this->makeAkreditasi($user);

        $found = $this->service->findAkreditasi($akreditasi->uuid);

        $this->assertNotNull($found);
        $this->assertSame($akreditasi->id, $found->id);
    }

    public function test_find_akreditasi_by_uuid_returns_null_for_unknown(): void
    {
        $result = $this->service->findAkreditasi('non-existent-uuid');
        $this->assertNull($result);
    }

    public function test_find_akreditasi_by_id_returns_model(): void
    {
        $user = $this->makePesantrenUser();
        $akreditasi = $this->makeAkreditasi($user);

        $found = $this->service->findAkreditasiById($akreditasi->id);

        $this->assertNotNull($found);
        $this->assertSame($akreditasi->id, $found->id);
    }

    // ─── deleteAkreditasi ────────────────────────────────────────────────────

    public function test_delete_akreditasi_soft_deletes_record(): void
    {
        $user = $this->makePesantrenUser();
        $akreditasi = $this->makeAkreditasi($user, 6);

        $result = $this->service->deleteAkreditasi($akreditasi->id);

        $this->assertTrue($result);
        $this->assertSoftDeleted('akreditasis', ['id' => $akreditasi->id]);
    }

    public function test_delete_akreditasi_returns_false_for_status_selesai(): void
    {
        $user = $this->makePesantrenUser();
        $akreditasi = $this->makeAkreditasi($user, 0); // Selesai

        $result = $this->service->deleteAkreditasi($akreditasi->id);

        $this->assertFalse($result);
        $this->assertDatabaseHas('akreditasis', ['id' => $akreditasi->id, 'deleted_at' => null]);
    }

    public function test_delete_akreditasi_force_deletes_selesai(): void
    {
        $user = $this->makePesantrenUser();
        $akreditasi = $this->makeAkreditasi($user, 0);

        $result = $this->service->deleteAkreditasi($akreditasi->id, force: true);

        $this->assertTrue($result);
        $this->assertSoftDeleted('akreditasis', ['id' => $akreditasi->id]);
    }

    // ─── getAvailableAsesors ─────────────────────────────────────────────────

    public function test_get_available_asesors_returns_unassigned_asesors(): void
    {
        $asesorUser = User::factory()->create(['role_id' => 2]);
        Asesor::create([
            'user_id' => $asesorUser->id,
            'nama_dengan_gelar' => 'Asesor Test',
            'nama_tanpa_gelar' => 'Asesor Test',
        ]);

        $result = $this->service->getAvailableAsesors();

        $this->assertGreaterThanOrEqual(1, $result->count());
    }

    // ─── rejectPengajuan ─────────────────────────────────────────────────────

    public function test_reject_pengajuan_throws_domain_exception(): void
    {
        $this->expectException(\DomainException::class);
        $this->service->rejectPengajuan(1, 'alasan');
    }
}
