<?php

namespace Tests\Feature;

use App\Models\Akreditasi;
use App\Models\AkreditasiCatatan;
use App\Models\Banding;
use App\Models\Edpm;
use App\Models\Ipm;
use App\Models\MasterEdpmButir;
use App\Models\MasterEdpmKomponen;
use App\Models\Pesantren;
use App\Models\SdmPesantren;
use App\Models\User;
use App\Services\BandingService;
use Carbon\Carbon;
use Database\Seeders\RoleSeeder;
use Faker\Factory as Faker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BandingServicePropertyTest extends TestCase
{
    use RefreshDatabase;

    protected BandingService $bandingService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->bandingService = app(BandingService::class);
    }

    /**
     * Helper: create a pesantren user with complete data for createSubmission compatibility.
     */
    private function createPesantrenUser(): User
    {
        $user = User::factory()->create(['role_id' => 3]);
        Pesantren::create([
            'user_id' => $user->id,
            'nama_pesantren' => 'Pesantren Test ' . $user->id,
        ]);
        return $user;
    }

    /**
     * Property 1: Banding creation correctness
     * For any valid akreditasi with appeal count below the configured limit,
     * creating a banding SHALL produce a Banding record with status "pending",
     * the submitted reason preserved exactly, the correct akreditasi_id, and a non-null created_at.
     *
     * **Validates: Requirements 1.1**
     */
    public function test_property_1_banding_creation_correctness(): void
    {
        $faker = Faker::create();

        for ($i = 0; $i < 100; $i++) {
            $user = $this->createPesantrenUser();
            $akreditasi = Akreditasi::create([
                'user_id' => $user->id,
                'status' => 2,
            ]);

            $alasan = $faker->sentence(rand(5, 30));

            $banding = $this->bandingService->createBanding(
                $akreditasi->id,
                $user->id,
                $alasan
            );

            $this->assertNotNull($banding, "Iteration {$i}: Banding should be created");
            $this->assertEquals('pending', $banding->status, "Iteration {$i}: Status should be pending");
            $this->assertEquals($alasan, $banding->alasan, "Iteration {$i}: Alasan should be preserved exactly");
            $this->assertEquals($akreditasi->id, $banding->akreditasi_id, "Iteration {$i}: akreditasi_id should match");
            $this->assertNotNull($banding->created_at, "Iteration {$i}: created_at should not be null");

            // Clean up for next iteration to avoid limit conflicts
            Banding::where('akreditasi_id', $akreditasi->id)->delete();
        }
    }

    /**
     * Property 4: Appeal limit enforcement
     * For any akreditasi where the number of existing Banding records is >= the configured banding_limit,
     * checkBandingEligibility SHALL return allowed = false.
     *
     * **Validates: Requirements 2.2, 2.3**
     */
    public function test_property_4_appeal_limit_enforcement(): void
    {
        $faker = Faker::create();

        for ($i = 0; $i < 100; $i++) {
            // Generate random limit between 1 and 5
            $limit = $faker->numberBetween(1, 5);
            config(['akreditasi.banding_limit' => $limit]);

            $user = $this->createPesantrenUser();
            $akreditasi = Akreditasi::create([
                'user_id' => $user->id,
                'status' => 2,
            ]);

            // Create a random count of bandings >= limit
            $count = $faker->numberBetween($limit, $limit + 3);
            for ($j = 0; $j < $count; $j++) {
                Banding::create([
                    'akreditasi_id' => $akreditasi->id,
                    'user_id' => $user->id,
                    'status' => $faker->randomElement(['pending', 'under_review', 'accepted', 'rejected']),
                    'alasan' => $faker->sentence(),
                ]);
            }

            $result = $this->bandingService->checkBandingEligibility($akreditasi->id);

            $this->assertFalse($result['allowed'], "Iteration {$i}: Should not be allowed when count ({$count}) >= limit ({$limit})");
            $this->assertEquals(0, $result['remaining'], "Iteration {$i}: Remaining should be 0");
            $this->assertNotNull($result['error'], "Iteration {$i}: Error message should not be null");
        }

        // Reset config
        config(['akreditasi.banding_limit' => 1]);
    }

    /**
     * Property 2: Reviewer assignment state transition
     * For any Banding record in "pending" status and any valid reviewer user_id,
     * assigning the reviewer SHALL change the status to "under_review" and store the reviewer's user_id.
     *
     * **Validates: Requirements 1.3, 4.3**
     */
    public function test_property_2_reviewer_assignment_state_transition(): void
    {
        $faker = Faker::create();

        for ($i = 0; $i < 100; $i++) {
            $user = $this->createPesantrenUser();
            $reviewer = User::factory()->create(['role_id' => 1]);
            $akreditasi = Akreditasi::create([
                'user_id' => $user->id,
                'status' => 3,
            ]);

            $banding = Banding::create([
                'akreditasi_id' => $akreditasi->id,
                'user_id' => $user->id,
                'status' => 'pending',
                'alasan' => $faker->sentence(),
            ]);

            $result = $this->bandingService->assignReviewer($banding->id, $reviewer->id);

            $this->assertTrue($result, "Iteration {$i}: assignReviewer should return true");

            $banding->refresh();
            $this->assertEquals('under_review', $banding->status, "Iteration {$i}: Status should be under_review");
            $this->assertEquals($reviewer->id, $banding->reviewer_id, "Iteration {$i}: reviewer_id should be stored");
        }
    }

    /**
     * Property 3: Decision state transition and recording
     * For any Banding record in "under_review" status and any decision (accept or reject)
     * with a valid explanation (≥10 chars), making the decision SHALL update the status,
     * store the explanation in keputusan, and set a non-null decided_at timestamp.
     *
     * **Validates: Requirements 1.4, 5.1, 5.3**
     */
    public function test_property_3_decision_state_transition(): void
    {
        $faker = Faker::create();

        for ($i = 0; $i < 100; $i++) {
            $user = $this->createPesantrenUser();
            $reviewer = User::factory()->create(['role_id' => 1]);
            $akreditasi = Akreditasi::create([
                'user_id' => $user->id,
                'status' => 3,
            ]);

            $banding = Banding::create([
                'akreditasi_id' => $akreditasi->id,
                'user_id' => $user->id,
                'reviewer_id' => $reviewer->id,
                'status' => 'under_review',
                'alasan' => $faker->sentence(),
                'review_deadline' => now()->addDays(14),
            ]);

            // Generate a valid keputusan (>= 10 chars)
            $keputusan = $faker->text(rand(10, 200));
            // Ensure at least 10 chars
            if (strlen($keputusan) < 10) {
                $keputusan = str_pad($keputusan, 10, 'x');
            }

            $decision = $faker->randomElement(['accept', 'reject']);

            if ($decision === 'accept') {
                $result = $this->bandingService->acceptBanding($banding->id, $keputusan);
                // acceptBanding may return null if createSubmission fails (data completeness check)
                // but the banding itself should still be updated
                $banding->refresh();
                $this->assertEquals('accepted', $banding->status, "Iteration {$i}: Status should be accepted");
            } else {
                $result = $this->bandingService->rejectBanding($banding->id, $keputusan);
                $this->assertTrue($result, "Iteration {$i}: rejectBanding should return true");
                $banding->refresh();
                $this->assertEquals('rejected', $banding->status, "Iteration {$i}: Status should be rejected");
            }

            $this->assertEquals($keputusan, $banding->keputusan, "Iteration {$i}: keputusan should be stored");
            $this->assertNotNull($banding->decided_at, "Iteration {$i}: decided_at should not be null");
        }
    }

    /**
     * Property 7: Explanation minimum length validation
     * For any string with fewer than 10 characters provided as a decision explanation,
     * the system SHALL reject the decision and leave the Banding record unchanged.
     *
     * **Validates: Requirements 5.5**
     */
    public function test_property_7_explanation_minimum_length_validation(): void
    {
        $faker = Faker::create();

        for ($i = 0; $i < 100; $i++) {
            $user = $this->createPesantrenUser();
            $reviewer = User::factory()->create(['role_id' => 1]);
            $akreditasi = Akreditasi::create([
                'user_id' => $user->id,
                'status' => 3,
            ]);

            $banding = Banding::create([
                'akreditasi_id' => $akreditasi->id,
                'user_id' => $user->id,
                'reviewer_id' => $reviewer->id,
                'status' => 'under_review',
                'alasan' => $faker->sentence(),
                'review_deadline' => now()->addDays(14),
            ]);

            // Generate a short keputusan (< 10 chars)
            $length = $faker->numberBetween(0, 9);
            $shortKeputusan = $faker->lexify(str_repeat('?', $length));

            $decision = $faker->randomElement(['accept', 'reject']);

            if ($decision === 'accept') {
                $result = $this->bandingService->acceptBanding($banding->id, $shortKeputusan);
                $this->assertNull($result, "Iteration {$i}: acceptBanding should return null for short keputusan ('{$shortKeputusan}', len=" . strlen($shortKeputusan) . ")");
            } else {
                $result = $this->bandingService->rejectBanding($banding->id, $shortKeputusan);
                $this->assertFalse($result, "Iteration {$i}: rejectBanding should return false for short keputusan ('{$shortKeputusan}', len=" . strlen($shortKeputusan) . ")");
            }

            // Banding should remain unchanged
            $banding->refresh();
            $this->assertEquals('under_review', $banding->status, "Iteration {$i}: Status should remain under_review");
            $this->assertNull($banding->keputusan, "Iteration {$i}: keputusan should remain null");
            $this->assertNull($banding->decided_at, "Iteration {$i}: decided_at should remain null");
        }
    }

    /**
     * Helper: create a pesantren user with COMPLETE data for createSubmission compatibility.
     * This includes Pesantren, IPM, SDM, and EDPM data.
     */
    private function createCompletePesantrenUser(): User
    {
        $user = User::factory()->create(['role_id' => 3]);
        Pesantren::create([
            'user_id' => $user->id,
            'nama_pesantren' => 'Pesantren Complete ' . $user->id,
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

        // Ensure at least one MasterEdpmButir exists and user has evaluated it
        $komponen = MasterEdpmKomponen::first() ?? MasterEdpmKomponen::create(['nama' => 'Standar Isi']);
        $butir = MasterEdpmButir::first() ?? MasterEdpmButir::create([
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

    /**
     * Property 5: Accept outcome — new akreditasi creation
     * For any accepted banding, the system SHALL create a new Akreditasi record
     * with status 6 (Pengajuan) and parent field set to the original akreditasi_id.
     *
     * **Validates: Requirements 5.2**
     */
    public function test_property_5_accept_outcome_new_akreditasi_creation(): void
    {
        $faker = Faker::create();

        for ($i = 0; $i < 100; $i++) {
            $user = $this->createCompletePesantrenUser();
            $reviewer = User::factory()->create(['role_id' => 1]);
            $akreditasi = Akreditasi::create([
                'user_id' => $user->id,
                'status' => 3,
            ]);

            $banding = Banding::create([
                'akreditasi_id' => $akreditasi->id,
                'user_id' => $user->id,
                'reviewer_id' => $reviewer->id,
                'status' => 'under_review',
                'alasan' => $faker->sentence(),
                'review_deadline' => now()->addDays(14),
            ]);

            // Generate a valid keputusan (>= 10 chars)
            $keputusan = $faker->text(rand(20, 200));
            if (strlen($keputusan) < 10) {
                $keputusan = str_pad($keputusan, 10, 'x');
            }

            $newAkreditasi = $this->bandingService->acceptBanding($banding->id, $keputusan);

            $this->assertNotNull($newAkreditasi, "Iteration {$i}: acceptBanding should return new Akreditasi");
            $this->assertEquals(6, (int) $newAkreditasi->status, "Iteration {$i}: New akreditasi should have status 6");
            $this->assertEquals($akreditasi->id, $newAkreditasi->parent, "Iteration {$i}: New akreditasi parent should be original akreditasi_id");
            $this->assertEquals($user->id, $newAkreditasi->user_id, "Iteration {$i}: New akreditasi should belong to same user");

            // Verify it exists in the database
            $this->assertDatabaseHas('akreditasis', [
                'id' => $newAkreditasi->id,
                'status' => 6,
                'parent' => $akreditasi->id,
            ]);

            // Clean up: delete the new akreditasi so next iteration's createSubmission doesn't fail
            // (createSubmission checks for existing active akreditasi)
            Akreditasi::where('id', $newAkreditasi->id)->forceDelete();
            // Also clean up the banding and original akreditasi
            Banding::where('id', $banding->id)->delete();
            Akreditasi::where('id', $akreditasi->id)->forceDelete();
            // Clean up user data for next iteration
            Edpm::where('user_id', $user->id)->delete();
            SdmPesantren::where('user_id', $user->id)->delete();
            Ipm::where('user_id', $user->id)->delete();
            Pesantren::where('user_id', $user->id)->delete();
        }
    }

    /**
     * Property 6: Reject outcome — akreditasi status revert
     * For any rejected banding, the associated akreditasi SHALL have its status set to 2 (Ditolak)
     * and an AkreditasiCatatan record SHALL be created containing the rejection explanation.
     *
     * **Validates: Requirements 5.4**
     */
    public function test_property_6_reject_outcome_akreditasi_status_revert(): void
    {
        $faker = Faker::create();

        for ($i = 0; $i < 100; $i++) {
            $user = $this->createPesantrenUser();
            $reviewer = User::factory()->create(['role_id' => 1]);
            $akreditasi = Akreditasi::create([
                'user_id' => $user->id,
                'status' => 3, // Validasi (banding submitted)
            ]);

            $banding = Banding::create([
                'akreditasi_id' => $akreditasi->id,
                'user_id' => $user->id,
                'reviewer_id' => $reviewer->id,
                'status' => 'under_review',
                'alasan' => $faker->sentence(),
                'review_deadline' => now()->addDays(14),
            ]);

            // Generate a valid keputusan (>= 10 chars)
            $keputusan = $faker->text(rand(20, 200));
            if (strlen($keputusan) < 10) {
                $keputusan = str_pad($keputusan, 10, 'x');
            }

            $result = $this->bandingService->rejectBanding($banding->id, $keputusan);

            $this->assertTrue($result, "Iteration {$i}: rejectBanding should return true");

            // Verify akreditasi status reverted to 2
            $akreditasi->refresh();
            $this->assertEquals(2, (int) $akreditasi->status, "Iteration {$i}: Akreditasi status should be reverted to 2");

            // Verify AkreditasiCatatan created with rejection explanation
            $catatan = AkreditasiCatatan::where('akreditasi_id', $akreditasi->id)
                ->where('tipe', 'banding_rejected')
                ->first();

            $this->assertNotNull($catatan, "Iteration {$i}: AkreditasiCatatan should be created");
            $this->assertEquals($keputusan, $catatan->catatan, "Iteration {$i}: Catatan should contain the rejection explanation");
            $this->assertEquals($reviewer->id, $catatan->user_id, "Iteration {$i}: Catatan user_id should be the reviewer");
        }
    }

    /**
     * Property 8: Status guard on decisions
     * For any Banding record whose status is NOT "under_review",
     * any attempt to make a decision SHALL be rejected and the record SHALL remain unchanged.
     *
     * **Validates: Requirements 5.6**
     */
    public function test_property_8_status_guard_on_decisions(): void
    {
        $faker = Faker::create();

        $nonReviewStatuses = ['pending', 'accepted', 'rejected'];

        for ($i = 0; $i < 100; $i++) {
            $user = $this->createPesantrenUser();
            $reviewer = User::factory()->create(['role_id' => 1]);
            $akreditasi = Akreditasi::create([
                'user_id' => $user->id,
                'status' => 2,
            ]);

            $status = $faker->randomElement($nonReviewStatuses);

            $bandingData = [
                'akreditasi_id' => $akreditasi->id,
                'user_id' => $user->id,
                'status' => $status,
                'alasan' => $faker->sentence(),
            ];

            // Add decided fields for accepted/rejected statuses
            if (in_array($status, ['accepted', 'rejected'])) {
                $bandingData['keputusan'] = 'Previous decision explanation text';
                $bandingData['decided_at'] = now()->subDays(1);
                $bandingData['reviewer_id'] = $reviewer->id;
            }

            $banding = Banding::create($bandingData);

            $validKeputusan = $faker->text(50);
            if (strlen($validKeputusan) < 10) {
                $validKeputusan = str_pad($validKeputusan, 10, 'x');
            }

            $originalStatus = $banding->status;
            $originalKeputusan = $banding->keputusan;
            $originalDecidedAt = $banding->decided_at;

            $decision = $faker->randomElement(['accept', 'reject']);

            if ($decision === 'accept') {
                $result = $this->bandingService->acceptBanding($banding->id, $validKeputusan);
                $this->assertNull($result, "Iteration {$i}: acceptBanding should return null for status '{$status}'");
            } else {
                $result = $this->bandingService->rejectBanding($banding->id, $validKeputusan);
                $this->assertFalse($result, "Iteration {$i}: rejectBanding should return false for status '{$status}'");
            }

            // Banding should remain unchanged
            $banding->refresh();
            $this->assertEquals($originalStatus, $banding->status, "Iteration {$i}: Status should remain '{$originalStatus}'");
        }
    }

    /**
     * Property 9: Review deadline calculation
     * For any reviewer assignment, the stored review_deadline SHALL equal the assignment
     * timestamp plus the configured banding_review_days number of days.
     *
     * **Validates: Requirements 4.4, 7.1**
     */
    public function test_property_9_deadline_calculation(): void
    {
        $faker = Faker::create();

        for ($i = 0; $i < 100; $i++) {
            // Generate a random config value for review days
            $reviewDays = $faker->randomElement([7, 14, 21, 30]);
            config(['akreditasi.banding_review_days' => $reviewDays]);

            // Generate a random assignment timestamp
            $assignmentTime = Carbon::now()
                ->subDays($faker->numberBetween(0, 60))
                ->addHours($faker->numberBetween(0, 23))
                ->addMinutes($faker->numberBetween(0, 59));

            Carbon::setTestNow($assignmentTime);

            $user = $this->createPesantrenUser();
            $reviewer = User::factory()->create(['role_id' => 1]);
            $akreditasi = Akreditasi::create([
                'user_id' => $user->id,
                'status' => 3,
            ]);

            $banding = Banding::create([
                'akreditasi_id' => $akreditasi->id,
                'user_id' => $user->id,
                'status' => 'pending',
                'alasan' => $faker->sentence(),
            ]);

            $result = $this->bandingService->assignReviewer($banding->id, $reviewer->id);
            $this->assertTrue($result, "Iteration {$i}: assignReviewer should return true");

            $banding->refresh();

            // Expected deadline = assignment time + configured days
            $expectedDeadline = $assignmentTime->copy()->addDays($reviewDays);

            $this->assertNotNull($banding->review_deadline, "Iteration {$i}: review_deadline should not be null");
            // Compare using format to avoid microsecond precision issues
            $this->assertEquals(
                $expectedDeadline->format('Y-m-d H:i:s'),
                $banding->review_deadline->format('Y-m-d H:i:s'),
                "Iteration {$i}: review_deadline ({$banding->review_deadline}) should equal assignment_time + {$reviewDays} days ({$expectedDeadline})"
            );

            Carbon::setTestNow(); // Reset time
        }

        // Reset config
        config(['akreditasi.banding_review_days' => 14]);
    }

    /**
     * Property 10: Overdue detection accuracy
     * For any Banding record in "under_review" status, isOverdue() SHALL return true if and only if
     * the current time is past the review_deadline, and daysOverdue() SHALL return the correct
     * number of days past the deadline.
     *
     * **Validates: Requirements 7.4, 7.5**
     */
    public function test_property_10_overdue_detection(): void
    {
        $faker = Faker::create();

        for ($i = 0; $i < 100; $i++) {
            $user = $this->createPesantrenUser();
            $reviewer = User::factory()->create(['role_id' => 1]);
            $akreditasi = Akreditasi::create([
                'user_id' => $user->id,
                'status' => 3,
            ]);

            // Generate a random deadline relative to "now"
            $daysOffset = $faker->numberBetween(-30, 30); // negative = past, positive = future
            $baseTime = Carbon::create(2025, 6, 15, 12, 0, 0);
            $deadline = $baseTime->copy()->addDays($daysOffset);

            $banding = Banding::create([
                'akreditasi_id' => $akreditasi->id,
                'user_id' => $user->id,
                'reviewer_id' => $reviewer->id,
                'status' => 'under_review',
                'alasan' => $faker->sentence(),
                'review_deadline' => $deadline,
            ]);

            // Set "now" to baseTime so we can predict the outcome
            Carbon::setTestNow($baseTime);

            if ($daysOffset < 0) {
                // Deadline is in the past → should be overdue
                $this->assertTrue(
                    $banding->isOverdue(),
                    "Iteration {$i}: isOverdue() should be true when deadline ({$deadline}) is before now ({$baseTime})"
                );

                $expectedDaysOverdue = (int) $deadline->diffInDays($baseTime, false);
                $this->assertEquals(
                    $expectedDaysOverdue,
                    $banding->daysOverdue(),
                    "Iteration {$i}: daysOverdue() should be {$expectedDaysOverdue} (deadline={$deadline}, now={$baseTime})"
                );
                $this->assertGreaterThan(0, $banding->daysOverdue(), "Iteration {$i}: daysOverdue() should be > 0 when overdue");
            } else {
                // Deadline is in the future or exactly now → should NOT be overdue
                $this->assertFalse(
                    $banding->isOverdue(),
                    "Iteration {$i}: isOverdue() should be false when deadline ({$deadline}) is at or after now ({$baseTime})"
                );
                $this->assertEquals(
                    0,
                    $banding->daysOverdue(),
                    "Iteration {$i}: daysOverdue() should be 0 when not overdue"
                );
            }

            Carbon::setTestNow(); // Reset time
        }
    }

    /**
     * Additional check for Property 10: non-under_review status should never be overdue
     */
    public function test_property_10_non_under_review_never_overdue(): void
    {
        $faker = Faker::create();

        $nonReviewStatuses = ['pending', 'accepted', 'rejected'];

        for ($i = 0; $i < 100; $i++) {
            $user = $this->createPesantrenUser();
            $akreditasi = Akreditasi::create([
                'user_id' => $user->id,
                'status' => 3,
            ]);

            $status = $faker->randomElement($nonReviewStatuses);

            $banding = Banding::create([
                'akreditasi_id' => $akreditasi->id,
                'user_id' => $user->id,
                'status' => $status,
                'alasan' => $faker->sentence(),
                'review_deadline' => now()->subDays($faker->numberBetween(1, 30)), // deadline in the past
            ]);

            // Even with a past deadline, non-under_review bandings should NOT be overdue
            $this->assertFalse(
                $banding->isOverdue(),
                "Iteration {$i}: isOverdue() should be false for status '{$status}' even with past deadline"
            );
            $this->assertEquals(
                0,
                $banding->daysOverdue(),
                "Iteration {$i}: daysOverdue() should be 0 for status '{$status}'"
            );
        }
    }

    /**
     * Property 11: Banding list default sorting
     * For any collection of Banding records returned by the list query with default sorting,
     * the records SHALL be ordered by created_at ascending (oldest first).
     *
     * **Validates: Requirements 3.5**
     */
    public function test_property_11_banding_list_sorting(): void
    {
        $faker = Faker::create();

        // Create 100 random banding records with varying dates
        $users = [];
        for ($i = 0; $i < 100; $i++) {
            $user = $this->createPesantrenUser();
            $users[] = $user;
            $akreditasi = Akreditasi::create([
                'user_id' => $user->id,
                'status' => 3,
            ]);

            // Create banding with a random created_at date
            $randomDate = Carbon::now()
                ->subDays($faker->numberBetween(0, 365))
                ->addHours($faker->numberBetween(0, 23))
                ->addMinutes($faker->numberBetween(0, 59))
                ->addSeconds($faker->numberBetween(0, 59));

            $banding = Banding::create([
                'akreditasi_id' => $akreditasi->id,
                'user_id' => $user->id,
                'status' => $faker->randomElement(['pending', 'under_review', 'accepted', 'rejected']),
                'alasan' => $faker->sentence(),
            ]);

            // Manually set created_at to the random date
            $banding->update(['created_at' => $randomDate]);
        }

        // Get paginated bandings using the service (all statuses, no search, large page to get all)
        $result = $this->bandingService->getPaginatedBandings(null, null, 200);

        $items = $result->items();
        $this->assertGreaterThanOrEqual(100, count($items), 'Should have at least 100 banding records');

        // Verify the list is sorted by created_at ascending
        for ($i = 1; $i < count($items); $i++) {
            $prev = $items[$i - 1]->created_at;
            $curr = $items[$i]->created_at;

            $this->assertTrue(
                $prev->lessThanOrEqualTo($curr),
                "Item at index {$i} (created_at={$curr}) should be >= item at index " . ($i - 1) . " (created_at={$prev}). List is not sorted ascending."
            );
        }
    }
}
