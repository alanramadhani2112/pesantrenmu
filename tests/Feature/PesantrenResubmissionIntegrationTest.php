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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PesantrenResubmissionIntegrationTest extends TestCase
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
     * Task 4.3: createSubmission returns null when resubmission limit is reached
     */
    public function test_create_submission_returns_null_when_resubmission_limit_reached(): void
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

        // Verify audit log is written
        Log::shouldReceive('info')
            ->once()
            ->withArgs(function ($message, $context) use ($user, $child2) {
                return $message === 'Resubmission blocked'
                    && $context['user_id'] === $user->id
                    && $context['akreditasi_id'] === $child2->id
                    && $context['reason'] === 'limit_reached'
                    && $context['chain_count'] === 2
                    && $context['limit'] === 2;
            });

        // Allow other log calls
        Log::shouldReceive('info')->andReturnNull();
        Log::shouldReceive('warning')->andReturnNull();
        Log::shouldReceive('error')->andReturnNull();

        $result = $this->service()->createSubmission($user->id, $child2->id);

        $this->assertNull($result);
    }

    /**
     * Task 4.4: createSubmission returns null when cooling period has not elapsed
     */
    public function test_create_submission_returns_null_when_cooling_period_not_elapsed(): void
    {
        $user = $this->createCompletePesantrenUser();

        // Set high limit, 30-day cooling period
        config(['akreditasi.resubmission_limit' => 10]);
        config(['akreditasi.cooling_period_days' => 30]);

        $now = Carbon::create(2025, 6, 15)->startOfDay();
        Carbon::setTestNow($now);

        // Create a rejected parent, rejected 10 days ago
        $parent = Akreditasi::create([
            'user_id' => $user->id,
            'status' => 2, // Ditolak
            'parent' => null,
        ]);

        // Set updated_at to 10 days ago (rejection date)
        \Illuminate\Support\Facades\DB::table('akreditasis')
            ->where('id', $parent->id)
            ->update(['updated_at' => $now->copy()->subDays(10)]);

        // Verify audit log is written
        Log::shouldReceive('info')
            ->once()
            ->withArgs(function ($message, $context) use ($user, $parent) {
                return $message === 'Resubmission blocked'
                    && $context['user_id'] === $user->id
                    && $context['akreditasi_id'] === $parent->id
                    && $context['reason'] === 'cooling_period';
            });

        // Allow other log calls
        Log::shouldReceive('info')->andReturnNull();
        Log::shouldReceive('warning')->andReturnNull();
        Log::shouldReceive('error')->andReturnNull();

        $result = $this->service()->createSubmission($user->id, $parent->id);

        $this->assertNull($result);
    }

    /**
     * Task 4.5: createSubmission succeeds when both limit and cooling period allow it
     */
    public function test_create_submission_succeeds_when_limit_and_cooling_allow(): void
    {
        $user = $this->createCompletePesantrenUser();

        // Set limit to 3, 30-day cooling period
        config(['akreditasi.resubmission_limit' => 3]);
        config(['akreditasi.cooling_period_days' => 30]);

        $now = Carbon::create(2025, 6, 15)->startOfDay();
        Carbon::setTestNow($now);

        // Create a rejected parent, rejected 31 days ago (cooling elapsed)
        $parent = Akreditasi::create([
            'user_id' => $user->id,
            'status' => 2, // Ditolak
            'parent' => null,
        ]);

        // Set updated_at to 31 days ago (cooling period elapsed)
        \Illuminate\Support\Facades\DB::table('akreditasis')
            ->where('id', $parent->id)
            ->update(['updated_at' => $now->copy()->subDays(31)]);

        $result = $this->service()->createSubmission($user->id, $parent->id);

        $this->assertNotNull($result);
        $this->assertSame(6, $result->status);
        $this->assertSame($parent->id, $result->parent);
        $this->assertDatabaseHas('akreditasis', [
            'id' => $result->id,
            'user_id' => $user->id,
            'status' => 6,
            'parent' => $parent->id,
        ]);
    }

    /**
     * Task 4.6: createSubmission succeeds for first submission (no parentId) — regression test
     */
    public function test_create_submission_succeeds_for_first_submission_without_parent_id(): void
    {
        $user = $this->createCompletePesantrenUser();

        $result = $this->service()->createSubmission($user->id);

        $this->assertNotNull($result);
        $this->assertSame(6, $result->status);
        $this->assertNull($result->parent);
        $this->assertDatabaseHas('akreditasis', [
            'id' => $result->id,
            'user_id' => $user->id,
            'status' => 6,
            'parent' => null,
        ]);
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
