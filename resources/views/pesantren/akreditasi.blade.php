@extends('layouts.app')

@section('header', 'Pusat Akreditasi')

@section('content')
@php
    $statusBadgeClass = [
        '0' => 'badge-light-warning',
        '1' => 'badge-light-success',
        '2' => 'badge-light-info',
        '-1' => 'badge-light-danger',
        'hasil_akhir' => 'badge-light-primary',
    ];
    $statusLabels = [
        '0' => 'Pending',
        '1' => 'Selesai',
        '2' => 'Kartu Kendali',
        '-1' => 'Perbaikan',
        'hasil_akhir' => 'Hasil Akhir',
    ];
    $tahapanLabels = [
        'pengajuan' => 'Pengajuan',
        'verifikasi' => 'Verifikasi',
        'visitasi' => 'Visitasi',
        'penilaian' => 'Penilaian',
        'hasil' => 'Hasil',
    ];
    $focuses = [
        '' => 'Semua Proses',
        'perbaikan' => 'Perbaikan',
        'kartu_kendali' => 'Kartu Kendali',
        'hasil' => 'Hasil',
    ];
    $focusRoutes = [
        '' => 'pesantren.akreditasi',
        'perbaikan' => 'pesantren.akreditasi.perbaikan',
        'kartu_kendali' => 'pesantren.akreditasi.kartu-kendali',
        'hasil' => 'pesantren.akreditasi.hasil',
    ];
    $focusConfig = [
        '' => [
            'title' => 'Pusat Akreditasi',
            'subtitle' => 'Satu tempat untuk mengajukan, memperbaiki, memantau visitasi, dan melihat hasil akreditasi.',
            'table_title' => 'Riwayat Pengajuan',
            'table_subtitle' => 'Semua pengajuan akreditasi pesantren Anda.',
            'empty_title' => 'Belum ada pengajuan akreditasi',
            'empty_description' => 'Belum ada pengajuan aktif. Lengkapi Profil, IPM, SDM, dan EDPM/IPR, lalu ajukan akreditasi dari halaman ini. Panduan IAPM tersedia sebagai bahan baca. Perbaikan, Kartu Kendali, dan Hasil akan muncul setelah proses berjalan.',
            'icon' => 'lock-2',
            'variant' => 'primary',
        ],
        'perbaikan' => [
            'title' => 'Status Perbaikan',
            'subtitle' => 'Bagian dari proses akreditasi. Jika ada catatan, tindak lanjutnya tetap muncul di pusat akreditasi ini.',
            'table_title' => 'Riwayat Pengajuan',
            'table_subtitle' => 'Semua pengajuan tetap ditampilkan agar konteks proses tidak terputus. Fokus saat ini: Perbaikan.',
            'empty_title' => 'Tidak ada perbaikan aktif',
            'empty_description' => 'Semua pengajuan tidak sedang membutuhkan perbaikan berkas.',
            'icon' => 'messages',
            'variant' => 'warning',
        ],
        'kartu_kendali' => [
            'title' => 'Kartu Kendali Visitasi',
            'subtitle' => 'Kartu kendali adalah status/berkas visitasi dalam proses akreditasi, bukan proses terpisah dari pengajuan.',
            'table_title' => 'Riwayat Pengajuan',
            'table_subtitle' => 'Semua pengajuan tetap ditampilkan agar kartu kendali terbaca sebagai bagian dari proses visitasi.',
            'empty_title' => 'Belum ada kartu kendali',
            'empty_description' => 'Belum ada pengajuan yang masuk tahap kartu kendali visitasi.',
            'icon' => 'check-circle',
            'variant' => 'info',
        ],
        'hasil' => [
            'title' => 'Hasil Akhir Akreditasi',
            'subtitle' => 'Hasil akhir muncul di halaman yang sama setelah validasi dan penerbitan SK selesai.',
            'table_title' => 'Riwayat Pengajuan',
            'table_subtitle' => 'Semua pengajuan tetap ditampilkan. Hasil akhir muncul jika validasi dan SK sudah selesai.',
            'empty_title' => 'Hasil akhir belum tersedia',
            'empty_description' => 'Hasil akhir akan muncul setelah proses validasi dan penerbitan SK selesai.',
            'icon' => 'chart-line-up',
            'variant' => 'success',
        ],
    ];
    $activeFocusConfig = $focusConfig[$focus] ?? $focusConfig[''];
    $queryParams = array_filter(request()->except(['focus', 'page']));
    $currentRoute = $focusRoutes[$focus] ?? $focusRoutes[''];
    $latestAkreditasi = $akreditasis->first();
    $latestStatusLabel = $latestAkreditasi
        ? ($statusLabels[$latestAkreditasi->status] ?? $latestAkreditasi->status)
        : 'Belum Ada';
@endphp

<div data-module-page="pesantren-akreditasi">
<x-ui.page
    title="Pusat Akreditasi"
    :subtitle="$activeFocusConfig['subtitle']"
>
    <x-slot:toolbar>
        @if($completeness['can_submit'] ?? false)
            <form action="{{ route('pesantren.akreditasi.create') }}" method="POST" id="createAkreditasiForm">
                @csrf
                <x-ui.button type="submit" variant="primary" id="btnCreateAkreditasi">
                    <i class="ki-solid ki-plus fs-4 me-1"></i>
                    Ajukan Akreditasi
                </x-ui.button>
            </form>
        @endif
    </x-slot:toolbar>

    @if(session('success'))
        <x-ui.alert variant="success" title="Berhasil" class="mb-4">{{ session('success') }}</x-ui.alert>
    @endif
    @if(session('error'))
        <x-ui.alert variant="danger" title="Gagal" class="mb-4">{{ session('error') }}</x-ui.alert>
    @endif

    {{-- Completeness Check --}}
    @if(!($completeness['can_submit'] ?? true))
        <x-ui.alert variant="warning" icon="information-3" title="Data Belum Lengkap" class="mb-4">
            <div class="mb-3">
                <div class="d-flex align-items-center gap-3 mb-2">
                    <div class="progress flex-grow-1 spm-progress-enhanced" style="height:8px;">
                        <div class="progress-bar bg-warning" style="width:{{ $completeness['percentage'] }}%"></div>
                    </div>
                    <span class="text-muted fs-8">{{ $completeness['complete'] }}/{{ $completeness['total'] }} ({{ $completeness['percentage'] }}%)</span>
                </div>
                @if(!empty($completeness['incomplete_items']))
                    <div class="mt-2 fs-8 text-muted">
                        <strong>Data yang perlu dilengkapi:</strong>
                        <ul class="mb-0 ps-3 mt-1">
                            @foreach($completeness['incomplete_items'] as $item)
                                <li class="d-flex align-items-start gap-1">
                                    <x-ui.icon name="information-5" class="fs-8 text-warning mt-1 flex-shrink-0" />
                                    <span>{{ $item }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                <div class="mt-3">
                    <a href="{{ route('pesantren.profile') }}" class="btn btn-sm btn-warning fw-semibold">
                        <x-ui.icon name="pencil" class="fs-4 me-1" /> Lengkapi Data Sekarang
                    </a>
                </div>
            </div>
        </x-ui.alert>
    @endif

    <div class="row g-5 mb-6 spm-akreditasi-context-cards">
        <div class="col-md-4">
            <x-ui.stat-card label="Pengajuan Terbaru" value="{{ $latestAkreditasi ? ($tahapanLabels[$latestAkreditasi->tahapan] ?? ucfirst($latestAkreditasi->tahapan)) : 'Belum Ada' }}" :variant="$activeFocusConfig['variant']" :icon="$activeFocusConfig['icon']" />
        </div>
        <div class="col-md-4">
            <x-ui.stat-card label="Data Tampil" value="{{ $akreditasis->total() }}" variant="info" icon="data" />
        </div>
        <div class="col-md-4">
            <x-ui.stat-card label="Status Terbaru" value="{{ $latestStatusLabel }}" variant="secondary" icon="information-5" />
        </div>
    </div>

    <x-ui.alert variant="info" icon="information-5" title="Alur Akreditasi" class="mb-5 spm-akreditasi-ux-note">
        Gunakan filter Tahapan untuk melihat posisi pengajuan. Perbaikan, kartu kendali, dan hasil tetap bagian dari satu alur yang sama.
        <div class="mt-3 d-flex flex-wrap gap-2">
            <a href="{{ route('documents.index', ['doc' => 'iapm']) }}" class="btn btn-sm btn-light-primary fw-semibold">
                <x-ui.icon name="document" class="fs-5 me-1" /> Baca Panduan IAPM
            </a>
        </div>
    </x-ui.alert>

    <x-ui.table
        :title="$activeFocusConfig['table_title']"
        :subtitle="$activeFocusConfig['table_subtitle']"
        :records="$akreditasis"
        :per-page-options="[5, 10, 25, 50]"
        table-class="spm-table-compact"
    >
        <x-slot name="filters">
            <form method="GET" action="{{ route($currentRoute) }}" id="pesantren-akreditasi-filter-form">
                <input type="hidden" name="sortField" value="{{ $sortField }}">
                <input type="hidden" name="sortAsc" value="{{ $sortAsc ? 'true' : 'false' }}">
                <div class="d-flex align-items-center gap-3 flex-wrap">
                    <x-datatable.search name="search" placeholder="Cari periode atau ID..." :value="$search" form="pesantren-akreditasi-filter-form" />

                    <x-ui.input name="periodeFilter" value="{{ $periodeFilter }}" class="w-auto min-w-120px" placeholder="2025" />

                    <x-ui.select name="tahapanFilter" size="sm" class="w-auto min-w-160px" onchange="this.form.submit()">
                        <option value="">Semua Tahapan</option>
                        @foreach($tahapanLabels as $val => $label)
                            <option value="{{ $val }}" {{ $tahapanFilter === $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </x-ui.select>

                    <x-ui.button type="submit" variant="light" size="sm">
                        <x-ui.icon name="setting-2" class="fs-4 me-1" />
                        Filter
                    </x-ui.button>
                </div>
            </form>
        </x-slot>

        <x-slot name="thead">
            <x-ui.table-th :min-width="false">No</x-ui.table-th>
            <x-ui.table-th :min-width="false">ID</x-ui.table-th>
            <x-ui.table-th>Periode</x-ui.table-th>
            <x-ui.table-th>Status</x-ui.table-th>
            <x-ui.table-th>Tahapan</x-ui.table-th>
            <x-ui.table-th>Tanggal Pengajuan</x-ui.table-th>
            <x-ui.table-th align="end">Aksi</x-ui.table-th>
        </x-slot>

        <x-slot name="tbody">
            @forelse($akreditasis as $akreditasi)
                @php $statusLabel = $statusLabels[$akreditasi->status] ?? $akreditasi->status; @endphp
                <tr>
                    <td>{{ $loop->iteration + (($akreditasis->currentPage() - 1) * $akreditasis->perPage()) }}</td>
                    <td class="fw-semibold">{{ $akreditasi->id }}</td>
                    <td>{{ $akreditasi->periode }}</td>
                    <td>
                        <span class="spm-status-badge badge {{ $statusBadgeClass[$akreditasi->status] ?? 'badge-light-secondary' }}">
                            {{ $statusLabel }}
                        </span>
                    </td>
                    <td>{{ $tahapanLabels[$akreditasi->tahapan] ?? ucfirst($akreditasi->tahapan) }}</td>
                    <td>{{ $akreditasi->created_at->format('d M Y') }}</td>
                    <td class="text-end">
                        <x-ui.action-menu>
                            <x-ui.action-menu-item :href="route('pesantren.akreditasi-detail', $akreditasi->uuid)" variant="primary">
                                <x-ui.icon name="eye" class="fs-5" />
                                Lihat Detail
                            </x-ui.action-menu-item>

                            @if($akreditasi->status === '0')
                                <x-ui.action-menu-item variant="danger" x-on:click="confirmDelete({{ $akreditasi->id }})">
                                    <x-ui.icon name="trash" class="fs-5" />
                                    Hapus Pengajuan
                                </x-ui.action-menu-item>

                                <x-ui.action-menu-item variant="warning" x-on:click="confirmCancel({{ $akreditasi->id }})">
                                    <x-ui.icon name="cross-circle" class="fs-5" />
                                    Batalkan Pengajuan
                                </x-ui.action-menu-item>
                            @endif

                            @if($akreditasi->status === '-1')
                                <x-ui.action-menu-item variant="danger" x-on:click="confirmDelete({{ $akreditasi->id }})">
                                    <x-ui.icon name="trash" class="fs-5" />
                                    Hapus Pengajuan
                                </x-ui.action-menu-item>

                                <x-ui.action-menu-item variant="primary" x-on:click="openBandingModal({{ $akreditasi->id }})">
                                    <x-ui.icon name="document" class="fs-5" />
                                    Banding
                                </x-ui.action-menu-item>
                            @endif

                            @if(in_array($akreditasi->status, ['1', 'hasil_akhir']))
                                <x-ui.action-menu-item variant="primary" x-on:click="openCatatanModal({{ $akreditasi->id }})">
                                    <x-ui.icon name="document" class="fs-5" />
                                    Catatan
                                </x-ui.action-menu-item>
                            @endif
                        </x-ui.action-menu>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7">
                        <x-ui.empty-state
                            :title="$activeFocusConfig['empty_title']"
                            :description="$activeFocusConfig['empty_description']"
                            class="py-15"
                        />
                    </td>
                </tr>
            @endforelse
        </x-slot>
    </x-ui.table>
</x-ui.page>
</div>

<form id="delete-akreditasi-form" action="{{ route('pesantren.akreditasi.delete') }}" method="POST" class="d-none">
    @csrf
    <input type="hidden" name="id" value="">
</form>

<form id="cancel-akreditasi-form" action="{{ route('pesantren.akreditasi.cancel') }}" method="POST" class="d-none">
    @csrf
    <input type="hidden" name="id" value="">
</form>

{{-- Banding Modal --}}
<x-ui.modal name="banding-modal" maxWidth="md">
    <form action="{{ route('pesantren.akreditasi.banding') }}" method="POST">
        @csrf
        <input type="hidden" name="id" id="bandingId">
        <x-ui.modal-header title="Ajukan Banding" />
        <x-ui.modal-body>
            <x-ui.form-field label="Alasan Banding" required>
                <textarea name="alasan" rows="4" class="form-control" placeholder="Jelaskan alasan banding Anda (minimal 50 karakter)..."></textarea>
            </x-ui.form-field>
        </x-ui.modal-body>
        <x-ui.modal-footer>
            <x-ui.button type="button" variant="light" x-on:click="$dispatch('close')">Batal</x-ui.button>
            <x-ui.button type="submit" variant="primary">Kirim Banding</x-ui.button>
        </x-ui.modal-footer>
    </form>
</x-ui.modal>

{{-- Catatan Modal --}}
<x-ui.modal name="catatan-modal" maxWidth="md">
    <x-ui.modal-header title="Catatan" />
    <x-ui.modal-body id="catatanContent">
        <div class="text-center py-4 text-muted">Memuat catatan...</div>
    </x-ui.modal-body>
    <x-ui.modal-footer>
        <x-ui.button type="button" variant="light" x-on:click="$dispatch('close')">Tutup</x-ui.button>
    </x-ui.modal-footer>
</x-ui.modal>

@push('scripts')
<script>
// Create akreditasi confirmation
document.getElementById('btnCreateAkreditasi')?.addEventListener('click', function(e) {
    e.preventDefault();
    window.SpmSwal.confirm({
        title: 'Ajukan Akreditasi Baru?',
        text: 'Setelah diajukan, data profil, IPM, SDM, dan EDPM akan terkunci dan tidak dapat diubah.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Ya, Ajukan',
        cancelButtonText: 'Batal',
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('createAkreditasiForm').requestSubmit();
        }
    });
});

function submitAkreditasiAction(formId, id) {
    const form = document.getElementById(formId);
    form.querySelector('input[name="id"]').value = id;
    form.requestSubmit();
}

function confirmDelete(id) {
    window.SpmSwal.confirm({
        title: 'Hapus Pengajuan?',
        text: 'Data pengajuan akan dihapus permanen.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, Hapus',
        cancelButtonText: 'Batal',
    }).then((result) => {
        if (result.isConfirmed) submitAkreditasiAction('delete-akreditasi-form', id);
    });
}

function confirmCancel(id) {
    window.SpmSwal.confirm({
        title: 'Batalkan Pengajuan?',
        text: 'Pengajuan yang dibatalkan tidak dapat dikembalikan.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, Batalkan',
        cancelButtonText: 'Tidak',
    }).then((result) => {
        if (result.isConfirmed) submitAkreditasiAction('cancel-akreditasi-form', id);
    });
}

// Banding modal
function openBandingModal(id) {
    document.getElementById('bandingId').value = id;
    window.dispatchEvent(new CustomEvent('open-modal', { detail: 'banding-modal' }));
}

const textNode = (tag, className, text) => {
    const node = document.createElement(tag);
    node.className = className;
    node.textContent = text ?? '';
    return node;
};

// Catatan modal (AJAX)
function openCatatanModal(id) {
    const content = document.getElementById('catatanContent');
    content.replaceChildren(textNode('div', 'text-center py-4 text-muted', 'Memuat catatan...'));
    window.dispatchEvent(new CustomEvent('open-modal', { detail: 'catatan-modal' }));

    fetch(`{{ route('pesantren.akreditasi.catatan', '__ID__') }}`.replace('__ID__', id))
        .then(res => res.json())
        .then(data => {
            if (!data.catatans?.length) {
                content.replaceChildren(textNode('div', 'text-center text-muted py-4', 'Belum ada catatan.'));
                return;
            }

            const nodes = data.catatans.map(c => {
                const item = document.createElement('div');
                const iconWrap = document.createElement('div');
                const icon = document.createElement('span');
                const iconEl = document.createElement('i');
                const body = document.createElement('div');
                const note = textNode('div', 'mt-2 text-gray-700', c.catatan);

                item.className = 'd-flex gap-3 mb-3 pb-3 border-bottom';
                iconWrap.className = 'flex-shrink-0';
                icon.className = 'symbol symbol-35px bg-light-primary rounded';
                iconEl.className = 'ki-solid ki-user fs-5 text-primary';
                note.style.whiteSpace = 'pre-line';

                icon.appendChild(iconEl);
                iconWrap.appendChild(icon);
                body.appendChild(textNode('div', 'fw-semibold', c.user_name));
                body.appendChild(textNode('div', 'text-muted fs-8', c.created_at));
                body.appendChild(note);
                item.append(iconWrap, body);
                return item;
            });

            content.replaceChildren(...nodes);
        })
        .catch(() => {
            content.replaceChildren(textNode('div', 'text-center text-danger py-4', 'Gagal memuat catatan.'));
        });
}
</script>
@endpush
@endsection
