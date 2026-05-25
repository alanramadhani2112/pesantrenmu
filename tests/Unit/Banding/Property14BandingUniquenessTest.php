<?php

namespace Tests\Unit\Banding;

use App\Models\Akreditasi;
use App\Models\Assessment;
use App\Models\Asesor;
use App\Models\Banding;
use App\Models\User;
use App\Services\BandingService;
use App\StateMachine\AkreditasiStateMachine;
use Carbon\Carbon;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Property-Based Test: Property 14 — Banding Uniqueness
 *
 * For any akreditasi that already has a banding record, attempting to submit
 * a second banding SHALL be rejected.
 *
 * **Validates: Requirements 14.3**
 *
 */
#[Group('akreditasi-workflow-redesign')]
class Property14BandingUniquenessTest extends TestCase
{
    use RefreshDatabase;

    protected BandingService $bandingService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->bandingService = app(BandingService::class);
    }

    // =========================================================================
    // Property 14 — Main property test (≥100 iterations)
    // =========================================================================

    /**
     * Property 14 — Banding Uniqueness:
     *
     * For any akreditasi that already has a banding record (regardless of the
     * banding's status: pending, under_review, accepted, or rejected),
     * attempting to submit a second banding via submitBanding() SHALL be
     * rejected with success=false.
     *
     * **Validates: Requirements 14.3**
     */
public function test_property14_second_banding_always_rejected(): void
    {
        $iterations = 100;

        $bandingStatuses = ['pending', 'under_review', 'accepted', 'rejected'];

        for ($i = 0; $i < $iterations; $i++) {
            // Pick a random existing banding status
            $existingStatus = $bandingStatuses[array_rand($bandingStatuses)];

            $setup = $this->createRejectedAkreditasiWithAssessors();
            $akreditasiId = $setup['akreditasi']->id;
            $pesantrenUserId = $setup['pesantrenUser']->id;

            // Create an existing banding record with the chosen status
            Banding::create([
                'akreditasi_id' => $akreditasiId,
                'user_id' => $pesantrenUserId,
                'status' => $existingStatus,
                'alasan' => 'Banding pertama yang sudah ada.',
            ]);

            // Attempt to submit a second banding
            $result = $this->bandingService->submitBanding(
                $akreditasiId,
                $pesantrenUserId,
                'Alasan banding kedua yang seharusnya ditolak.'
            );

            $this->assertFalse(
                $result['success'],
                "Iteration {$i}: submitBanding should be rejected when a banding already exists " .
                "(existing status: {$existingStatus})"
            );

            $this->assertNull(
                $result['banding'],
                "Iteration {$i}: banding should be null when submission is rejected"
            );

            $this->assertNotNull(
                $result['error'],
                "Iteration {$i}: error message should be provided when submission is rejected"
            );

            // Verify only 1 banding record exists in the database
            $bandingCount = Banding::where('akreditasi_id', $akreditasiId)->count();
            $this->assertSame(
                1,
                $bandingCount,
                "Iteration {$i}: Only 1 banding record should exist for akreditasi #{$akreditasiId}"
            );
        }
    }

    /**
     * Property 14 — First banding is accepted:
     *
     * When no banding record exists for an akreditasi at status -1 with
     * assessors assigned and within the 14-day window, the first banding
     * submission SHALL succeed.
     *
     * **Validates: Requirements 14.1, 14.2**
     */
public function test_property14_first_banding_is_accepted(): void
    {
        $iterations = 100;

        for ($i = 0; $i < $iterations; $i++) {
            $setup = $this->createRejectedAkreditasiWithAssessors();
            $akreditasiId = $setup['akreditasi']->id;
            $pesantrenUserId = $setup['pesantrenUser']->id;

            // Verify no banding exists yet
            $this->assertSame(
                0,
                Banding::where('akreditasi_id', $akreditasiId)->count(),
                "Iteration {$i}: No banding should exist before first submission"
            );

            // Submit first banding
            $alasan = 'Alasan banding pertama yang valid - ' . str_repeat('x', random_int(10, 100));
            $result = $this->bandingService->submitBanding(
                $akreditasiId,
                $pesantrenUserId,
                $alasan
            );

            $this->assertTrue(
                $result['success'],
                "Iteration {$i}: First banding submission should succeed. Error: " . ($result['error'] ?? 'none')
            );

            $this->assertNotNull(
                $result['banding'],
                "Iteration {$i}: Banding record should be returned on success"
            );

            $this->assertNull(
                $result['error'],
                "Iteration {$i}: No error should be returned on success"
            );

            // Verify exactly 1 banding record was created
            $bandingCount = Banding::where('akreditasi_id', $akreditasiId)->count();
            $this->assertSame(
                1,
                $bandingCount,
                "Iteration {$i}: Exactly 1 banding record should exist after first submission"
            );

            // Verify akreditasi transitioned to status -2
            $akreditasi = Akreditasi::withTrashed()->find($akreditasiId);
            $this->assertSame(
                AkreditasiStateMachine::STATUS_BANDING,
                (int) $akreditasi->status,
                "Iteration {$i}: Akreditasi should be at status -2 (Banding) after submission"
            );
        }
    }

    /**
     * Property 14 — Uniqueness is enforced regardless of banding status:
     *
     * Even if the existing banding was accepted or rejected (decided),
     * a new banding submission SHALL still be rejected.
     *
     * **Validates: Requirements 14.3**
     */
public function test_property14_uniqueness_enforced_for_decided_bandings(): void
    {
        $iterations = 100;

        for ($i = 0; $i < $iterations; $i++) {
            $setup = $this->createAkreditasiAtBandingStatus();
            $akreditasiId = $setup['akreditasi']->id;
            $pesantrenUserId = $setup['pesantrenUser']->id;

            // Create a decided banding (accepted or rejected)
            $decidedStatus = (random_int(0, 1) === 0) ? 'accepted' : 'rejected';
            Banding::create([
                'akreditasi_id' => $akreditasiId,
                'user_id' => $pesantrenUserId,
                'status' => $decidedStatus,
                'alasan' => 'Banding yang sudah diputuskan.',
                'keputusan' => 'Keputusan admin.',
                'decided_at' => now()->subDays(random_int(1, 30)),
            ]);

            // Attempt to submit another banding
            $result = $this->bandingService->submitBanding(
                $akreditasiId,
                $pesantrenUserId,
                'Mencoba banding lagi setelah keputusan.'
            );

            $this->assertFalse(
                $result['success'],
                "Iteration {$i}: submitBanding should be rejected even when existing banding is '{$decidedStatus}'"
            );

            $bandingCount = Banding::where('akreditasi_id', $akreditasiId)->count();
            $this->assertSame(
                1,
                $bandingCount,
                "Iteration {$i}: Only 1 banding record should exist"
            );
        }
    }

    /**
     * Property 14 — Banding rejected when no assessors assigned:
     *
     * When akreditasi was rejected at status 5 (Verifikasi Berkas) without
     * assessors being assigned, banding SHALL be rejected.
     *
     * **Validates: Requirements 13.2, 13.3**
     */
public function test_property14_banding_rejected_without_assessors(): void
    {
        $iterations = 100;

        for ($i = 0; $i < $iterations; $i++) {
            $pesantrenUser = User::factory()->create(['role_id' => 3]);

            // Create akreditasi at status -1 WITHOUT assessors
            $akreditasi = Akreditasi::create([
                'user_id' => $pesantrenUser->id,
                'status' => AkreditasiStateMachine::STATUS_DITOLAK,
            ]);

            // Ensure no assessments exist
            $this->assertSame(
                0,
                Assessment::where('akreditasi_id', $akreditasi->id)->count(),
                "Iteration {$i}: No assessments should exist"
            );

            $result = $this->bandingService->submitBanding(
                $akreditasi->id,
                $pesantrenUser->id,
                'Mencoba banding tanpa asesor.'
            );

            $this->assertFalse(
                $result['success'],
                "Iteration {$i}: submitBanding should be rejected when no assessors were assigned"
            );

            $this->assertSame(
                0,
                Banding::where('akreditasi_id', $akreditasi->id)->count(),
                "Iteration {$i}: No banding record should be created"
            );
        }
    }

    /**
     * Property 14 — Banding rejected outside 14-day window:
     *
     * When the rejection date is more than 14 days ago, banding SHALL be rejected.
     *
     * **Validates: Requirements 14.10**
     */
public function test_property14_banding_rejected_outside_14_day_window(): void
    {
        $iterations = 100;

        for ($i = 0; $i < $iterations; $i++) {
            // Random number of days past the 14-day window (15 to 365 days ago)
            $daysAgo = random_int(15, 365);

            $setup = $this->createRejectedAkreditasiWithAssessors(daysAgo: $daysAgo);
            $akreditasiId = $setup['akreditasi']->id;
            $pesantrenUserId = $setup['pesantrenUser']->id;

            $result = $this->bandingService->submitBanding(
                $akreditasiId,
                $pesantrenUserId,
                'Mencoba banding setelah 14 hari.'
            );

            $this->assertFalse(
                $result['success'],
                "Iteration {$i}: submitBanding should be rejected when {$daysAgo} days have passed since rejection"
            );

            $this->assertSame(
                0,
                Banding::where('akreditasi_id', $akreditasiId)->count(),
                "Iteration {$i}: No banding record should be created outside the 14-day window"
            );
        }
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    /**
     * Create a pesantren user with akreditasi at status -1 (Ditolak) and
     * assessors assigned (Assessment records exist).
     *
     * @param int $daysAgo How many days ago the rejection occurred (default: within 14-day window)
     */
private function createRejectedAkreditasiWithAssessors(int $daysAgo = 1): array
    {
        $pesantrenUser = User::factory()->create(['role_id' => 3]);

        $akreditasi = Akreditasi::create([
            'user_id' => $pesantrenUser->id,
            'status' => AkreditasiStateMachine::STATUS_DITOLAK,
        ]);

        // Set updated_at to simulate rejection date
        DB::table('akreditasis')
            ->where('id', $akreditasi->id)
            ->update(['updated_at' => Carbon::now()->subDays($daysAgo)]);

        // Create assessors and assessment records
        $asesor1User = User::factory()->create(['role_id' => 2]);
        $asesor1 = Asesor::create([
            'user_id' => $asesor1User->id,
            'nama_dengan_gelar' => 'Dr. Asesor 1, M.Pd.',
            'nama_tanpa_gelar' => 'Asesor 1',
        ]);

        $asesor2User = User::factory()->create(['role_id' => 2]);
        $asesor2 = Asesor::create([
            'user_id' => $asesor2User->id,
            'nama_dengan_gelar' => 'Dr. Asesor 2, M.Pd.',
            'nama_tanpa_gelar' => 'Asesor 2',
        ]);

        Assessment::create([
            'akreditasi_id' => $akreditasi->id,
            'asesor_id' => $asesor1->id,
            'tipe' => 1,
            'tanggal_mulai' => now()->subDays(30),
            'tanggal_berakhir' => now()->subDays(10),
        ]);

        Assessment::create([
            'akreditasi_id' => $akreditasi->id,
            'asesor_id' => $asesor2->id,
            'tipe' => 2,
            'tanggal_mulai' => now()->subDays(30),
            'tanggal_berakhir' => now()->subDays(10),
        ]);

        return [
            'pesantrenUser' => $pesantrenUser,
            'akreditasi' => $akreditasi->fresh(),
            'asesor1User' => $asesor1User,
            'asesor2User' => $asesor2User,
        ];
    }

    /**
     * Create an akreditasi at status -2 (Banding) for testing decideBanding scenarios.
     */
private function createAkreditasiAtBandingStatus(): array
    {
        $pesantrenUser = User::factory()->create(['role_id' => 3]);

        $akreditasi = Akreditasi::create([
            'user_id' => $pesantrenUser->id,
            'status' => AkreditasiStateMachine::STATUS_BANDING,
        ]);

        return [
            'pesantrenUser' => $pesantrenUser,
            'akreditasi' => $akreditasi,
        ];
    }
}
