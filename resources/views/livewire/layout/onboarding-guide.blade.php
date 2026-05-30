<?php

use App\Services\OnboardingService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Volt\Component;

new class extends Component {
    public bool $showModal = false;
    public array $steps = [];
    public array $completionStatus = [];

    public function mount(): void
    {
        try {
            /** @var \App\Models\User $user */
            $user = Auth::user();

            if (!$user) {
                return;
            }

            $onboardingService = app(OnboardingService::class);

            // Hanya tampilkan otomatis bila onboarding belum selesai/dilewati
            // DAN semua langkah belum selesai (jangan pop-up "Selamat" tiba-tiba)
            if (!$onboardingService->shouldShowOnboarding($user->id)) {
                return;
            }

            $this->steps = $onboardingService->getStepsForRole($user->role_id);
            $this->completionStatus = $onboardingService->getStepCompletionStatus($user->id, $user->role_id);

            // Jangan auto-pop bila semua langkah sudah selesai
            $allDone = !in_array(false, $this->completionStatus, true) && !empty($this->completionStatus);
            if ($allDone) {
                // Tandai selesai secara otomatis, tidak perlu tampilkan modal
                $onboardingService->completeOnboarding($user->id);
                return;
            }

            $this->showModal = true;
        } catch (\Exception $e) {
            Log::error('OnboardingGuide: Failed to load onboarding state', [
                'error' => $e->getMessage(),
            ]);

            $this->showModal = false;
        }
    }

    public function openGuide(): void
    {
        try {
            /** @var \App\Models\User $user */
            $user = Auth::user();

            if (!$user) {
                return;
            }

            $onboardingService = app(OnboardingService::class);
            $this->steps = $onboardingService->getStepsForRole($user->role_id);
            $this->completionStatus = $onboardingService->getStepCompletionStatus($user->id, $user->role_id);
            $this->showModal = true;
        } catch (\Exception $e) {
            Log::error('OnboardingGuide: Failed to open guide', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function navigateToStep(string $stepKey): void
    {
        try {
            /** @var \App\Models\User $user */
            $user = Auth::user();

            if (!$user) {
                return;
            }

            $onboardingService = app(OnboardingService::class);
            $steps = $onboardingService->getStepsForRole($user->role_id);

            $step = collect($steps)->firstWhere('key', $stepKey);

            if (!$step) {
                return;
            }

            // Mark as visited for visit-based steps
            if ($step['completion_type'] === 'visit_based') {
                $onboardingService->markStepVisited($user->id, $stepKey);
            }

            $this->refreshStatus();

            $this->redirect(route($step['route']), navigate: true);
        } catch (\Exception $e) {
            Log::error('OnboardingGuide: Failed to navigate to step', [
                'step_key' => $stepKey,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function skipOnboarding(): void
    {
        try {
            /** @var \App\Models\User $user */
            $user = Auth::user();

            if (!$user) {
                return;
            }

            $onboardingService = app(OnboardingService::class);
            $onboardingService->skipOnboarding($user->id);

            $this->showModal = false;
        } catch (\Exception $e) {
            Log::error('OnboardingGuide: Failed to skip onboarding', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function completeOnboarding(): void
    {
        try {
            /** @var \App\Models\User $user */
            $user = Auth::user();

            if (!$user) {
                return;
            }

            $onboardingService = app(OnboardingService::class);
            $onboardingService->completeOnboarding($user->id);

            $this->showModal = false;
        } catch (\Exception $e) {
            Log::error('OnboardingGuide: Failed to complete onboarding', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function refreshStatus(): void
    {
        try {
            /** @var \App\Models\User $user */
            $user = Auth::user();

            if (!$user) {
                return;
            }

            $onboardingService = app(OnboardingService::class);
            $this->completionStatus = $onboardingService->getStepCompletionStatus($user->id, $user->role_id);
        } catch (\Exception $e) {
            Log::error('OnboardingGuide: Failed to refresh status', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function allStepsCompleted(): bool
    {
        if (empty($this->completionStatus)) {
            return false;
        }

        return !in_array(false, $this->completionStatus, true);
    }
}; ?>

<div x-on:open-onboarding-guide.window="$wire.openGuide()">
    @if($showModal)
    <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0, 0, 0, 0.5);" role="dialog" aria-modal="true" aria-labelledby="onboarding-modal-title">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <h3 class="modal-title fw-semibold text-gray-800" id="onboarding-modal-title">
                        <x-ui.icon name="rocket" class="fs-2x text-primary me-2" />
                        Panduan Memulai PesantrenMu
                    </h3>
                    <x-ui.button type="button" variant="light" size="sm" class="btn-icon btn-active-color-primary" wire:click="skipOnboarding" aria-label="Tutup panduan">
                        <x-ui.icon name="cross-circle" class="fs-2" />
                    </x-ui.button>
                </div>

                <div class="modal-body pt-2">
                    @if($this->allStepsCompleted())
                        {{-- Congratulatory message when all steps completed --}}
                        <div class="text-center py-10">
                            <x-ui.icon name="check-circle" class="fs-5x text-success mb-5" />
                            <h2 class="fw-semibold text-gray-800 mb-3">Selamat! 🎉</h2>
                            <p class="text-gray-600 fs-5 mb-8">
                                Anda telah menyelesaikan semua langkah panduan. Sistem siap digunakan sepenuhnya.
                            </p>
                            <x-ui.button type="button" variant="primary" size="lg" wire:click="completeOnboarding">
                                <x-ui.icon name="check-circle" class="fs-2 me-1" />
                                Selesai
                            </x-ui.button>
                        </div>
                    @else
                        {{-- Onboarding checklist --}}
                        <p class="text-gray-600 fs-6 mb-6">
                            Ikuti langkah-langkah berikut untuk memulai menggunakan sistem. Klik pada setiap langkah untuk menuju halaman terkait.
                        </p>

                        <div class="d-flex flex-column gap-3">
                            @foreach($steps as $index => $step)
                                @php
                                    $isCompleted = $completionStatus[$step['key']] ?? false;
                                    $icon = $step['icon'] ?? 'information';
                                    $description = $step['description'] ?? '';
                                @endphp
                                <div
                                    wire:click="navigateToStep('{{ $step['key'] }}')"
                                    class="d-flex align-items-center p-4 rounded border border-dashed cursor-pointer
                                        {{ $isCompleted ? 'border-success bg-light-success' : 'border-gray-300 bg-hover-light' }}"
                                    style="cursor: pointer;"
                                    role="button"
                                    tabindex="0"
                                    aria-label="Langkah {{ $index + 1 }}: {{ $step['label'] }}"
                                >
                                    {{-- Step icon or checkmark --}}
                                    <div class="me-4 flex-shrink-0">
                                        @if($isCompleted)
                                            <div class="w-45px h-45px rounded-circle bg-success d-flex align-items-center justify-content-center">
                                                <x-ui.icon name="check-circle" class="fs-3 text-white" />
                                            </div>
                                        @else
                                            <div class="w-45px h-45px rounded-circle bg-light-primary d-flex align-items-center justify-content-center">
                                                <x-ui.icon :name="$icon" class="fs-3 text-primary" />
                                            </div>
                                        @endif
                                    </div>

                                    {{-- Step label + description --}}
                                    <div class="flex-grow-1 min-w-0">
                                        <div class="fw-semibold fs-6 {{ $isCompleted ? 'text-success text-decoration-line-through' : 'text-gray-800' }}">
                                            {{ $step['label'] }}
                                        </div>
                                        @if($description)
                                            <div class="text-muted fs-7 mt-1">{{ $description }}</div>
                                        @endif
                                    </div>

                                    {{-- Arrow icon --}}
                                    <div class="ms-3 flex-shrink-0">
                                        <x-ui.icon name="arrow-left" class="fs-4 {{ $isCompleted ? 'text-success' : 'text-gray-400' }}" style="transform: rotate(180deg);" />
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        {{-- Progress summary --}}
                        @php
                            $completedCount = count(array_filter($completionStatus));
                            $totalCount = count($steps);
                        @endphp
                        <div class="mt-5 mb-2">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-gray-600 fs-7">Progres</span>
                                <span class="text-gray-800 fw-semibold fs-7">{{ $completedCount }}/{{ $totalCount }} langkah selesai</span>
                            </div>
                            <x-ui.progress
                                :value="$totalCount > 0 ? ($completedCount / $totalCount) * 100 : 0"
                                variant="success"
                            />
                        </div>
                    @endif
                </div>

                @if(!$this->allStepsCompleted())
                <div class="modal-footer border-0 pt-0">
                    <x-ui.button type="button" variant="light-danger" size="sm" wire:click="skipOnboarding">
                        Tutup
                    </x-ui.button>
                    @if(isset($completedCount) && $completedCount === $totalCount)
                        <x-ui.button type="button" variant="primary" size="sm" wire:click="completeOnboarding">
                            Selesai
                        </x-ui.button>
                    @endif
                </div>
                @endif
            </div>
        </div>
    </div>
    @endif
</div>
