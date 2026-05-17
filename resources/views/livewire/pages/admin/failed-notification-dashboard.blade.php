<div>
    <x-slot name="header">{{ __('Notifikasi Gagal') }}</x-slot>

    <x-ui.index-layout
        title="Notifikasi Gagal"
        subtitle="Pantau dan kelola notifikasi yang gagal terkirim setelah semua percobaan ulang habis."
    >
        <x-slot name="toolbar">
            <x-ui.badge variant="primary">Admin</x-ui.badge>
            @if($pendingCount > 0)
                <x-ui.badge variant="danger">Pending: {{ $pendingCount }}</x-ui.badge>
            @endif
        </x-slot>

        @if(session('status'))
            <div class="alert alert-success d-flex align-items-center mb-6" role="alert">
                <x-ui.icon name="check-circle" class="fs-4 me-3 text-success" />
                <div>{{ session('status') }}</div>
            </div>
        @endif

        <x-datatable.layout
            title="Daftar Notifikasi Gagal"
            subtitle="Gunakan tombol Kirim Ulang untuk mencoba kembali, atau Abaikan untuk menutup laporan."
            :records="$failedNotifications"
        >
            <x-slot name="filters">
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <x-ui.button
                        type="button"
                        wire:click="$set('statusFilter', 'pending')"
                        :variant="$statusFilter === 'pending' ? 'danger' : 'light'"
                        size="sm"
                    >
                        Pending
                    </x-ui.button>

                    <x-ui.button
                        type="button"
                        wire:click="$set('statusFilter', 'resolved')"
                        :variant="$statusFilter === 'resolved' ? 'success' : 'light'"
                        size="sm"
                    >
                        Resolved
                    </x-ui.button>

                    <x-ui.button
                        type="button"
                        wire:click="$set('statusFilter', 'dismissed')"
                        :variant="$statusFilter === 'dismissed' ? 'secondary' : 'light'"
                        size="sm"
                    >
                        Dismissed
                    </x-ui.button>

                    <x-ui.button
                        type="button"
                        wire:click="$set('statusFilter', '')"
                        :variant="$statusFilter === '' ? 'primary' : 'light'"
                        size="sm"
                    >
                        Semua
                    </x-ui.button>
                </div>

                <x-datatable.search placeholder="Cari tipe, alasan, atau penerima..." />
            </x-slot>

            <x-slot name="thead">
                <x-ui.table-th>Waktu Gagal</x-ui.table-th>
                <x-ui.table-th>Penerima</x-ui.table-th>
                <x-ui.table-th>Tipe Notifikasi</x-ui.table-th>
                <x-ui.table-th>Alasan Kegagalan</x-ui.table-th>
                <x-ui.table-th align="center">Status</x-ui.table-th>
                <x-ui.table-th align="end">Aksi</x-ui.table-th>
            </x-slot>

            <x-slot name="tbody">
                @forelse ($failedNotifications as $item)
                    @php
                        $statusVariant = match ($item->status) {
                            'pending'   => 'danger',
                            'resolved'  => 'success',
                            'dismissed' => 'secondary',
                            default     => 'light',
                        };
                        $statusLabel = match ($item->status) {
                            'pending'   => 'Pending',
                            'resolved'  => 'Resolved',
                            'dismissed' => 'Dismissed',
                            default     => $item->status,
                        };
                    @endphp

                    <tr wire:key="fn-{{ $item->id }}">
                        <td>
                            <div class="d-flex flex-column">
                                <span class="text-gray-900 fw-semibold fs-7">
                                    {{ $item->failed_at?->format('d/m/Y H:i') ?? '-' }}
                                </span>
                                @if($item->resolved_at)
                                    <span class="text-muted fs-8">
                                        Resolved: {{ $item->resolved_at->format('d/m/Y H:i') }}
                                    </span>
                                @endif
                            </div>
                        </td>

                        <td>
                            <div class="d-flex flex-column">
                                <span class="text-gray-900 fw-bold fs-6">
                                    {{ $item->notifiable?->name ?? '(Pengguna dihapus)' }}
                                </span>
                                <span class="text-muted fw-semibold fs-7">
                                    {{ $item->notifiable?->email ?? 'ID: ' . $item->notifiable_id }}
                                </span>
                            </div>
                        </td>

                        <td>
                            <span class="badge badge-light-primary fw-semibold fs-8">
                                {{ $item->notification_type }}
                            </span>
                        </td>

                        <td>
                            <span class="text-gray-700 fs-7" title="{{ $item->failure_reason }}">
                                {{ \Illuminate\Support\Str::limit($item->failure_reason, 80) }}
                            </span>
                        </td>

                        <td class="text-center">
                            <x-ui.badge :variant="$statusVariant">{{ $statusLabel }}</x-ui.badge>
                        </td>

                        <td class="text-end">
                            @if($item->status === 'pending')
                                <x-ui.action-menu>
                                    <x-ui.action-menu-item
                                        wire:click="retry({{ $item->id }})"
                                        variant="primary"
                                    >
                                        <x-ui.icon name="arrows-circle" class="fs-4" />
                                        Kirim Ulang
                                    </x-ui.action-menu-item>

                                    <x-ui.action-menu-item
                                        wire:click="dismiss({{ $item->id }})"
                                        variant="secondary"
                                    >
                                        <x-ui.icon name="cross-circle" class="fs-4" />
                                        Abaikan
                                    </x-ui.action-menu-item>
                                </x-ui.action-menu>
                            @else
                                <span class="text-muted fs-8">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">
                            <x-ui.empty-state
                                title="Tidak ada notifikasi gagal"
                                description="Semua notifikasi berhasil terkirim atau sudah ditangani."
                                class="py-15"
                            />
                        </td>
                    </tr>
                @endforelse
            </x-slot>
        </x-datatable.layout>
    </x-ui.index-layout>
</div>
