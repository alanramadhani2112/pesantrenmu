<?php

use App\Models\Akreditasi;
use App\Services\TrashService;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Gate;

new #[Layout('layouts.app')] class extends Component {
    use WithPagination;

    public string $search = '';

    public int $perPage = 10;

    public ?int $previewId = null;

    public ?array $previewData = null;

    public function mount(): void
    {
        if (! auth()->user()->canAccessAdminArea()) {
            abort(403);
        }
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    public function openRestoreConfirm(int $id): void
    {
        $this->previewId = $id;
        $this->previewData = app(TrashService::class)->getRestorePreview($id);
        $this->dispatch('open-modal', 'restore-modal');
    }

    public function openForceDeleteConfirm(int $id): void
    {
        $this->previewId = $id;
        $this->previewData = app(TrashService::class)->getRestorePreview($id);
        $this->dispatch('open-modal', 'force-delete-modal');
    }

    public function restore(): void
    {
        Gate::authorize('trash.restore');

        if ($this->previewId === null) {
            return;
        }

        $service = app(TrashService::class);

        try {
            $count = $service->restore($this->previewId);
            session()->flash('status', 'Akreditasi berhasil dipulihkan beserta '.($count - 1).' record terkait.');
        } catch (\Throwable $e) {
            session()->flash('error', 'Gagal memulihkan akreditasi: '.$e->getMessage());
        }

        $this->reset(['previewId', 'previewData']);
        $this->dispatch('close-modal', 'restore-modal');
    }

    public function forceDelete(): void
    {
        Gate::authorize('trash.purge');

        if ($this->previewId === null) {
            return;
        }

        $service = app(TrashService::class);

        try {
            $count = $service->forceDelete($this->previewId);
            session()->flash('status', "{$count} record berhasil dihapus permanen.");
        } catch (\Throwable $e) {
            session()->flash('error', 'Gagal menghapus permanen: '.$e->getMessage());
        }

        $this->reset(['previewId', 'previewData']);
        $this->dispatch('close-modal', 'force-delete-modal');
    }

    public function getTrashedAkreditasisProperty()
    {
        return app(TrashService::class)->getPaginatedTrashed(
            $this->search !== '' ? $this->search : null,
            $this->perPage
        );
    }

    public function getRetentionDaysProperty(): int
    {
        return (int) config('akreditasi.trash.retention_days', 90);
    }

    public function getTrashCountProperty(): int
    {
        return app(TrashService::class)->getTrashCount();
    }
}; ?>

<div data-admin-trash-page="metronic">
    <x-slot name="header">{{ __('Sampah Akreditasi') }}</x-slot>

    <x-ui.index-layout
        title="Sampah Akreditasi"
        subtitle="Pulihkan akreditasi yang terhapus atau hapus permanen sebelum batas retensi {{ $this->retentionDays }} hari berakhir."
    >
        <x-slot name="toolbar">
            <x-ui.badge variant="primary">Admin</x-ui.badge>
            @if ($this->trashCount > 0)
                <x-ui.badge variant="warning">Total: {{ $this->trashCount }}</x-ui.badge>
            @endif
        </x-slot>

        @if (session('status'))
            <div class="alert alert-success d-flex align-items-center mb-6" role="alert">
                <x-ui.icon name="check-circle" class="fs-4 me-3 text-success" />
                <div>{{ session('status') }}</div>
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger d-flex align-items-center mb-6" role="alert">
                <x-ui.icon name="cross-circle" class="fs-4 me-3 text-danger" />
                <div>{{ session('error') }}</div>
            </div>
        @endif

        <x-ui.card class="mb-6">
            <div class="d-flex align-items-start gap-3 p-4">
                <x-ui.icon name="information-5" class="fs-2x text-info" />
                <div>
                    <div class="fw-bold text-gray-900">Masa Retensi</div>
                    <div class="text-muted fs-7">
                        Akreditasi yang dihapus akan otomatis dihapus permanen setelah <span class="fw-bold">{{ $this->retentionDays }} hari</span>.
                        Pulihkan sebelum waktu retensi habis jika dibutuhkan kembali.
                    </div>
                </div>
            </div>
        </x-ui.card>

        <x-datatable.layout
            title="Daftar Akreditasi Terhapus"
            subtitle="Cari berdasarkan nama pesantren atau pengguna pengaju."
            :records="$this->trashedAkreditasis"
        >
            <x-slot name="filters">
                <x-datatable.search placeholder="Cari pesantren atau pengguna..." />
            </x-slot>

            <x-slot name="thead">
                <x-ui.table-th>Pesantren</x-ui.table-th>
                <x-ui.table-th>Status Sebelum Dihapus</x-ui.table-th>
                <x-ui.table-th>Dihapus Pada</x-ui.table-th>
                <x-ui.table-th>Sisa Retensi</x-ui.table-th>
                <x-ui.table-th align="end">Tindakan</x-ui.table-th>
            </x-slot>

            <x-slot name="tbody">
                @forelse ($this->trashedAkreditasis as $item)
                    @php
                        $deletedAt = $item->deleted_at;
                        $retentionDays = $this->retentionDays;
                        $expiresAt = $deletedAt?->copy()->addDays($retentionDays);
                        $remainingDays = $expiresAt ? max(0, (int) round(now()->floatDiffInDays($expiresAt, false))) : null;
                        $isExpiringSoon = $remainingDays !== null && $remainingDays <= 7;
                    @endphp
                    <tr>
                        <td>
                            <div class="d-flex flex-column">
                                <span class="text-gray-900 fw-bold fs-6">
                                    {{ $item->user?->pesantren?->nama_pesantren ?? $item->user?->name ?? '(Akun terhapus)' }}
                                </span>
                                <span class="text-muted fs-8">
                                    ID: {{ $item->id }}
                                </span>
                            </div>
                        </td>

                        <td>
                            <x-ui.badge variant="secondary">
                                {{ \App\Models\Akreditasi::getStatusLabel($item->status) }}
                            </x-ui.badge>
                        </td>

                        <td>
                            <span class="text-gray-700 fs-7">
                                {{ $deletedAt?->format('d/m/Y H:i') ?? '-' }}
                            </span>
                            <div class="text-muted fs-8">
                                {{ $deletedAt?->diffForHumans() ?? '' }}
                            </div>
                        </td>

                        <td>
                            @if ($remainingDays !== null)
                                <x-ui.badge :variant="$isExpiringSoon ? 'danger' : 'success'">
                                    {{ $remainingDays }} hari
                                </x-ui.badge>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>

                        <td class="text-end">
                            <x-ui.action-menu>
                                <x-ui.action-menu-item
                                    wire:click="openRestoreConfirm({{ $item->id }})"
                                    variant="success"
                                >
                                    <x-ui.icon name="arrows-circle" class="fs-4" />
                                    Pulihkan
                                </x-ui.action-menu-item>

                                <x-ui.action-menu-item
                                    wire:click="openForceDeleteConfirm({{ $item->id }})"
                                    variant="danger"
                                >
                                    <x-ui.icon name="trash" class="fs-4" />
                                    Hapus Permanen
                                </x-ui.action-menu-item>
                            </x-ui.action-menu>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted py-8">
                            <x-ui.icon name="trash" class="fs-2x text-muted mb-2" />
                            <div>Tidak ada akreditasi terhapus saat ini.</div>
                        </td>
                    </tr>
                @endforelse
            </x-slot>
        </x-datatable.layout>
    </x-ui.index-layout>

    {{-- Restore confirmation modal --}}
    <x-ui.modal name="restore-modal">
        <x-ui.modal-header
            title="Pulihkan Akreditasi"
            subtitle="Tindakan ini akan memulihkan akreditasi beserta seluruh record terkait."
            icon="arrows-circle"
            variant="success"
        />

        <x-ui.modal-body>
            @if ($previewData)
                <p class="text-gray-700 fs-6">Anda akan memulihkan akreditasi berikut beserta semua record terkait:</p>
                <ul class="list-unstyled fs-7 mt-3">
                    <li><strong>Pesantren:</strong> {{ $previewData['akreditasi']->user?->pesantren?->nama_pesantren ?? $previewData['akreditasi']->user?->name ?? 'N/A' }}</li>
                    <li><strong>Assessment:</strong> {{ $previewData['children']['assessment'] }} record</li>
                    <li><strong>EDPM:</strong> {{ $previewData['children']['akreditasi_edpm'] }} record</li>
                    <li><strong>Catatan EDPM:</strong> {{ $previewData['children']['akreditasi_edpm_catatan'] }} record</li>
                    <li class="text-success fw-bold mt-2">Total: {{ $previewData['children']['total'] + 1 }} record</li>
                </ul>
            @endif
        </x-ui.modal-body>

        <x-ui.modal-footer>
            <x-ui.button x-on:click="$dispatch('close-modal', 'restore-modal')" variant="light">
                Batal
            </x-ui.button>
            <x-ui.button wire:click="restore" variant="success">
                <x-ui.icon name="arrows-circle" class="fs-4 me-1" />
                Pulihkan Sekarang
            </x-ui.button>
        </x-ui.modal-footer>
    </x-ui.modal>

    {{-- Force delete confirmation modal --}}
    <x-ui.modal name="force-delete-modal">
        <x-ui.modal-header
            title="Hapus Permanen Akreditasi"
            subtitle="Tindakan ini tidak dapat dibatalkan."
            icon="information-5"
            variant="danger"
        />

        <x-ui.modal-body>
            @if ($previewData)
                <div class="alert alert-danger d-flex align-items-start gap-3 mb-4" role="alert">
                    <x-ui.icon name="information-5" class="fs-2x text-danger" />
                    <div>
                        <div class="fw-bold">Tindakan Tidak Dapat Dibatalkan</div>
                        <div class="fs-7">Akreditasi ini akan dihapus permanen dari database beserta semua record terkait.</div>
                    </div>
                </div>

                <ul class="list-unstyled fs-7 mt-3">
                    <li><strong>Pesantren:</strong> {{ $previewData['akreditasi']->user?->pesantren?->nama_pesantren ?? $previewData['akreditasi']->user?->name ?? 'N/A' }}</li>
                    <li><strong>Assessment:</strong> {{ $previewData['children']['assessment'] }} record</li>
                    <li><strong>EDPM:</strong> {{ $previewData['children']['akreditasi_edpm'] }} record</li>
                    <li><strong>Catatan EDPM:</strong> {{ $previewData['children']['akreditasi_edpm_catatan'] }} record</li>
                    <li class="text-danger fw-bold mt-2">Total dihapus permanen: {{ $previewData['children']['total'] + 1 }} record</li>
                </ul>
            @endif
        </x-ui.modal-body>

        <x-ui.modal-footer>
            <x-ui.button x-on:click="$dispatch('close-modal', 'force-delete-modal')" variant="light">
                Batal
            </x-ui.button>
            <x-ui.button wire:click="forceDelete" variant="danger">
                <x-ui.icon name="trash" class="fs-4 me-1" />
                Hapus Permanen
            </x-ui.button>
        </x-ui.modal-footer>
    </x-ui.modal>
</div>
