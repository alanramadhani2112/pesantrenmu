<?php

namespace Tests\Feature;

use App\Models\Akreditasi;
use App\Models\Edpm;
use App\Models\Ipm;
use App\Models\MasterEdpmButir;
use App\Models\MasterEdpmKomponen;
use App\Models\Pesantren;
use App\Models\SdmPesantren;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Volt\Volt;
use Tests\TestCase;

class PesantrenAkreditasiDetailResubmissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        Notification::fake();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /**
     * Task 6.6: Rejected akreditasi shows resubmission count and limit
     */
    public function test_rejected_akreditasi_shows_resubmission_count_and_limit(): void
    {
        $user = $this->createCompletePesantrenUser();
        $this->actingAs($user);

        config(['akreditasi.resubmission_limit' => 3]);
        config(['akreditasi.cooling_period_days' => 0]);

        // Create a rejected akreditasi (root, no resubmissions yet)
        $akreditasi = Akreditasi::create([
            'user_id' => $user->id,
            'status' => 2,
            'catatan' => 'Data tidak lengkap',
            'parent' => null,
        ]);

        $component = Volt::test('pages.pesantren.akreditasi-detail', ['uuid' => $akreditasi->uuid])
            ->set('activeTab', 'hasil')
            ->assertSee('Pengajuan Ulang:')
            ->assertSee('0/3')
            ->assertSee('Pengajuan Ditolak');

        // Verify the resubmissionStatus property is loaded
        $this->assertEquals(0, $component->get('resubmissionStatus')['count']);
        $this->assertEquals(3, $component->get('resubmissionStatus')['limit']);
    }

    /**
     * Task 6.7: Button disabled when limit reached, shows correct message
     */
    public function test_button_disabled_when_limit_reached_shows_correct_message(): void
    {
        $user = $this->createCompletePesantrenUser();
        $this->actingAs($user);

        config(['akreditasi.resubmission_limit' => 1]);
        config(['akreditasi.cooling_period_days' => 0]);

        // Create a chain: root -> child1 (1 resubmission = limit)
        $root = Akreditasi::create([
            'user_id' => $user->id,
            'status' => 2,
            'catatan' => 'Ditolak pertama',
            'parent' => null,
        ]);

        $child1 = Akreditasi::create([
            'user_id' => $user->id,
            'status' => 2,
            'catatan' => 'Ditolak kedua',
            'parent' => $root->id,
        ]);

        $component = Volt::test('pages.pesantren.akreditasi-detail', ['uuid' => $child1->uuid])
            ->set('activeTab', 'hasil')
            ->assertSee('1/1')
            ->assertSee('Batas pengajuan ulang telah tercapai');

        // Verify can_resubmit is false
        $this->assertFalse($component->get('resubmissionStatus')['can_resubmit']);
    }

    /**
     * Task 6.8: Button disabled when cooling period active, shows remaining days
     */
    public function test_button_disabled_when_cooling_period_active_shows_remaining_days(): void
    {
        $user = $this->createCompletePesantrenUser();
        $this->actingAs($user);

        config(['akreditasi.resubmission_limit' => 3]);
        config(['akreditasi.cooling_period_days' => 30]);

        $now = Carbon::create(2025, 6, 15)->startOfDay();
        Carbon::setTestNow($now);

        // Create a rejected akreditasi, rejected 10 days ago
        $akreditasi = Akreditasi::create([
            'user_id' => $user->id,
            'status' => 2,
            'catatan' => 'Perlu perbaikan',
            'parent' => null,
        ]);

        // Set updated_at to 10 days ago (rejection date)
        \Illuminate\Support\Facades\DB::table('akreditasis')
            ->where('id', $akreditasi->id)
            ->update(['updated_at' => $now->copy()->subDays(10)]);

        $component = Volt::test('pages.pesantren.akreditasi-detail', ['uuid' => $akreditasi->uuid])
            ->set('activeTab', 'hasil')
            ->assertSee('0/3')
            ->assertSee('20 hari lagi');

        // Verify can_resubmit is false and cooling days are correct
        $this->assertFalse($component->get('resubmissionStatus')['can_resubmit']);
        $this->assertEquals(20, $component->get('resubmissionStatus')['cooling_remaining_days']);
    }

    /**
     * Task 6.9: Button enabled when eligible, clicking creates resubmission
     */
    public function test_button_enabled_when_eligible_clicking_creates_resubmission(): void
    {
        $user = $this->createCompletePesantrenUser();
        $this->actingAs($user);

        config(['akreditasi.resubmission_limit' => 3]);
        config(['akreditasi.cooling_period_days' => 0]);

        // Create a rejected akreditasi with no cooling period
        $akreditasi = Akreditasi::create([
            'user_id' => $user->id,
            'status' => 2,
            'catatan' => 'Perlu perbaikan',
            'parent' => null,
        ]);

        $component = Volt::test('pages.pesantren.akreditasi-detail', ['uuid' => $akreditasi->uuid]);

        // Verify can_resubmit is true
        $this->assertTrue($component->get('resubmissionStatus')['can_resubmit']);

        // Click the resubmit button
        $component->call('resubmit');

        // Verify a new akreditasi was created
        $newAkreditasi = Akreditasi::where('parent', $akreditasi->id)->first();
        $this->assertNotNull($newAkreditasi);
        $this->assertEquals(6, $newAkreditasi->status);
        $this->assertEquals($user->id, $newAkreditasi->user_id);
        $this->assertEquals($akreditasi->id, $newAkreditasi->parent);

        // Verify redirect was dispatched
        $component->assertRedirect(route('pesantren.akreditasi-detail', $newAkreditasi->uuid));
    }

    private function createCompletePesantrenUser(): User
    {
        $user = User::factory()->create(['role_id' => 3]);

        Pesantren::create([
            'user_id' => $user->id,
            'nama_pesantren' => 'Pesantren TDD',
            'is_locked' => false,
        ]);

        Ipm::create([
            'user_id' => $user->id,
            'nsp_file' => 'ipm/nsp.pdf',
            'lulus_santri_file' => 'ipm/lulus.pdf',
            'kurikulum_file' => 'ipm/kurikulum.pdf',
            'buku_ajar_file' => 'ipm/buku-ajar.pdf',
        ]);

        SdmPesantren::create([
            'user_id' => $user->id,
            'tingkat' => 'spm',
        ]);

        $komponen = MasterEdpmKomponen::create(['nama' => 'Standar Isi']);
        $butir = MasterEdpmButir::create([
            'komponen_id' => $komponen->id,
            'no_sk' => '1',
            'nomor_butir' => '1.1',
            'butir_pernyataan' => 'Pesantren memiliki dokumen kurikulum.',
        ]);

        Edpm::create([
            'user_id' => $user->id,
            'butir_id' => $butir->id,
            'isian' => '4',
        ]);

        return $user->refresh();
    }
}
