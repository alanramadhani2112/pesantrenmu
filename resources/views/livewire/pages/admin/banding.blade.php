<?php

use App\Models\Banding;
use App\Services\BandingService;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Illuminate\Support\Facades\Auth;

new #[Layout('layouts.app')] class extends Component {
    use \Livewire\WithPagination;

    public $statusFilter = 'all';
    public $search = '';
    public $perPage = 10;

    public function updatedStatusFilter()
    {
        $this->resetPage();
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function mount()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        if (!$user->canAccessAdminArea()) {
                    abort(403);
                }
    }

    public function getBandingsProperty()
    {
        $bandingService = app(BandingService::class);
        return $bandingService->getPaginatedBandings(
            $this->statusFilter,
            $this->search,
            $this->perPage
        );
    }

    public function getPendingCountProperty()
    {
        $bandingService = app(BandingService::class);
        return $bandingService->getPendingCount();
    }
}; ?>

<div data-admin-banding-page="metronic">
    <x-slot name="header">{{ __('Banding') }}</x-slot>

    <x-ui.index-layout
        title="Banding"
        subtitle="Kelola pengajuan banding pesantren dari satu daftar."
    >
        <x-slot name="toolbar">
            <x-ui.badge variant="primary">Admin</x-ui.badge>
            <x-ui.badge variant="warning">Pending: {{ $this->pendingCount }}</x-ui.badge>
        </x-slot>

        <x-datatable.layout
            title="Daftar Banding"
            subtitle="Filter berdasarkan status, cari pesantren, lalu tindak lanjuti banding yang membutuhkan keputusan."
            :records="$this->bandings"
        >
            <x-slot name="filters">
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <x-ui.button type="button" wire:click="$set('statusFilter', 'all')" :variant="$statusFilter === 'all' ? 'primary' : 'light'" size="sm">
                        Semua
                    </x-ui.button>

                    <x-ui.button type="button" wire:click="$set('statusFilter', 'pending')" :variant="$statusFilter === 'pending' ? 'warning' : 'light'" size="sm">
                        Pending
                    </x-ui.button>

                    <x-ui.button type="button" wire:click="$set('statusFilter', 'under_review')" :variant="$statusFilter === 'under_review' ? 'info' : 'light'" size="sm">
                        Under Review
                    </x-ui.button>

                    <x-ui.button type="button" wire:click="$set('statusFilter', 'accepted')" :variant="$statusFilter === 'accepted' ? 'success' : 'light'" size="sm">
                        Accepted
                    </x-ui.button>

                    <x-ui.button type="button" wire:click="$set('statusFilter', 'rejected')" :variant="$statusFilter === 'rejected' ? 'danger' : 'light'" size="sm">
                        Rejected
                    </x-ui.button>
                </div>

                <x-datatable.search placeholder="Cari Pesantren..." />
            </x-slot>

            <x-slot name="thead">
                <x-ui.table-th>Pesantren</x-ui.table-th>
                <x-ui.table-th>Tanggal Pengajuan</x-ui.table-th>
                <x-ui.table-th>Alasan</x-ui.table-th>
                <x-ui.table-th align="center">Status</x-ui.table-th>
                <x-ui.table-th>Reviewer</x-ui.table-th>
                <x-ui.table-th align="center">Hari Sejak Pengajuan</x-ui.table-th>
            </x-slot>

            <x-slot name="tbody">
                @forelse ($this->bandings as $banding)
                @php
                    $isOverdue = $banding->isOverdue();
                    $pesantrenName = $banding->akreditasi?->user?->pesantren?->nama_pesantren ?? '-';
                    $daysSinceSubmission = (int) $banding->created_at->diffInDays(now());

                    $statusVariant = match ($banding->status) {
                        'pending' => 'warning',
                        'under_review' => 'info',
                        'accepted' => 'success',
                        'rejected' => 'danger',
                        default => 'light',
                    };

                    $statusLabel = match ($banding->status) {
                        'pending' => 'Pending',
                        'under_review' => 'Under Review',
                        'accepted' => 'Accepted',
                        'rejected' => 'Rejected',
                        default => $banding->status,
                    };
                @endphp

                <tr wire:key="banding-{{ $banding->id }}" class="{{ $isOverdue ? 'bg-light-danger' : '' }}" data-overdue="{{ $isOverdue ? 'true' : 'false' }}">
                    <td>
                        <div class="d-flex flex-column">
                            <span class="text-gray-900 fw-bold fs-6">{{ $pesantrenName }}</span>
                        </div>
                    </td>

                    <td>
                        <span class="text-gray-700 fw-semibold">{{ $banding->created_at->format('d/m/Y') }}</span>
                    </td>

                    <td>
                        <span class="text-gray-700" title="{{ $banding->alasan }}">{{ \Illuminate\Support\Str::limit($banding->alasan, 50) }}</span>
                    </td>

                    <td class="text-center">
                        <x-ui.badge :variant="$statusVariant">{{ $statusLabel }}</x-ui.badge>
                    </td>

                    <td>
                        <span class="text-gray-700">{{ $banding->reviewer?->name ?? '-' }}</span>
                    </td>

                    <td class="text-center">
                        <span class="fw-bold {{ $isOverdue ? 'text-danger' : 'text-gray-900' }}">{{ $daysSinceSubmission }} hari</span>
                        @if($isOverdue)
                            <span class="badge badge-light-danger fs-9 ms-1">Overdue</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6">
                        <x-ui.empty-state
                            title="Data tidak ditemukan"
                            description="Belum ada pengajuan banding atau coba ubah filter pencarian."
                            class="py-15"
                        />
                    </td>
                </tr>
                @endforelse
            </x-slot>
        </x-datatable.layout>
    </x-ui.index-layout>
</div>
