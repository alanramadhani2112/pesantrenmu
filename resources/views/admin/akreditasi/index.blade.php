@extends('layouts.app')

@section('content')
@php
    $statusVariantMap = [
        0 => 'success',
        -1 => 'danger', -2 => 'danger',
        1 => 'warning',
        2 => 'info',
        3 => 'primary', 4 => 'primary', 5 => 'primary', 6 => 'primary',
    ];

    $stageMap = [
        6 => ['label' => 'Pengajuan', 'variant' => 'primary'],
        5 => ['label' => 'Verifikasi Berkas', 'variant' => 'warning'],
        4 => ['label' => 'Review Asesor', 'variant' => 'info'],
        3 => ['label' => 'Visitasi', 'variant' => 'info'],
        2 => ['label' => 'Penilaian Pasca Visitasi', 'variant' => 'info'],
        1 => ['label' => 'Validasi Admin', 'variant' => 'warning'],
        0 => ['label' => 'Selesai', 'variant' => 'success'],
        -1 => ['label' => 'Ditolak', 'variant' => 'danger'],
        -2 => ['label' => 'Banding', 'variant' => 'warning'],
    ];

    $activeCount = ($statusCounts['pengajuan'] ?? 0)
        + ($statusCounts['verifikasi'] ?? 0)
        + ($statusCounts['assessment'] ?? 0)
        + ($statusCounts['visitasi'] ?? 0)
        + ($statusCounts['pasca_visitasi'] ?? 0)
        + ($statusCounts['validasi'] ?? 0);
@endphp

<div data-admin-akreditasi-page="metronic" x-data="adminAkreditasiPage()">
    <x-slot name="header">{{ __('Akreditasi') }}</x-slot>

    <x-ui.index-layout
        title="Akreditasi"
        subtitle="Kelola pengajuan, penilaian, visitasi, dan tindak lanjut pesantren dari satu daftar akreditasi."
    >
        <x-slot name="toolbar">
            <x-ui.badge variant="primary">Admin</x-ui.badge>
            <x-ui.badge variant="warning">Aktif: {{ $activeCount }}</x-ui.badge>
            @if(($statusCounts['overdue'] ?? 0) > 0)
                <x-ui.badge variant="danger">Terlambat: {{ $statusCounts['overdue'] }}</x-ui.badge>
            @endif
        </x-slot>

        <x-akreditasi.workflow-stepper
            :status="$workflowStatus"
            title="Tahapan Akreditasi LP2M"
            subtitle="Alur kerja dari pengajuan berkas sampai hasil akhir akreditasi."
            class="mb-6"
        />

        <div class="row g-6 mb-6">
            <div class="col-12 col-xl-8">
                <x-ui.card
                    title="Prioritas Operasional"
                    subtitle="Gunakan ringkasan ini untuk menentukan antrean pengajuan yang perlu diproses terlebih dahulu."
                    class="h-100"
                >
                    <div class="row g-4">
                        <div class="col-12 col-md-4">
                            <x-ui.metric-box
                                label="Pengajuan"
                                :value="$statusCounts['pengajuan'] ?? 0"
                                variant="primary"
                                description="Pengajuan baru menunggu dibuka untuk verifikasi berkas."
                                actionLabel="Buka Pengajuan"
                                :actionHref="route('admin.akreditasi', array_merge(request()->query(), ['statusFilter' => 'pengajuan']))"
                            />
                        </div>

                        <div class="col-12 col-md-4">
                            <x-ui.metric-box
                                label="Review Berkas & Asesor"
                                :value="($statusCounts['verifikasi'] ?? 0) + ($statusCounts['assessment'] ?? 0)"
                                variant="warning"
                                description="Verifikasi awal admin dan review asesor sebelum visitasi dijadwalkan."
                                actionLabel="Pantau Review"
                                :actionHref="route('admin.akreditasi', array_merge(request()->query(), ['statusFilter' => 'assessment']))"
                            />
                        </div>

                        <div class="col-12 col-md-4">
                            <x-ui.metric-box
                                label="Visitasi & Penilaian"
                                :value="($statusCounts['visitasi'] ?? 0) + ($statusCounts['pasca_visitasi'] ?? 0) + ($statusCounts['validasi'] ?? 0)"
                                variant="info"
                                description="Visitasi lapangan, penilaian pasca visitasi, dan Nilai Verifikasi admin."
                                actionLabel="Lihat Jadwal"
                                :actionHref="route('admin.akreditasi', array_merge(request()->query(), ['statusFilter' => 'visitasi']))"
                            />
                        </div>
                    </div>
                </x-ui.card>
            </div>

            <div class="col-12 col-xl-4">
                <x-ui.card
                    title="Mode Kerja Admin"
                    subtitle="Alur keputusan tetap mengikuti proses yang sudah berjalan."
                    class="h-100"
                >
                    <div class="d-flex flex-column gap-4">
                        <div class="d-flex align-items-start gap-3">
                            <span class="badge badge-circle badge-light-primary">1</span>
                            <div>
                                <div class="fw-semibold text-gray-900">Verifikasi & tetapkan asesor</div>
                                <div class="text-muted fs-7">Buka berkas, periksa kelengkapan, tetapkan asesor.</div>
                            </div>
                        </div>
                        <div class="d-flex align-items-start gap-3">
                            <span class="badge badge-circle badge-light-primary">2</span>
                            <div>
                                <div class="fw-semibold text-gray-900">Pantau & jadwalkan visitasi</div>
                                <div class="text-muted fs-7">Jadwalkan visitasi, tinjau laporan, tetapkan NV.</div>
                            </div>
                        </div>
                        <div class="d-flex align-items-start gap-3">
                            <span class="badge badge-circle badge-light-primary">3</span>
                            <div>
                                <div class="fw-semibold text-gray-900">Tindak lanjuti</div>
                                <div class="text-muted fs-7">Keputusan admin tercatat pada riwayat akreditasi pesantren.</div>
                            </div>
                        </div>
                    </div>
                </x-ui.card>
            </div>
        </div>

        <x-ui.table title="Daftar Akreditasi" subtitle="Pengajuan, penilaian, visitasi, dan tindak lanjut pesantren." :records="$akreditasis" :show-per-page="false">
            <x-slot name="filters">
                <form method="GET" action="{{ route('admin.akreditasi') }}" id="admin-akreditasi-filters">
                    <div class="d-flex align-items-center gap-3 flex-wrap">
                        <x-datatable.search name="search" placeholder="Cari Pesantren..." :value="$search" form="admin-akreditasi-filters" />

                        <x-ui.select name="statusFilter" size="sm" class="w-auto min-w-280px" onchange="this.form.submit()">
                            <option value="pengajuan" @selected($statusFilter === 'pengajuan')>Pengajuan ({{ $statusCounts['pengajuan'] ?? 0 }})</option>
                            <option value="verifikasi" @selected($statusFilter === 'verifikasi')>Verifikasi Berkas ({{ $statusCounts['verifikasi'] ?? 0 }})</option>
                            <option value="assessment" @selected($statusFilter === 'assessment')>Review Asesor ({{ $statusCounts['assessment'] ?? 0 }})</option>
                            <option value="visitasi" @selected($statusFilter === 'visitasi')>Visitasi & Penilaian Pasca Visitasi ({{ ($statusCounts['visitasi'] ?? 0) + ($statusCounts['pasca_visitasi'] ?? 0) }})</option>
                            <option value="validasi" @selected($statusFilter === 'validasi')>Validasi Admin ({{ $statusCounts['validasi'] ?? 0 }})</option>
                            <option value="overdue" @selected($statusFilter === 'overdue')>Terlambat ({{ $statusCounts['overdue'] ?? 0 }})</option>
                            <option value="" @selected($statusFilter === '')>Semua</option>
                        </x-ui.select>

                        <x-ui.table-per-page name="perPage" :value="$perPage" :options="[10, 25, 50]" form="admin-akreditasi-filters" />

                        <input type="hidden" name="sortField" value="{{ $sortField }}">
                        <input type="hidden" name="sortAsc" value="{{ $sortAsc ? 'true' : 'false' }}">
                    </div>
                </form>
            </x-slot>

            <x-slot name="thead">
                <x-ui.table-th class="w-60px">
                    <x-ui.table-checkbox model="selectAll" />
                </x-ui.table-th>
                <x-ui.table-th field="user_id" :sortField="$sortField" :sortAsc="$sortAsc" form="admin-akreditasi-filters">Pesantren</x-ui.table-th>
                <x-ui.table-th field="created_at" :sortField="$sortField" :sortAsc="$sortAsc" form="admin-akreditasi-filters">Tahap Akreditasi</x-ui.table-th>
                <x-ui.table-th align="center">Nilai</x-ui.table-th>
                <x-ui.table-th align="center">Peringkat</x-ui.table-th>
                <x-ui.table-th align="center">Status</x-ui.table-th>
                <x-ui.table-th>Catatan</x-ui.table-th>
                <x-ui.table-th align="end">Aksi</x-ui.table-th>
            </x-slot>

            <x-slot name="tbody">
                @forelse ($akreditasis as $item)
                @php
                    $stage = $stageMap[(int) $item->status] ?? ['label' => 'Unknown', 'variant' => 'secondary'];
                    $stageDate = match ((int) $item->status) {
                        6 => $item->created_at->format('d/m/y'),
                        5 => $item->created_at->format('d/m/y'),
                        4 => $item->assessment1 ? \Carbon\Carbon::parse($item->assessment1->tanggal_mulai)->format('d/m/y') : '-',
                        3 => $item->tgl_visitasi ? \Carbon\Carbon::parse($item->tgl_visitasi)->format('d/m/y') : '-',
                        2 => $item->visitasi_confirmed_at ? \Carbon\Carbon::parse($item->visitasi_confirmed_at)->format('d/m/y') : ($item->tgl_visitasi ? \Carbon\Carbon::parse($item->tgl_visitasi)->format('d/m/y') : '-'),
                        1, 0, -1, -2 => $item->updated_at->format('d/m/y'),
                        default => '-',
                    };
                    $statusVariant = $statusVariantMap[(int) $item->status] ?? 'secondary';
                    $statusLabel = \App\Models\Akreditasi::getStatusLabel($item->status);
                @endphp

                <tr>
                    <td class="text-center">
                        <x-ui.table-checkbox value="{{ $item->id }}" x-model="selectedIds" />
                    </td>

                    <td>
                        <div class="d-flex flex-column">
                            <span class="text-gray-900 fw-semibold fs-6">{{ $item->user->pesantren->nama_pesantren ?? $item->user->name }}</span>
                            <span class="text-muted fw-semibold fs-7">{{ $item->user->email }}</span>
                        </div>
                    </td>

                    <td>
                        <div class="d-flex flex-column gap-1">
                            <div class="d-flex align-items-center gap-2">
                                <x-ui.badge :variant="$stage['variant']">{{ $stage['label'] }}</x-ui.badge>
                            </div>
                            <span class="text-muted fw-semibold fs-7">
                                {{ $stageDate }}
                                @if($item->tgl_visitasi_akhir && $item->tgl_visitasi != $item->tgl_visitasi_akhir)
                                    - {{ \Carbon\Carbon::parse($item->tgl_visitasi_akhir)->format('d/m/y') }}
                                @endif
                            </span>
                        </div>
                    </td>

                    <td class="text-center">
                        <span class="fw-semibold text-gray-900">{{ $item->nilai ?? '-' }}</span>
                    </td>

                    <td class="text-center">
                        <span class="fw-semibold text-gray-900">{{ $item->peringkat ?? '-' }}</span>
                    </td>

                    <td class="text-center">
                        <div class="d-flex flex-column align-items-center gap-1">
                            <x-ui.badge :variant="$statusVariant">{{ $statusLabel }}</x-ui.badge>
                            @if ($item->status == 4)
                                <x-ui.badge variant="info" class="fs-9">Pra Visitasi</x-ui.badge>
                            @endif
                            @if ($item->status == 2)
                                @php
                                    $progressTracker = app(\App\Services\ProgressTracker::class);
                                    $blockingStatus = $progressTracker->getBlockingStatus($item->id);
                                @endphp
                                @if ($blockingStatus['blocked'])
                                    @foreach ($blockingStatus['blockers'] as $blocker)
                                        @if ($blocker === 'asesor2_na')
                                            <x-ui.badge variant="warning" class="fs-9">Menunggu Anggota</x-ui.badge>
                                        @elseif ($blocker === 'asesor1_na' || $blocker === 'asesor1_nk')
                                            <x-ui.badge variant="warning" class="fs-9">Menunggu Ketua</x-ui.badge>
                                        @endif
                                    @endforeach
                                @endif
                            @endif
                            @if (isset($overdueMap[$item->id]))
                                <x-ui.badge variant="danger" class="fs-9">
                                    Terlambat {{ $overdueMap[$item->id] }} hari
                                </x-ui.badge>
                            @endif
                        </div>
                    </td>

                    <td>
                        <x-ui.button type="button" @click="openCatatanModal({{ $item->id }})" variant="light-warning" size="sm">
                            <x-ui.icon name="document" class="fs-4 me-1" />
                            {{ $item->catatans->count() }} Catatan
                        </x-ui.button>
                    </td>

                    <td class="text-end">
                        <x-ui.action-menu>
                            @if ($item->status == 6)
                                <x-ui.action-menu-item :href="route('admin.akreditasi-detail', $item->uuid)" variant="primary">
                                    <x-ui.icon name="eye" class="fs-4" />
                                    Buka untuk Review
                                </x-ui.action-menu-item>
                            @endif

                            <x-ui.action-menu-item :href="route('admin.akreditasi-detail', $item->uuid)">
                                <x-ui.icon name="eye" class="fs-4" />
                                Lihat Detail
                            </x-ui.action-menu-item>

                            <x-ui.action-menu-item
                                variant="danger"
                                x-on:click="window.SpmSwal.confirm({ title: 'Hapus data?', text: 'Pengajuan akreditasi yang dihapus tidak dapat dikembalikan!', icon: 'warning', showCancelButton: true, confirmButtonText: 'Ya, hapus', cancelButtonText: 'Batal', }).then((result) => { if (result.isConfirmed) { document.getElementById('deleteForm').querySelector('input[name=id]').value = {{ $item->id }}; document.getElementById('deleteForm').submit(); } })"
                            >
                                <x-ui.icon name="trash" class="fs-4" />
                                Hapus
                            </x-ui.action-menu-item>
                        </x-ui.action-menu>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8">
                        <x-ui.empty-state
                            title="Data tidak ditemukan"
                            description="Coba ubah filter atau kata kunci pencarian."
                            class="py-15"
                        />
                    </td>
                </tr>
                @endforelse
            </x-slot>
        </x-ui.table>
    </x-ui.index-layout>

    {{-- Catatan Modal --}}
    <x-ui.modal name="catatan-modal" focusable>
        <form id="catatan-modal-form" method="POST" action="{{ route('admin.akreditasi.catatan-modal') }}" x-on:submit.prevent="loadCatatan($event)">
            @csrf
            <input type="hidden" name="id" id="catatanAkreditasiId" value="">
        </form>
        <div id="catatan-modal-content"></div>
    </x-ui.modal>

    {{-- Delete Form --}}
    <form method="POST" action="{{ route('admin.akreditasi.delete') }}" id="deleteForm" style="display:none;">
        @csrf
        @method('DELETE')
        <input type="hidden" name="id" value="">
    </form>
</div>

<script>
function adminAkreditasiPage() {
    return {
        selectedIds: [],
        selectAllToggle(event) {
            this.selectedIds = event.target.checked
                ? Array.from(document.querySelectorAll('.row-checkbox')).map((checkbox) => checkbox.value)
                : [];
        },
        openCatatanModal(id) {
            document.getElementById('catatanAkreditasiId').value = id;
            document.getElementById('catatan-modal-form').requestSubmit();
            this.$dispatch('open-modal', 'catatan-modal');
        },
        async loadCatatan(event) {
            const target = document.getElementById('catatan-modal-content');
            target.innerHTML = '<div class="p-6 text-muted">Memuat catatan...</div>';

            const response = await fetch(event.target.action, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: new FormData(event.target),
            });

            target.innerHTML = response.ok
                ? await response.text()
                : '<div class="p-6 text-danger">Gagal memuat catatan.</div>';
        },
    };
}
</script>
@endsection
