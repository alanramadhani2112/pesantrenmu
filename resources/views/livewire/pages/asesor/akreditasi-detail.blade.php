@use('App\Models\Akreditasi')
@use('Illuminate\Support\Facades\Storage')
@php
    $statusVariant = match ((int) $akreditasi->status) {
        1 => 'success',
        2 => 'danger',
        3 => 'warning',
        4 => 'info',
        default => 'primary',
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
@endphp

<x-slot name="header">{{ __('Akreditasi Detail') }}</x-slot>

<x-ui.page
    title="Visitasi Akreditasi"
    subtitle="{{ $pesantren?->nama_pesantren ?? $akreditasi->user->name }}"
    x-data="{ ...akreditasiManagement(), ...asesorManagement() }"
>
    <x-slot:toolbar>
        <x-ui.status-badge :variant="$statusVariant">
            {{ Akreditasi::getStatusLabel($akreditasi->status) }}
        </x-ui.status-badge>

        <x-ui.button :href="route('asesor.akreditasi')" variant="light">
            <x-ui.icon name="exit-right" class="fs-4 me-1" />
            Kembali
        </x-ui.button>
    </x-slot:toolbar>

    <div class="row g-5 mb-6">
        <div class="col-lg-4">
            <x-ui.stat-card label="Status Tugas" value="{{ Akreditasi::getStatusLabel($akreditasi->status) }}" variant="{{ $statusVariant }}">
                <x-slot:icon><x-ui.icon name="shield-tick" class="fs-2" /></x-slot:icon>
            </x-ui.stat-card>
        </div>

        <div class="col-lg-4">
            <x-ui.stat-card label="Jadwal Visitasi" value="{{ $akreditasi->tgl_visitasi ? \Carbon\Carbon::parse($akreditasi->tgl_visitasi)->format('d M Y') : 'Belum Dijadwalkan' }}" variant="info">
                <x-slot:icon><x-ui.icon name="calendar" class="fs-2" /></x-slot:icon>
            </x-ui.stat-card>
        </div>

        <div class="col-lg-4">
            <x-ui.stat-card label="Tab Aktif" value="{{ \Illuminate\Support\Str::headline($activeTab) }}" variant="success">
                <x-slot:icon><x-ui.icon name="menu" class="fs-2" /></x-slot:icon>
            </x-ui.stat-card>
        </div>
    </div>

    @if (session('status'))
        <div class="alert alert-success d-flex align-items-center gap-3">
            <x-ui.icon name="check-circle" class="fs-2" />
            <span class="fw-semibold">{{ session('status') }}</span>
        </div>
    @endif

    <x-ui.card flush>
        <div class="px-6 pt-5">
            <x-ui.tabs>
                <x-ui.tab wire:click="setTab('profil')" :active="$activeTab === 'profil'">Profil</x-ui.tab>
                <x-ui.tab wire:click="setTab('ipm')" :active="$activeTab === 'ipm'">IPM</x-ui.tab>
                <x-ui.tab wire:click="setTab('sdm')" :active="$activeTab === 'sdm'">SDM</x-ui.tab>
                <x-ui.tab wire:click="setTab('edpm_pesantren')" :active="$activeTab === 'edpm_pesantren'">EDPM</x-ui.tab>
                @if($akreditasi->status != 5)
                    <x-ui.tab wire:click="setTab('instrumen')" :active="$activeTab === 'instrumen'">NA</x-ui.tab>
                @endif
                @if($akreditasi->status == 3 || $akreditasi->status == 1 || $akreditasi->status == 2)
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
                                        <x-ui.stat-card label="Total Luas Tanah" value="{{ $pesantren->luas_tanah ?? '-' }} m2" variant="success">
                                            <x-slot:icon><x-ui.icon name="geolocation" class="fs-2" /></x-slot:icon>
                                        </x-ui.stat-card>
                                        <x-ui.stat-card label="Total Luas Bangunan" value="{{ $pesantren->luas_bangunan ?? '-' }} m2" variant="info">
                                            <x-slot:icon><x-ui.icon name="category" class="fs-2" /></x-slot:icon>
                                        </x-ui.stat-card>
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
                    <x-ui.section-card title="EDPM Pesantren" subtitle="Isian evaluasi diri dan tautan bukti pesantren.">
                        <div class="p-6">
                            <x-ui.simple-table tableClass="spm-edpm-review-table">
                                <thead>
                                    <tr>
                                        <th class="ps-4 w-100px">No Butir</th>
                                        <th>Pernyataan</th>
                                        <th class="text-center w-125px">Isian</th>
                                        <th class="text-center pe-4 w-150px">Bukti</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($komponens as $komponen)
                                        @foreach ($komponen->butirs as $butir)
                                            <tr>
                                                <td class="ps-4 fw-bold text-primary">{{ $butir->nomor_butir }}</td>
                                                <td class="spm-edpm-statement">{{ $butir->butir_pernyataan }}</td>
                                                <td class="text-center"><x-ui.badge variant="warning">{{ $pesantrenEvaluasis[$butir->id] ?? '-' }}</x-ui.badge></td>
                                                <td class="text-center pe-4">
                                                    @if(!empty($pesantrenLinks[$butir->id]))
                                                        <x-ui.button :href="$pesantrenLinks[$butir->id]" target="_blank" variant="light" size="sm">Bukti</x-ui.button>
                                                    @else
                                                        <x-ui.status-badge variant="secondary">-</x-ui.status-badge>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    @endforeach
                                </tbody>
                            </x-ui.simple-table>
                        </div>
                    </x-ui.section-card>

                    <x-ui.section-card title="Catatan Kinerja Satuan Pendidikan" subtitle="Catatan pesantren per komponen.">
                        <div class="p-6">
                            <div class="row g-5">
                                @foreach ($komponens as $komponen)
                                    <div class="col-lg-6">
                                        <div class="spm-soft-panel h-100">
                                            <div class="spm-detail-label">{{ $komponen->nama }}</div>
                                            <div class="spm-detail-value spm-detail-value-muted">{{ $pesantrenCatatans[$komponen->id] ?: 'Tidak ada catatan.' }}</div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </x-ui.section-card>
                </div>
            @endif

            @if ($activeTab === 'instrumen')
                <div class="d-flex flex-column gap-6">
                    @if ($akreditasi->status == 4)
                        <div class="spm-inline-alert">
                            <x-ui.icon name="timer" class="fs-2 text-warning" />
                            <div>
                                <div class="spm-inline-alert-title">Data Sedang Diverifikasi</div>
                                <div class="spm-inline-alert-text">
                                    Penilaian sedang dalam proses verifikasi oleh admin. Nilai NK dapat diisi ketua setelah NA1 dan NA2 lengkap.
                                </div>
                            </div>
                        </div>
                    @endif

                    <form wire:submit="saveAsesorEdpm" class="d-flex flex-column gap-6">
                        <x-ui.section-card title="Penilaian Asesor" subtitle="Isi NA, NK, dan catatan butir sesuai peran asesor.">
                            <div class="p-6">
                                <x-ui.simple-table tableClass="spm-score-table">
                                    <thead>
                                        <tr>
                                            <th class="ps-4 w-150px">Komponen</th>
                                            <th class="text-center w-80px">No SK</th>
                                            <th class="text-center w-90px">No Butir</th>
                                            <th>Butir Pernyataan</th>
                                            @if ($this->asesorTipe == 1)
                                                <th class="text-center w-110px">NA 1</th>
                                                <th class="text-center w-90px">NA 2</th>
                                                <th class="text-center w-110px">NK</th>
                                                <th class="pe-4 w-240px">Catatan Butir</th>
                                            @else
                                                <th class="text-center pe-4 w-110px">NA</th>
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
                                                        @if ($akreditasi->status == 3)
                                                            <x-ui.badge variant="primary">{{ $asesorEvaluasis[$butir->id] ?: '-' }}</x-ui.badge>
                                                        @else
                                                            <x-ui.select
                                                                model="asesorEvaluasis.{{ $butir->id }}"
                                                                modifier="live"
                                                                :options="['1' => '1', '2' => '2', '3' => '3', '4' => '4']"
                                                                placeholder="Pilih"
                                                                size="sm"
                                                                class="spm-score-control mx-auto"
                                                                :disabled="$akreditasi->status == 4 && ($asesorTipe == 2 || !$isLocked) ? false : true"
                                                            />
                                                        @endif
                                                        @error('asesorEvaluasis.' . $butir->id)
                                                            <div class="invalid-feedback d-block fs-9">{{ $message }}</div>
                                                        @enderror
                                                    </td>

                                                    @if ($this->asesorTipe == 1)
                                                        <td class="text-center fw-bold text-success">{{ $otherAsesorEvaluasis[$butir->id] ?? '' }}</td>
                                                        <td class="text-center">
                                                            @if ($akreditasi->status == 3)
                                                                <x-ui.badge variant="warning">{{ $asesorNks[$butir->id] ?: '-' }}</x-ui.badge>
                                                            @else
                                                                <x-ui.select
                                                                    model="asesorNks.{{ $butir->id }}"
                                                                    modifier="live"
                                                                    :options="['1' => '1', '2' => '2', '3' => '3', '4' => '4']"
                                                                    placeholder="Pilih"
                                                                    size="sm"
                                                                    class="spm-score-control mx-auto"
                                                                    :disabled="$akreditasi->status == 4 && !empty($asesorEvaluasis[$butir->id]) && !empty($otherAsesorEvaluasis[$butir->id]) ? false : true"
                                                                />
                                                            @endif
                                                            @error('asesorNks.' . $butir->id)
                                                                <div class="invalid-feedback d-block fs-9">{{ $message }}</div>
                                                            @enderror
                                                        </td>
                                                        <td class="pe-4">
                                                            @if ($akreditasi->status == 3)
                                                                <div class="fs-8 text-muted">{{ $asesorButirCatatans[$butir->id] ?: '-' }}</div>
                                                            @else
                                                                <x-ui.textarea
                                                                    model="asesorButirCatatans.{{ $butir->id }}"
                                                                    modifier="live"
                                                                    rows="2"
                                                                    class="fs-8"
                                                                    placeholder="Catatan butir..."
                                                                    :disabled="$akreditasi->status == 4 ? false : true"
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
                            <x-ui.section-card title="Catatan Rekomendasi Komponen" subtitle="Ringkasan rekomendasi NK per komponen.">
                                <div class="p-6">
                                    <div class="row g-5">
                                        @foreach ($komponens as $komponen)
                                            <div class="col-lg-6">
                                                <div class="spm-soft-panel h-100">
                                                    <div class="spm-detail-label">{{ $komponen->nama }}</div>
                                                    @if ($akreditasi->status == 3)
                                                        <div class="spm-detail-value spm-detail-value-muted">{!! $asesorCatatans[$komponen->id] ?: '<span class="text-muted">Tidak ada catatan.</span>' !!}</div>
                                                    @else
                                                        <x-quill-editor
                                                            wire:model.live="asesorCatatans.{{ $komponen->id }}"
                                                            placeholder="Masukkan catatan rekomendasi {{ $komponen->nama }}..."
                                                            :disabled="$akreditasi->status != 4"
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

                    <div class="spm-action-panel d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-4">
                        <div>
                            <h3 class="spm-card-title mb-1">Evaluasi Asesor</h3>
                            <div class="text-muted fw-semibold fs-7">Pastikan semua data sudah lengkap sebelum verifikasi final.</div>
                        </div>
                        @if ($akreditasi->status == 4)
                            <div class="d-flex flex-wrap justify-content-end gap-2">
                                @if(!$isLocked || $asesorTipe == 2)
                                    <x-ui.button type="button" @click="confirmSaveDraft($wire)" wire:loading.attr="disabled" variant="light">
                                        Simpan Draft
                                    </x-ui.button>
                                @endif

                                @if($asesorTipe == 1)
                                    <x-ui.button type="button" @click="confirmVerification($wire)" wire:loading.attr="disabled" variant="primary">
                                        Selesaikan & Verifikasi
                                    </x-ui.button>
                                @else
                                    <x-ui.button type="button" @click="confirmAsesor2Final($wire)" wire:loading.attr="disabled" variant="primary">
                                        Selesaikan Final
                                    </x-ui.button>
                                @endif
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
                </div>
            @endif

            @if ($activeTab === 'laporan_visitasi')
                <x-ui.section-card title="Laporan Visitasi" subtitle="Unggah laporan visitasi sesuai peran asesor.">
                    <div class="p-6">
                        <div class="row g-5">
                            <div class="col-lg-4">
                                <div class="spm-soft-panel h-100">
                                    <div class="spm-detail-label">Langkah 1</div>
                                    <div class="spm-detail-value">Unduh template laporan visitasi dari menu dokumen.</div>
                                    <x-ui.button :href="route('documents.index', ['doc' => 'visitasi'])" variant="light" size="sm" class="mt-4">Buka Dokumen</x-ui.button>
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <div class="spm-soft-panel h-100">
                                    <div class="spm-detail-label">Langkah 2</div>
                                    <div class="spm-detail-value">Tinjau data penilaian, rekomendasi, dan tanda tangan sebelum unggah.</div>
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <div class="spm-soft-panel h-100">
                                    @php
                                        $myFile = $asesorTipe == 1 ? $akreditasi->laporan_visitasi_file : $akreditasi->laporan_visitasi_file_2;
                                        $otherFile = $asesorTipe == 1 ? $akreditasi->laporan_visitasi_file_2 : $akreditasi->laporan_visitasi_file;
                                        $otherLabel = $asesorTipe == 1 ? 'Anggota (Asesor 2)' : 'Ketua (Asesor 1)';
                                    @endphp

                                    @if($myFile && !$errors->has('laporan_visitasi_file'))
                                        <x-ui.document-item label="Laporan Visitasi Saya" :href="Storage::url($myFile)" />
                                    @else
                                        <x-ui.form-field label="Upload Laporan Visitasi" for="laporan_visitasi_file" :error="$errors->get('laporan_visitasi_file')">
                                            <x-ui.file-upload
                                                model="laporan_visitasi_file"
                                                id="laporan_visitasi_file"
                                                :file="$laporan_visitasi_file"
                                                placeholder="Pilih file laporan"
                                            />
                                        </x-ui.form-field>

                                        @if($laporan_visitasi_file)
                                            <x-ui.button type="button" @click="confirmUploadLaporan($wire)" wire:loading.attr="disabled" class="w-100 justify-content-center">
                                                <span wire:loading.remove wire:target="uploadLaporanVisitasi">Simpan Laporan</span>
                                                <span wire:loading wire:target="uploadLaporanVisitasi">Mengunggah...</span>
                                            </x-ui.button>
                                        @endif
                                    @endif

                                    <div class="border-top border-gray-200 mt-5 pt-5">
                                        <div class="spm-detail-label">Status Laporan {{ $otherLabel }}</div>
                                        <x-ui.status-badge :variant="$otherFile ? 'success' : 'secondary'">
                                            {{ $otherFile ? 'Sudah Diunggah' : 'Belum Diunggah' }}
                                        </x-ui.status-badge>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </x-ui.section-card>
            @endif
        </div>
    </x-ui.card>
</x-ui.page>
