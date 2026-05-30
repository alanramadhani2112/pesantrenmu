<?php

namespace Tests\Feature;

use App\Models\Akreditasi;
use App\Models\Asesor;
use App\Models\Assessment;
use App\Models\Pesantren;
use App\Models\User;
use App\Notifications\AkreditasiNotification;
use App\Services\AkreditasiWorkflowService;
use Carbon\Carbon;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[Group('Feature: assessment-visitasi-timeout')]
class ApprovePengajuanDeadlineTest extends TestCase
{
    use RefreshDatabase;

    protected AkreditasiWorkflowService $workflowService;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);
        $this->seed(RolePermissionSeeder::class);

        Notification::fake();

        $this->admin = User::factory()->create(['role_id' => 1]);
        Auth::login($this->admin);
        $this->workflowService = app(AkreditasiWorkflowService::class);
    }

    public function test_approval_without_explicit_end_date_uses_config_duration(): void
    {
        $today = Carbon::create(2025, 11, 1, 0, 0, 0);
        Carbon::setTestNow($today);
        config(['akreditasi-timeout.assessment.default_duration_days' => 30]);

        [$akreditasi, $asesor1, $asesor2] = $this->createReviewReadyAkreditasi();

        $this->workflowService->approveBerkas($akreditasi->id, $this->admin->id, $asesor1->user_id, $asesor2->user_id);

        $assessment = Assessment::where('akreditasi_id', $akreditasi->id)
            ->where('tipe', 1)
            ->first();

        $this->assertNotNull($assessment);
        $this->assertSame($today->copy()->addDays(30)->toDateString(), $assessment->tanggal_berakhir->toDateString());

        Carbon::setTestNow();
    }

    public function test_approval_without_explicit_end_date_uses_custom_config_duration(): void
    {
        $today = Carbon::create(2025, 12, 1, 0, 0, 0);
        Carbon::setTestNow($today);
        config(['akreditasi-timeout.assessment.default_duration_days' => 45]);

        [$akreditasi, $asesor1, $asesor2] = $this->createReviewReadyAkreditasi();

        $this->workflowService->approveBerkas($akreditasi->id, $this->admin->id, $asesor1->user_id, $asesor2->user_id);

        $assessment = Assessment::where('akreditasi_id', $akreditasi->id)
            ->where('tipe', 1)
            ->first();

        $this->assertNotNull($assessment);
        $this->assertSame($today->copy()->addDays(45)->toDateString(), $assessment->tanggal_berakhir->toDateString());

        Carbon::setTestNow();
    }

    public function test_existing_approval_changes_status_to_review_asesor(): void
    {
        [$akreditasi, $asesor1, $asesor2] = $this->createReviewReadyAkreditasi();

        $this->workflowService->approveBerkas($akreditasi->id, $this->admin->id, $asesor1->user_id, $asesor2->user_id);

        $this->assertSame(4, (int) $akreditasi->fresh()->status);
    }

    public function test_existing_approval_creates_assessment_records(): void
    {
        [$akreditasi, $asesor1, $asesor2] = $this->createReviewReadyAkreditasi();

        $this->workflowService->approveBerkas($akreditasi->id, $this->admin->id, $asesor1->user_id, $asesor2->user_id);

        $this->assertDatabaseHas('assessments', [
            'akreditasi_id' => $akreditasi->id,
            'asesor_id' => $asesor1->id,
            'tipe' => 1,
        ]);

        $this->assertDatabaseHas('assessments', [
            'akreditasi_id' => $akreditasi->id,
            'asesor_id' => $asesor2->id,
            'tipe' => 2,
        ]);
    }

    public function test_existing_approval_throws_exception_for_non_review_status(): void
    {
        $pesantrenUser = $this->createPesantrenUser();
        $asesor1 = $this->createAsesor('Asesor 1');
        $asesor2 = $this->createAsesor('Asesor 2');
        $akreditasi = Akreditasi::create([
            'user_id' => $pesantrenUser->id,
            'status' => 6,
        ]);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Verifikasi Berkas');

        $this->workflowService->approveBerkas($akreditasi->id, $this->admin->id, $asesor1->user_id, $asesor2->user_id);
    }

    public function test_existing_approval_sends_notifications(): void
    {
        [$akreditasi, $asesor1, $asesor2, $pesantrenUser] = $this->createReviewReadyAkreditasi();

        $this->workflowService->approveBerkas($akreditasi->id, $this->admin->id, $asesor1->user_id, $asesor2->user_id);

        Notification::assertSentTo($pesantrenUser, AkreditasiNotification::class);
        Notification::assertSentTo($asesor1->user, AkreditasiNotification::class);
        Notification::assertSentTo($asesor2->user, AkreditasiNotification::class);
    }

    /**
     * @return array{0: Akreditasi, 1: Asesor, 2: Asesor, 3: User}
     */
    private function createReviewReadyAkreditasi(): array
    {
        $pesantrenUser = $this->createPesantrenUser();
        $asesor1 = $this->createAsesor('Asesor 1');
        $asesor2 = $this->createAsesor('Asesor 2');

        $akreditasi = Akreditasi::create([
            'user_id' => $pesantrenUser->id,
            'status' => 6,
        ]);

        $this->workflowService->openForReview($akreditasi->id, $this->admin->id);
        $akreditasi->refresh();

        return [$akreditasi, $asesor1, $asesor2, $pesantrenUser];
    }

    private function createPesantrenUser(): User
    {
        $user = User::factory()->create(['role_id' => 3]);
        Pesantren::create([
            'user_id' => $user->id,
            'nama_pesantren' => 'Pesantren Approval Test '.$user->id,
        ]);

        return $user;
    }

    private function createAsesor(string $name): Asesor
    {
        $user = User::factory()->create(['role_id' => 2]);

        return Asesor::create([
            'user_id' => $user->id,
            'nama_dengan_gelar' => $name,
            'nama_tanpa_gelar' => $name,
        ]);
    }
}
