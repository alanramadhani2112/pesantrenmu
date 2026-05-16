<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserOnboarding;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Unit tests for the OnboardingGuide Livewire component.
 *
 * Validates: Requirements 5.1, 5.5, 6.5, 7.5, 8.3
 */
class OnboardingGuideTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    // ─── Requirement 5.1: Modal shows for new Pesantren user ────────────────────

    public function test_modal_shows_for_new_pesantren_user(): void
    {
        $user = User::factory()->create(['role_id' => 3]);

        $this->actingAs($user);

        $component = Livewire::test('layout.onboarding-guide');

        $component->assertSet('showModal', true);
        $component->assertSet('steps', fn ($steps) => count($steps) === 5);
    }

    public function test_modal_shows_for_new_admin_user(): void
    {
        $user = User::factory()->create(['role_id' => 1]);

        $this->actingAs($user);

        $component = Livewire::test('layout.onboarding-guide');

        $component->assertSet('showModal', true);
        $component->assertSet('steps', fn ($steps) => count($steps) === 4);
    }

    public function test_modal_shows_for_new_asesor_user(): void
    {
        $user = User::factory()->create(['role_id' => 2]);

        $this->actingAs($user);

        $component = Livewire::test('layout.onboarding-guide');

        $component->assertSet('showModal', true);
        $component->assertSet('steps', fn ($steps) => count($steps) === 3);
    }

    // ─── Requirement 8.3: Modal does not show for completed user ────────────────

    public function test_modal_does_not_show_for_completed_user(): void
    {
        $user = User::factory()->create(['role_id' => 3]);

        UserOnboarding::create([
            'user_id' => $user->id,
            'completed_at' => now(),
            'visited_steps' => [],
        ]);

        $this->actingAs($user);

        $component = Livewire::test('layout.onboarding-guide');

        $component->assertSet('showModal', false);
    }

    public function test_modal_does_not_show_for_skipped_user(): void
    {
        $user = User::factory()->create(['role_id' => 3]);

        UserOnboarding::create([
            'user_id' => $user->id,
            'skipped_at' => now(),
            'visited_steps' => [],
        ]);

        $this->actingAs($user);

        $component = Livewire::test('layout.onboarding-guide');

        $component->assertSet('showModal', false);
    }

    // ─── Requirement 6.5, 7.5: navigateToStep dispatches navigation ─────────────

    public function test_navigate_to_step_redirects_admin_user(): void
    {
        $user = User::factory()->create(['role_id' => 1]);

        $this->actingAs($user);

        $component = Livewire::test('layout.onboarding-guide');

        $component->call('navigateToStep', 'lihat_pesantren');

        $component->assertRedirect(route('admin.pesantren.index'));
    }

    public function test_navigate_to_step_redirects_asesor_user(): void
    {
        $user = User::factory()->create(['role_id' => 2]);

        $this->actingAs($user);

        $component = Livewire::test('layout.onboarding-guide');

        $component->call('navigateToStep', 'profil_asesor');

        $component->assertRedirect(route('asesor.profile'));
    }

    public function test_navigate_to_step_redirects_pesantren_user(): void
    {
        $user = User::factory()->create(['role_id' => 3]);

        $this->actingAs($user);

        $component = Livewire::test('layout.onboarding-guide');

        $component->call('navigateToStep', 'profil');

        $component->assertRedirect(route('pesantren.profile'));
    }

    // ─── Requirement 6.5, 7.5: skipOnboarding updates state and closes modal ────

    public function test_skip_onboarding_closes_modal_and_updates_state(): void
    {
        $user = User::factory()->create(['role_id' => 3]);

        $this->actingAs($user);

        $component = Livewire::test('layout.onboarding-guide');

        $component->assertSet('showModal', true);

        $component->call('skipOnboarding');

        $component->assertSet('showModal', false);

        // Verify database was updated
        $onboarding = UserOnboarding::where('user_id', $user->id)->first();
        $this->assertNotNull($onboarding);
        $this->assertNotNull($onboarding->skipped_at);
    }

    // ─── Requirement 5.5, 8.3: completeOnboarding updates state and closes modal ─

    public function test_complete_onboarding_closes_modal_and_updates_state(): void
    {
        $user = User::factory()->create(['role_id' => 3]);

        $this->actingAs($user);

        $component = Livewire::test('layout.onboarding-guide');

        $component->assertSet('showModal', true);

        $component->call('completeOnboarding');

        $component->assertSet('showModal', false);

        // Verify database was updated
        $onboarding = UserOnboarding::where('user_id', $user->id)->first();
        $this->assertNotNull($onboarding);
        $this->assertNotNull($onboarding->completed_at);
    }

    // ─── Requirement 6.5: Admin skip onboarding ─────────────────────────────────

    public function test_admin_skip_onboarding_closes_modal(): void
    {
        $user = User::factory()->create(['role_id' => 1]);

        $this->actingAs($user);

        $component = Livewire::test('layout.onboarding-guide');

        $component->assertSet('showModal', true);

        $component->call('skipOnboarding');

        $component->assertSet('showModal', false);

        $onboarding = UserOnboarding::where('user_id', $user->id)->first();
        $this->assertNotNull($onboarding->skipped_at);
    }

    // ─── Requirement 7.5: Asesor skip onboarding ────────────────────────────────

    public function test_asesor_skip_onboarding_closes_modal(): void
    {
        $user = User::factory()->create(['role_id' => 2]);

        $this->actingAs($user);

        $component = Livewire::test('layout.onboarding-guide');

        $component->assertSet('showModal', true);

        $component->call('skipOnboarding');

        $component->assertSet('showModal', false);

        $onboarding = UserOnboarding::where('user_id', $user->id)->first();
        $this->assertNotNull($onboarding->skipped_at);
    }
}
