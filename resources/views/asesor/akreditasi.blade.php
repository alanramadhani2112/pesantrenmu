@extends('layouts.app')

@section('content')
<div x-data="asesorAkreditasiPage()" data-module-page="asesor-akreditasi">
    @php
        $emptyTitle = match ($activeFocus) {
            'review' => 'Belum ada berkas yang perlu direview',
            'jadwal' => 'Belum ada visitasi yang perlu dijadwalkan',
            'nilai' => 'Belum ada nilai visitasi yang perlu diinput',
            'laporan_visitasi' => 'Belum ada laporan visitasi yang perlu ditindaklanjuti',
            default => 'Belum ada tugas akreditasi ditugaskan',
        };
    @endphp

    <x-slot name="header">{{ __($context['title']) }}</x-slot>

    <x-ui.page
        :title="$context['title']"
        :subtitle="$context['subtitle']"
        data-akreditasi-context="{{ $activeFocus }}"
    >
        @php
            $assessmentCollection = method_exists($assessments, 'getCollection')
                ? $assessments->getCollection()
                : collect($assessments);
            $totalTugas = method_exists($assessments, 'total')
                ? $assessments->total()
                : $assessmentCollection->count();
            $assessmentAktif = $assessmentCollection->filter(fn ($item) => $item->akreditasi && (int) $item->akreditasi->status === 4)->count();
            $visitasiAktif = $assessmentCollection->filter(fn ($item) => $item->akreditasi && in_array((int) $item->akreditasi->status, [3, 2], true))->count();
        @endphp

        @php
            $workflowTabs = [
                ['label' => 'Semua Tugas', 'route' => route('asesor.akreditasi'), 'active' => $activeFocus === 'tugas'],
                ['label' => 'Review Berkas', 'route' => route('asesor.akreditasi.review'), 'active' => $activeFocus === 'review'],
                ['label' => 'Jadwal Visitasi', 'route' => route('asesor.akreditasi.jadwal'), 'active' => $activeFocus === 'jadwal'],
                ['label' => 'Input Nilai', 'route' => route('asesor.akreditasi.nilai'), 'active' => $activeFocus === 'nilai'],
                ['label' => 'Laporan Visitasi', 'route' => route('asesor.akreditasi.laporan-visitasi'), 'active' => $activeFocus === 'laporan_visitasi'],
            ];
        @endphp

        <x-ui.tabs class="mb-6 spm-asesor-workflow-tabs">
            @foreach($workflowTabs as $tab)
                <li class="nav-item">
                    <a href="{{ $tab['route'] }}"
                        data-ui-tab="metronic"
                        role="tab"
                        class="nav-link text-active-primary spm-tab-link {{ $tab['active'] ? 'active' : '' }}"
                        aria-selected="{{ $tab['active'] ? 'true' : 'false' }}">
                        {{ $tab['label'] }}
                    </a>
                </li>
            @endforeach
        </x-ui.tabs>

        <div class="row g-6 mb-6">
            <div class="col-12 col-xl-8">
                <x-ui.card
                    title="Prioritas Tugas Asesor"
                    subtitle="Mulai dari tugas yang sedang aktif, lalu lanjutkan ke instrumen dan laporan visitasi."
                    class="h-100"
                >
                    <div class="row g-4">
                        <div class="col-12 col-md-4">
                            <x-ui.metric-box
                                label="Total Tugas"
                                :value="$totalTugas"
                                variant="primary"
                                description="Daftar pengajuan akreditasi yang ditugaskan ke asesor."
                            />
                        </div>

                        <div class="col-12 col-md-4">
                            <x-ui.metric-box
                                label="Penilaian"
                                :value="$assessmentAktif"
                                variant="warning"
                                description="Tugas yang perlu dilanjutkan ke pengisian instrumen akreditasi."
                            />
                        </div>

                        <div class="col-12 col-md-4">
                            <x-ui.metric-box
                                label="Visitasi"
                                :value="$visitasiAktif"
                                variant="info"
                                description="Jadwal dan laporan visitasi yang perlu diselesaikan."
                            />
                        </div>
                    </div>
                </x-ui.card>
            </div>

            <div class="col-12 col-xl-4">
                <x-ui.card
                    title="Alur Kerja Asesor"
                    subtitle="Aksi utama tetap berada di menu tiap baris tugas akreditasi."
                    class="h-100"
                >
                    <div class="d-flex flex-column gap-4">
                        <div class="d-flex align-items-start gap-3">
                            <x-ui.badge variant="primary" class="rounded-circle min-w-25px h-25px justify-content-center p-0">1</x-ui.badge>
                            <div>
                                <div class="fw-semibold text-gray-900">Buka detail</div>
                                <div class="text-muted fs-7">Cek profil pesantren, instrumen, dan catatan sebelum memberi penilaian.</div>
                            </div>
                        </div>
                        <div class="d-flex align-items-start gap-3">
                            <x-ui.badge variant="primary" class="rounded-circle min-w-25px h-25px justify-content-center p-0">2</x-ui.badge>
                            <div>
                                <div class="fw-semibold text-gray-900">Isi instrumen</div>
                                <div class="text-muted fs-7">Gunakan aksi input nilai saat status pengajuan sudah memungkinkan.</div>
                            </div>
                        </div>
                        <div class="d-flex align-items-start gap-3">
                            <x-ui.badge variant="primary" class="rounded-circle min-w-25px h-25px justify-content-center p-0">3</x-ui.badge>
                            <div>
                                <div class="fw-semibold text-gray-900">Selesaikan visitasi</div>
                                <div class="text-muted fs-7">Atur jadwal, unggah laporan, dan tindak lanjuti revisi dari menu aksi.</div>
                            </div>
                        </div>
                    </div>
                </x-ui.card>
            </div>
        </div>

        <x-ui.table
            :title="$context['tableTitle']"
            :subtitle="$context['tableSubtitle']"
            :records="$assessments"
            class="spm-table-shell--asesor-akreditasi spm-table-shell--asesor-{{ $activeFocus }}"
        >
            <x-slot name="filters">
                <form method="GET" action="{{ route('asesor.akreditasi') }}" id="asesor-akreditasi-filter-form" class="d-flex align-items-center gap-3 flex-wrap">
                    <input type="hidden" name="focus" value="{{ $focus }}">
                    <input type="hidden" name="perPage" value="{{ $perPage }}">
                    <input type="hidden" name="sortField" value="{{ $sortField }}">
                    <input type="hidden" name="sortAsc" value="{{ $sortAsc ? 'true' : 'false' }}">

                    <x-datatable.search name="search" placeholder="Cari Pesantren..." :value="$search" form="asesor-akreditasi-filter-form" />

                    <x-ui.select name="periodeFilter" size="sm" class="w-auto min-w-120px" onchange="this.form.submit()">
                        <option value="">Periode</option>
                        @for($i = date('Y'); $i >= 2024; $i--)
                            <option value="{{ $i }}" {{ $periodeFilter == $i ? 'selected' : '' }}>{{ $i }}</option>
                        @endfor
                    </x-ui.select>

                    <x-ui.select name="statusFilter" size="sm" class="w-auto min-w-180px" onchange="this.form.submit()">
                        <option value="">Status</option>
                        <option value="belum" {{ $statusFilter === 'belum' ? 'selected' : '' }}>Review Berkas</option>
                        <option value="siap" {{ $statusFilter === 'siap' ? 'selected' : '' }}>Visitasi Terjadwal</option>
                        <option value="penilaian" {{ $statusFilter === 'penilaian' ? 'selected' : '' }}>Penilaian Pasca Visitasi</option>
                        <option value="revisi" {{ $statusFilter === 'revisi' ? 'selected' : '' }}>Perlu Revisi</option>
                        <option value="selesai" {{ $statusFilter === 'selesai' ? 'selected' : '' }}>Selesai</option>
                    </x-ui.select>
                </form>
            </x-slot>

            <x-slot name="thead">
                <x-ui.table-th>Pesantren</x-ui.table-th>
                <x-ui.table-th align="center">Jadwal Review</x-ui.table-th>
                <x-ui.table-th align="center">Status</x-ui.table-th>
                <x-ui.table-th align="center">Jadwal Visitasi</x-ui.table-th>
                <x-ui.table-th>Catatan</x-ui.table-th>
                <x-ui.table-th align="end">Aksi</x-ui.table-th>
            </x-slot>

            <x-slot name="tbody">
                @forelse ($assessments as $index => $item)
                @if($item->akreditasi)
                <tr>
                    <td>
                        <span class="text-gray-900 fw-semibold fs-6">{{ $item->akreditasi->user?->pesantren?->nama_pesantren ?? $item->akreditasi->user?->name ?? 'N/A' }}</span>
                    </td>
                    <td class="text-center">
                        <span class="text-muted fw-semibold">
                            {{ \Carbon\Carbon::parse($item->tanggal_mulai)->format('d') }}–{{ \Carbon\Carbon::parse($item->tanggal_berakhir)->format('d M Y') }}
                        </span>
                    </td>
                    <td class="text-center">
                        @if($item->akreditasi->status == 0)
                        <x-ui.status-badge variant="success">Selesai</x-ui.status-badge>
                        @elseif($item->akreditasi->status == -1)
                        <x-ui.status-badge variant="danger">Ditolak</x-ui.status-badge>
                        @elseif($item->akreditasi->status == 1)
                        <x-ui.status-badge variant="primary">Validasi Admin</x-ui.status-badge>
                        @elseif($item->akreditasi->status == 2)
                        <x-ui.status-badge variant="info">Penilaian Pasca Visitasi</x-ui.status-badge>
                        @elseif($item->akreditasi->status == 3)
                        <x-ui.status-badge variant="info">Visitasi Terjadwal</x-ui.status-badge>
                        @elseif($item->akreditasi->catatans->whereNotNull('perbaikan')->filter(fn($c) => !empty($c->perbaikan))->isNotEmpty())
                        <x-ui.status-badge variant="danger">Perlu Revisi</x-ui.status-badge>
                        @else
                        <x-ui.status-badge variant="warning">Review Berkas</x-ui.status-badge>
                        @endif
                    </td>
                    <td class="text-center text-muted fw-semibold">
                        @if($item->akreditasi->tgl_visitasi)
                        {{ \Carbon\Carbon::parse($item->akreditasi->tgl_visitasi)->format('d') }}–{{ \Carbon\Carbon::parse($item->akreditasi->tgl_visitasi_akhir)->format('d M Y') }}
                        @else
                        <span class="text-muted">Belum Dijadwalkan</span>
                        @endif
                    </td>
                    <td>
                        <x-ui.button type="button" variant="light" size="sm" x-on:click="openCatatanModal({{ $item->akreditasi->id }})">
                            <x-ui.icon name="document" class="fs-5 me-1" />
                            {{ $item->akreditasi->catatans->count() > 0 ? $item->akreditasi->catatans->count() . ' Catatan' : 'Catatan' }}
                        </x-ui.button>
                    </td>
                    <td class="text-end">
                        <x-ui.action-menu>
                            <x-ui.action-menu-item :href="route('asesor.akreditasi-detail', $item->akreditasi->uuid)">
                                <x-ui.icon name="eye" class="fs-5 text-gray-500" />
                                Lihat Detail
                            </x-ui.action-menu-item>

                            @if(in_array((int) $item->akreditasi->status, [4], true) && $activeFocus === 'jadwal')
                                <x-ui.action-menu-item
                                    variant="primary"
                                    x-on:click="openJadwalModal({{ $item->akreditasi->id }}, '{{ addslashes($item->akreditasi->user?->pesantren?->nama_pesantren ?? $item->akreditasi->user?->name ?? 'N/A') }}', '{{ \Carbon\Carbon::parse($item->tanggal_mulai)->format('Y-m-d') }}', '{{ \Carbon\Carbon::parse($item->tanggal_berakhir)->format('Y-m-d') }}')"
                                >
                                    <x-ui.icon name="timer" class="fs-5" />
                                    Atur Jadwal
                                </x-ui.action-menu-item>

                                <x-ui.action-menu-item
                                    variant="danger"
                                    x-on:click="openTolakModal({{ $item->akreditasi->id }}, '{{ addslashes($item->akreditasi->user?->pesantren?->nama_pesantren ?? $item->akreditasi->user?->name ?? 'N/A') }}', '{{ \Carbon\Carbon::parse($item->tanggal_mulai)->format('d') }}-{{ \Carbon\Carbon::parse($item->tanggal_berakhir)->format('d M Y') }}')"
                                >
                                    <x-ui.icon name="cross-circle" class="fs-5" />
                                    Tolak Dokumen
                                </x-ui.action-menu-item>
                            @endif

                            @if(in_array((int) $item->akreditasi->status, [2, 1, 0, -1], true))
                                <x-ui.action-menu-item
                                    :href="route('asesor.akreditasi-detail', ['uuid' => $item->akreditasi->uuid, 'activeTab' => 'instrumen'])"
                                    variant="primary"
                                >
                                    <x-ui.icon name="pencil" class="fs-5" />
                                    {{ ($item->akreditasi->status == 0 || $item->akreditasi->status == -1) ? 'Lihat Nilai Akreditasi' : 'Input Nilai Akreditasi' }}
                                </x-ui.action-menu-item>
                            @endif

                            @if(in_array((int) $item->akreditasi->status, [2, 1, 0, -1], true))
                                <x-ui.action-menu-item
                                    :href="route('asesor.akreditasi-detail', ['uuid' => $item->akreditasi->uuid, 'activeTab' => 'laporan_visitasi'])"
                                    variant="success"
                                >
                                    <x-ui.icon name="document" class="fs-5" />
                                    {{ in_array($item->akreditasi->status, [3, 2]) ? 'Unggah Laporan' : 'Lihat Laporan Visitasi' }}
                                </x-ui.action-menu-item>
                            @endif
                        </x-ui.action-menu>
                    </td>
                </tr>
                @endif
                @empty
                <tr>
                    <td colspan="6">
                        <x-ui.empty-state :title="$emptyTitle" class="py-15" />
                    </td>
                </tr>
                @endforelse
            </x-slot>
        </x-ui.table>
    </x-ui.page>

    {{-- Modal Atur Jadwal Visitasi --}}
    <div x-show="showJadwalModal" x-cloak class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <form method="POST" action="{{ route('asesor.akreditasi.schedule-visitasi') }}">
                    @csrf
                    <input type="hidden" name="akreditasi_id" x-model="jadwalForm.akreditasi_id">

                    <div class="modal-header">
                        <h5 class="modal-title">Atur Jadwal Visitasi</h5>
                        <button type="button" class="btn-close" x-on:click="showJadwalModal = false"></button>
                    </div>

                    <div class="modal-body">
                        <div class="bg-light rounded-4 p-6 border border-gray-200 mb-8">
                            <div class="mb-4">
                                <p class="text-muted fs-8 fw-semibold text-uppercase mb-1">Pesantren</p>
                                <p class="fs-6 fw-semibold text-gray-900" x-text="jadwalForm.pesantren"></p>
                            </div>
                            <div>
                                <p class="text-muted fs-8 fw-semibold text-uppercase mb-1">Jadwal Penilaian</p>
                                <p class="fs-6 fw-semibold text-gray-900" x-text="jadwalForm.periodeLabel"></p>
                            </div>
                        </div>

                        <h3 class="fw-semibold text-gray-900 fs-6 mb-4">Input Jadwal</h3>
                        <div class="row g-5 mb-4">
                            <div class="col-md-6">
                                <label class="form-label">Tanggal Mulai Visitasi</label>
                                <input type="date" name="tanggal_mulai" class="form-control" x-model="jadwalForm.tanggal_mulai" :min="jadwalForm.periode_mulai" :max="jadwalForm.periode_akhir" required>
                                @error('tanggal_mulai') <div class="text-danger fs-7 mt-1">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Tanggal Selesai Visitasi</label>
                                <input type="date" name="tanggal_akhir" class="form-control" x-model="jadwalForm.tanggal_akhir" :min="jadwalForm.tanggal_mulai" :max="jadwalForm.periode_akhir" required>
                                @error('tanggal_akhir') <div class="text-danger fs-7 mt-1">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <div class="notice d-flex bg-light-warning rounded border-warning border border-dashed p-4 mb-4">
                            <div class="fw-semibold text-warning fs-7">
                                Rentang visitasi maksimal 14 hari dan harus berada dalam periode review yang telah ditetapkan.
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Catatan Tambahan</label>
                            <textarea name="catatan" class="form-control" rows="4" placeholder="Contoh: Koordinasi kedatangan dengan pimpinan pesantren pukul 08.00 WIB."></textarea>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" x-on:click="showJadwalModal = false">Batal</button>
                        <button type="submit" class="btn btn-primary">Atur Jadwal Visitasi</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Modal Tolak Dokumen --}}
    <div x-show="showTolakModal" x-cloak class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <form method="POST" action="{{ route('asesor.akreditasi.reject-document') }}">
                    @csrf
                    <input type="hidden" name="akreditasi_id" x-model="tolakForm.akreditasi_id">

                    <div class="modal-header">
                        <h5 class="modal-title text-danger">Tolak Dokumen</h5>
                        <button type="button" class="btn-close" x-on:click="showTolakModal = false"></button>
                    </div>

                    <div class="modal-body">
                        <div class="bg-light rounded-4 p-6 border border-gray-200 mb-8">
                            <div class="mb-4">
                                <p class="text-muted fs-8 fw-semibold text-uppercase mb-1">Pesantren</p>
                                <p class="fs-6 fw-semibold text-gray-900" x-text="tolakForm.pesantren"></p>
                            </div>
                            <div>
                                <p class="text-muted fs-8 fw-semibold text-uppercase mb-1">Periode Review</p>
                                <p class="fs-6 fw-semibold text-gray-900" x-text="tolakForm.periodeLabel"></p>
                            </div>
                        </div>

                        <div class="mb-6">
                            <label class="form-label fw-semibold">Dokumen yang Memerlukan Perbaikan</label>
                            <div class="row g-3">
                                @foreach(['Profil Pesantren', 'IPM', 'Data SDM', 'EDPM'] as $docName)
                                    <div class="col-6 col-md-3">
                                        <div class="form-check">
                                            <input type="checkbox" name="perbaikan[]" value="{{ $docName }}" class="form-check-input" id="perbaikan-{{ Str::slug($docName) }}">
                                            <label class="form-check-label" for="perbaikan-{{ Str::slug($docName) }}">{{ $docName }}</label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            <div class="form-text">Minimal satu bagian harus dipilih sebelum melanjutkan.</div>
                            @error('perbaikan') <div class="text-danger fs-7 mt-1">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold">Alasan Penolakan</label>
                            <textarea name="catatan" class="form-control" rows="4" placeholder="Jelaskan secara spesifik bagian yang perlu diperbaiki." required minlength="10"></textarea>
                            @error('catatan') <div class="text-danger fs-7 mt-1">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" x-on:click="showTolakModal = false">Batal</button>
                        <button type="submit" class="btn btn-danger">Tolak Dokumen</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Modal Catatan (AJAX loaded) --}}
    <div x-show="showCatatanModal" x-cloak class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Catatan Akreditasi</h5>
                    <button type="button" class="btn-close" x-on:click="showCatatanModal = false"></button>
                </div>
                <div class="modal-body spm-modal-content-scroll">
                    <div x-show="catatanLoading" class="text-center py-8">
                        <div class="spinner-border text-primary" role="status"></div>
                        <p class="text-muted mt-3">Memuat catatan...</p>
                    </div>
                    <div x-show="!catatanLoading">
                        <template x-if="catatanData && catatanData.catatans && catatanData.catatans.length > 0">
                            <div class="d-flex flex-column gap-6">
                                <template x-for="(catatan, idx) in catatanData.catatans" :key="idx">
                                    <div class="border-bottom border-gray-200 pb-6">
                                        <div class="d-flex align-items-center gap-3 mb-4">
                                            <div class="symbol symbol-40px">
                                                <div class="symbol-label bg-light-primary text-primary fw-semibold text-uppercase" x-text="catatan.user.substring(0, 2)"></div>
                                            </div>
                                            <div>
                                                <h6 class="fw-semibold text-gray-900 mb-0" x-text="catatan.user"></h6>
                                                <span class="text-muted fs-8" x-text="catatan.created_at"></span>
                                            </div>
                                        </div>
                                        <div class="rounded-4 p-4 bg-light-primary">
                                            <p class="fs-7 fw-medium text-gray-700 mb-0" x-text="catatan.catatan" style="white-space: pre-line;"></p>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </template>
                        <template x-if="!catatanData || !catatanData.catatans || catatanData.catatans.length === 0">
                            <div class="text-center py-12">
                                <x-ui.icon name="document" class="fs-3x text-gray-300 mb-3 d-block mx-auto" />
                                <p class="text-muted fw-semibold fs-7">Tidak ada catatan ditemukan.</p>
                            </div>
                        </template>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" x-on:click="showCatatanModal = false">Tutup</button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function asesorAkreditasiPage() {
    return {
        showJadwalModal: false,
        showTolakModal: false,
        showCatatanModal: false,
        catatanLoading: false,
        catatanData: null,

        jadwalForm: {
            akreditasi_id: '',
            pesantren: '',
            periodeLabel: '',
            periode_mulai: '',
            periode_akhir: '',
            tanggal_mulai: '',
            tanggal_akhir: '',
        },

        tolakForm: {
            akreditasi_id: '',
            pesantren: '',
            periodeLabel: '',
        },

        openJadwalModal(akreditasiId, pesantren, periodeMulai, periodeAkhir) {
            const defaultDate = new Date();
            defaultDate.setDate(defaultDate.getDate() + 7);
            const defaultStr = defaultDate.toISOString().split('T')[0];

            this.jadwalForm = {
                akreditasi_id: akreditasiId,
                pesantren: pesantren,
                periodeLabel: periodeMulai + ' s/d ' + periodeAkhir,
                periode_mulai: periodeMulai,
                periode_akhir: periodeAkhir,
                tanggal_mulai: defaultStr > periodeMulai ? defaultStr : periodeMulai,
                tanggal_akhir: defaultStr > periodeMulai ? defaultStr : periodeMulai,
            };
            this.showJadwalModal = true;
        },

        openTolakModal(akreditasiId, pesantren, periodeLabel) {
            this.tolakForm = {
                akreditasi_id: akreditasiId,
                pesantren: pesantren,
                periodeLabel: periodeLabel,
            };
            this.showTolakModal = true;
        },

        async openCatatanModal(akreditasiId) {
            this.catatanLoading = true;
            this.catatanData = null;
            this.showCatatanModal = true;

            try {
                const response = await fetch(`{{ url('asesor/akreditasi/catatan') }}/${akreditasiId}`);
                if (response.ok) {
                    this.catatanData = await response.json();
                }
            } catch (e) {
                console.error('Failed to load catatan:', e);
            } finally {
                this.catatanLoading = false;
            }
        },
    };
}
</script>
@endpush
@endsection
