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
            <x-ui.badge variant="warning">Tertunda: {{ $this->pendingCount }}</x-ui.badge>
        </x-slot>

        <x-datatable.layout
            title="Daftar Banding"
            subtitle="Pengajuan banding pesantren yang membutuhkan peninjauan dan keputusan admin."
            :records="$this->bandings"
        >
            <x-slot name="filters">
                <x-datatable.search placeholder="Cari Pesantren..." />

                <x-ui.filter-select model="statusFilter" placeholder="Semua Status">
                    <option value="all">Semua</option>
                    <option value="pending">Tertunda</option>
                    <option value="under_review">Dalam Peninjauan</option>
                    <option value="accepted">Diterima</option>
                    <option value="rejected">Ditolak</option>
                </x-ui.filter-select>
            </x-slot>

            <x-slot name="thead">
                <x-ui.table-th>Pesantren</x-ui.table-th>
                <x-ui.table-th>Tanggal Pengajuan</x-ui.table-th>
                <x-ui.table-th>Alasan</x-ui.table-th>
                <x-ui.table-th align="center">Status</x-ui.table-th>
                <x-ui.table-th>Peninjau</x-ui.table-th>
                <x-ui.table-th align="center">Hari Sejak Pengajuan</x-ui.table-th>
                <x-ui.table-th align="end">Aksi</x-ui.table-th>
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
                        'pending' => 'Tertunda',
                        'under_review' => 'Dalam Peninjauan',
                        'accepted' => 'Diterima',
                        'rejected' => 'Ditolak',
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
                            <x-ui.badge variant="danger" class="ms-1">Terlambat</x-ui.badge>
                        @endif
                    </td>
                    <td class="text-end">
                        <x-ui.action-menu>
                            <x-ui.action-menu-item :href="route('admin.banding-detail', $banding->id)">
                                <x-ui.icon name="eye" class="fs-5 text-gray-500" />
                                Lihat Detail
                            </x-ui.action-menu-item>
                        </x-ui.action-menu>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7">
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
