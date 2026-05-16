<?php

namespace Tests\Feature;

use App\Models\Akreditasi;
use App\Models\Asesor;
use App\Models\Assessment;
use App\Models\Banding;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Unit tests for the SidebarBadges Livewire component.
 *
 * Validates: Requirements 3.1, 3.2, 3.3, 4.1, 4.2
 */
class SidebarBadgesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    /**
     * Test renders correct counts for Admin role.
     *
     * Validates: Requirements 3.1, 3.2
     */
    public function test_admin_sees_correct_pending_akreditasi_count(): void
    {
        $admin = User::factory()->create(['role_id' => 1]);
        $pesantrenUser = User::factory()->create(['role_id' => 3]);

        // Create Akreditasi records with various statuses
        Akreditasi::create(['user_id' => $pesantrenUser->id, 'uuid' => (string) \Illuminate\Support\Str::uuid(), 'status' => 6]);
        Akreditasi::create(['user_id' => $pesantrenUser->id, 'uuid' => (string) \Illuminate\Support\Str::uuid(), 'status' => 6]);
        Akreditasi::create(['user_id' => $pesantrenUser->id, 'uuid' => (string) \Illuminate\Support\Str::uuid(), 'status' => 6]);
        // Non-pending statuses
        Akreditasi::create(['user_id' => $pesantrenUser->id, 'uuid' => (string) \Illuminate\Support\Str::uuid(), 'status' => 1]);
        Akreditasi::create(['user_id' => $pesantrenUser->id, 'uuid' => (string) \Illuminate\Support\Str::uuid(), 'status' => 4]);

        $this->actingAs($admin);

        $component = Livewire::test('layout.sidebar-badges');

        $component->assertSet('pendingAkreditasiCount', 3);
    }

    /**
     * Test renders correct pending banding count for Admin role.
     *
     * Validates: Requirements 3.1, 3.2
     */
    public function test_admin_sees_correct_pending_banding_count(): void
    {
        $admin = User::factory()->create(['role_id' => 1]);
        $pesantrenUser = User::factory()->create(['role_id' => 3]);

        $akreditasi = Akreditasi::create([
            'user_id' => $pesantrenUser->id,
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'status' => 1,
        ]);

        // Create Banding records with various statuses
        Banding::create(['akreditasi_id' => $akreditasi->id, 'user_id' => $pesantrenUser->id, 'status' => 'pending', 'alasan' => 'Test']);
        Banding::create(['akreditasi_id' => $akreditasi->id, 'user_id' => $pesantrenUser->id, 'status' => 'pending', 'alasan' => 'Test']);
        // Non-pending statuses
        Banding::create(['akreditasi_id' => $akreditasi->id, 'user_id' => $pesantrenUser->id, 'status' => 'under_review', 'alasan' => 'Test']);
        Banding::create(['akreditasi_id' => $akreditasi->id, 'user_id' => $pesantrenUser->id, 'status' => 'accepted', 'alasan' => 'Test']);

        $this->actingAs($admin);

        $component = Livewire::test('layout.sidebar-badges');

        $component->assertSet('pendingBandingCount', 2);
    }

    /**
     * Test renders correct counts for Asesor role.
     *
     * Validates: Requirements 4.1
     */
    public function test_asesor_sees_correct_active_task_count(): void
    {
        $asesorUser = User::factory()->create(['role_id' => 2]);
        $asesor = Asesor::create([
            'user_id' => $asesorUser->id,
            'nama_dengan_gelar' => 'Dr. Test Asesor',
            'nama_tanpa_gelar' => 'Test Asesor',
        ]);

        $pesantrenUser = User::factory()->create(['role_id' => 3]);

        // Create Akreditasi with active statuses (4=Visitasi, 5=Assessment) assigned to asesor
        $akreditasi1 = Akreditasi::create(['user_id' => $pesantrenUser->id, 'uuid' => (string) \Illuminate\Support\Str::uuid(), 'status' => 4]);
        Assessment::create(['akreditasi_id' => $akreditasi1->id, 'asesor_id' => $asesor->id, 'tipe' => 1, 'tanggal_mulai' => now()->toDateString(), 'tanggal_berakhir' => now()->addDays(30)->toDateString()]);

        $akreditasi2 = Akreditasi::create(['user_id' => $pesantrenUser->id, 'uuid' => (string) \Illuminate\Support\Str::uuid(), 'status' => 5]);
        Assessment::create(['akreditasi_id' => $akreditasi2->id, 'asesor_id' => $asesor->id, 'tipe' => 1, 'tanggal_mulai' => now()->toDateString(), 'tanggal_berakhir' => now()->addDays(30)->toDateString()]);

        // Akreditasi with non-active status assigned to asesor (should NOT count)
        $akreditasi3 = Akreditasi::create(['user_id' => $pesantrenUser->id, 'uuid' => (string) \Illuminate\Support\Str::uuid(), 'status' => 1]);
        Assessment::create(['akreditasi_id' => $akreditasi3->id, 'asesor_id' => $asesor->id, 'tipe' => 1, 'tanggal_mulai' => now()->toDateString(), 'tanggal_berakhir' => now()->addDays(30)->toDateString()]);

        // Akreditasi with active status NOT assigned to this asesor (should NOT count)
        $otherAsesorUser = User::factory()->create(['role_id' => 2]);
        $otherAsesor = Asesor::create(['user_id' => $otherAsesorUser->id, 'nama_dengan_gelar' => 'Dr. Other', 'nama_tanpa_gelar' => 'Other']);
        $akreditasi4 = Akreditasi::create(['user_id' => $pesantrenUser->id, 'uuid' => (string) \Illuminate\Support\Str::uuid(), 'status' => 4]);
        Assessment::create(['akreditasi_id' => $akreditasi4->id, 'asesor_id' => $otherAsesor->id, 'tipe' => 1, 'tanggal_mulai' => now()->toDateString(), 'tanggal_berakhir' => now()->addDays(30)->toDateString()]);

        $this->actingAs($asesorUser);

        $component = Livewire::test('layout.sidebar-badges');

        $component->assertSet('activeTaskCount', 2);
    }

    /**
     * Test badge hidden when count is zero for Admin.
     *
     * Validates: Requirements 3.3
     */
    public function test_admin_badges_show_zero_when_no_pending_items(): void
    {
        $admin = User::factory()->create(['role_id' => 1]);
        $pesantrenUser = User::factory()->create(['role_id' => 3]);

        // Create only non-pending records
        Akreditasi::create(['user_id' => $pesantrenUser->id, 'uuid' => (string) \Illuminate\Support\Str::uuid(), 'status' => 1]);
        Akreditasi::create(['user_id' => $pesantrenUser->id, 'uuid' => (string) \Illuminate\Support\Str::uuid(), 'status' => 4]);

        $this->actingAs($admin);

        $component = Livewire::test('layout.sidebar-badges');

        $component->assertSet('pendingAkreditasiCount', 0);
        $component->assertSet('pendingBandingCount', 0);
        $component->assertSeeHtml('data-pending-akreditasi="0"');
        $component->assertSeeHtml('data-pending-banding="0"');
    }

    /**
     * Test badge hidden when count is zero for Asesor.
     *
     * Validates: Requirements 4.2
     */
    public function test_asesor_badge_shows_zero_when_no_active_tasks(): void
    {
        $asesorUser = User::factory()->create(['role_id' => 2]);
        $asesor = Asesor::create([
            'user_id' => $asesorUser->id,
            'nama_dengan_gelar' => 'Dr. Test Asesor',
            'nama_tanpa_gelar' => 'Test Asesor',
        ]);

        $pesantrenUser = User::factory()->create(['role_id' => 3]);

        // Create Akreditasi with non-active status assigned to asesor
        $akreditasi = Akreditasi::create(['user_id' => $pesantrenUser->id, 'uuid' => (string) \Illuminate\Support\Str::uuid(), 'status' => 1]);
        Assessment::create(['akreditasi_id' => $akreditasi->id, 'asesor_id' => $asesor->id, 'tipe' => 1, 'tanggal_mulai' => now()->toDateString(), 'tanggal_berakhir' => now()->addDays(30)->toDateString()]);

        $this->actingAs($asesorUser);

        $component = Livewire::test('layout.sidebar-badges');

        $component->assertSet('activeTaskCount', 0);
        $component->assertSeeHtml('data-active-tasks="0"');
    }

    /**
     * Test asesor without asesor profile gets zero active tasks.
     *
     * Validates: Requirements 4.1, 4.2
     */
    public function test_asesor_without_profile_gets_zero_active_tasks(): void
    {
        // Asesor user without an Asesor model record
        $asesorUser = User::factory()->create(['role_id' => 2]);

        $this->actingAs($asesorUser);

        $component = Livewire::test('layout.sidebar-badges');

        $component->assertSet('activeTaskCount', 0);
    }
}
