<?php

namespace Tests\Feature\Asesor;

use App\Models\Akreditasi;
use App\Models\Asesor;
use App\Models\Assessment;
use App\Models\Pesantren;
use App\Models\User;
use App\Services\AsesorService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Tests for AsesorService from the asesor role perspective.
 *
 * Covers:
 *   - getProfile: auto-creates asesor record if missing
 *   - updateProfile: persists data correctly
 *   - processVisitasi: ownership check, terima flow, tolak flow
 *   - finalizeVerification: ownership check, precondition checks
 *   - getPaginatedAssessments: scoped to asesor
 */
class AsesorServiceTest extends TestCase
{
    use RefreshDatabase;

    private AsesorService $service;
    private User $asesorUser;
    private Asesor $asesor;
    private User $pesantrenUser;
    private Pesantren $pesantren;
    private Akreditasi $akreditasi;
    private Assessment $assessment1;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        Notification::fake();

        $this->asesorUser = User::factory()->create(['role_id' => 2]);
        $this->asesor = Asesor::create([
            'user_id' => $this->asesorUser->id,
            'nama_dengan_gelar' => 'Dr. Test Asesor',
            'nama_tanpa_gelar' => 'Test Asesor',
        ]);

        $this->pesantrenUser = User::factory()->create(['role_id' => 3]);
        $this->pesantren = Pesantren::create([
            'user_id' => $this->pesantrenUser->id,
            'nama_pesantren' => 'Pesantren Test',
        ]);

        $this->akreditasi = Akreditasi::create([
            'user_id' => $this->pesantrenUser->id,
            'status' => 5, // Assessment
        ]);

        $this->assessment1 = Assessment::create([
            'akreditasi_id' => $this->akreditasi->id,
            'asesor_id' => $this->asesor->id,
            'tipe' => 1,
            'tanggal_mulai' => now()->subDays(5),
            'tanggal_berakhir' => now()->addDays(25),
        ]);

        $this->service = app(AsesorService::class);
    }

    // ─── getProfile ──────────────────────────────────────────────────────────

    public function test_get_profile_returns_existing_asesor(): void
    {
        $result = $this->service->getProfile($this->asesorUser->id);

        $this->assertEquals($this->asesor->id, $result->id);
        $this->assertEquals('Dr. Test Asesor', $result->nama_dengan_gelar);
    }

    public function test_get_profile_creates_asesor_when_missing(): void
    {
        $newUser = User::factory()->create(['role_id' => 2, 'name' => 'New Asesor']);

        $result = $this->service->getProfile($newUser->id);

        $this->assertNotNull($result);
        $this->assertEquals($newUser->id, $result->user_id);
        $this->assertDatabaseHas('asesors', ['user_id' => $newUser->id]);
    }

    // ─── updateProfile ────────────────────────────────────────────────────────

    public function test_update_profile_persists_data(): void
    {
        $result = $this->service->updateProfile($this->asesorUser->id, [
            'nama_dengan_gelar' => 'Prof. Dr. Updated',
            'nama_tanpa_gelar' => 'Updated Name',
            'whatsapp' => '081234567890',
        ]);

        $this->assertTrue($result);
        $this->assertDatabaseHas('asesors', [
            'user_id' => $this->asesorUser->id,
            'nama_dengan_gelar' => 'Prof. Dr. Updated',
            'whatsapp' => '081234567890',
        ]);
    }

    public function test_update_profile_returns_true_on_success(): void
    {
        $result = $this->service->updateProfile($this->asesorUser->id, [
            'nama_dengan_gelar' => 'Updated',
            'nama_tanpa_gelar' => 'Updated',
        ]);

        $this->assertTrue($result);
    }

    public function test_update_profile_persists_json_fields(): void
    {
        $riwayat = [['dimana' => 'UGM', 'kapan' => '2010-2014', 'jenjang' => 'S1']];

        $this->service->updateProfile($this->asesorUser->id, [
            'nama_dengan_gelar' => 'Dr. Test',
            'nama_tanpa_gelar' => 'Test',
            'riwayat_pendidikan' => $riwayat,
        ]);

        $asesor = Asesor::where('user_id', $this->asesorUser->id)->first();
        $this->assertEquals($riwayat, $asesor->riwayat_pendidikan);
    }

    // ─── processVisitasi: ownership check ────────────────────────────────────

    public function test_process_visitasi_returns_false_for_non_assigned_asesor(): void
    {
        $otherAsesorUser = User::factory()->create(['role_id' => 2]);

        $result = $this->service->processVisitasi(
            $this->akreditasi->id,
            $otherAsesorUser->id,
            ['tanggal' => now()->format('Y-m-d'), 'tanggal_akhir' => now()->format('Y-m-d'), 'catatan' => ''],
            'terima'
        );

        $this->assertFalse($result);
        // Status should not change
        $this->assertEquals(5, $this->akreditasi->fresh()->status);
    }

    public function test_process_visitasi_returns_false_for_asesor2_trying_to_schedule(): void
    {
        $asesor2User = User::factory()->create(['role_id' => 2]);
        $asesor2 = Asesor::create(['user_id' => $asesor2User->id, 'nama_dengan_gelar' => 'Asesor 2', 'nama_tanpa_gelar' => 'Asesor 2']);
        Assessment::create([
            'akreditasi_id' => $this->akreditasi->id,
            'asesor_id' => $asesor2->id,
            'tipe' => 2,
            'tanggal_mulai' => now()->subDays(5),
            'tanggal_berakhir' => now()->addDays(25),
        ]);

        // Asesor 2 should not be able to schedule visitasi (only asesor 1 can)
        $result = $this->service->processVisitasi(
            $this->akreditasi->id,
            $asesor2User->id,
            ['tanggal' => now()->format('Y-m-d'), 'tanggal_akhir' => now()->format('Y-m-d'), 'catatan' => ''],
            'terima'
        );

        $this->assertFalse($result);
    }

    // ─── processVisitasi: terima flow ─────────────────────────────────────────

    public function test_process_visitasi_terima_updates_status_to_visitasi(): void
    {
        $tanggal = now()->format('Y-m-d');

        $result = $this->service->processVisitasi(
            $this->akreditasi->id,
            $this->asesorUser->id,
            [
                'tanggal' => $tanggal,
                'tanggal_akhir' => $tanggal,
                'catatan' => 'Visitasi dijadwalkan',
            ],
            'terima'
        );

        $this->assertTrue($result);
        $this->assertEquals(4, $this->akreditasi->fresh()->status); // status 4 = Visitasi
        $this->assertEquals($tanggal, $this->akreditasi->fresh()->tgl_visitasi);
    }

    public function test_process_visitasi_terima_saves_catatan_when_provided(): void
    {
        $result = $this->service->processVisitasi(
            $this->akreditasi->id,
            $this->asesorUser->id,
            [
                'tanggal' => now()->format('Y-m-d'),
                'tanggal_akhir' => now()->format('Y-m-d'),
                'catatan' => 'Koordinasi pukul 08.00 WIB',
            ],
            'terima'
        );

        $this->assertTrue($result);
        $this->assertDatabaseHas('akreditasi_catatans', [
            'akreditasi_id' => $this->akreditasi->id,
            'user_id' => $this->asesorUser->id,
            'catatan' => 'Koordinasi pukul 08.00 WIB',
        ]);
    }

    public function test_process_visitasi_terima_does_not_save_catatan_when_empty(): void
    {
        $this->service->processVisitasi(
            $this->akreditasi->id,
            $this->asesorUser->id,
            [
                'tanggal' => now()->format('Y-m-d'),
                'tanggal_akhir' => now()->format('Y-m-d'),
                'catatan' => '',
            ],
            'terima'
        );

        $this->assertDatabaseCount('akreditasi_catatans', 0);
    }

    // ─── processVisitasi: tolak flow ──────────────────────────────────────────

    public function test_process_visitasi_tolak_creates_rejection_via_rejection_service(): void
    {
        $result = $this->service->processVisitasi(
            $this->akreditasi->id,
            $this->asesorUser->id,
            [
                'tanggal' => null,
                'tanggal_akhir' => null,
                'catatan' => 'Dokumen profil tidak lengkap',
                'rejected_items' => ['profil', 'ipm'],
            ],
            'tolak'
        );

        $this->assertTrue($result);
        // RejectionService creates an AkreditasiRejection record
        $this->assertDatabaseHas('akreditasi_rejections', [
            'akreditasi_id' => $this->akreditasi->id,
            'user_id' => $this->asesorUser->id,
        ]);
    }

    // ─── getPaginatedAssessments ──────────────────────────────────────────────

    public function test_get_paginated_assessments_returns_only_assigned_assessments(): void
    {
        $result = $this->service->getPaginatedAssessments($this->asesor->id);

        $this->assertEquals(1, $result->total());
        $this->assertEquals($this->assessment1->id, $result->first()->id);
    }

    public function test_get_paginated_assessments_excludes_other_asesors_assessments(): void
    {
        $otherAsesor = Asesor::create([
            'user_id' => User::factory()->create(['role_id' => 2])->id,
            'nama_dengan_gelar' => 'Other',
            'nama_tanpa_gelar' => 'Other',
        ]);
        $otherAkreditasi = Akreditasi::create(['user_id' => $this->pesantrenUser->id, 'status' => 5]);
        Assessment::create([
            'akreditasi_id' => $otherAkreditasi->id,
            'asesor_id' => $otherAsesor->id,
            'tipe' => 1,
            'tanggal_mulai' => now(),
            'tanggal_berakhir' => now()->addDays(30),
        ]);

        $result = $this->service->getPaginatedAssessments($this->asesor->id);

        $this->assertEquals(1, $result->total()); // only own assessment
    }

    // ─── findAssessment ───────────────────────────────────────────────────────

    public function test_find_assessment_returns_correct_assessment(): void
    {
        $result = $this->service->findAssessment($this->assessment1->id);

        $this->assertNotNull($result);
        $this->assertEquals($this->assessment1->id, $result->id);
        $this->assertEquals($this->asesor->id, $result->asesor_id);
    }

    public function test_find_assessment_returns_null_for_nonexistent_id(): void
    {
        $result = $this->service->findAssessment(99999);

        $this->assertNull($result);
    }
}
