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
use App\Services\PesantrenService;
use Carbon\Carbon;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ResubmissionRegressionTest extends TestCase
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
     * Task 8.3: Soft-deleted resubmissions are counted toward the limit (cannot circumvent by deleting)
     */
    public function test_soft_deleted_resubmissions_are_counted_toward_limit(): void
    {
        $user = $this->createCompletePesantrenUser();

        // Set limit to 2, disable cooling period
        config(['akreditasi.resubmission_limit' => 2]);
        config(['akreditasi.cooling_period_days' => 0]);

        // Create a chain: root -> child1 -> child2 (2 resubmissions = limit)
        $root = Akreditasi::create([
            'user_id' => $user->id,
            'status' => 2, // Ditolak
            'parent' => null,
        ]);

        $child1 = Akreditasi::create([
            'user_id' => $user->id,
            'status' => 2, // Ditolak
            'parent' => $root->id,
        ]);

        $child2 = Akreditasi::create([
            'user_id' => $user->id,
            'status' => 2, // Ditolak
            'parent' => $child1->id,
        ]);

        // Soft-delete child1 and child2 to try to circumvent the limit
        $child1->delete();
        $child2->delete();

        // Verify the soft-deleted records still exist in the database
        $this->assertSoftDeleted('akreditasis', ['id' => $child1->id]);
        $this->assertSoftDeleted('akreditasis', ['id' => $child2->id]);

        // Attempt to create a new resubmission - should be blocked because
        // soft-deleted resubmissions are still counted toward the limit
        $result = $this->service()->createSubmission($user->id, $child2->id);

        $this->assertNull($result, 'Resubmission should be blocked even when previous resubmissions are soft-deleted');
    }

    /**
     * Task 8.4: limit=0 disallows all resubmissions
     */
    public function test_limit_zero_disallows_all_resubmissions(): void
    {
        $user = $this->createCompletePesantrenUser();

        // Set limit to 0, disable cooling period
        config(['akreditasi.resubmission_limit' => 0]);
        config(['akreditasi.cooling_period_days' => 0]);

        // Create a rejected akreditasi
        $rejected = Akreditasi::create([
            'user_id' => $user->id,
            'status' => 2, // Ditolak
            'parent' => null,
        ]);

        // Attempt to create a resubmission - should be blocked because limit is 0
        $result = $this->service()->createSubmission($user->id, $rejected->id);

        $this->assertNull($result, 'Resubmission should be blocked when limit is set to 0');
    }

    /**
     * Task 8.5: cooling_period_days=0 allows immediate resubmission
     */
    public function test_cooling_period_zero_allows_immediate_resubmission(): void
    {
        $user = $this->createCompletePesantrenUser();

        // Set reasonable limit, disable cooling period
        config(['akreditasi.resubmission_limit' => 3]);
        config(['akreditasi.cooling_period_days' => 0]);

        $now = Carbon::now();
        Carbon::setTestNow($now);

        // Create a rejected akreditasi just now (updated_at = now)
        $rejected = Akreditasi::create([
            'user_id' => $user->id,
            'status' => 2, // Ditolak
            'parent' => null,
        ]);

        // Attempt to create a resubmission immediately - should succeed
        // because cooling_period_days=0 means no waiting required
        $result = $this->service()->createSubmission($user->id, $rejected->id);

        $this->assertNotNull($result, 'Resubmission should succeed when cooling_period_days is 0');
        $this->assertSame(6, $result->status);
        $this->assertSame($rejected->id, $result->parent);
    }

    private function service(): PesantrenService
    {
        return app(PesantrenService::class);
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
