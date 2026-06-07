<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\OnboardingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class OnboardingController extends Controller
{
    public function __construct(
        private readonly OnboardingService $onboardingService
    ) {}

    public function status(): JsonResponse
    {
        try {
            /** @var \App\Models\User $user */
            $user = Auth::user();

            if (! $user) {
                return response()->json(['showModal' => false]);
            }

            if (! $this->onboardingService->shouldShowOnboarding($user->id)) {
                return response()->json(['showModal' => false]);
            }

            $steps = $this->onboardingService->getStepsForRole($user->role_id);
            $completionStatus = $this->onboardingService->getStepCompletionStatus($user->id, $user->role_id);

            $allCompleted = ! in_array(false, $completionStatus, true) && ! empty($completionStatus);

            if ($allCompleted) {
                $this->onboardingService->completeOnboarding($user->id);

                return response()->json(['showModal' => false]);
            }

            return response()->json([
                'showModal' => true,
                'steps' => $steps,
                'completionStatus' => $completionStatus,
                'allCompleted' => false,
            ]);
        } catch (\Exception $e) {
            Log::error('Onboarding: Failed to load status', [
                'error' => $e->getMessage(),
            ]);

            return response()->json(['showModal' => false]);
        }
    }

    public function navigateToStep(Request $request): JsonResponse
    {
        try {
            $request->validate(['step_key' => 'required|string']);

            /** @var \App\Models\User $user */
            $user = Auth::user();

            if (! $user) {
                return response()->json(['url' => '/dashboard']);
            }

            $steps = $this->onboardingService->getStepsForRole($user->role_id);
            $step = collect($steps)->firstWhere('key', $request->input('step_key'));

            if (! $step) {
                return response()->json(['url' => '/dashboard'], 404);
            }

            if ($step['completion_type'] === 'visit_based') {
                $this->onboardingService->markStepVisited($user->id, $request->input('step_key'));
            }

            return response()->json(['url' => route($step['route'])]);
        } catch (\Exception $e) {
            Log::error('Onboarding: Failed to navigate to step', [
                'step_key' => $request->input('step_key'),
                'error' => $e->getMessage(),
            ]);

            return response()->json(['url' => '/dashboard'], 500);
        }
    }

    public function skip(): JsonResponse
    {
        try {
            /** @var \App\Models\User $user */
            $user = Auth::user();

            if (! $user) {
                return response()->json(['success' => false], 401);
            }

            $this->onboardingService->skipOnboarding($user->id);

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error('Onboarding: Failed to skip', [
                'error' => $e->getMessage(),
            ]);

            return response()->json(['success' => false], 500);
        }
    }

    public function complete(): JsonResponse
    {
        try {
            /** @var \App\Models\User $user */
            $user = Auth::user();

            if (! $user) {
                return response()->json(['success' => false], 401);
            }

            $this->onboardingService->completeOnboarding($user->id);

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error('Onboarding: Failed to complete', [
                'error' => $e->getMessage(),
            ]);

            return response()->json(['success' => false], 500);
        }
    }
}
