@extends('layouts.app')

@section('header', 'Pengajuan Akreditasi')

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
        '' => 'Semua',
        'perbaikan' => 'Perbaikan',
        'kartu_kendali' => 'Kartu Kendali',
        'hasil' => 'Hasil',
    ];
    $queryParams = array_filter(request()->except(['focus', 'page']));
@endphp

<div data-module-page="pesantren-akreditasi">
<x-ui.page
    title="Pengajuan Akreditasi"
    subtitle="Kelola pengajuan, pantau status, dan ajukan banding akreditasi pesantren Anda."
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
                    <div class="progress flex-grow-1" style="height:8px;">
                        <div class="progress-bar bg-warning" style="width:{{ $completeness['percentage'] }}%"></div>
                    </div>
                    <span class="text-muted fs-8">{{ $completeness['complete'] }}/{{ $completeness['total'] }} ({{ $completeness['percentage'] }}%)</span>
                </div>
                @if(!empty($completeness['incomplete_items']))
                    <div class="mt-2 fs-8 text-muted">
                        <strong>Data yang perlu dilengkapi:</strong>
                        <ul class="mb-0 ps-3 mt-1">
                            @foreach($completeness['incomplete_items'] as $item)
                                <li>{{ $item }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        </x-ui.alert>
    @endif

    {{-- Focus Tabs --}}
    <x-ui.tabs class="mb-6">
        @foreach($focuses as $key => $label)
            <a href="{{ request()->fullUrlWithQuery(array_merge($queryParams, ['focus' => $key])) }}" class="nav-link {{ $focus === $key ? 'active' : '' }}">
                {{ $label }}
            </a>
        @endforeach
    </x-ui.tabs>

    {{-- Filter Form --}}
    <form method="GET" action="{{ route('pesantren.akreditasi') }}" class="mb-6">
        <input type="hidden" name="focus" value="{{ $focus }}">
        <input type="hidden" name="sortField" value="{{ $sortField }}">
        <input type="hidden" name="sortAsc" value="{{ $sortAsc ? 'true' : 'false' }}">
        <div class="row g-3 align-items-end">
            <div class="col-md-4">
                <x-ui.form-field label="Cari">
                    <input type="search" name="search" value="{{ $search }}" class="form-control" placeholder="Cari periode atau ID...">
                </x-ui.form-field>
            </div>
            <div class="col-md-2">
                <x-ui.form-field label="Periode">
                    <input type="text" name="periodeFilter" value="{{ $periodeFilter }}" class="form-control" placeholder="2025">
                </x-ui.form-field>
            </div>
            <div class="col-md-2">
                <x-ui.form-field label="Tahapan">
                    <select name="tahapanFilter" class="form-select">
                        <option value="">Semua</option>
                        @foreach($tahapanLabels as $val => $label)
                            <option value="{{ $val }}" {{ $tahapanFilter === $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </x-ui.form-field>
            </div>
            <div class="col-md-2">
                <x-ui.form-field label="Per Halaman">
                    <select name="perPage" class="form-select">
                        @foreach([5, 10, 25, 50] as $pp)
                            <option value="{{ $pp }}" {{ $perPage == $pp ? 'selected' : '' }}>{{ $pp }}</option>
                        @endforeach
                    </select>
                </x-ui.form-field>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <x-ui.button type="submit" variant="light" class="w-100">
                    <i class="ki-solid ki-filter-search fs-4 me-1"></i> Filter
                </x-ui.button>
            </div>
        </div>
    </form>

    {{-- Table --}}
    @if($akreditasis->count() > 0)
        <div class="table-responsive" data-ui-table="metronic">
            <table class="table table-bordered table-row-dashed align-middle">
                <thead>
                    <tr class="bg-light">
                        <th>No</th>
                        <th>ID</th>
                        <th>Periode</th>
                        <th>Status</th>
                        <th>Tahapan</th>
                        <th>Tanggal Pengajuan</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($akreditasis as $akreditasi)
                        @php $statusLabel = $statusLabels[$akreditasi->status] ?? $akreditasi->status; @endphp
                        <tr>
                            <td>{{ $loop->iteration + (($akreditasis->currentPage() - 1) * $akreditasis->perPage()) }}</td>
                            <td class="fw-semibold">{{ $akreditasi->id }}</td>
                            <td>{{ $akreditasi->periode }}</td>
                            <td>
                                <span class="badge {{ $statusBadgeClass[$akreditasi->status] ?? 'badge-light-secondary' }}">
                                    {{ $statusLabel }}
                                </span>
                            </td>
                            <td>{{ $tahapanLabels[$akreditasi->tahapan] ?? ucfirst($akreditasi->tahapan) }}</td>
                            <td>{{ $akreditasi->created_at->format('d M Y') }}</td>
                            <td class="text-end">
                                <div class="d-flex gap-2 justify-content-end">
                                    @if($akreditasi->kartu_kendali)
                                        <a href="{{ route('pesantren.akreditasi-detail', $akreditasi->uuid) }}" class="btn btn-sm btn-light">
                                            <i class="ki-solid ki-eye fs-6"></i>
                                        </a>
                                    @endif

                                    @if($akreditasi->status === '0')
                                        <form action="{{ route('pesantren.akreditasi.delete') }}" method="POST" class="d-inline delete-form">
                                            @csrf
                                            <input type="hidden" name="id" value="{{ $akreditasi->id }}">
                                            <x-ui.button type="submit" variant="light-danger" size="sm" class="btn-delete">
                                                <i class="ki-solid ki-trash fs-6"></i>
                                            </x-ui.button>
                                        </form>
                                        <form action="{{ route('pesantren.akreditasi.cancel') }}" method="POST" class="d-inline cancel-form">
                                            @csrf
                                            <input type="hidden" name="id" value="{{ $akreditasi->id }}">
                                            <x-ui.button type="submit" variant="light-warning" size="sm" class="btn-cancel">
                                                <i class="ki-solid ki-cross fs-6"></i>
                                            </x-ui.button>
                                        </form>
                                    @endif

                                    @if($akreditasi->status === '-1')
                                        <form action="{{ route('pesantren.akreditasi.delete') }}" method="POST" class="d-inline delete-form">
                                            @csrf
                                            <input type="hidden" name="id" value="{{ $akreditasi->id }}">
                                            <x-ui.button type="submit" variant="light-danger" size="sm" class="btn-delete">
                                                <i class="ki-solid ki-trash fs-6"></i>
                                            </x-ui.button>
                                        </form>
                                        <x-ui.button type="button" variant="light-primary" size="sm"
                                            onclick="openBandingModal({{ $akreditasi->id }})">
                                            <i class="ki-solid ki-message-text fs-6"></i> Banding
                                        </x-ui.button>
                                    @endif

                                    @if(in_array($akreditasi->status, ['1', 'hasil_akhir']))
                                        <x-ui.button type="button" variant="light-primary" size="sm"
                                            onclick="openCatatanModal({{ $akreditasi->id }})">
                                            <i class="ki-solid ki-message-text fs-6"></i> Catatan
                                        </x-ui.button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $akreditasis->appends(request()->query())->links() }}
        </div>
    @else
        <x-ui.alert variant="info" title="Tidak Ada Data" class="mb-0">
            Belum ada pengajuan akreditasi. Lengkapi profil dan data pendukung untuk mengajukan akreditasi.
        </x-ui.alert>
    @endif
</x-ui.page>
</div>

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

// Delete confirmation
document.querySelectorAll('.btn-delete').forEach(btn => {
    btn.addEventListener('click', function(e) {
        e.preventDefault();
        const form = this.closest('form');
        window.SpmSwal.confirm({
            title: 'Hapus Pengajuan?',
            text: 'Data pengajuan akan dihapus permanen.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, Hapus',
            cancelButtonText: 'Batal',
        }).then((result) => {
            if (result.isConfirmed) {
                form.requestSubmit();
            }
        });
    });
});

// Cancel confirmation
document.querySelectorAll('.btn-cancel').forEach(btn => {
    btn.addEventListener('click', function(e) {
        e.preventDefault();
        const form = this.closest('form');
        window.SpmSwal.confirm({
            title: 'Batalkan Pengajuan?',
            text: 'Pengajuan yang dibatalkan tidak dapat dikembalikan.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, Batalkan',
            cancelButtonText: 'Tidak',
        }).then((result) => {
            if (result.isConfirmed) {
                form.requestSubmit();
            }
        });
    });
});

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
