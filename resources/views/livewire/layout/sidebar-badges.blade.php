<?php

use App\Models\Akreditasi;
use App\Models\Banding;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Livewire\Volt\Component;

new class extends Component {
    public int $pendingAkreditasiCount = 0;
    public int $pendingBandingCount = 0;
    public int $activeTaskCount = 0;

    public function mount(): void
    {
        $this->getBadgeCounts();
    }

    public function getBadgeCounts(): void
    {
        try {
            /** @var \App\Models\User $user */
            $user = Auth::user();

            if (!$user) {
                return;
            }

            if ($user->canAccessAdminArea()) {
                $this->loadAdminBadges();
            } elseif ($user->isAsesor()) {
                $this->loadAsesorBadges($user);
            }
        } catch (\Exception $e) {
            Log::error('SidebarBadges: Failed to load badge counts', [
                'error' => $e->getMessage(),
            ]);

            // Gracefully return zero counts on failure
            $this->pendingAkreditasiCount = 0;
            $this->pendingBandingCount = 0;
            $this->activeTaskCount = 0;
        }
    }

    private function loadAdminBadges(): void
    {
        // P-8 fix: cache badge counts for 30s to avoid sustained polling load.
        // With 50 active admins polling every 30s = 100 queries/min → 2 queries/min.
        $this->pendingAkreditasiCount = Cache::remember(
            'badge:admin:pending_akreditasi',
            30,
            fn () => Akreditasi::where('status', 6)->count()
        );

        $this->pendingBandingCount = Cache::remember(
            'badge:admin:pending_banding',
            30,
            fn () => Banding::where('status', 'pending')->count()
        );
    }

    private function loadAsesorBadges(\App\Models\User $user): void
    {
        $asesor = $user->asesor;

        if (!$asesor) {
            $this->activeTaskCount = 0;
            return;
        }

        // P-8 fix: cache per-asesor badge count for 30s.
        $this->activeTaskCount = Cache::remember(
            'badge:asesor:' . $asesor->id . ':active_tasks',
            30,
            fn () => Akreditasi::whereIn('status', [4, 5])
                ->whereHas('assessments', function ($query) use ($asesor) {
                    $query->where('asesor_id', $asesor->id);
                })
                ->count()
        );
    }
}; ?>

<div wire:poll.visible.60s="getBadgeCounts">
    {{-- This component provides badge count data to the sidebar.
         The actual badge rendering is handled by sidebar-link components
         that reference these public properties. --}}
    <div class="d-none"
         data-pending-akreditasi="{{ $pendingAkreditasiCount }}"
         data-pending-banding="{{ $pendingBandingCount }}"
         data-active-tasks="{{ $activeTaskCount }}">
    </div>
</div>
