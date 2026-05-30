<?php

namespace Tests\Feature\ConcurrentAccess;

use App\Exceptions\ConflictException;
use App\Models\Akreditasi;
use App\Models\Pesantren;
use App\Models\User;
use App\Services\AkreditasiWorkflowService;
use Carbon\Carbon;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[Group('Feature:concurrent-access-handling')]
class OptimisticLockPropertyTest extends TestCase
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

    public static function staleTimestampProvider(): array
    {
        $cases = [];

        for ($i = 0; $i < 20; $i++) {
            $cases["case_{$i}"] = [
                Carbon::now()
                    ->subDays($i + 1)
                    ->subMinutes($i)
                    ->toISOString(),
            ];
        }

        return $cases;
    }

    #[DataProvider('staleTimestampProvider')]
    public function test_stale_timestamp_throws_conflict_exception_and_db_unchanged(string $staleTimestamp): void
    {
        $akreditasi = $this->createValidasiAkreditasi();
        $originalStatus = (int) $akreditasi->status;

        $this->assertNotSame($akreditasi->updated_at->toISOString(), $staleTimestamp);
        $this->expectException(ConflictException::class);

        try {
            $this->workflowService->rejectAtValidasi(
                $akreditasi->id,
                $this->admin->id,
                'Catatan penolakan validasi final yang cukup panjang.',
                $staleTimestamp,
                [['category' => 'lainnya', 'explanation' => 'Catatan penolakan validasi final yang cukup panjang.']]
            );
        } finally {
            $this->assertSame($originalStatus, (int) $akreditasi->fresh()->status);
        }
    }

    public static function freshTimestampProvider(): array
    {
        return collect(range(0, 19))
            ->mapWithKeys(fn (int $i): array => ["case_{$i}" => [$i]])
            ->all();
    }

    #[DataProvider('freshTimestampProvider')]
    public function test_fresh_timestamp_allows_final_rejection_to_proceed(int $iteration): void
    {
        $akreditasi = $this->createValidasiAkreditasi();
        $freshTimestamp = $akreditasi->updated_at->toISOString();

        $this->workflowService->rejectAtValidasi(
            $akreditasi->id,
            $this->admin->id,
            "Catatan penolakan validasi final iterasi {$iteration}.",
            $freshTimestamp,
            [['category' => 'lainnya', 'explanation' => "Catatan penolakan validasi final iterasi {$iteration}."]]
        );

        $this->assertSame(-1, (int) $akreditasi->fresh()->status);
        $this->assertDatabaseHas('akreditasi_rejections', [
            'akreditasi_id' => $akreditasi->id,
            'type' => 'admin_final',
            'status' => 'final',
        ]);
    }

    private function createValidasiAkreditasi(): Akreditasi
    {
        $pesantrenUser = User::factory()->create(['role_id' => 3]);
        Pesantren::create([
            'user_id' => $pesantrenUser->id,
            'nama_pesantren' => 'Pesantren Optimistic Lock '.$pesantrenUser->id,
            'is_locked' => true,
        ]);

        return Akreditasi::create([
            'user_id' => $pesantrenUser->id,
            'status' => 1,
        ]);
    }
}
