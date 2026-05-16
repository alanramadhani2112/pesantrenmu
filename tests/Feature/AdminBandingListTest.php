<?php

namespace Tests\Feature;

use App\Models\Akreditasi;
use App\Models\Banding;
use App\Models\Pesantren;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class AdminBandingListTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    private function createPesantrenUser(string $pesantrenName = 'Pesantren Test'): User
    {
        $user = User::factory()->create(['role_id' => 3]);
        Pesantren::create([
            'user_id' => $user->id,
            'nama_pesantren' => $pesantrenName,
        ]);
        return $user;
    }

    /**
     * Task 8.7: Admin banding list renders with correct data and filters work
     */
    public function test_admin_banding_list_renders_with_correct_data(): void
    {
        $admin = User::factory()->create(['role_id' => 1]);
        $pesantrenUser = $this->createPesantrenUser('Pesantren Al-Hikmah');

        $akreditasi = Akreditasi::create([
            'user_id' => $pesantrenUser->id,
            'status' => 3,
        ]);

        $banding = Banding::create([
            'akreditasi_id' => $akreditasi->id,
            'user_id' => $pesantrenUser->id,
            'status' => 'pending',
            'alasan' => 'Saya tidak setuju dengan hasil penilaian yang diberikan.',
        ]);

        $this->actingAs($admin);

        $component = Volt::test('pages.admin.banding')
            ->assertSee('Pesantren Al-Hikmah')
            ->assertSee('Saya tidak setuju dengan hasil penilaian yang dibe...')
            ->assertSee('Pending');
    }

    /**
     * Task 8.7: Status filter works correctly
     */
    public function test_admin_banding_list_status_filter_works(): void
    {
        $admin = User::factory()->create(['role_id' => 1]);
        $reviewer = User::factory()->create(['role_id' => 1]);

        $pesantrenUser1 = $this->createPesantrenUser('Pesantren Pending');
        $pesantrenUser2 = $this->createPesantrenUser('Pesantren Review');

        $akreditasi1 = Akreditasi::create([
            'user_id' => $pesantrenUser1->id,
            'status' => 3,
        ]);

        $akreditasi2 = Akreditasi::create([
            'user_id' => $pesantrenUser2->id,
            'status' => 3,
        ]);

        Banding::create([
            'akreditasi_id' => $akreditasi1->id,
            'user_id' => $pesantrenUser1->id,
            'status' => 'pending',
            'alasan' => 'Alasan banding pending.',
        ]);

        Banding::create([
            'akreditasi_id' => $akreditasi2->id,
            'user_id' => $pesantrenUser2->id,
            'reviewer_id' => $reviewer->id,
            'status' => 'under_review',
            'alasan' => 'Alasan banding under review.',
            'review_deadline' => now()->addDays(14),
        ]);

        $this->actingAs($admin);

        // Filter by pending - should show only pending banding
        $component = Volt::test('pages.admin.banding')
            ->set('statusFilter', 'pending')
            ->assertSee('Pesantren Pending')
            ->assertDontSee('Pesantren Review');

        // Filter by under_review - should show only under_review banding
        $component->set('statusFilter', 'under_review')
            ->assertSee('Pesantren Review')
            ->assertDontSee('Pesantren Pending');

        // Filter by all - should show both
        $component->set('statusFilter', 'all')
            ->assertSee('Pesantren Pending')
            ->assertSee('Pesantren Review');
    }

    /**
     * Task 8.7: Search filter works correctly
     */
    public function test_admin_banding_list_search_works(): void
    {
        $admin = User::factory()->create(['role_id' => 1]);

        $pesantrenUser1 = $this->createPesantrenUser('Pesantren Al-Falah');
        $pesantrenUser2 = $this->createPesantrenUser('Pesantren Darussalam');

        $akreditasi1 = Akreditasi::create([
            'user_id' => $pesantrenUser1->id,
            'status' => 3,
        ]);

        $akreditasi2 = Akreditasi::create([
            'user_id' => $pesantrenUser2->id,
            'status' => 3,
        ]);

        Banding::create([
            'akreditasi_id' => $akreditasi1->id,
            'user_id' => $pesantrenUser1->id,
            'status' => 'pending',
            'alasan' => 'Alasan banding 1.',
        ]);

        Banding::create([
            'akreditasi_id' => $akreditasi2->id,
            'user_id' => $pesantrenUser2->id,
            'status' => 'pending',
            'alasan' => 'Alasan banding 2.',
        ]);

        $this->actingAs($admin);

        // Search for Al-Falah
        $component = Volt::test('pages.admin.banding')
            ->set('search', 'Al-Falah')
            ->assertSee('Pesantren Al-Falah')
            ->assertDontSee('Pesantren Darussalam');

        // Search for Darussalam
        $component->set('search', 'Darussalam')
            ->assertSee('Pesantren Darussalam')
            ->assertDontSee('Pesantren Al-Falah');
    }

    /**
     * Task 8.8: Overdue bandings show red highlight indicator
     */
    public function test_overdue_bandings_show_red_highlight_indicator(): void
    {
        $admin = User::factory()->create(['role_id' => 1]);
        $reviewer = User::factory()->create(['role_id' => 1]);

        $pesantrenUser = $this->createPesantrenUser('Pesantren Overdue');

        $akreditasi = Akreditasi::create([
            'user_id' => $pesantrenUser->id,
            'status' => 3,
        ]);

        // Create an overdue banding (deadline in the past)
        Banding::create([
            'akreditasi_id' => $akreditasi->id,
            'user_id' => $pesantrenUser->id,
            'reviewer_id' => $reviewer->id,
            'status' => 'under_review',
            'alasan' => 'Alasan banding overdue.',
            'review_deadline' => Carbon::now()->subDays(5),
        ]);

        $this->actingAs($admin);

        $component = Volt::test('pages.admin.banding')
            ->assertSee('Pesantren Overdue')
            ->assertSee('Overdue')
            ->assertSeeHtml('bg-light-danger');
    }

    /**
     * Task 8.8: Non-overdue bandings do NOT show red highlight
     */
    public function test_non_overdue_bandings_do_not_show_red_highlight(): void
    {
        $admin = User::factory()->create(['role_id' => 1]);
        $reviewer = User::factory()->create(['role_id' => 1]);

        $pesantrenUser = $this->createPesantrenUser('Pesantren Normal');

        $akreditasi = Akreditasi::create([
            'user_id' => $pesantrenUser->id,
            'status' => 3,
        ]);

        // Create a non-overdue banding (deadline in the future)
        Banding::create([
            'akreditasi_id' => $akreditasi->id,
            'user_id' => $pesantrenUser->id,
            'reviewer_id' => $reviewer->id,
            'status' => 'under_review',
            'alasan' => 'Alasan banding normal.',
            'review_deadline' => Carbon::now()->addDays(10),
        ]);

        $this->actingAs($admin);

        $component = Volt::test('pages.admin.banding')
            ->assertSee('Pesantren Normal')
            ->assertDontSee('Overdue');
    }

    /**
     * Task 8.7: Non-admin users cannot access banding list
     */
    public function test_non_admin_cannot_access_banding_list(): void
    {
        $pesantrenUser = $this->createPesantrenUser('Pesantren Unauthorized');

        $this->actingAs($pesantrenUser);

        $response = $this->get('/admin/banding');
        $response->assertStatus(403);
    }
}
