<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
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
            /** @var User $user */
            $user = Auth::user();

            if (! $user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sesi tidak valid.',
                    'showModal' => false,
                ], 401);
            }

            if (! $this->onboardingService->shouldShowOnboarding($user->id)) {
                return response()->json([
                    'success' => true,
                    'message' => null,
                    'showModal' => false,
                ]);
            }

            $steps = $this->onboardingService->getStepsForRole($user->role_id);
            $completionStatus = $this->onboardingService->getStepCompletionStatus($user->id, $user->role_id);

            $allCompleted = ! in_array(false, $completionStatus, true) && ! empty($completionStatus);

            if ($allCompleted) {
                $this->onboardingService->completeOnboarding($user->id);

                return response()->json([
                    'success' => true,
                    'message' => null,
                    'showModal' => false,
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => null,
                'showModal' => true,
                'steps' => $steps,
                'completionStatus' => $completionStatus,
                'allCompleted' => false,
            ]);
        } catch (\Exception $e) {
            Log::error('Onboarding: Failed to load status', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat status onboarding.',
                'showModal' => false,
            ], 500);
        }
    }

    public function navigateToStep(Request $request): JsonResponse
    {
        try {
            $request->validate(['step_key' => 'required|string']);

            /** @var User $user */
            $user = Auth::user();

            if (! $user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sesi tidak valid.',
                    'url' => '/dashboard',
                ], 401);
            }

            $steps = $this->onboardingService->getStepsForRole($user->role_id);
            $step = collect($steps)->firstWhere('key', $request->input('step_key'));

            if (! $step) {
                return response()->json([
                    'success' => false,
                    'message' => 'Langkah onboarding tidak ditemukan.',
                    'url' => '/dashboard',
                ], 404);
            }

            if ($step['completion_type'] === 'visit_based') {
                $this->onboardingService->markStepVisited($user->id, $request->input('step_key'));
            }

            return response()->json([
                'success' => true,
                'message' => null,
                'url' => route($step['route']),
            ]);
        } catch (\Exception $e) {
            Log::error('Onboarding: Failed to navigate to step', [
                'step_key' => $request->input('step_key'),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal membuka langkah onboarding.',
                'url' => '/dashboard',
            ], 500);
        }
    }

    public function skip(): JsonResponse
    {
        try {
            /** @var User $user */
            $user = Auth::user();

            if (! $user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sesi tidak valid.',
                ], 401);
            }

            $this->onboardingService->skipOnboarding($user->id);

            return response()->json(['success' => true, 'message' => null]);
        } catch (\Exception $e) {
            Log::error('Onboarding: Failed to skip', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal melewati onboarding.',
            ], 500);
        }
    }

    public function complete(): JsonResponse
    {
        try {
            /** @var User $user */
            $user = Auth::user();

            if (! $user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sesi tidak valid.',
                ], 401);
            }

            $this->onboardingService->completeOnboarding($user->id);

            return response()->json(['success' => true, 'message' => null]);
        } catch (\Exception $e) {
            Log::error('Onboarding: Failed to complete', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal menyelesaikan onboarding.',
            ], 500);
        }
    }
}
