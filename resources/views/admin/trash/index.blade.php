@extends('layouts.app')

@section('content')
<div data-admin-trash-page="metronic" x-data="adminTrashPage()">
    <x-slot name="header">{{ __('Arsip Akreditasi') }}</x-slot>

    <x-ui.index-layout
        title="Arsip Akreditasi"
        subtitle="Pulihkan akreditasi yang terhapus atau hapus permanen sebelum batas retensi {{ $retentionDays }} hari berakhir."
    >
        <x-slot name="toolbar">
            <x-ui.badge variant="primary">Admin</x-ui.badge>
            @if ($trashCount > 0)
                <x-ui.badge variant="warning">Total: {{ $trashCount }}</x-ui.badge>
            @endif
        </x-slot>

        <x-ui.card class="mb-6">
            <div class="d-flex align-items-start gap-3 p-4">
                <x-ui.icon name="information-5" class="fs-2x text-info" />
                <div>
                    <div class="fw-semibold text-gray-900">Masa Retensi</div>
                    <div class="text-muted fs-7">
                        Akreditasi yang dihapus akan otomatis dihapus permanen setelah <span class="fw-semibold">{{ $retentionDays }} hari</span>.
                        Pulihkan sebelum waktu retensi habis jika dibutuhkan kembali.
                    </div>
                </div>
            </div>
        </x-ui.card>

        <x-ui.table
            title="Daftar Akreditasi Terhapus"
            subtitle="Cari berdasarkan nama pesantren atau pengguna pengaju."
            :records="$trashedAkreditasis->appends(compact('search', 'perPage'))"
            :show-per-page="false"
        >
            <x-slot name="filters">
                <form method="GET" action="{{ route('admin.trash') }}" id="trash-filter-form" class="mb-5">
                    <div class="d-flex gap-3 align-items-center">
                        <x-datatable.search name="search" placeholder="Cari pesantren atau pengguna..." :value="$search" form="trash-filter-form" />
                        <x-ui.table-per-page name="perPage" :value="$perPage" :options="[10, 25, 50]" form="trash-filter-form" />
                    </div>
                </form>
            </x-slot>

            <x-slot name="thead">
                <x-ui.table-th>Pesantren</x-ui.table-th>
                <x-ui.table-th>Status Sebelum Dihapus</x-ui.table-th>
                <x-ui.table-th>Dihapus Pada</x-ui.table-th>
                <x-ui.table-th>Sisa Retensi</x-ui.table-th>
                <x-ui.table-th align="end">Tindakan</x-ui.table-th>
            </x-slot>

            <x-slot name="tbody">
                @forelse ($trashedAkreditasis as $item)
                    @php
                        $deletedAt = $item->deleted_at;
                        $expiresAt = $deletedAt?->copy()->addDays($retentionDays);
                        $remainingDays = $expiresAt ? max(0, (int) round(now()->floatDiffInDays($expiresAt, false))) : null;
                        $isExpiringSoon = $remainingDays !== null && $remainingDays <= 7;
                    @endphp
                    <tr>
                        <td>
                            <div class="d-flex flex-column">
                                <span class="text-gray-900 fw-semibold fs-6">
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
                                    variant="success"
                                    x-on:click.prevent="openRestoreConfirm({{ $item->id }})"
                                >
                                    <x-ui.icon name="arrows-circle" class="fs-4" />
                                    Pulihkan
                                </x-ui.action-menu-item>

                                <x-ui.action-menu-item
                                    variant="danger"
                                    x-on:click.prevent="openForceDeleteConfirm({{ $item->id }})"
                                >
                                    <x-ui.icon name="trash" class="fs-4" />
                                    Hapus Permanen
                                </x-ui.action-menu-item>
                            </x-ui.action-menu>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">
                            <x-ui.empty-state
                                title="Tidak ada akreditasi terhapus saat ini"
                                description="Arsip kosong. Akreditasi yang dihapus akan tampil di sini selama masa retensi."
                                icon="trash"
                                class="py-15"
                            />
                        </td>
                    </tr>
                @endforelse
            </x-slot>
        </x-ui.table>
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
            <template x-if="previewData">
                <div>
                    <p class="text-gray-700 fs-6">Anda akan memulihkan akreditasi berikut beserta semua record terkait:</p>
                    <ul class="list-unstyled fs-7 mt-3">
                        <li><strong>Pesantren:</strong> <span x-text="previewData?.akreditasi?.user?.pesantren?.nama_pesantren ?? previewData?.akreditasi?.user?.name ?? 'N/A'"></span></li>
                        <li><strong>Penugasan Asesor:</strong> <span x-text="previewData?.children?.assessment + ' record'"></span></li>
                        <li><strong>EDPM:</strong> <span x-text="previewData?.children?.akreditasi_edpm + ' record'"></span></li>
                        <li><strong>Catatan EDPM:</strong> <span x-text="previewData?.children?.akreditasi_edpm_catatan + ' record'"></span></li>
                        <li class="text-success fw-semibold mt-2">Total: <span x-text="(previewData?.children?.total ?? 0) + 1 + ' record'"></span></li>
                    </ul>
                </div>
            </template>
            <template x-if="!previewData">
                <div class="text-center py-4">
                    <span class="spinner-border spinner-border-sm text-primary" role="status"></span>
                    <span class="ms-2 text-muted">Memuat data...</span>
                </div>
            </template>
        </x-ui.modal-body>

        <x-ui.modal-footer>
            <x-ui.button x-on:click="$dispatch('close-modal', 'restore-modal'); clearPreview()" variant="light">
                Batal
            </x-ui.button>
            <form method="POST" action="{{ route('admin.trash.restore') }}" x-show="previewData">
                @csrf
                <input type="hidden" name="id" x-model="previewId">
                <x-ui.button type="submit" variant="success">
                    <x-ui.icon name="arrows-circle" class="fs-4 me-1" />
                    Pulihkan Sekarang
                </x-ui.button>
            </form>
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
            <template x-if="previewData">
                <div>
                    <x-ui.alert variant="danger" title="Tindakan Tidak Dapat Dibatalkan" class="mb-4">
                        Akreditasi ini akan dihapus permanen dari database beserta semua record terkait.
                    </x-ui.alert>

                    <ul class="list-unstyled fs-7 mt-3">
                        <li><strong>Pesantren:</strong> <span x-text="previewData?.akreditasi?.user?.pesantren?.nama_pesantren ?? previewData?.akreditasi?.user?.name ?? 'N/A'"></span></li>
                        <li><strong>Penugasan Asesor:</strong> <span x-text="previewData?.children?.assessment + ' record'"></span></li>
                        <li><strong>EDPM:</strong> <span x-text="previewData?.children?.akreditasi_edpm + ' record'"></span></li>
                        <li><strong>Catatan EDPM:</strong> <span x-text="previewData?.children?.akreditasi_edpm_catatan + ' record'"></span></li>
                        <li class="text-danger fw-semibold mt-2">Total dihapus permanen: <span x-text="(previewData?.children?.total ?? 0) + 1 + ' record'"></span></li>
                    </ul>
                </div>
            </template>
            <template x-if="!previewData">
                <div class="text-center py-4">
                    <span class="spinner-border spinner-border-sm text-primary" role="status"></span>
                    <span class="ms-2 text-muted">Memuat data...</span>
                </div>
            </template>
        </x-ui.modal-body>

        <x-ui.modal-footer>
            <x-ui.button x-on:click="$dispatch('close-modal', 'force-delete-modal'); clearPreview()" variant="light">
                Batal
            </x-ui.button>
            <form method="POST" action="{{ route('admin.trash.force-delete') }}" x-show="previewData">
                @csrf
                <input type="hidden" name="id" x-model="previewId">
                <x-ui.button type="submit" variant="danger">
                    <x-ui.icon name="trash" class="fs-4 me-1" />
                    Hapus Permanen
                </x-ui.button>
            </form>
        </x-ui.modal-footer>
    </x-ui.modal>
</div>

<script>
    function adminTrashPage() {
        return {
            previewId: null,
            previewData: null,

            async openRestoreConfirm(id) {
                this.previewId = id;
                this.previewData = null;
                window.dispatchEvent(new CustomEvent('open-modal', { detail: 'restore-modal' }));
                await this.fetchPreview(id);
            },

            async openForceDeleteConfirm(id) {
                this.previewId = id;
                this.previewData = null;
                window.dispatchEvent(new CustomEvent('open-modal', { detail: 'force-delete-modal' }));
                await this.fetchPreview(id);
            },

            async fetchPreview(id) {
                try {
                    const resp = await fetch(`/admin/trash/preview/${id}`, {
                        headers: { 'Accept': 'application/json' }
                    });
                    if (resp.ok) {
                        this.previewData = await resp.json();
                    }
                } catch (e) {
                    console.error('Gagal memuat preview:', e);
                }
            },

            clearPreview() {
                this.previewId = null;
                this.previewData = null;
            }
        };
    }
</script>
@endsection
