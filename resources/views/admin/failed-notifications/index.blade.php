@extends('layouts.app')

@section('content')
<div x-data="failedNotificationPage()" class="spm-failed-notifications-page">
    <x-ui.index-layout
        title="Notifikasi Gagal"
        subtitle="Pantau dan kelola notifikasi yang gagal terkirim setelah semua percobaan ulang habis."
    >
        <x-slot name="toolbar">
            <x-ui.badge variant="secondary">Admin</x-ui.badge>
            @if($pendingCount > 0)
                <x-ui.badge variant="danger">Pending: {{ $pendingCount }}</x-ui.badge>
            @endif
        </x-slot>

        <x-ui.table
            title="Daftar Notifikasi Gagal"
            subtitle="Gunakan tombol Kirim Ulang untuk mencoba kembali, atau Abaikan untuk menutup laporan."
            :records="$failedNotifications"
            :per-page-options="[15, 25, 50]"
            table-class="spm-failed-notifications-table"
        >
            <x-slot name="filters">
                <form method="GET" action="{{ route('admin.failed-notifications') }}" id="fn-filter-form">
                    <div class="spm-table-filter-grid spm-table-filter-grid--compact spm-failed-notifications-filters">
                        <x-ui.select name="status" size="sm" class="spm-failed-notifications-status-filter" onchange="this.form.submit()">
                            <option value="" @selected($statusFilter === '')>Semua status</option>
                            <option value="pending" @selected($statusFilter === 'pending')>Pending</option>
                            <option value="resolved" @selected($statusFilter === 'resolved')>Resolved</option>
                            <option value="dismissed" @selected($statusFilter === 'dismissed')>Dismissed</option>
                        </x-ui.select>

                        <x-datatable.search name="search" placeholder="Cari tipe, alasan, atau penerima..." :value="$search" form="fn-filter-form" />
                    </div>
                </form>
            </x-slot>

            <x-slot name="thead">
                <x-ui.table-th class="spm-failed-notifications-col-time">Waktu Gagal</x-ui.table-th>
                <x-ui.table-th class="spm-failed-notifications-col-recipient">Penerima</x-ui.table-th>
                <x-ui.table-th class="spm-failed-notifications-col-type">Tipe Notifikasi</x-ui.table-th>
                <x-ui.table-th class="spm-failed-notifications-col-reason">Alasan Gagal</x-ui.table-th>
                <x-ui.table-th class="spm-failed-notifications-col-status" align="center">Status</x-ui.table-th>
                <x-ui.table-th class="spm-failed-notifications-col-action" align="end">Aksi</x-ui.table-th>
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

                    <tr>
                        <td class="spm-failed-notifications-cell-time">
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

                        <td class="spm-failed-notifications-cell-recipient">
                            <div class="d-flex flex-column min-w-0">
                                <span class="text-gray-900 fw-semibold fs-6">
                                    {{ $item->notifiable?->name ?? '(Pengguna dihapus)' }}
                                </span>
                                <span class="text-muted fw-semibold fs-7 text-break">
                                    {{ $item->notifiable?->email ?? 'ID: ' . $item->notifiable_id }}
                                </span>
                            </div>
                        </td>

                        <td class="spm-failed-notifications-cell-type">
                            <x-ui.badge variant="primary" class="spm-failed-notifications-type-badge">
                                {{ $item->notification_type }}
                            </x-ui.badge>
                        </td>

                        <td class="spm-failed-notifications-cell-reason">
                            <span class="text-gray-700 fs-7 spm-failed-notifications-reason" title="{{ $item->failure_reason }}">
                                {{ \Illuminate\Support\Str::limit($item->failure_reason, 80) }}
                            </span>
                        </td>

                        <td class="text-center spm-failed-notifications-cell-status">
                            <x-ui.badge :variant="$statusVariant">{{ $statusLabel }}</x-ui.badge>
                        </td>

                        <td class="text-end spm-failed-notifications-cell-action">
                            @if($item->status === 'pending')
                                <x-ui.action-menu>
                                    <x-ui.action-menu-item
                                        x-on:click="confirmRetry({{ $item->id }})"
                                        variant="primary"
                                    >
                                        <x-ui.icon name="arrows-circle" class="fs-4" />
                                        Kirim Ulang
                                    </x-ui.action-menu-item>

                                    <x-ui.action-menu-item
                                        x-on:click="confirmDismiss({{ $item->id }})"
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
        </x-ui.table>
    </x-ui.index-layout>
</div>
@endsection

@push('scripts')
<script>
function failedNotificationPage() {
    const csrfToken = @js(csrf_token());
    const retryUrl = @js(route('admin.failed-notifications.retry', '__ID__'));
    const dismissUrl = @js(route('admin.failed-notifications.dismiss', '__ID__'));
    const submitPost = (urlTemplate, id) => {
        const form = document.createElement('form');
        const token = document.createElement('input');
        form.method = 'POST';
        form.action = urlTemplate.replace('__ID__', id);
        token.type = 'hidden';
        token.name = '_token';
        token.value = csrfToken;
        form.appendChild(token);
        document.body.appendChild(form);
        form.requestSubmit();
    };

    return {
        confirmRetry(id) {
            window.SpmSwal.confirm({
                title: 'Kirim ulang notifikasi?',
                text: 'Notifikasi akan dikirim ulang ke penerima.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Ya, kirim ulang',
                cancelButtonText: 'Batal',
            }).then((result) => {
                if (result.isConfirmed) submitPost(retryUrl, id);
            });
        },
        confirmDismiss(id) {
            window.SpmSwal.confirm({
                title: 'Abaikan notifikasi?',
                text: 'Notifikasi ini akan ditandai sebagai diabaikan.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, abaikan',
                cancelButtonText: 'Batal',
            }).then((result) => {
                if (result.isConfirmed) submitPost(dismissUrl, id);
            });
        }
    };
}
</script>
@endpush
