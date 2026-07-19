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

    <x-ui.page
        :title="$context['title']"
        :subtitle="$context['subtitle']"
        data-akreditasi-context="{{ $activeFocus }}"
    >
        @php
            $totalTugas = $summary['total'];
            $reviewAktif = $summary['review'];
            $visitasiAktif = $summary['visitasi'];
            $penilaianAktif = $summary['penilaian'];
        @endphp
        <div class="row g-5 mb-5">
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
                                label="Review Berkas"
                                :value="$reviewAktif"
                                variant="warning"
                                description="Tugas yang perlu dicek sebelum visitasi."
                            />
                        </div>

                        <div class="col-12 col-md-4">
                            <x-ui.metric-box
                                label="Penilaian"
                                :value="$penilaianAktif"
                                variant="info"
                                description="Tugas input nilai pasca visitasi."
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
                <form method="GET" action="{{ route('asesor.akreditasi') }}" id="asesor-akreditasi-filter-form" class="spm-table-filter-grid spm-table-filter-grid--wide">
                    <input type="hidden" name="perPage" value="{{ $perPage }}">
                    <input type="hidden" name="sortField" value="{{ $sortField }}">
                    <input type="hidden" name="sortAsc" value="{{ $sortAsc ? 'true' : 'false' }}">
                    @if($focus !== '')
                        <input type="hidden" name="focus" value="{{ $focus }}">
                    @endif

                    <x-datatable.search name="search" placeholder="Cari Pesantren..." :value="$search" form="asesor-akreditasi-filter-form" />

                    <x-ui.select name="periodeFilter" size="sm" class="w-auto min-w-120px" onchange="this.form.submit()">
                        <option value="">Periode</option>
                        @for($i = date('Y'); $i >= 2024; $i--)
                            <option value="{{ $i }}" {{ $periodeFilter == $i ? 'selected' : '' }}>{{ $i }}</option>
                        @endfor
                    </x-ui.select>

                    <x-ui.select name="statusFilter" size="sm" class="w-auto min-w-220px" onchange="this.form.submit()">
                        <option value="">Semua Status</option>
                        <option value="review" {{ $statusFilter === 'review' ? 'selected' : '' }}>Review Berkas</option>
                        <option value="revisi" {{ $statusFilter === 'revisi' ? 'selected' : '' }}>Perlu Revisi</option>
                        <option value="3" {{ $statusFilter === '3' ? 'selected' : '' }}>Visitasi Terjadwal</option>
                        <option value="2" {{ $statusFilter === '2' ? 'selected' : '' }}>Penilaian Pasca Visitasi</option>
                        <option value="1" {{ $statusFilter === '1' ? 'selected' : '' }}>Validasi Admin</option>
                        <option value="0" {{ $statusFilter === '0' ? 'selected' : '' }}>Selesai</option>
                        <option value="-1" {{ $statusFilter === '-1' ? 'selected' : '' }}>Ditolak</option>
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
                @php
                    $akreditasiStatus = $item->akreditasi->catatans->whereNotNull('perbaikan')->filter(fn($c) => !empty($c->perbaikan))->isNotEmpty()
                        ? ['label' => 'Perlu Revisi', 'variant' => 'danger']
                        : \App\Support\AkreditasiStatusPresenter::for($item->akreditasi->status);

                    if ((int) $item->akreditasi->status === \App\Models\Akreditasi::STATUS_VISITASI) {
                        $akreditasiStatus['label'] = 'Visitasi Terjadwal';
                    }
                @endphp
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
                        <x-ui.status-badge :variant="$akreditasiStatus['variant']">{{ $akreditasiStatus['label'] }}</x-ui.status-badge>
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

                            @if(in_array((int) $item->akreditasi->status, [4], true))
                                @php
                                    $pesantrenName = $item->akreditasi->user?->pesantren?->nama_pesantren
                                        ?? $item->akreditasi->user?->name
                                        ?? 'N/A';
                                    $tanggalMulai = \Carbon\Carbon::parse($item->tanggal_mulai);
                                    $tanggalBerakhir = \Carbon\Carbon::parse($item->tanggal_berakhir);
                                @endphp
                                <x-ui.action-menu-item
                                    variant="primary"
                                    data-pesantren="{{ $pesantrenName }}"
                                    data-mulai="{{ $tanggalMulai->format('Y-m-d') }}"
                                    data-akhir="{{ $tanggalBerakhir->format('Y-m-d') }}"
                                    x-on:click="openJadwalModal({{ $item->akreditasi->id }}, $el.dataset.pesantren, $el.dataset.mulai, $el.dataset.akhir)"
                                >
                                    <x-ui.icon name="timer" class="fs-5" />
                                    Atur Jadwal
                                </x-ui.action-menu-item>

                                <x-ui.action-menu-item
                                    variant="danger"
                                    data-pesantren="{{ $pesantrenName }}"
                                    data-periode="{{ $tanggalMulai->format('d') }} - {{ $tanggalBerakhir->format('d M Y') }}"
                                    x-on:click="openTolakModal({{ $item->akreditasi->id }}, $el.dataset.pesantren, $el.dataset.periode)"
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
    <div x-show="showJadwalModal" x-cloak class="modal fade" x-bind:class="{ 'show d-block': showJadwalModal }" tabindex="-1" role="dialog" aria-modal="true" style="background-color: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <form method="POST" action="{{ route('asesor.akreditasi.schedule-visitasi') }}">
                    @csrf
                    <input type="hidden" name="akreditasi_id" x-model="jadwalForm.akreditasi_id">

                    <x-ui.modal-header title="Atur Jadwal Visitasi" icon="calendar" x-on:close="closeModals()" />

                    <x-ui.modal-body>
                        <div class="bg-body rounded border border-dashed border-gray-300 p-5 mb-5">
                            <div class="mb-4">
                                <p class="text-muted fs-8 fw-semibold mb-1">Pesantren</p>
                                <p class="fs-6 fw-semibold text-gray-900" x-text="jadwalForm.pesantren"></p>
                            </div>
                            <div>
                                <p class="text-muted fs-8 fw-semibold mb-1">Jadwal Penilaian</p>
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

                        <div class="notice d-flex bg-body rounded border border-dashed border-gray-300 p-4 mb-4">
                            <div class="fw-semibold text-warning fs-7">
                                Rentang visitasi maksimal 14 hari dan harus berada dalam periode review yang telah ditetapkan.
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Catatan Tambahan</label>
                            <textarea name="catatan" class="form-control" rows="4" placeholder="Contoh: Koordinasi kedatangan dengan pimpinan pesantren pukul 08.00 WIB."></textarea>
                        </div>
                    </x-ui.modal-body>

                    <x-ui.modal-footer>
                        <x-ui.button type="button" variant="light" x-on:click="closeModals()">Batal</x-ui.button>
                        <x-ui.button type="submit" variant="primary">Atur Jadwal Visitasi</x-ui.button>
                    </x-ui.modal-footer>
                </form>
            </div>
        </div>
    </div>

    {{-- Modal Tolak Dokumen --}}
    <div x-show="showTolakModal" x-cloak class="modal fade" x-bind:class="{ 'show d-block': showTolakModal }" tabindex="-1" role="dialog" aria-modal="true" style="background-color: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <form method="POST" action="{{ route('asesor.akreditasi.reject-document') }}">
                    @csrf
                    <input type="hidden" name="akreditasi_id" x-model="tolakForm.akreditasi_id">

                    <x-ui.modal-header title="Tolak Dokumen" icon="cross-circle" variant="danger" x-on:close="closeModals()" />

                    <x-ui.modal-body>
                        <div class="bg-body rounded border border-dashed border-gray-300 p-5 mb-5">
                            <div class="mb-4">
                                <p class="text-muted fs-8 fw-semibold mb-1">Pesantren</p>
                                <p class="fs-6 fw-semibold text-gray-900" x-text="tolakForm.pesantren"></p>
                            </div>
                            <div>
                                <p class="text-muted fs-8 fw-semibold mb-1">Periode Review</p>
                                <p class="fs-6 fw-semibold text-gray-900" x-text="tolakForm.periodeLabel"></p>
                            </div>
                        </div>

                            <div class="mb-5">
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
                    </x-ui.modal-body>

                    <x-ui.modal-footer>
                        <x-ui.button type="button" variant="light" x-on:click="closeModals()">Batal</x-ui.button>
                        <x-ui.button type="submit" variant="danger">Tolak Dokumen</x-ui.button>
                    </x-ui.modal-footer>
                </form>
            </div>
        </div>
    </div>

    {{-- Modal Catatan (AJAX loaded) --}}
    <div x-show="showCatatanModal" x-cloak class="modal fade" x-bind:class="{ 'show d-block': showCatatanModal }" tabindex="-1" role="dialog" aria-modal="true" style="background-color: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <x-ui.modal-header title="Catatan Akreditasi" icon="document" x-on:close="closeModals()" />
                <x-ui.modal-body class="spm-modal-content-scroll">
                    <div x-show="catatanLoading" class="text-center py-8">
                        <div class="spinner-border text-primary" role="status"></div>
                        <p class="text-muted mt-3">Memuat catatan...</p>
                    </div>
                    <div x-show="!catatanLoading">
                        <template x-if="catatanData && catatanData.catatans && catatanData.catatans.length > 0">
                            <div class="d-flex flex-column gap-5">
                                <template x-for="(catatan, idx) in catatanData.catatans" :key="idx">
                                    <div class="border-bottom border-gray-200 pb-6">
                                        <div class="d-flex align-items-center gap-3 mb-4">
                                            <div class="symbol symbol-40px">
                                <div class="symbol-label bg-body border border-dashed border-gray-300 text-primary fw-semibold" x-text="catatan.user.substring(0, 2)"></div>
                                            </div>
                                            <div>
                                                <h6 class="fw-semibold text-gray-900 mb-0" x-text="catatan.user"></h6>
                                                <span class="text-muted fs-8" x-text="catatan.created_at"></span>
                                            </div>
                                        </div>
                            <div class="rounded p-4 bg-body border border-dashed border-gray-300">
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
                </x-ui.modal-body>
                <x-ui.modal-footer>
                    <x-ui.button type="button" variant="light" x-on:click="closeModals()">Tutup</x-ui.button>
                </x-ui.modal-footer>
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

        closeModals() {
            this.showJadwalModal = false;
            this.showTolakModal = false;
            this.showCatatanModal = false;
        },

        openJadwalModal(akreditasiId, pesantren, periodeMulai, periodeAkhir) {
            this.closeModals();
            const defaultDate = new Date();
            defaultDate.setDate(defaultDate.getDate() + 7);
            const defaultStr = defaultDate.toISOString().split('T')[0];
            const startDate = [defaultStr, periodeMulai].sort().pop();
            const clampedDate = [startDate, periodeAkhir].sort()[0];

            this.jadwalForm = {
                akreditasi_id: akreditasiId,
                pesantren: pesantren,
                periodeLabel: periodeMulai + ' s/d ' + periodeAkhir,
                periode_mulai: periodeMulai,
                periode_akhir: periodeAkhir,
                tanggal_mulai: clampedDate,
                tanggal_akhir: clampedDate,
            };
            this.showJadwalModal = true;
        },

        openTolakModal(akreditasiId, pesantren, periodeLabel) {
            this.closeModals();
            this.tolakForm = {
                akreditasi_id: akreditasiId,
                pesantren: pesantren,
                periodeLabel: periodeLabel,
            };
            this.showTolakModal = true;
        },

        async openCatatanModal(akreditasiId) {
            this.closeModals();
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
