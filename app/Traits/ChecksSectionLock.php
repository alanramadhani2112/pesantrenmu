<?php

namespace App\Traits;

use App\Models\Akreditasi;
use App\Services\RejectionService;

/**
 * Trait for Blade-backed controllers/components to check section lock status
 * with partial unlock support from the structured rejection flow.
 *
 * A section is editable if EITHER:
 * 1. The pesantren is NOT locked (is_locked = false), OR
 * 2. The pesantren IS locked BUT the section is unlocked via RejectionService::isSectionUnlocked()
 */
trait ChecksSectionLock
{
    /**
     * Check if a specific section is editable for the current user.
     *
     * @param  string  $section  Section identifier (e.g., 'profil', 'ipm.nsp', 'sdm', 'edpm.butir.3')
     */
    protected function isSectionEditable(string $section): bool
    {
        // L-2 fix: guard terhadap unauthenticated context (queue job, public route, dll)
        if (! auth()->check()) {
            return false;
        }

        $pesantren = auth()->user()->pesantren;

        // If pesantren is not locked, everything is editable
        if (! $pesantren || ! $pesantren->is_locked) {
            return true;
        }

        // Pesantren is locked — check if this section is unlocked via rejection
        $akreditasi = $this->getActiveAkreditasi();
        if (! $akreditasi) {
            return false;
        }

        $rejectionService = app(RejectionService::class);

        return $rejectionService->isSectionUnlocked($akreditasi->id, $section);
    }

    /**
     * Get the lock status indicator for a section.
     *
     * Returns:
     * - 'unlocked' if pesantren is not locked (normal editing)
     * - 'locked' if section is locked
     * - 'unlocked_for_correction' if section is unlocked via rejection
     *
     * @param  string  $section  Section identifier
     */
    protected function getSectionLockStatus(string $section): string
    {
        // L-2 fix: guard terhadap unauthenticated context
        if (! auth()->check()) {
            return 'locked';
        }

        $pesantren = auth()->user()->pesantren;

        if (! $pesantren || ! $pesantren->is_locked) {
            return 'unlocked';
        }

        $akreditasi = $this->getActiveAkreditasi();
        if (! $akreditasi) {
            return 'locked';
        }

        $rejectionService = app(RejectionService::class);
        if ($rejectionService->isSectionUnlocked($akreditasi->id, $section)) {
            return 'unlocked_for_correction';
        }

        return 'locked';
    }

    /**
     * Get the active akreditasi at status 5 for the current user.
     */
    protected function getActiveAkreditasi(): ?Akreditasi
    {
        // L-2 fix: guard terhadap unauthenticated context
        if (! auth()->check()) {
            return null;
        }

        return Akreditasi::where('user_id', auth()->id())
            ->where('status', 5)
            ->latest()
            ->first();
    }
}
