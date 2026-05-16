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

            if (!$onboardingService->shouldShowOnboarding($user->id)) {
                return;
            }

            $this->showModal = true;
            $this->steps = $onboardingService->getStepsForRole($user->role_id);
            $this->completionStatus = $onboardingService->getStepCompletionStatus($user->id, $user->role_id);
        } catch (\Exception $e) {
            Log::error('OnboardingGuide: Failed to load onboarding state', [
                'error' => $e->getMessage(),
            ]);

            $this->showModal = false;
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

<div>
    @if($showModal)
    <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0, 0, 0, 0.5);">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <h3 class="modal-title fw-bold text-gray-800">
                        <i class="ki-duotone ki-rocket fs-2x text-primary me-2">
                            <span class="path1"></span>
                            <span class="path2"></span>
                        </i>
                        Selamat Datang di PesantrenMu!
                    </h3>
                    <button type="button" class="btn btn-sm btn-icon btn-active-color-primary" wire:click="skipOnboarding">
                        <i class="ki-duotone ki-cross fs-1">
                            <span class="path1"></span>
                            <span class="path2"></span>
                        </i>
                    </button>
                </div>

                <div class="modal-body pt-2">
                    @if($this->allStepsCompleted())
                        {{-- Congratulatory message when all steps completed --}}
                        <div class="text-center py-10">
                            <i class="ki-duotone ki-check-circle fs-5x text-success mb-5">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                            <h2 class="fw-bold text-gray-800 mb-3">Selamat! 🎉</h2>
                            <p class="text-gray-600 fs-5 mb-8">
                                Anda telah menyelesaikan semua langkah panduan. Sistem siap digunakan sepenuhnya.
                            </p>
                            <button type="button" class="btn btn-primary btn-lg" wire:click="completeOnboarding">
                                <i class="ki-duotone ki-check fs-2 me-1">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                                Selesai
                            </button>
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
                                @endphp
                                <div
                                    wire:click="navigateToStep('{{ $step['key'] }}')"
                                    class="d-flex align-items-center p-4 rounded border border-dashed cursor-pointer
                                        {{ $isCompleted ? 'border-success bg-light-success' : 'border-gray-300 bg-hover-light' }}"
                                    style="cursor: pointer;"
                                >
                                    {{-- Step number or checkmark --}}
                                    <div class="me-4">
                                        @if($isCompleted)
                                            <div class="w-35px h-35px rounded-circle bg-success d-flex align-items-center justify-content-center">
                                                <i class="ki-duotone ki-check fs-4 text-white">
                                                    <span class="path1"></span>
                                                    <span class="path2"></span>
                                                </i>
                                            </div>
                                        @else
                                            <div class="w-35px h-35px rounded-circle bg-light-primary d-flex align-items-center justify-content-center">
                                                <span class="fw-bold text-primary fs-6">{{ $index + 1 }}</span>
                                            </div>
                                        @endif
                                    </div>

                                    {{-- Step label --}}
                                    <div class="flex-grow-1">
                                        <span class="fw-semibold fs-5 {{ $isCompleted ? 'text-success text-decoration-line-through' : 'text-gray-800' }}">
                                            {{ $step['label'] }}
                                        </span>
                                    </div>

                                    {{-- Arrow icon --}}
                                    <div>
                                        <i class="ki-duotone ki-arrow-right fs-4 {{ $isCompleted ? 'text-success' : 'text-gray-400' }}">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                        </i>
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
                            <div class="progress h-8px">
                                <div class="progress-bar bg-success" role="progressbar"
                                     style="width: {{ $totalCount > 0 ? ($completedCount / $totalCount) * 100 : 0 }}%"
                                     aria-valuenow="{{ $completedCount }}" aria-valuemin="0" aria-valuemax="{{ $totalCount }}">
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                @if(!$this->allStepsCompleted())
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light-danger btn-sm" wire:click="skipOnboarding">
                        Lewati
                    </button>
                    @if($completedCount === $totalCount)
                        <button type="button" class="btn btn-primary btn-sm" wire:click="completeOnboarding">
                            Selesai
                        </button>
                    @endif
                </div>
                @endif
            </div>
        </div>
    </div>
    @endif
</div>
