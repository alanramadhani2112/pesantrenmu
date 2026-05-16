<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserOnboarding;
use App\Services\OnboardingService;
use App\Services\SidebarProgressService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

/**
 * Unit tests for OnboardingService.
 *
 * Tests onboarding state management, skip/complete functionality,
 * and DB failure fallback to session storage.
 *
 * Validates: Requirements 5.1, 8.1, 8.2, 8.3, 8.4
 */
class OnboardingServiceTest extends TestCase
{
    use RefreshDatabase;

    protected OnboardingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->service = app(OnboardingService::class);
    }

    // ─── Requirement 5.1: New user gets onboarding shown ────────────────────────

    public function test_new_user_should_show_onboarding(): void
    {
        $user = User::factory()->create(['role_id' => 3]);

        $result = $this->service->shouldShowOnboarding($user->id);

        $this->assertTrue($result);
    }

    public function test_new_user_gets_onboarding_record_created(): void
    {
        $user = User::factory()->create(['role_id' => 3]);

        $onboarding = $this->service->getOnboarding($user->id);

        $this->assertInstanceOf(UserOnboarding::class, $onboarding);
        $this->assertSame($user->id, $onboarding->user_id);
        $this->assertNull($onboarding->completed_at);
        $this->assertNull($onboarding->skipped_at);
        $this->assertSame([], $onboarding->visited_steps);
    }

    // ─── Requirement 8.3: Completed user does not get onboarding shown ──────────

    public function test_completed_user_does_not_show_onboarding(): void
    {
        $user = User::factory()->create(['role_id' => 3]);

        UserOnboarding::create([
            'user_id' => $user->id,
            'completed_at' => now(),
            'visited_steps' => [],
        ]);

        $result = $this->service->shouldShowOnboarding($user->id);

        $this->assertFalse($result);
    }

    // ─── Requirement 8.3: Skipped user does not get onboarding shown ────────────

    public function test_skipped_user_does_not_show_onboarding(): void
    {
        $user = User::factory()->create(['role_id' => 3]);

        UserOnboarding::create([
            'user_id' => $user->id,
            'skipped_at' => now(),
            'visited_steps' => [],
        ]);

        $result = $this->service->shouldShowOnboarding($user->id);

        $this->assertFalse($result);
    }

    // ─── Requirement 8.2: Skip stores skipped_at timestamp ──────────────────────

    public function test_skip_stores_skipped_at_timestamp(): void
    {
        $user = User::factory()->create(['role_id' => 3]);

        $this->service->skipOnboarding($user->id);

        $onboarding = UserOnboarding::where('user_id', $user->id)->first();

        $this->assertNotNull($onboarding);
        $this->assertNotNull($onboarding->skipped_at);
        $this->assertNull($onboarding->completed_at);
    }

    // ─── Requirement 8.2: Complete stores completed_at timestamp ────────────────

    public function test_complete_stores_completed_at_timestamp(): void
    {
        $user = User::factory()->create(['role_id' => 3]);

        $this->service->completeOnboarding($user->id);

        $onboarding = UserOnboarding::where('user_id', $user->id)->first();

        $this->assertNotNull($onboarding);
        $this->assertNotNull($onboarding->completed_at);
        $this->assertNull($onboarding->skipped_at);
    }

    // ─── Requirement 8.4: DB failure falls back to session ──────────────────────

    public function test_db_failure_on_get_onboarding_falls_back_to_session(): void
    {
        $user = User::factory()->create(['role_id' => 3]);

        // Store some state in session first
        Session::put("onboarding_state.{$user->id}", [
            'visited_steps' => ['profil', 'ipm'],
            'completed_at' => null,
            'skipped_at' => null,
        ]);

        // Drop table to simulate DB failure
        \Illuminate\Support\Facades\Schema::drop('user_onboardings');

        $onboarding = $this->service->getOnboarding($user->id);

        // Should return a transient model with session data
        $this->assertInstanceOf(UserOnboarding::class, $onboarding);
        $this->assertSame($user->id, $onboarding->user_id);
        $this->assertSame(['profil', 'ipm'], $onboarding->visited_steps);
    }

    public function test_db_failure_on_mark_step_visited_falls_back_to_session(): void
    {
        $user = User::factory()->create(['role_id' => 3]);

        // Create onboarding record first
        $onboarding = $this->service->getOnboarding($user->id);

        Session::flush();

        // Mock the UserOnboarding model to throw on markStepVisited
        // We'll use a partial mock of the service that throws during the operation
        $mockOnboarding = $this->createPartialMock(UserOnboarding::class, ['markStepVisited']);
        $mockOnboarding->method('markStepVisited')
            ->willThrowException(new \RuntimeException('DB connection lost'));

        // Create a service mock that returns our throwing model
        $mockService = $this->getMockBuilder(OnboardingService::class)
            ->setConstructorArgs([app(SidebarProgressService::class)])
            ->onlyMethods(['getOnboarding'])
            ->getMock();

        $mockService->method('getOnboarding')
            ->willReturn($mockOnboarding);

        $mockService->markStepVisited($user->id, 'profil');

        // Verify session fallback was used
        $sessionState = Session::get("onboarding_state.{$user->id}");
        $this->assertNotNull($sessionState);
        $this->assertArrayHasKey('visited_steps', $sessionState);
        $this->assertContains('profil', $sessionState['visited_steps']);
    }

    public function test_db_failure_on_get_onboarding_returns_empty_state_when_no_session(): void
    {
        $user = User::factory()->create(['role_id' => 3]);

        Session::flush();

        // Drop table to simulate DB failure
        \Illuminate\Support\Facades\Schema::drop('user_onboardings');

        $onboarding = $this->service->getOnboarding($user->id);

        // Should return a transient model with empty defaults
        $this->assertInstanceOf(UserOnboarding::class, $onboarding);
        $this->assertSame($user->id, $onboarding->user_id);
        $this->assertSame([], $onboarding->visited_steps);
        $this->assertNull($onboarding->completed_at);
        $this->assertNull($onboarding->skipped_at);
    }
}
