<?php

use App\Models\Akreditasi;
use App\Models\Banding;
use Illuminate\Support\Facades\Auth;
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
        // Count Akreditasi with status=6 (Pengajuan/pending_review)
        $this->pendingAkreditasiCount = Akreditasi::where('status', 6)->count();

        // Count Banding with status='pending'
        $this->pendingBandingCount = Banding::where('status', 'pending')->count();
    }

    private function loadAsesorBadges(\App\Models\User $user): void
    {
        $asesor = $user->asesor;

        if (!$asesor) {
            $this->activeTaskCount = 0;
            return;
        }

        // Count Akreditasi assigned to this asesor with active statuses
        // Status 4 = Visitasi (in_progress), Status 5 = Assessment (assigned)
        $this->activeTaskCount = Akreditasi::whereIn('status', [4, 5])
            ->whereHas('assessments', function ($query) use ($asesor) {
                $query->where('asesor_id', $asesor->id);
            })
            ->count();
    }
}; ?>

<div wire:poll.30s="getBadgeCounts">
    {{-- This component provides badge count data to the sidebar.
         The actual badge rendering is handled by sidebar-link components
         that reference these public properties. --}}
    <div class="d-none"
         data-pending-akreditasi="{{ $pendingAkreditasiCount }}"
         data-pending-banding="{{ $pendingBandingCount }}"
         data-active-tasks="{{ $activeTaskCount }}">
    </div>
</div>
