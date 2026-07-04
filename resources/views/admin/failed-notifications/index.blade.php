@extends('layouts.app')

@section('content')
<div x-data="failedNotificationPage()">
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

        <x-datatable.layout
            title="Daftar Notifikasi Gagal"
            subtitle="Gunakan tombol Kirim Ulang untuk mencoba kembali, atau Abaikan untuk menutup laporan."
            :records="$failedNotifications"
        >
            <x-slot name="filters">
                <form method="GET" action="{{ route('admin.failed-notifications') }}" id="fn-filter-form" class="mb-5">
                    <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                        <a href="{{ route('admin.failed-notifications', array_merge(request()->except('status', 'page'), ['status' => 'pending'])) }}"
                           class="btn btn-sm btn-{{ $statusFilter === 'pending' ? 'danger' : 'light' }}">
                            Pending
                        </a>
                        <a href="{{ route('admin.failed-notifications', array_merge(request()->except('status', 'page'), ['status' => 'resolved'])) }}"
                           class="btn btn-sm btn-{{ $statusFilter === 'resolved' ? 'success' : 'light' }}">
                            Resolved
                        </a>
                        <a href="{{ route('admin.failed-notifications', array_merge(request()->except('status', 'page'), ['status' => 'dismissed'])) }}"
                           class="btn btn-sm btn-{{ $statusFilter === 'dismissed' ? 'secondary' : 'light' }}">
                            Dismissed
                        </a>
                        <a href="{{ route('admin.failed-notifications', array_merge(request()->except('status', 'page'), ['status' => ''])) }}"
                           class="btn btn-sm btn-{{ $statusFilter === '' ? 'primary' : 'light' }}">
                            Semua
                        </a>
                    </div>

                    <div class="d-flex gap-3 align-items-center">
                        <x-datatable.search name="search" placeholder="Cari tipe, alasan, atau penerima..." :value="$search" form="fn-filter-form" />
                        <input type="hidden" name="status" value="{{ $statusFilter }}" />
                        <select name="perPage" class="form-select form-select-sm" style="width: 80px;" onchange="this.form.submit()">
                            @foreach([15, 25, 50] as $pp)
                                <option value="{{ $pp }}" @selected($perPage == $pp)>{{ $pp }}</option>
                            @endforeach
                        </select>
                    </div>
                </form>
            </x-slot>

            <x-slot name="thead">
                <x-ui.table-th>Waktu Gagal</x-ui.table-th>
                <x-ui.table-th>Penerima</x-ui.table-th>
                <x-ui.table-th>Tipe Notifikasi</x-ui.table-th>
                <x-ui.table-th>Alasan Gagal</x-ui.table-th>
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

                    <tr>
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
                                <span class="text-gray-900 fw-semibold fs-6">
                                    {{ $item->notifiable?->name ?? '(Pengguna dihapus)' }}
                                </span>
                                <span class="text-muted fw-semibold fs-7">
                                    {{ $item->notifiable?->email ?? 'ID: ' . $item->notifiable_id }}
                                </span>
                            </div>
                        </td>

                        <td>
                            <x-ui.badge variant="primary">
                                {{ $item->notification_type }}
                            </x-ui.badge>
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
        </x-datatable.layout>
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
