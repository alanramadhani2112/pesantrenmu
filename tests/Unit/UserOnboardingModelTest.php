<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\UserOnboarding;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class UserOnboardingModelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_user_onboarding_belongs_to_user(): void
    {
        $user = User::factory()->create(['role_id' => 3]);
        $onboarding = UserOnboarding::create([
            'user_id' => $user->id,
        ]);

        $this->assertInstanceOf(User::class, $onboarding->user);
        $this->assertEquals($user->id, $onboarding->user->id);
    }

    public function test_completed_at_is_cast_to_datetime(): void
    {
        $user = User::factory()->create(['role_id' => 3]);
        $onboarding = UserOnboarding::create([
            'user_id' => $user->id,
            'completed_at' => '2026-05-20 10:00:00',
        ]);

        $onboarding->refresh();
        $this->assertInstanceOf(Carbon::class, $onboarding->completed_at);
    }

    public function test_skipped_at_is_cast_to_datetime(): void
    {
        $user = User::factory()->create(['role_id' => 3]);
        $onboarding = UserOnboarding::create([
            'user_id' => $user->id,
            'skipped_at' => '2026-05-20 10:00:00',
        ]);

        $onboarding->refresh();
        $this->assertInstanceOf(Carbon::class, $onboarding->skipped_at);
    }

    public function test_visited_steps_is_cast_to_array(): void
    {
        $user = User::factory()->create(['role_id' => 3]);
        $onboarding = UserOnboarding::create([
            'user_id' => $user->id,
            'visited_steps' => ['profil', 'ipm'],
        ]);

        $onboarding->refresh();
        $this->assertIsArray($onboarding->visited_steps);
        $this->assertEquals(['profil', 'ipm'], $onboarding->visited_steps);
    }

    public function test_is_completed_returns_true_when_completed_at_is_set(): void
    {
        $user = User::factory()->create(['role_id' => 3]);
        $onboarding = UserOnboarding::create([
            'user_id' => $user->id,
            'completed_at' => now(),
        ]);

        $this->assertTrue($onboarding->isCompleted());
    }

    public function test_is_completed_returns_true_when_skipped_at_is_set(): void
    {
        $user = User::factory()->create(['role_id' => 3]);
        $onboarding = UserOnboarding::create([
            'user_id' => $user->id,
            'skipped_at' => now(),
        ]);

        $this->assertTrue($onboarding->isCompleted());
    }

    public function test_is_completed_returns_false_when_neither_set(): void
    {
        $user = User::factory()->create(['role_id' => 3]);
        $onboarding = UserOnboarding::create([
            'user_id' => $user->id,
        ]);

        $this->assertFalse($onboarding->isCompleted());
    }

    public function test_has_visited_step_returns_true_for_visited_step(): void
    {
        $user = User::factory()->create(['role_id' => 3]);
        $onboarding = UserOnboarding::create([
            'user_id' => $user->id,
            'visited_steps' => ['profil', 'ipm'],
        ]);

        $this->assertTrue($onboarding->hasVisitedStep('profil'));
        $this->assertTrue($onboarding->hasVisitedStep('ipm'));
    }

    public function test_has_visited_step_returns_false_for_unvisited_step(): void
    {
        $user = User::factory()->create(['role_id' => 3]);
        $onboarding = UserOnboarding::create([
            'user_id' => $user->id,
            'visited_steps' => ['profil'],
        ]);

        $this->assertFalse($onboarding->hasVisitedStep('ipm'));
    }

    public function test_has_visited_step_returns_false_when_visited_steps_is_null(): void
    {
        $user = User::factory()->create(['role_id' => 3]);
        $onboarding = UserOnboarding::create([
            'user_id' => $user->id,
            'visited_steps' => null,
        ]);

        $this->assertFalse($onboarding->hasVisitedStep('profil'));
    }

    public function test_mark_step_visited_adds_step_to_visited_steps(): void
    {
        $user = User::factory()->create(['role_id' => 3]);
        $onboarding = UserOnboarding::create([
            'user_id' => $user->id,
            'visited_steps' => [],
        ]);

        $onboarding->markStepVisited('profil');
        $onboarding->refresh();

        $this->assertTrue($onboarding->hasVisitedStep('profil'));
        $this->assertEquals(['profil'], $onboarding->visited_steps);
    }

    public function test_mark_step_visited_does_not_duplicate_existing_step(): void
    {
        $user = User::factory()->create(['role_id' => 3]);
        $onboarding = UserOnboarding::create([
            'user_id' => $user->id,
            'visited_steps' => ['profil'],
        ]);

        $onboarding->markStepVisited('profil');
        $onboarding->refresh();

        $this->assertEquals(['profil'], $onboarding->visited_steps);
    }

    public function test_mark_step_visited_appends_to_existing_steps(): void
    {
        $user = User::factory()->create(['role_id' => 3]);
        $onboarding = UserOnboarding::create([
            'user_id' => $user->id,
            'visited_steps' => ['profil'],
        ]);

        $onboarding->markStepVisited('ipm');
        $onboarding->refresh();

        $this->assertEquals(['profil', 'ipm'], $onboarding->visited_steps);
    }

    public function test_mark_step_visited_handles_null_visited_steps(): void
    {
        $user = User::factory()->create(['role_id' => 3]);
        $onboarding = UserOnboarding::create([
            'user_id' => $user->id,
            'visited_steps' => null,
        ]);

        $onboarding->markStepVisited('profil');
        $onboarding->refresh();

        $this->assertEquals(['profil'], $onboarding->visited_steps);
    }
}
