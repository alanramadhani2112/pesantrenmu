@use('App\Models\Akreditasi')
@use('Illuminate\Support\Facades\Storage')
@php
    $statusVariant = match ((int) $akreditasi->status) {
        0 => 'success',
        -1, -2 => 'danger',
        1 => 'warning',
        2 => 'info',
        3, 4, 5, 6 => 'primary',
        default => 'secondary',
    };

    $ipmItems = [
        'nsp_file' => '1. Izin operasional Kementerian Agama (NSP)',
        'lulus_santri_file' => '2. Pernah meluluskan santri / memiliki santri kelas akhir',
        'kurikulum_file' => '3. Menyelenggarakan kurikulum Dirasah Islamiyah',
        'buku_ajar_file' => '4. Menggunakan buku ajar terbitan LP2 PPM',
    ];

    $dokumenUtama = [
        'status_kepemilikan_tanah' => 'Status Kepemilikan Tanah',
        'sertifikat_nsp' => 'Sertifikat NSP',
        'rk_anggaran' => 'Rencana Kerja Anggaran',
        'silabus_rpp' => 'Silabus dan RPP',
        'peraturan_kepegawaian' => 'Peraturan Kepegawaian',
        'file_lk_iapm' => 'File LK Penilaian IAPM',
        'laporan_tahunan' => 'Laporan Tahunan',
    ];

    $dokumenSekunder = [
        'dok_profil' => 'Dokumen Profil',
        'dok_nsp' => 'Dokumen NSP',
        'dok_renstra' => 'Dokumen Renstra',
        'dok_rk_anggaran' => 'Dokumen RK Anggaran',
        'dok_kurikulum' => 'Dokumen Kurikulum',
        'dok_silabus_rpp' => 'Dokumen Silabus & RPP',
        'dok_kepengasuhan' => 'Dokumen Kepengasuhan',
        'dok_peraturan_kepegawaian' => 'Dokumen Peraturan Kepegawaian',
        'dok_sarpras' => 'Dokumen Sarpras',
        'dok_laporan_tahunan' => 'Dokumen Laporan Tahunan',
        'dok_sop' => 'Dokumen SOP',
    ];

    $canSubmitDocumentRejection = (int) $asesorTipe === 1
        && (int) $akreditasi->status === \App\StateMachine\AkreditasiStateMachine::STATUS_ASSESSMENT
        && ! empty($rejectionStatus)
        && (! $rejectionStatus['active'] || ! in_array($rejectionStatus['active']->status, ['pending', 'submitted'], true))
        && $rejectionStatus['count'] < $rejectionStatus['limit'];
@endphp

<x-slot name="header">{{ __('Detail Akreditasi') }}</x-slot>

<x-ui.page
    title="Visitasi Akreditasi"
    subtitle="{{ $pesantren?->nama_pesantren ?? $akreditasi->user->name }}"
    class="spm-detail-page"
    x-data="{ ...akreditasiManagement(), ...asesorManagement() }"
    wire:poll.10s="checkForUpdates"
>
    <x-akreditasi.presence-indicator :akreditasi-id="$akreditasi->id" />
    <x-slot:toolbar>
        <x-ui.status-badge :variant="$statusVariant">
            {{ Akreditasi::getStatusLabel($akreditasi->status) }}
        </x-ui.status-badge>

        @if((int) $akreditasi->status === 3 && $asesorTipe === 1)
            <x-ui.button type="button" wire:click="confirmVisitasiSelesai" wire:loading.attr="disabled" variant="success" size="sm">
                <span wire:loading.remove wire:target="confirmVisitasiSelesai">
                    <x-ui.icon name="check-circle" class="fs-4 me-1" />
                    Konfirmasi Visitasi Selesai
                </span>
                <span wire:loading wire:target="confirmVisitasiSelesai">Memproses...</span>
            </x-ui.button>
        @endif

        @if((int) $akreditasi->status === 2 && $asesorTipe === 1)
            <x-ui.button type="button" wire:click="finalizeScoring" wire:loading.attr="disabled" variant="primary" size="sm">
                <span wire:loading.remove wire:target="finalizeScoring">
                    <x-ui.icon name="shield-tick" class="fs-4 me-1" />
                    Finalisasi Penilaian
                </span>
                <span wire:loading wire:target="finalizeScoring">Memproses...</span>
            </x-ui.button>
        @endif

        @if($canSubmitDocumentRejection)
            <x-ui.button
                type="button"
                variant="light-danger"
                size="sm"
                x-on:click="$dispatch('open-modal', 'asesor-reject-documents-modal')"
            >
                <x-ui.icon name="cross-circle" class="fs-4 me-1" />
                Tolak Dokumen
            </x-ui.button>
        @endif

        <x-ui.button :href="route('asesor.akreditasi')" variant="light">
            <x-ui.icon name="exit-right" class="fs-4 me-1" />
            Kembali
        </x-ui.button>
    </x-slot:toolbar>

    <div class="row g-5 mb-6">
        <div class="col-lg-4">
            <x-ui.stat-card label="Status Tugas" value="{{ Akreditasi::getStatusLabel($akreditasi->status) }}" variant="{{ $statusVariant }}" icon="shield-tick" />
        </div>

        <div class="col-lg-4">
            <x-ui.stat-card label="Jadwal Visitasi" value="{{ $akreditasi->tgl_visitasi ? \Carbon\Carbon::parse($akreditasi->tgl_visitasi)->format('d M Y') : 'Belum Dijadwalkan' }}" variant="info" icon="calendar" />
        </div>

        <div class="col-lg-4">
            <x-ui.stat-card label="Peran Penilaian" value="{{ (int) $asesorTipe === 1 ? 'Ketua Kelompok' : 'Anggota Kelompok' }}" variant="success" icon="security-user" />
        </div>
    </div>

    <x-akreditasi.workflow-stepper
        :status="$akreditasi->status"
        title="Tahapan Akreditasi LP2M"
        subtitle="Gunakan alur ini untuk membedakan review dokumen, visitasi, dan penilaian pasca visitasi."
        class="mb-6"
    />

    @if (session('status'))
        <x-ui.alert variant="success">{{ session('status') }}</x-ui.alert>
    @endif

    <x-ui.card flush>
        <div class="px-6 pt-5">
            <x-ui.tabs>
                <x-ui.tab wire:click="setTab('profil')" :active="$activeTab === 'profil'">Profil</x-ui.tab>
                <x-ui.tab wire:click="setTab('ipm')" :active="$activeTab === 'ipm'">IPM</x-ui.tab>
                <x-ui.tab wire:click="setTab('sdm')" :active="$activeTab === 'sdm'">SDM</x-ui.tab>
                <x-ui.tab wire:click="setTab('edpm_pesantren')" :active="$activeTab === 'edpm_pesantren'">EDPM</x-ui.tab>
                @if(in_array((int) $akreditasi->status, [
                    \App\StateMachine\AkreditasiStateMachine::STATUS_PASCA_VISITASI,
                    \App\StateMachine\AkreditasiStateMachine::STATUS_VALIDASI_ADMIN,
                    \App\StateMachine\AkreditasiStateMachine::STATUS_SELESAI,
                ], true))
                    <x-ui.tab wire:click="setTab('instrumen')" :active="$activeTab === 'instrumen'">Penilaian</x-ui.tab>
                @endif
                @if($akreditasi->status == \App\StateMachine\AkreditasiStateMachine::STATUS_VALIDASI_ADMIN
                    || $akreditasi->status == \App\StateMachine\AkreditasiStateMachine::STATUS_PASCA_VISITASI
                    || $akreditasi->status == \App\StateMachine\AkreditasiStateMachine::STATUS_SELESAI)
                    <x-ui.tab wire:click="setTab('laporan_visitasi')" :active="$activeTab === 'laporan_visitasi'">Laporan Visitasi</x-ui.tab>
                @endif
            </x-ui.tabs>
        </div>

        <div class="p-6">
            @if ($activeTab === 'profil')
                <div class="d-flex flex-column gap-6">
                    <x-ui.section-card title="Profil Pesantren" subtitle="Identitas dan jadwal visitasi pengajuan.">
                        <div class="p-6">
                            <div class="row g-5">
                                <x-ui.detail-item label="Nama Pesantren" value="{{ $pesantren->nama_pesantren ?? '-' }}" />
                                <x-ui.detail-item label="NSP" value="{{ $pesantren->ns_pesantren ?? '-' }}" />
                                <x-ui.detail-item label="Alamat" span="2">
                                    <div class="spm-detail-block spm-detail-value-muted">{{ $pesantren->alamat ?? '-' }}</div>
                                </x-ui.detail-item>
                                <x-ui.detail-item label="Kota/Kabupaten" value="{{ $pesantren->kota_kabupaten ?? '-' }}" />
                                <x-ui.detail-item label="Provinsi" value="{{ $pesantren->provinsi ?? '-' }}" />
                                <x-ui.detail-item label="Nama Mudir" value="{{ $pesantren->nama_mudir ?? '-' }}" />
                                <x-ui.detail-item label="Tahun Pendirian" value="{{ $pesantren->tahun_pendirian ?? '-' }}" />
                                @if($akreditasi->tgl_visitasi)
                                    <x-ui.detail-item label="Tanggal Visitasi" span="2">
                                        <div class="spm-detail-block">
                                            {{ \Carbon\Carbon::parse($akreditasi->tgl_visitasi)->format('d F Y') }}
                                            @if($akreditasi->tgl_visitasi_akhir && $akreditasi->tgl_visitasi != $akreditasi->tgl_visitasi_akhir)
                                                - {{ \Carbon\Carbon::parse($akreditasi->tgl_visitasi_akhir)->format('d F Y') }}
                                            @endif
                                        </div>
                                    </x-ui.detail-item>
                                @endif
                            </div>
                        </div>
                    </x-ui.section-card>

                    <x-ui.section-card title="Layanan & Fasilitas" subtitle="Unit layanan pendidikan dan kapasitas sarana.">
                        <div class="p-6">
                            <div class="row g-6">
                                <div class="col-lg-7">
                                    @if($pesantren && $pesantren->units && $pesantren->units->count() > 0)
                                        <x-ui.simple-table dense>
                                            <thead>
                                                <tr>
                                                    <th class="ps-4">Unit</th>
                                                    <th class="text-end pe-4">Jumlah Rombel</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($pesantren->units as $unit)
                                                    <tr>
                                                        <td class="ps-4 text-uppercase fw-bold">{{ $unit->unit }}</td>
                                                        <td class="text-end pe-4"><x-ui.badge variant="success">{{ $unit->jumlah_rombel }} Rombel</x-ui.badge></td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </x-ui.simple-table>
                                    @else
                                        <x-ui.empty-state title="Belum Ada Unit" description="Data unit pendidikan belum diisi." />
                                    @endif
                                </div>
                                <div class="col-lg-5">
                                    <div class="d-flex flex-column gap-4">
                                        <x-ui.stat-card label="Total Luas Tanah" value="{{ $pesantren->luas_tanah ?? '-' }} m2" variant="success" icon="geolocation" />
                                        <x-ui.stat-card label="Total Luas Bangunan" value="{{ $pesantren->luas_bangunan ?? '-' }} m2" variant="info" icon="category" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </x-ui.section-card>

                    <x-ui.section-card title="Dokumen Pesantren" subtitle="Dokumen utama dan pendukung untuk proses visitasi.">
                        <div class="p-6">
                            <div class="row g-5">
                                <div class="col-lg-6">
                                    <div class="spm-detail-label mb-3">Dokumen Utama</div>
                                    <div class="spm-document-list">
                                        @foreach($dokumenUtama as $field => $label)
                                            <x-ui.document-item :label="$label" :href="$pesantren && $pesantren->$field ? Storage::url($pesantren->$field) : null" />
                                        @endforeach
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="spm-detail-label mb-3">Dokumen Sekunder</div>
                                    <div class="spm-document-list">
                                        @foreach($dokumenSekunder as $field => $label)
                                            <x-ui.document-item :label="$label" :href="$pesantren && $pesantren->$field ? Storage::url($pesantren->$field) : null" />
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    </x-ui.section-card>
                </div>
            @endif

            @if ($activeTab === 'ipm')
                <x-ui.section-card title="Indikator Pemenuhan Mutlak" subtitle="Status dokumen IPM pesantren.">
                    <div class="p-6">
                        <div class="spm-document-list">
                            @foreach ($ipmItems as $field => $label)
                                <x-ui.document-item :label="$label" :href="$ipm && $ipm->$field ? Storage::url($ipm->$field) : null" />
                            @endforeach
                        </div>
                    </div>
                </x-ui.section-card>
            @endif

            @if ($activeTab === 'sdm')
                <x-ui.section-card title="Rekapitulasi Data SDM" subtitle="Rekap santri, ustadz, pamong, musyrif, dan tenaga kependidikan.">
                    <div class="p-6">
                        <x-ui.simple-table tableClass="spm-wide-table">
                            <thead>
                                <tr class="text-center">
                                    <th rowspan="2" class="ps-4">No.</th>
                                    <th rowspan="2" class="text-start">Bentuk</th>
                                    <th colspan="2">Santri</th>
                                    <th colspan="2">Ustadz Dirosah</th>
                                    <th colspan="2">Ustadz Non Dirosah</th>
                                    <th colspan="2">Pamong</th>
                                    <th colspan="2">Musyrif/Ah</th>
                                    <th colspan="2" class="pe-4">Tenaga Kependidikan</th>
                                </tr>
                                <tr class="text-center">
                                    @for($i = 0; $i < 6; $i++)
                                        <th>L</th>
                                        <th>P</th>
                                    @endfor
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($levels as $index => $level)
                                    <tr class="text-center">
                                        <td class="ps-4 fw-bold">{{ $index + 1 }}</td>
                                        <td class="text-start text-uppercase fw-bold">{{ $level }}</td>
                                        @foreach($fields as $field)
                                            <td>{{ $sdm[$level]->$field ?? 0 }}</td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="text-center">
                                    <td colspan="2" class="ps-4 text-uppercase text-start">Jumlah</td>
                                    @foreach($fields as $field)
                                        <td>{{ $this->getTotal($field) }}</td>
                                    @endforeach
                                </tr>
                            </tfoot>
                        </x-ui.simple-table>
                    </div>
                </x-ui.section-card>
            @endif

            @if ($activeTab === 'edpm_pesantren')
                <div class="d-flex flex-column gap-6">
                    <x-ui.section-card title="EDPM Pesantren" subtitle="Format tabel diselaraskan dengan tabel NA agar asesor membaca EDPM, butir, bukti, dan catatan dalam pola yang sama.">
                        <div class="p-6">
                            <x-ui.simple-table tableClass="spm-score-table spm-score-table--readonly">
                                <thead>
                                    <tr>
                                        <th class="ps-4 w-150px">Komponen</th>
                                        <th class="text-center w-80px">No SK</th>
                                        <th class="text-center w-90px">No Butir</th>
                                        <th>Butir Pernyataan</th>
                                        <th class="text-center w-100px">EDPM</th>
                                        <th class="text-center w-120px">Bukti</th>
                                        <th class="pe-4 w-280px">Catatan Komponen</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($komponens as $komponen)
                                        @php $butirsCount = count($komponen->butirs); @endphp
                                        @foreach ($komponen->butirs as $index => $butir)
                                            <tr>
                                                @if ($index === 0)
                                                    <td rowspan="{{ $butirsCount }}" class="ps-4 fw-bold text-primary text-uppercase align-middle">{{ $komponen->nama }}</td>
                                                @endif
                                                <td class="text-center text-muted">{{ $butir->no_sk }}</td>
                                                <td class="text-center fw-bold">{{ $butir->nomor_butir }}</td>
                                                <td class="spm-edpm-statement">{{ $butir->butir_pernyataan }}</td>
                                                <td class="text-center">
                                                    <x-ui.badge variant="warning" class="spm-score-badge">{{ $pesantrenEvaluasis[$butir->id] ?? '-' }}</x-ui.badge>
                                                </td>
                                                <td class="text-center">
                                                    @if(!empty($pesantrenLinks[$butir->id]))
                                                        <x-ui.button :href="$pesantrenLinks[$butir->id]" target="_blank" variant="light-primary" size="sm">Bukti</x-ui.button>
                                                    @else
                                                        <x-ui.status-badge variant="secondary">-</x-ui.status-badge>
                                                    @endif
                                                </td>
                                                @if ($index === 0)
                                                    <td rowspan="{{ $butirsCount }}" class="pe-4 align-top">
                                                        <div class="spm-detail-value spm-detail-value-muted">
                                                            {{ $pesantrenCatatans[$komponen->id] ?: 'Tidak ada catatan.' }}
                                                        </div>
                                                    </td>
                                                @endif
                                            </tr>
                                        @endforeach
                                    @endforeach
                                </tbody>
                            </x-ui.simple-table>
                        </div>
                    </x-ui.section-card>
                </div>
            @endif

            @if ($activeTab === 'instrumen')
                <div class="d-flex flex-column gap-6">
                    @if ($akreditasi->status == \App\StateMachine\AkreditasiStateMachine::STATUS_VALIDASI_ADMIN)
                        <x-ui.alert variant="warning" icon="timer" title="Data Sedang Diverifikasi">
                            Penilaian sedang dalam proses verifikasi oleh admin. Nilai Kelompok hanya dapat diisi Ketua Kelompok setelah Nilai Ketua dan Nilai Anggota final seluruhnya.
                        </x-ui.alert>
                    @endif

                    {{-- Progress Indicators are only available after visitasi is confirmed selesai. --}}
                    @if ($akreditasi->status == \App\StateMachine\AkreditasiStateMachine::STATUS_PASCA_VISITASI
                        && ($asesor1NaProgress || $asesor2NaProgress))
                        <x-ui.section-card title="Progress Penilaian" subtitle="Kelengkapan pengisian butir oleh masing-masing asesor.">
                            <div class="p-6">
                                <div class="row g-5">
                                    @if ($asesorTipe == 1)
                                        {{-- Ketua Kelompok sees their own score and group score progress --}}
                                        @if ($asesor1NaProgress)
                                            @php $color1Na = $asesor1NaProgress['percentage'] >= 100 ? 'green' : ($asesor1NaProgress['percentage'] >= 50 ? 'amber' : 'red'); @endphp
                                            <div class="col-lg-4">
                                                <x-progress-indicator
                                                    :filled="$asesor1NaProgress['filled']"
                                                    :total="$asesor1NaProgress['total']"
                                                    :percentage="$asesor1NaProgress['percentage']"
                                                    label="Nilai Ketua"
                                                    :color="$color1Na"
                                                />
                                            </div>
                                        @endif
                                        @if ($asesor1NkProgress)
                                            @php $color1Nk = $asesor1NkProgress['percentage'] >= 100 ? 'green' : ($asesor1NkProgress['percentage'] >= 50 ? 'amber' : 'red'); @endphp
                                            <div class="col-lg-4">
                                                <x-progress-indicator
                                                    :filled="$asesor1NkProgress['filled']"
                                                    :total="$asesor1NkProgress['total']"
                                                    :percentage="$asesor1NkProgress['percentage']"
                                                    label="Nilai Kelompok"
                                                    :color="$color1Nk"
                                                />
                                            </div>
                                        @endif
                                        @if ($asesor2NaProgress)
                                            @php $color2Na = $asesor2NaProgress['percentage'] >= 100 ? 'green' : ($asesor2NaProgress['percentage'] >= 50 ? 'amber' : 'red'); @endphp
                                            <div class="col-lg-4">
                                                <x-progress-indicator
                                                    :filled="$asesor2NaProgress['filled']"
                                                    :total="$asesor2NaProgress['total']"
                                                    :percentage="$asesor2NaProgress['percentage']"
                                                    label="Nilai Anggota"
                                                    :color="$color2Na"
                                                />
                                            </div>
                                        @endif

                                        {{-- Nilai Kelompok stays locked until both roles submit final scores --}}
                                        @if (! $nilaiKelompokUnlocked)
                                            <div class="col-12">
                                                <x-ui.alert variant="warning" icon="information-2" title="Nilai Kelompok Terkunci" class="mb-0">
                                                    Nilai Kelompok akan terbuka setelah Nilai Ketua dan Nilai Anggota disubmit final seluruhnya.
                                                    @if ($asesor2NaProgress)
                                                        Progress Anggota: {{ $asesor2NaProgress['filled'] }}/{{ $asesor2NaProgress['total'] }} butir ({{ number_format($asesor2NaProgress['percentage'], 0) }}%).
                                                    @endif
                                                </x-ui.alert>
                                            </div>
                                        @endif
                                    @else
                                        {{-- Anggota Kelompok sees their own score progress --}}
                                        @if ($asesor2NaProgress)
                                            @php $color2Na = $asesor2NaProgress['percentage'] >= 100 ? 'green' : ($asesor2NaProgress['percentage'] >= 50 ? 'amber' : 'red'); @endphp
                                            <div class="col-lg-6">
                                                <x-progress-indicator
                                                    :filled="$asesor2NaProgress['filled']"
                                                    :total="$asesor2NaProgress['total']"
                                                    :percentage="$asesor2NaProgress['percentage']"
                                                    label="Nilai Anggota"
                                                    :color="$color2Na"
                                                />
                                            </div>
                                        @endif
                                        @if ($asesor1NaProgress)
                                            @php $color1Na = $asesor1NaProgress['percentage'] >= 100 ? 'green' : ($asesor1NaProgress['percentage'] >= 50 ? 'amber' : 'red'); @endphp
                                            <div class="col-lg-6">
                                                <x-progress-indicator
                                                    :filled="$asesor1NaProgress['filled']"
                                                    :total="$asesor1NaProgress['total']"
                                                    :percentage="$asesor1NaProgress['percentage']"
                                                    label="Nilai Ketua"
                                                    :color="$color1Na"
                                                />
                                            </div>
                                        @endif
                                    @endif
                                </div>
                            </div>
                        </x-ui.section-card>
                    @endif

                    <form x-on:submit.prevent="confirmSaveInstrumen($wire)" class="d-flex flex-column gap-6">
                        <x-ui.section-card title="Penilaian Visitasi" subtitle="Isi nilai sesuai peran. Nilai Kelompok dibuka setelah nilai Ketua dan Anggota final.">
                            <div class="p-6">
                                <x-ui.simple-table tableClass="spm-score-table">
                                    <thead>
                                        <tr>
                                            <th class="ps-4 w-150px">Komponen</th>
                                            <th class="text-center w-80px">No SK</th>
                                            <th class="text-center w-90px">No Butir</th>
                                            <th>Butir Pernyataan</th>
                                            <th class="text-center w-100px">EDPM</th>
                                            @if ($this->asesorTipe == 1)
                                                <th class="text-center w-110px">Nilai Ketua</th>
                                                <th class="text-center w-110px">Nilai Anggota</th>
                                                <th class="text-center w-90px">Delta</th>
                                                <th class="text-center w-120px">Nilai Kelompok</th>
                                                <th class="pe-4 w-240px">Catatan Butir</th>
                                            @else
                                                <th class="text-center pe-4 w-120px">Nilai Anggota</th>
                                            @endif
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($komponens as $komponen)
                                            @php $butirsCount = count($komponen->butirs); @endphp
                                            @foreach ($komponen->butirs as $index => $butir)
                                                <tr>
                                                    @if ($index === 0)
                                                        <td rowspan="{{ $butirsCount }}" class="ps-4 fw-bold text-primary text-uppercase align-middle">{{ $komponen->nama }}</td>
                                                    @endif
                                                    <td class="text-center text-muted">{{ $butir->no_sk }}</td>
                                                    <td class="text-center fw-bold">{{ $butir->nomor_butir }}</td>
                                                    <td class="spm-edpm-statement">{{ $butir->butir_pernyataan }}</td>
                                                    <td class="text-center">
                                                        <x-ui.badge variant="warning" class="spm-score-badge">{{ $pesantrenEvaluasis[$butir->id] ?? '-' }}</x-ui.badge>
                                                    </td>
                                                    <td class="text-center">
                                                        @if ($akreditasi->status == \App\StateMachine\AkreditasiStateMachine::STATUS_VALIDASI_ADMIN)
                                                            <x-ui.badge variant="primary">{{ $asesorEvaluasis[$butir->id] ?: '-' }}</x-ui.badge>
                                                        @else
                                                            @php $naFinal = isset($asesorFinalStatus[$butir->id]) && $asesorFinalStatus[$butir->id]; @endphp
                                                            @if($naFinal)
                                                                <x-ui.badge variant="success">{{ $asesorEvaluasis[$butir->id] ?? '-' }} <x-ui.icon name="lock" class="fs-9 ms-1" /></x-ui.badge>
                                                            @else
                                                                <div class="d-flex align-items-center gap-1 justify-content-center">
                                                                    <x-ui.select
                                                                        model="asesorEvaluasis.{{ $butir->id }}"
                                                                        modifier="live"
                                                                        :options="['1' => '1', '2' => '2', '3' => '3', '4' => '4']"
                                                                        placeholder="-"
                                                                        size="sm"
                                                                        class="spm-score-control"
                                                                        :disabled="$akreditasi->status == \App\StateMachine\AkreditasiStateMachine::STATUS_PASCA_VISITASI && ($asesorTipe == 2 || !$isLocked) ? false : true"
                                                                    />
                                                                    @if(!empty($asesorEvaluasis[$butir->id]))
                                                                        <x-ui.button
                                                                            type="button"
                                                                            size="sm"
                                                                            variant="light-success"
                                                                            wire:click="saveNaValue({{ $butir->id }}, {{ $asesorEvaluasis[$butir->id] ?? 0 }}, true)"
                                                                            title="Kunci sebagai Final"
                                                                            class="px-2 py-1"
                                                                        >
                                                                            <x-ui.icon name="lock" class="fs-8" />
                                                                        </x-ui.button>
                                                                    @endif
                                                                </div>
                                                            @endif
                                                        @endif
                                                        @error('asesorEvaluasis.' . $butir->id)
                                                            <div class="invalid-feedback d-block fs-9">{{ $message }}</div>
                                                        @enderror
                                                    </td>

                                                    @if ($this->asesorTipe == 1)
                                                        <td class="text-center fw-bold text-success">{{ $otherAsesorEvaluasis[$butir->id] ?? '' }}</td>
                                                        <td class="text-center">
                                                            @php
                                                                $na1Value = $asesorEvaluasis[$butir->id] ?? null;
                                                                $na2Value = $otherAsesorEvaluasis[$butir->id] ?? null;
                                                                $deltaValue = is_numeric($na1Value) && is_numeric($na2Value)
                                                                    ? abs((int) $na1Value - (int) $na2Value)
                                                                    : null;
                                                            @endphp
                                                            @if(! is_null($deltaValue))
                                                                <x-ui.badge :variant="$deltaValue === 0 ? 'success' : 'warning'" class="spm-score-badge">
                                                                    {{ $deltaValue }}
                                                                </x-ui.badge>
                                                            @else
                                                                <span class="text-muted">-</span>
                                                            @endif
                                                        </td>
                                                        <td class="text-center">
                                                            @if ($akreditasi->status == \App\StateMachine\AkreditasiStateMachine::STATUS_VALIDASI_ADMIN)
                                                                <x-ui.badge variant="warning">{{ $asesorNks[$butir->id] ?: '-' }}</x-ui.badge>
                                                            @else
                                                                <x-ui.select
                                                                    model="asesorNks.{{ $butir->id }}"
                                                                    modifier="live"
                                                                    :options="['1' => '1', '2' => '2', '3' => '3', '4' => '4']"
                                                                    placeholder="{{ $nilaiKelompokUnlocked ? 'Pilih' : 'Terkunci' }}"
                                                                    size="sm"
                                                                    class="spm-score-control mx-auto"
                                                                    :disabled="$akreditasi->status == \App\StateMachine\AkreditasiStateMachine::STATUS_PASCA_VISITASI && $nilaiKelompokUnlocked ? false : true"
                                                                />
                                                            @endif
                                                            @error('asesorNks.' . $butir->id)
                                                                <div class="invalid-feedback d-block fs-9">{{ $message }}</div>
                                                            @enderror
                                                        </td>
                                                        <td class="pe-4">
                                                            @if ($akreditasi->status == \App\StateMachine\AkreditasiStateMachine::STATUS_VALIDASI_ADMIN)
                                                                <div class="fs-8 text-muted">{{ $asesorButirCatatans[$butir->id] ?: '-' }}</div>
                                                            @else
                                                                <x-ui.textarea
                                                                    model="asesorButirCatatans.{{ $butir->id }}"
                                                                    modifier="live"
                                                                    rows="2"
                                                                    class="fs-8"
                                                                    placeholder="Catatan butir..."
                                                                    :disabled="$akreditasi->status == \App\StateMachine\AkreditasiStateMachine::STATUS_PASCA_VISITASI ? false : true"
                                                                />
                                                            @endif
                                                        </td>
                                                    @endif
                                                </tr>
                                            @endforeach
                                        @endforeach
                                    </tbody>
                                </x-ui.simple-table>
                            </div>
                        </x-ui.section-card>

                        @if ($this->asesorTipe == 1)
                            <x-ui.section-card title="Catatan Rekomendasi Komponen" subtitle="Ringkasan rekomendasi hasil Nilai Kelompok per komponen.">
                                <div class="p-6">
                                    <div class="row g-5">
                                        @foreach ($komponens as $komponen)
                                            <div class="col-lg-6">
                                                <div class="spm-soft-panel h-100">
                                                    <div class="spm-detail-label">{{ $komponen->nama }}</div>
                                                    @if ($akreditasi->status == \App\StateMachine\AkreditasiStateMachine::STATUS_VALIDASI_ADMIN)
                                                        <div class="spm-detail-value spm-detail-value-muted">{!! $asesorCatatans[$komponen->id] ?: '<span class="text-muted">Tidak ada catatan.</span>' !!}</div>
                                                    @else
                                                        <x-quill-editor
                                                            wire:model.live="asesorCatatans.{{ $komponen->id }}"
                                                            placeholder="Masukkan catatan rekomendasi {{ $komponen->nama }}..."
                                                            :disabled="$akreditasi->status != \App\StateMachine\AkreditasiStateMachine::STATUS_PASCA_VISITASI"
                                                        />
                                                    @endif
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </x-ui.section-card>
                        @endif
                    </form>

                    {{-- Task 6.1 & 6.2: Dismissible finalization error alert --}}
                    <x-ui.alert
                        variant="danger"
                        icon="cross-circle"
                        title="Finalisasi Gagal"
                        x-data="{ show: false, errorType: '', details: null }"
                        x-on:finalization-failed.window="
                            errorType = $event.detail.error;
                            details = $event.detail.details;
                            show = true;
                            $nextTick(() => $el.scrollIntoView({ behavior: 'smooth', block: 'center' }));
                        "
                        x-show="show"
                        x-transition:enter="transition ease-out duration-150"
                        x-transition:enter-start="opacity-0"
                        x-transition:enter-end="opacity-100"
                        x-transition:leave="transition ease-in duration-100"
                        x-transition:leave-start="opacity-100"
                        x-transition:leave-end="opacity-0"
                        style="display: none;"
                        class="mb-0"
                    >
                        <template x-if="errorType === 'asesor2_incomplete'">
                            <span>
                                Anggota Kelompok belum menyelesaikan Nilai Anggota
                                <template x-if="details">
                                    (<span x-text="details.filled"></span>/<span x-text="details.total"></span> butir, <span x-text="Math.round(details.percentage)"></span>%)
                                </template>.
                            </span>
                        </template>
                        <template x-if="errorType === 'asesor1_na_incomplete'">
                            <span>
                                Nilai Ketua belum lengkap
                                <template x-if="details">
                                    (<span x-text="details.filled"></span>/<span x-text="details.total"></span> butir)
                                </template>.
                            </span>
                        </template>
                        <template x-if="errorType === 'asesor1_nk_incomplete'">
                            <span>
                                Nilai Kelompok belum lengkap
                                <template x-if="details">
                                    (<span x-text="details.filled"></span>/<span x-text="details.total"></span> butir)
                                </template>.
                            </span>
                        </template>
                        <template x-if="!['asesor2_incomplete','asesor1_na_incomplete','asesor1_nk_incomplete'].includes(errorType)">
                            <span>Terjadi kesalahan saat finalisasi. Silakan coba lagi.</span>
                        </template>

                        <x-slot:actions>
                            <x-ui.button unstyled type="button" class="btn-close" @click="show = false" aria-label="Tutup"></x-ui.button>
                        </x-slot:actions>
                    </x-ui.alert>

                    <div class="spm-action-panel d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-4">
                        <div>
                            <h3 class="spm-card-title mb-1">Evaluasi Visitasi</h3>
                            <div class="text-muted fw-semibold fs-7">Pastikan semua data sudah lengkap sebelum verifikasi final.</div>
                        </div>
                        @if ((int) $akreditasi->status === \App\StateMachine\AkreditasiStateMachine::STATUS_PASCA_VISITASI)
                            <div class="d-flex flex-wrap justify-content-end gap-2">
                                <x-ui.button type="button" @click="confirmSaveInstrumen($wire)" wire:loading.attr="disabled" variant="primary">
                                    {{ $asesorTipe == 1 ? 'Simpan Nilai Ketua/Kelompok' : 'Simpan Nilai Anggota' }}
                                </x-ui.button>
                            </div>
                        @endif
                    </div>

                    <div class="spm-scroll-actions">
                        <x-ui.button
                            type="button"
                            variant="primary"
                            class="btn-icon"
                            title="Scroll ke atas"
                            onclick="document.getElementById('main-content-scroll')?.scrollTo({top: 0, behavior: 'smooth'})"
                        >
                            <x-ui.icon name="arrow-up" class="fs-2" />
                        </x-ui.button>
                        <x-ui.button
                            type="button"
                            variant="primary"
                            class="btn-icon"
                            title="Scroll ke bawah"
                            onclick="const el = document.getElementById('main-content-scroll'); el?.scrollTo({top: el.scrollHeight, behavior: 'smooth'})"
                        >
                            <x-ui.icon name="arrow-down" class="fs-2" />
                        </x-ui.button>
                    </div>

                    {{-- Konfirmasi Visitasi Selesai (Ketua Kelompok, status Visitasi) --}}
                    @if((int)$akreditasi->status === \App\StateMachine\AkreditasiStateMachine::STATUS_VISITASI && $asesorTipe == 1)
                        @php $visitasiStarted = $akreditasi->tgl_visitasi && \Carbon\Carbon::today()->gte(\Carbon\Carbon::parse($akreditasi->tgl_visitasi)->startOfDay()); @endphp
                        @if($visitasiStarted)
                            <div class="mt-6">
                                <x-ui.section-card title="Konfirmasi Visitasi">
                                    <div class="p-6">
                                        <p class="text-gray-700 mb-4">Konfirmasi bahwa visitasi telah selesai dilaksanakan. Akreditasi akan masuk ke tahap Penilaian Pasca Visitasi.</p>
                                        <x-ui.button type="button" wire:click="confirmVisitasiSelesai" wire:loading.attr="disabled" variant="success">
                                            <span wire:loading.remove wire:target="confirmVisitasiSelesai">Konfirmasi Visitasi Selesai</span>
                                            <span wire:loading wire:target="confirmVisitasiSelesai">Memproses...</span>
                                        </x-ui.button>
                                    </div>
                                </x-ui.section-card>
                            </div>
                        @endif
                    @endif

                    {{-- Finalisasi Penilaian (Ketua Kelompok, status Penilaian Pasca Visitasi) --}}
                    @if((int)$akreditasi->status === \App\StateMachine\AkreditasiStateMachine::STATUS_PASCA_VISITASI && $asesorTipe == 1)
                        <div class="mt-6">
                            <x-ui.section-card title="Finalisasi Penilaian">
                                <div class="p-6">
                                    <p class="text-gray-700 mb-4">Finalisasi Nilai Ketua, Nilai Anggota, Nilai Kelompok, dan catatan. Setelah difinalisasi, akreditasi akan masuk ke tahap Validasi Admin.</p>
                                    <x-ui.button type="button" wire:click="finalizeScoring" wire:loading.attr="disabled" variant="primary">
                                        <span wire:loading.remove wire:target="finalizeScoring">Finalisasi Penilaian</span>
                                        <span wire:loading wire:target="finalizeScoring">Memproses...</span>
                                    </x-ui.button>
                                </div>
                            </x-ui.section-card>
                        </div>
                    @endif
                </div>
            @endif

            @if ($activeTab === 'laporan_visitasi')
                <div class="d-flex flex-column gap-6">
                    {{-- Laporan Individu --}}
                    <x-ui.section-card
                        title="{{ $asesorTipe === 1 ? 'Laporan Visitasi Individu Ketua Kelompok' : 'Laporan Visitasi Individu Anggota Kelompok' }}"
                        subtitle="Unggah laporan hasil visitasi individu Anda (PDF/DOCX, max 5MB)."
                    >
                        <div class="p-6">
                            @php
                                $laporanIndividuPath = $asesorTipe === 1
                                    ? $akreditasi->laporan_visitasi_asesor1
                                    : $akreditasi->laporan_visitasi_asesor2;
                            @endphp

                            @if($laporanIndividuPath)
                                <div class="d-flex align-items-center gap-4 mb-4">
                                    <x-ui.icon name="document" class="fs-2 text-success" />
                                    <div>
                                        <div class="fw-bold text-success">Laporan sudah diunggah</div>
                                        <a href="{{ Storage::url($laporanIndividuPath) }}" target="_blank" class="text-primary fs-7">Lihat Laporan</a>
                                    </div>
                                </div>
                            @endif

                            @if((int) $akreditasi->status === \App\StateMachine\AkreditasiStateMachine::STATUS_PASCA_VISITASI)
                                <x-ui.form-field
                                    :label="$laporanIndividuPath ? 'Ganti Laporan Individu' : 'Unggah Laporan Individu'"
                                    for="laporan_individu_file"
                                    :error="$errors->get('laporan_individu_file')"
                                    hint="PDF atau DOCX, maksimal 5MB"
                                >
                                    <x-ui.file-upload
                                        model="laporan_individu_file"
                                        id="laporan_individu_file"
                                        accept=".pdf,.docx"
                                        :file="$laporan_individu_file"
                                        placeholder="Pilih file laporan individu"
                                    />
                                </x-ui.form-field>
                                @if($laporan_individu_file)
                                    <x-ui.button type="button" wire:click="uploadLaporanIndividu" wire:loading.attr="disabled" variant="primary" class="mt-3">
                                        <span wire:loading.remove wire:target="uploadLaporanIndividu">Simpan Laporan Individu</span>
                                        <span wire:loading wire:target="uploadLaporanIndividu">Mengunggah...</span>
                                    </x-ui.button>
                                @endif
                            @else
                                @if(!$laporanIndividuPath)
                                    <div class="text-muted fs-7">Laporan dapat diunggah saat status Penilaian Pasca Visitasi.</div>
                                @endif
                            @endif
                        </div>
                    </x-ui.section-card>

                    {{-- Laporan Kelompok (Ketua Kelompok only) --}}
                    @if($asesorTipe === 1)
                        <x-ui.section-card title="Laporan Visitasi Kelompok" subtitle="Unggah laporan kelompok hasil visitasi (PDF/DOCX, max 5MB). Hanya Ketua Kelompok yang dapat mengunggah.">
                            <div class="p-6">
                                @if($akreditasi->laporan_visitasi_kelompok)
                                    <div class="d-flex align-items-center gap-4 mb-4">
                                        <x-ui.icon name="document" class="fs-2 text-success" />
                                        <div>
                                            <div class="fw-bold text-success">Laporan kelompok sudah diunggah</div>
                                            <a href="{{ Storage::url($akreditasi->laporan_visitasi_kelompok) }}" target="_blank" class="text-primary fs-7">Lihat Laporan</a>
                                        </div>
                                    </div>
                                @endif

                                @if((int) $akreditasi->status === \App\StateMachine\AkreditasiStateMachine::STATUS_PASCA_VISITASI)
                                    <x-ui.form-field
                                        :label="$akreditasi->laporan_visitasi_kelompok ? 'Ganti Laporan Kelompok' : 'Unggah Laporan Kelompok'"
                                        for="laporan_kelompok_file"
                                        :error="$errors->get('laporan_kelompok_file')"
                                        hint="PDF atau DOCX, maksimal 5MB"
                                    >
                                        <x-ui.file-upload
                                            model="laporan_kelompok_file"
                                            id="laporan_kelompok_file"
                                            accept=".pdf,.docx"
                                            :file="$laporan_kelompok_file"
                                            placeholder="Pilih file laporan kelompok"
                                        />
                                    </x-ui.form-field>
                                    @if($laporan_kelompok_file)
                                        <x-ui.button type="button" wire:click="uploadLaporanKelompok" wire:loading.attr="disabled" variant="primary" class="mt-3">
                                            <span wire:loading.remove wire:target="uploadLaporanKelompok">Simpan Laporan Kelompok</span>
                                            <span wire:loading wire:target="uploadLaporanKelompok">Mengunggah...</span>
                                        </x-ui.button>
                                    @endif
                                @else
                                    @if(!$akreditasi->laporan_visitasi_kelompok)
                                        <div class="text-muted fs-7">Laporan dapat diunggah saat status Penilaian Pasca Visitasi.</div>
                                    @endif
                                @endif
                            </div>
                        </x-ui.section-card>
                    @endif
                </div>
            @endif

            {{-- Rejection Section for Ketua Kelompok --}}
            @if($asesorTipe == 1 && !empty($rejectionStatus))
                {{-- Rejection count and remaining attempts --}}
                @if($rejectionStatus['count'] > 0 || $rejectionStatus['active'])
                    <div class="mt-6">
                        <x-ui.section-card title="Status Penolakan" subtitle="Informasi penolakan dan sisa kesempatan.">
                            <div class="p-6">
                                <div class="d-flex align-items-center gap-3 mb-4">
                                    <span class="fw-semibold">Penolakan:</span>
                                    <x-ui.badge variant="{{ $rejectionStatus['count'] >= $rejectionStatus['limit'] ? 'danger' : 'info' }}">
                                        {{ $rejectionStatus['count'] }} dari {{ $rejectionStatus['limit'] }}
                                    </x-ui.badge>
                                    @if($rejectionStatus['limit'] - $rejectionStatus['count'] > 0)
                                        <span class="text-muted fs-8">(Sisa {{ $rejectionStatus['limit'] - $rejectionStatus['count'] }} kesempatan)</span>
                                    @endif
                                </div>

                                {{-- Accept/Reject options after perbaikan submission --}}
                                @if($rejectionStatus['active'] && $rejectionStatus['active']->status === 'submitted')
                                    <x-ui.alert variant="info" icon="information-2" title="Perbaikan Telah Dikirim" class="mb-4">
                                        <div>
                                            Pesantren telah mengirim perbaikan. Silakan review dan pilih tindakan.
                                            @if($rejectionStatus['active']->items)
                                                <div class="mt-2">
                                                    <span class="text-muted fs-8">Item yang diperbaiki:</span>
                                                    <div class="d-flex flex-wrap gap-1 mt-1">
                                                        @foreach($rejectionStatus['active']->items as $item)
                                                            <x-ui.badge variant="light">{{ $item }}</x-ui.badge>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            @endif
                                            <div class="d-flex gap-2 mt-3">
                                                <x-ui.button @click="confirmTerimaPerbaikan($wire)" wire:loading.attr="disabled" variant="success" size="sm">
                                                    <span wire:loading.remove wire:target="acceptPerbaikan">Terima Perbaikan</span>
                                                    <span wire:loading wire:target="acceptPerbaikan">Memproses...</span>
                                                </x-ui.button>
                                                <x-ui.button wire:click="rejectAgain" variant="danger" size="sm">
                                                    Tolak Lagi
                                                </x-ui.button>
                                            </div>
                                        </div>
                                    </x-ui.alert>
                                @endif

                                {{-- Rejection History --}}
                                @if($rejectionStatus['history']->count() > 0)
                                    <div class="spm-detail-label mb-3">Riwayat Penolakan</div>
                                    <div class="d-flex flex-column gap-3">
                                        @foreach($rejectionStatus['history'] as $rejection)
                                            <div class="spm-soft-panel">
                                                <div class="d-flex align-items-center justify-content-between mb-2">
                                                    <div class="fw-bold">
                                                        @if($rejection->type === 'admin_final')
                                                            Penolakan Final (Admin)
                                                        @else
                                                            Penolakan #{{ $rejection->rejection_number }}
                                                        @endif
                                                    </div>
                                                    <x-ui.badge variant="{{ match($rejection->status) {
                                                        'pending' => 'warning',
                                                        'submitted' => 'info',
                                                        'accepted' => 'success',
                                                        'expired' => 'danger',
                                                        'limit_reached' => 'danger',
                                                        'final' => 'danger',
                                                        default => 'secondary',
                                                    } }}">
                                                        {{ match($rejection->status) {
                                                            'pending' => 'Menunggu Perbaikan',
                                                            'submitted' => 'Perbaikan Dikirim',
                                                            'accepted' => 'Diterima',
                                                            'expired' => 'Kadaluarsa',
                                                            'limit_reached' => 'Batas Tercapai',
                                                            'final' => 'Final',
                                                            default => $rejection->status,
                                                        } }}
                                                    </x-ui.badge>
                                                </div>
                                                @if($rejection->items)
                                                    <div class="mb-2">
                                                        <span class="text-muted fs-8">Item ditolak:</span>
                                                        <div class="d-flex flex-wrap gap-1 mt-1">
                                                            @foreach($rejection->items as $item)
                                                                <x-ui.badge variant="light">{{ $item }}</x-ui.badge>
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                @endif
                                                @if($rejection->explanation)
                                                    <div class="mb-2">
                                                        <span class="text-muted fs-8">Catatan:</span>
                                                        <div class="fs-7">{{ $rejection->explanation }}</div>
                                                    </div>
                                                @endif
                                                <div class="d-flex gap-3 text-muted fs-8">
                                                    <span>Tanggal: {{ $rejection->created_at->format('d M Y H:i') }}</span>
                                                    @if($rejection->perbaikan_submitted_at)
                                                        <span>Perbaikan dikirim: {{ $rejection->perbaikan_submitted_at->format('d M Y H:i') }}</span>
                                                    @endif
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </x-ui.section-card>
                    </div>
                @endif

            @endif
        </div>
    </x-ui.card>

    @if($canSubmitDocumentRejection)
        <x-ui.modal name="asesor-reject-documents-modal" maxWidth="2xl" focusable>
            <form x-on:submit.prevent="confirmKirimPenolakan($wire)" data-ui-modal-form>
                <x-ui.modal-header
                    title="Tolak Dokumen"
                    subtitle="Pilih bagian yang perlu diperbaiki, lalu berikan catatan yang jelas untuk pesantren."
                    icon="cross-circle"
                    variant="danger"
                />

                <x-ui.modal-body>
                    <x-ui.form-field label="Item yang Ditolak" required :error="$errors->get('rejectedItems')">
                        <div class="row g-3">
                            @foreach($selectableItems as $section)
                                <div class="col-lg-6">
                                    <div class="spm-soft-panel h-100">
                                        @if(empty($section['children']))
                                            <x-ui.checkbox model="rejectedItems" :value="$section['id']" class="align-items-center">
                                                <span class="fw-bold">{{ $section['label'] }}</span>
                                            </x-ui.checkbox>
                                        @else
                                            <div class="fw-bold text-gray-800 mb-3">{{ $section['label'] }}</div>
                                            <div class="d-flex flex-column gap-2 ps-1">
                                                @foreach($section['children'] as $child)
                                                    @if(empty($child['children']))
                                                        <x-ui.checkbox model="rejectedItems" :value="$child['id']" class="align-items-center">
                                                            <span>{{ $child['label'] }}</span>
                                                        </x-ui.checkbox>
                                                    @else
                                                        <div class="fw-semibold text-gray-700 mt-1">{{ $child['label'] }}</div>
                                                        <div class="d-flex flex-column gap-1 ps-3">
                                                            @foreach($child['children'] as $subChild)
                                                                <x-ui.checkbox model="rejectedItems" :value="$subChild['id']" class="align-items-center">
                                                                    <span class="fs-8">{{ $subChild['label'] }}</span>
                                                                </x-ui.checkbox>
                                                            @endforeach
                                                        </div>
                                                    @endif
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </x-ui.form-field>

                    <x-ui.form-field label="Catatan Penolakan" for="rejectionExplanation" required :error="$errors->get('rejectionExplanation')" hint="Minimal 10 karakter. Tulis bagian yang perlu diperbaiki dan ekspektasi perbaikannya.">
                        <x-ui.textarea
                            model="rejectionExplanation"
                            id="rejectionExplanation"
                            rows="4"
                            required
                            placeholder="Contoh: Dokumen kurikulum belum memuat struktur program dan perlu dilengkapi dengan bukti pendukung..."
                        />
                    </x-ui.form-field>
                </x-ui.modal-body>

                <x-ui.modal-footer>
                    <x-ui.button type="button" variant="light" x-on:click="$dispatch('close-modal', 'asesor-reject-documents-modal')">Batal</x-ui.button>
                    <x-ui.button type="submit" variant="danger" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="submitRejection">
                            <x-ui.icon name="cross-circle" class="fs-4 me-1" />
                            Kirim Penolakan
                        </span>
                        <span wire:loading wire:target="submitRejection">Memproses...</span>
                    </x-ui.button>
                </x-ui.modal-footer>
            </form>
        </x-ui.modal>
    @endif
</x-ui.page>
