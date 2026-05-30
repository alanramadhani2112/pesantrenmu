<?php

namespace App\Services;

use App\Models\Akreditasi;
use App\Models\AkreditasiEdpm;
use App\Models\UserOnboarding;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class OnboardingService
{
    /**
     * Role ID constants.
     */
    private const ROLE_ADMIN = 1;

    private const ROLE_ASESOR = 2;

    private const ROLE_PESANTREN = 3;

    /**
     * Akreditasi status threshold for "submitted" (status >= 6 means submitted).
     */
    private const AKREDITASI_STATUS_SUBMITTED = 6;

    public function __construct(
        private SidebarProgressService $progressService
    ) {}

    /**
     * Get or create onboarding record for a user.
     */
    public function getOnboarding(int $userId): UserOnboarding
    {
        try {
            return UserOnboarding::firstOrCreate(
                ['user_id' => $userId],
                ['visited_steps' => []]
            );
        } catch (\Exception $e) {
            Log::error('OnboardingService: Failed to get/create onboarding record', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            // Fallback: return a transient model from session if DB fails
            $sessionState = Session::get("onboarding_state.{$userId}", []);

            $onboarding = new UserOnboarding([
                'user_id' => $userId,
                'visited_steps' => $sessionState['visited_steps'] ?? [],
                'completed_at' => $sessionState['completed_at'] ?? null,
                'skipped_at' => $sessionState['skipped_at'] ?? null,
            ]);

            return $onboarding;
        }
    }

    /**
     * Check if onboarding should be shown (not completed/skipped).
     */
    public function shouldShowOnboarding(int $userId): bool
    {
        $onboarding = $this->getOnboarding($userId);

        return ! $onboarding->isCompleted();
    }

    /**
     * Get the onboarding steps configuration for a role.
     *
     * @return array<int, array{key: string, label: string, route: string, completion_type: string}>
     */
    public function getStepsForRole(?int $roleId): array
    {
        return match ($roleId) {
            self::ROLE_PESANTREN => $this->getPesantrenSteps(),
            self::ROLE_ADMIN => $this->getAdminSteps(),
            self::ROLE_ASESOR => $this->getAsesorSteps(),
            default => [],
        };
    }

    /**
     * Get completion status for each step.
     *
     * @return array<string, bool>
     */
    public function getStepCompletionStatus(int $userId, ?int $roleId): array
    {
        $steps = $this->getStepsForRole($roleId);
        $status = [];

        foreach ($steps as $step) {
            $status[$step['key']] = $this->isStepCompleted($userId, $step);
        }

        return $status;
    }

    /**
     * Mark a step as visited (for Admin/Asesor visit-based completion).
     */
    public function markStepVisited(int $userId, string $stepKey): void
    {
        try {
            $onboarding = $this->getOnboarding($userId);
            $onboarding->markStepVisited($stepKey);
        } catch (\Exception $e) {
            Log::error('OnboardingService: Failed to mark step visited', [
                'user_id' => $userId,
                'step_key' => $stepKey,
                'error' => $e->getMessage(),
            ]);

            // Fallback to session storage
            $sessionState = Session::get("onboarding_state.{$userId}", []);
            $visitedSteps = $sessionState['visited_steps'] ?? [];

            if (! in_array($stepKey, $visitedSteps)) {
                $visitedSteps[] = $stepKey;
            }

            $sessionState['visited_steps'] = $visitedSteps;
            Session::put("onboarding_state.{$userId}", $sessionState);
        }
    }

    /**
     * Mark onboarding as completed.
     */
    public function completeOnboarding(int $userId): void
    {
        try {
            $onboarding = $this->getOnboarding($userId);
            $onboarding->update(['completed_at' => now()]);
        } catch (\Exception $e) {
            Log::error('OnboardingService: Failed to complete onboarding', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            // Fallback to session storage
            $sessionState = Session::get("onboarding_state.{$userId}", []);
            $sessionState['completed_at'] = now()->toISOString();
            Session::put("onboarding_state.{$userId}", $sessionState);
        }
    }

    /**
     * Mark onboarding as skipped.
     */
    public function skipOnboarding(int $userId): void
    {
        try {
            $onboarding = $this->getOnboarding($userId);
            $onboarding->update(['skipped_at' => now()]);
        } catch (\Exception $e) {
            Log::error('OnboardingService: Failed to skip onboarding', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            // Fallback to session storage
            $sessionState = Session::get("onboarding_state.{$userId}", []);
            $sessionState['skipped_at'] = now()->toISOString();
            Session::put("onboarding_state.{$userId}", $sessionState);
        }
    }

    /**
     * Get Pesantren onboarding steps (data_based completion).
     */
    private function getPesantrenSteps(): array
    {
        return [
            [
                'key' => 'profil',
                'label' => 'Lengkapi Profil',
                'description' => 'Isi data identitas pesantren, kontak, dan informasi dasar yang diperlukan untuk proses akreditasi.',
                'icon' => 'profile-user',
                'route' => 'pesantren.profile',
                'completion_type' => 'data_based',
            ],
            [
                'key' => 'ipm',
                'label' => 'Isi Data IPM',
                'description' => 'Unggah dokumen pendukung untuk empat kriteria Indikator Pemenuhan Mutlak.',
                'icon' => 'document',
                'route' => 'pesantren.ipm',
                'completion_type' => 'data_based',
            ],
            [
                'key' => 'sdm',
                'label' => 'Isi Data SDM',
                'description' => 'Rekap jumlah santri, ustadz, pamong, musyrif, dan tenaga kependidikan per jenjang.',
                'icon' => 'people',
                'route' => 'pesantren.sdm',
                'completion_type' => 'data_based',
            ],
            [
                'key' => 'edpm',
                'label' => 'Isi EDPM',
                'description' => 'Evaluasi kinerja per komponen dan lampirkan tautan bukti pendukung.',
                'icon' => 'category',
                'route' => 'pesantren.edpm',
                'completion_type' => 'data_based',
            ],
            [
                'key' => 'akreditasi',
                'label' => 'Ajukan Akreditasi',
                'description' => 'Kirim pengajuan akreditasi setelah semua data profil, IPM, SDM, dan EDPM terisi lengkap.',
                'icon' => 'medal-star',
                'route' => 'pesantren.akreditasi',
                'completion_type' => 'data_based',
            ],
        ];
    }

    /**
     * Get Admin onboarding steps (visit_based completion).
     */
    private function getAdminSteps(): array
    {
        return [
            [
                'key' => 'lihat_pesantren',
                'label' => 'Lihat Daftar Pesantren',
                'description' => 'Pantau status akun dan akreditasi seluruh pesantren yang terdaftar.',
                'icon' => 'category',
                'route' => 'admin.pesantren.index',
                'completion_type' => 'visit_based',
            ],
            [
                'key' => 'lihat_asesor',
                'label' => 'Lihat Daftar Asesor',
                'description' => 'Kelola data asesor, penugasan aktif, dan ketersediaan untuk visitasi.',
                'icon' => 'teacher',
                'route' => 'admin.asesor.index',
                'completion_type' => 'visit_based',
            ],
            [
                'key' => 'review_akreditasi',
                'label' => 'Review Pengajuan Akreditasi',
                'description' => 'Verifikasi pengajuan baru, tugaskan asesor, dan pantau progres penilaian.',
                'icon' => 'shield-tick',
                'route' => 'admin.akreditasi',
                'completion_type' => 'visit_based',
            ],
            [
                'key' => 'kelola_banding',
                'label' => 'Kelola Banding',
                'description' => 'Tinjau dan putuskan pengajuan banding dari pesantren yang tidak puas dengan hasil akreditasi.',
                'icon' => 'files-tablet',
                'route' => 'admin.banding',
                'completion_type' => 'visit_based',
            ],
        ];
    }

    /**
     * Get Asesor onboarding steps (visit_based completion).
     */
    private function getAsesorSteps(): array
    {
        return [
            [
                'key' => 'profil_asesor',
                'label' => 'Lengkapi Profil Asesor',
                'description' => 'Isi data identitas, keahlian, dan kontak asesor untuk keperluan penugasan resmi.',
                'icon' => 'profile-user',
                'route' => 'asesor.profile',
                'completion_type' => 'visit_based',
            ],
            [
                'key' => 'lihat_tugas',
                'label' => 'Lihat Tugas Akreditasi',
                'description' => 'Pantau daftar akreditasi yang ditugaskan, jadwal visitasi, dan status penilaian.',
                'icon' => 'calendar-tick',
                'route' => 'asesor.akreditasi',
                'completion_type' => 'visit_based',
            ],
            [
                'key' => 'panduan_visitasi',
                'label' => 'Pelajari Panduan Visitasi',
                'description' => 'Baca dokumen panduan dan referensi sebelum melakukan visitasi ke pesantren.',
                'icon' => 'files-tablet',
                'route' => 'documents.index',
                'completion_type' => 'visit_based',
            ],
        ];
    }

    /**
     * Determine if a specific step is completed.
     */
    private function isStepCompleted(int $userId, array $step): bool
    {
        if ($step['completion_type'] === 'data_based') {
            return $this->isDataBasedStepCompleted($userId, $step['key']);
        }

        if ($step['completion_type'] === 'visit_based') {
            return $this->isVisitBasedStepCompleted($userId, $step['key']);
        }

        return false;
    }

    /**
     * Check if a data-based step is completed by verifying data presence.
     */
    private function isDataBasedStepCompleted(int $userId, string $stepKey): bool
    {
        return match ($stepKey) {
            'profil' => $this->progressService->getSectionProgress($userId, 'profil')['status'] === 'complete',
            'ipm' => $this->progressService->getSectionProgress($userId, 'ipm')['status'] === 'complete',
            'sdm' => $this->progressService->getSectionProgress($userId, 'sdm')['status'] === 'complete',
            'edpm' => $this->hasEdpmRecords($userId),
            'akreditasi' => $this->hasSubmittedAkreditasi($userId),
            default => false,
        };
    }

    /**
     * Check if a visit-based step is completed by checking visited_steps.
     */
    private function isVisitBasedStepCompleted(int $userId, string $stepKey): bool
    {
        $onboarding = $this->getOnboarding($userId);

        return $onboarding->hasVisitedStep($stepKey);
    }

    /**
     * Check if user has any AkreditasiEdpm records.
     */
    private function hasEdpmRecords(int $userId): bool
    {
        return AkreditasiEdpm::whereIn(
            'akreditasi_id',
            Akreditasi::where('user_id', $userId)->select('id')
        )->exists();
    }

    /**
     * Check if user has any Akreditasi with status >= submitted.
     */
    private function hasSubmittedAkreditasi(int $userId): bool
    {
        return Akreditasi::where('user_id', $userId)
            ->where('status', '>=', self::AKREDITASI_STATUS_SUBMITTED)
            ->exists();
    }
}
