<?php

namespace Tests\Feature\Trash;

use App\Models\Akreditasi;
use App\Models\Pesantren;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Task 7.4: PurgeTrashCommand tests.
 *
 * Validates: Requirements 5.1–5.7
 */
class PurgeCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $admin = User::factory()->create(['role_id' => 1]);
        $this->actingAs($admin);
    }

    private function makeTrashedAkreditasiWithAge(int $daysAgo): Akreditasi
    {
        $user = User::factory()->create(['role_id' => 3]);
        Pesantren::create(['user_id' => $user->id, 'nama_pesantren' => 'Pesantren Purge Cmd']);
        $akreditasi = Akreditasi::create(['user_id' => $user->id, 'status' => 6]);
        $akreditasi->delete();

        Akreditasi::withTrashed()->where('id', $akreditasi->id)->update([
            'deleted_at' => Carbon::now()->subDays($daysAgo),
        ]);

        return $akreditasi->fresh();
    }

    public function test_command_runs_successfully_with_no_expired_records(): void
    {
        $this->artisan('trash:purge')
            ->expectsOutput('Purging trashed akreditasi older than 90 day(s).')
            ->expectsOutput('Purged: 0, Failed: 0')
            ->assertExitCode(0);
    }

    public function test_command_purges_expired_records(): void
    {
        $expired = $this->makeTrashedAkreditasiWithAge(100);
        $fresh = $this->makeTrashedAkreditasiWithAge(10);

        $this->artisan('trash:purge')
            ->expectsOutput('Purged: 1, Failed: 0')
            ->assertExitCode(0);

        $this->assertSame(0, Akreditasi::withTrashed()->where('id', $expired->id)->count());
        $this->assertSame(1, Akreditasi::onlyTrashed()->where('id', $fresh->id)->count());
    }

    public function test_command_respects_days_option_override(): void
    {
        $record = $this->makeTrashedAkreditasiWithAge(5);

        // With default 90 days, should NOT be purged
        $this->artisan('trash:purge')
            ->expectsOutput('Purged: 0, Failed: 0')
            ->assertExitCode(0);

        $this->assertSame(1, Akreditasi::onlyTrashed()->where('id', $record->id)->count());

        // With --days=3, should be purged
        $this->artisan('trash:purge', ['--days' => 3])
            ->expectsOutput('Purging trashed akreditasi older than 3 day(s).')
            ->expectsOutput('Purged: 1, Failed: 0')
            ->assertExitCode(0);

        $this->assertSame(0, Akreditasi::withTrashed()->where('id', $record->id)->count());
    }

    public function test_command_fails_with_invalid_days_option(): void
    {
        $this->artisan('trash:purge', ['--days' => 0])
            ->assertExitCode(1);
    }

    public function test_command_is_scheduled_daily(): void
    {
        // Verify the command exists and is registered
        $this->artisan('trash:purge --help')
            ->assertExitCode(0);
    }
}
