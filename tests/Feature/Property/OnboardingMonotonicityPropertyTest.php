<?php

namespace Tests\Feature\Property;

use App\Models\User;
use App\Models\UserOnboarding;
use App\Services\OnboardingService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Property 4: Visit-based onboarding step completion is monotonic
 *
 * For any Admin or Asesor user and any sequence of page visits, marking a step as visited SHALL:
 * - Add the step key to the visited_steps array if not already present
 * - Never remove previously visited steps
 * - Mark the step as completed in the onboarding checklist
 *
 * The set of completed steps SHALL always be a superset of any previous state (monotonically increasing).
 *
 * **Validates: Requirements 6.4, 7.4**
 */
class OnboardingMonotonicityPropertyTest extends TestCase
{
    use RefreshDatabase;

    protected OnboardingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->service = app(OnboardingService::class);
    }

    /**
     * Admin steps (visit_based): lihat_pesantren, lihat_asesor, review_akreditasi, kelola_banding
     */
    private const ADMIN_STEPS = [
        'lihat_pesantren',
        'lihat_asesor',
        'review_akreditasi',
        'kelola_banding',
    ];

    /**
     * Asesor steps (visit_based): profil_asesor, lihat_tugas, panduan_visitasi
     */
    private const ASESOR_STEPS = [
        'profil_asesor',
        'lihat_tugas',
        'panduan_visitasi',
    ];

    /**
     * Generate 120 random visit sequences for Admin and Asesor users.
     *
     * Each case contains:
     * - roleId: 1 (Admin) or 2 (Asesor)
     * - visitSequence: array of step keys to visit in order (may contain duplicates)
     */
    public static function randomVisitSequencesProvider(): array
    {
        $cases = [];
        $seed = crc32('onboarding_monotonicity_property_test');
        mt_srand($seed);

        for ($i = 0; $i < 120; $i++) {
            // Randomly pick Admin (1) or Asesor (2)
            $roleId = mt_rand(1, 2);
            $steps = $roleId === 1 ? self::ADMIN_STEPS : self::ASESOR_STEPS;

            // Generate a random-length visit sequence (1 to 10 visits)
            $sequenceLength = mt_rand(1, 10);
            $visitSequence = [];

            for ($j = 0; $j < $sequenceLength; $j++) {
                // Pick a random step (may repeat, which tests idempotency)
                $visitSequence[] = $steps[mt_rand(0, count($steps) - 1)];
            }

            $roleName = $roleId === 1 ? 'admin' : 'asesor';
            $cases["iteration_{$i}_{$roleName}_" . count($visitSequence) . "_visits"] = [
                $roleId,
                $visitSequence,
            ];
        }

        return $cases;
    }

    /**
     * Property 4: Visit-based onboarding step completion is monotonic
     *
     * **Validates: Requirements 6.4, 7.4**
     *
     */
#[DataProvider('randomVisitSequencesProvider')]
public function test_property_4_visit_based_onboarding_monotonicity(
        int $roleId,
        array $visitSequence
    ): void {
        $user = User::factory()->create(['role_id' => $roleId]);

        // Track the set of steps we've visited so far
        $expectedVisitedSteps = [];

        foreach ($visitSequence as $stepKey) {
            // Record the completion status BEFORE this visit
            $statusBefore = $this->service->getStepCompletionStatus($user->id, $roleId);

            // Mark the step as visited
            $this->service->markStepVisited($user->id, $stepKey);

            // Add to our expected set
            if (!in_array($stepKey, $expectedVisitedSteps)) {
                $expectedVisitedSteps[] = $stepKey;
            }

            // Get the completion status AFTER this visit
            $statusAfter = $this->service->getStepCompletionStatus($user->id, $roleId);

            // Verify: The step just visited is now marked as completed
            $this->assertTrue(
                $statusAfter[$stepKey],
                "Step '{$stepKey}' should be marked as completed after visiting it"
            );

            // Verify: No previously completed steps have become uncompleted (monotonicity)
            foreach ($statusBefore as $key => $wasCompleted) {
                if ($wasCompleted) {
                    $this->assertTrue(
                        $statusAfter[$key],
                        "Step '{$key}' was completed before but became uncompleted after visiting '{$stepKey}' — violates monotonicity"
                    );
                }
            }

            // Verify: visited_steps array contains all previously visited steps (superset property)
            $onboarding = UserOnboarding::where('user_id', $user->id)->first();
            $actualVisitedSteps = $onboarding->visited_steps ?? [];

            foreach ($expectedVisitedSteps as $expectedStep) {
                $this->assertContains(
                    $expectedStep,
                    $actualVisitedSteps,
                    "visited_steps should contain '{$expectedStep}' but it's missing — superset property violated"
                );
            }
        }
    }
}
