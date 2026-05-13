<?php

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;

new #[Layout('layouts.app')] class extends Component {
    public $akreditasi;
    public $pesantren;
    public $ipm;
    public $sdm;
    public $komponens;
    public $levels = [];
    public $fields = [
        'santri_l',
        'santri_p',
        'ustadz_dirosah_l',
        'ustadz_dirosah_p',
        'ustadz_non_dirosah_l',
        'ustadz_non_dirosah_p',
        'pamong_l',
        'pamong_p',
        'musyrif_l',
        'musyrif_p',
        'tendik_l',
        'tendik_p',
    ];

    public $pesantrenEvaluasis = [];
    public $pesantrenCatatans = [];
    public $pesantrenLinks = [];

    public $asesor1Evaluasis = [];
    public $asesor2Evaluasis = [];
    public $asesor1Nks = [];
    public $adminNvs = [];
    public $asesorButirCatatans = [];
    public $asesorCatatans = [];

    #[Url]
    public $activeTab = 'profil';
    public $kartu_kendali_file;
    public $visitasiTemplate;

    use WithFileUploads;

    public function mount($uuid)
    {
        $pesantrenService = app(\App\Services\PesantrenService::class);
        $data = $pesantrenService->getAkreditasiDetail($uuid, Auth::id());

        $this->akreditasi = $data['akreditasi'];
        $this->pesantren = $data['pesantren'];
        $this->ipm = $data['ipm'];
        $this->sdm = $data['sdm'];
        $this->komponens = $data['komponens'];
        $this->visitasiTemplate = $data['visitasiTemplate'];

        if ($this->pesantren && $this->pesantren->relationLoaded('units')) {
            $this->levels = $this->pesantren->units->pluck('unit')->toArray();
        }

        // Pesantren EDPM
        $this->pesantrenEvaluasis = $data['pesantren_edpm']['evaluasis'];
        $this->pesantrenLinks = $data['pesantren_edpm']['links'];
        $this->pesantrenCatatans = $data['pesantren_edpm']['catatans']->toArray();

        // Assessor 1
        if (!empty($data['asesor1'])) {
            $this->asesor1Evaluasis = $data['asesor1']['evaluasis'];
            $this->asesor1Nks = $data['asesor1']['nks'];
            $this->adminNvs = $data['asesor1']['nvs'];
            $this->asesorButirCatatans = $data['asesor1']['butir_catatans'];
            $this->asesorCatatans = $data['asesor1']['catatans'];
        }

        // Assessor 2
        if (!empty($data['asesor2'])) {
            $this->asesor2Evaluasis = $data['asesor2']['evaluasis'];
        }

        // Ensure all components have entries in catatans
        foreach ($this->komponens as $komponen) {
            if (!isset($this->pesantrenCatatans[$komponen->id])) {
                $this->pesantrenCatatans[$komponen->id] = '';
            }
        }
    }

    public function setTab($tab)
    {
        $this->activeTab = $tab;
    }

    public function getTotal($field)
    {
        $total = 0;
        foreach ($this->levels as $level) {
            $total += (int)($this->sdm[$level]->$field ?? 0);
        }
        return $total;
    }

    public function uploadKartuKendali()
    {
        $pesantrenService = app(\App\Services\PesantrenService::class);
        
        if ($this->akreditasi->status != 3) {
            return;
        }

        $this->validate([
            'kartu_kendali_file' => 'required|file|mimes:pdf,docx|max:5120',
        ], [
            'kartu_kendali_file.required' => 'File Kartu Kendali wajib diunggah.',
            'kartu_kendali_file.mimes' => 'Format file harus PDF atau DOCX.',
            'kartu_kendali_file.max' => 'Ukuran file maksimal 5MB.',
        ]);

        $path = $this->kartu_kendali_file->store('akreditasi/kartu_kendali', 'public');

        $pesantrenService->uploadKartuKendali($this->akreditasi->id, $path);

        $this->reset(['kartu_kendali_file']);

        $this->dispatch(
            'notification-received',
            type: 'success',
            title: 'Berhasil!',
            message: 'Kartu Kendali berhasil diunggah.'
        );
    }
}; ?>

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
    title="Detail Pengajuan Akreditasi"
    subtitle="{{ $pesantren?->nama_pesantren ?? 'Pesantren' }}"
    x-data="akreditasiPesantren()"
>
    <x-slot:toolbar>
        <x-ui.status-badge :variant="$statusVariant">
            {{ \App\Models\Akreditasi::getStatusLabel($akreditasi->status) }}
        </x-ui.status-badge>

        <x-ui.button :href="route('pesantren.akreditasi')" variant="light">
            <x-ui.icon name="exit-right" class="fs-4 me-1" />
            Kembali
        </x-ui.button>
    </x-slot:toolbar>

    <div class="row g-5 mb-6">
        <div class="col-lg-4">
            <x-ui.stat-card label="Status Pengajuan" value="{{ \App\Models\Akreditasi::getStatusLabel($akreditasi->status) }}" variant="{{ $statusVariant }}">
                <x-slot:icon><x-ui.icon name="shield-tick" class="fs-2" /></x-slot:icon>
            </x-ui.stat-card>
        </div>

        <div class="col-lg-4">
            <x-ui.stat-card label="Kelengkapan Data" value="{{ ($ipm ? 1 : 0) + ($sdm ? 1 : 0) + (filled($pesantrenEvaluasis) ? 1 : 0) }} Bagian" variant="info">
                <x-slot:icon><x-ui.icon name="document" class="fs-2" /></x-slot:icon>
            </x-ui.stat-card>
        </div>

        <div class="col-lg-4">
            <x-ui.stat-card label="Jadwal Visitasi" value="{{ $akreditasi->tgl_visitasi ? \Carbon\Carbon::parse($akreditasi->tgl_visitasi)->format('d M Y') : 'Menunggu Penjadwalan' }}" variant="success">
                <x-slot:icon><x-ui.icon name="calendar" class="fs-2" /></x-slot:icon>
            </x-ui.stat-card>
        </div>
    </div>

    <x-ui.card flush>
        <div class="px-6 pt-5">
            <x-ui.tabs>
                <x-ui.tab wire:click="setTab('profil')" :active="$activeTab === 'profil'">Profil</x-ui.tab>
                <x-ui.tab wire:click="setTab('ipm')" :active="$activeTab === 'ipm'">IPM</x-ui.tab>
                <x-ui.tab wire:click="setTab('sdm')" :active="$activeTab === 'sdm'">SDM</x-ui.tab>
                <x-ui.tab wire:click="setTab('edpm')" :active="$activeTab === 'edpm'">EDPM</x-ui.tab>
                @if($akreditasi->status == 1 || $akreditasi->status == 2)
                    <x-ui.tab wire:click="setTab('hasil')" :active="$activeTab === 'hasil'">Hasil Penilaian</x-ui.tab>
                @endif
                @if($akreditasi->status == 3)
                    <x-ui.tab wire:click="setTab('kartu')" :active="$activeTab === 'kartu'">Kartu Kendali</x-ui.tab>
                @endif
            </x-ui.tabs>
        </div>

        <div class="p-6">
            @if ($activeTab === 'profil')
                <div class="d-flex flex-column gap-6">
                    <x-ui.section-card title="Profil Pesantren" subtitle="Identitas pesantren pada pengajuan akreditasi.">
                        <div class="p-6">
                            <div class="row g-5">
                                <x-ui.detail-item label="Nama Pesantren" value="{{ $pesantren->nama_pesantren ?? '-' }}" />
                                <x-ui.detail-item label="NSP" value="{{ $pesantren->ns_pesantren ?? '-' }}" />
                                <x-ui.detail-item label="Alamat" span="2">
                                    <div class="spm-detail-block spm-detail-value-muted">{{ $pesantren->alamat ?? '-' }}</div>
                                </x-ui.detail-item>
                                <x-ui.detail-item label="Kota/Kabupaten" value="{{ $pesantren->kota_kabupaten ?? '-' }}" />
                                <x-ui.detail-item label="Provinsi" value="{{ $pesantren->provinsi ?? '-' }}" />

                                @if($akreditasi->tgl_visitasi)
                                    <x-ui.detail-item label="Jadwal Visitasi" span="2">
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
                                                        <td class="text-end pe-4">
                                                            <x-ui.badge variant="success">{{ $unit->jumlah_rombel }} Rombel</x-ui.badge>
                                                        </td>
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

                    <x-ui.section-card title="Dokumen Pengajuan" subtitle="Status dokumen pendukung pengajuan.">
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
                <x-ui.section-card title="Indikator Pemenuhan Mutlak" subtitle="Dokumen IPM yang sudah dikirim.">
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
                                @foreach ($levels as $index => $level)
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

            @if ($activeTab === 'edpm')
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
                                                <td class="text-center">
                                                    <x-ui.badge variant="warning">{{ $pesantrenEvaluasis[$butir->id] ?? '-' }}</x-ui.badge>
                                                </td>
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
                                            <div class="spm-detail-value spm-detail-value-muted">
                                                {{ $pesantrenCatatans[$komponen->id] ?: 'Tidak ada catatan.' }}
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </x-ui.section-card>
                </div>
            @endif

            @if ($activeTab === 'hasil')
                <div class="d-flex flex-column gap-6">
                    @if($akreditasi->status == 1)
                        <x-ui.section-card title="Hasil Akreditasi Akhir" subtitle="Nilai, peringkat, SK, dan masa berlaku.">
                            <div class="p-6">
                                <div class="row g-5">
                                    <div class="col-md-6"><div class="spm-result-metric"><div class="spm-detail-label">Nilai Akhir</div><div class="fs-2 fw-bold text-success">{{ $akreditasi->nilai }}</div></div></div>
                                    <div class="col-md-6"><div class="spm-result-metric"><div class="spm-detail-label">Peringkat</div><div class="fs-2 fw-bold text-success">{{ $akreditasi->peringkat }}</div></div></div>
                                    <x-ui.detail-item label="Nomor SK" value="{{ $akreditasi->nomor_sk }}" />
                                    <x-ui.detail-item label="Masa Berlaku">
                                        {{ \Carbon\Carbon::parse($akreditasi->masa_berlaku)->format('d F Y') }}
                                        @if($akreditasi->masa_berlaku_akhir && $akreditasi->masa_berlaku != $akreditasi->masa_berlaku_akhir)
                                            - {{ \Carbon\Carbon::parse($akreditasi->masa_berlaku_akhir)->format('d F Y') }}
                                        @endif
                                    </x-ui.detail-item>
                                    @if($akreditasi->sertifikat_path)
                                        <div class="col-12">
                                            <x-ui.button :href="Storage::url($akreditasi->sertifikat_path)" target="_blank" variant="success">Unduh Sertifikat</x-ui.button>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </x-ui.section-card>
                    @elseif($akreditasi->status == 2)
                        <div class="spm-inline-alert">
                            <x-ui.icon name="cross-circle" class="fs-2 text-danger" />
                            <div>
                                <div class="spm-inline-alert-title">Pengajuan Ditolak</div>
                                <div class="spm-inline-alert-text">Catatan: {{ $akreditasi->catatan }}</div>
                            </div>
                        </div>
                    @endif

                    <x-ui.section-card title="Data Penilaian" subtitle="Catatan rekomendasi asesor per komponen.">
                        <div class="p-6">
                            <x-ui.simple-table>
                                <thead>
                                    <tr>
                                        <th class="ps-4">Komponen</th>
                                        <th class="pe-4">Catatan Rekomendasi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($komponens as $komponen)
                                        <tr>
                                            <td class="ps-4 text-uppercase fw-bold">{{ $komponen->nama }}</td>
                                            <td class="pe-4">{!! $asesorCatatans[$komponen->id] ?? '-' !!}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </x-ui.simple-table>
                        </div>
                    </x-ui.section-card>
                </div>
            @endif

            @if ($activeTab === 'kartu')
                <x-ui.section-card title="Kartu Kendali" subtitle="Unduh, tinjau, lalu unggah kembali kartu kendali final.">
                    <div class="p-6">
                        <div class="row g-5">
                            <div class="col-lg-4">
                                <div class="spm-soft-panel h-100">
                                    <div class="spm-detail-label">Langkah 1</div>
                                    <div class="spm-detail-value">Unduh berkas kartu kendali dari menu dokumen.</div>
                                    <x-ui.button :href="route('documents.index', ['doc' => 'all'])" variant="light" size="sm" class="mt-4">Buka Dokumen</x-ui.button>
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <div class="spm-soft-panel h-100">
                                    <div class="spm-detail-label">Langkah 2</div>
                                    <div class="spm-detail-value">Tinjau kelengkapan data dan tanda tangan hasil visitasi.</div>
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <div class="spm-soft-panel h-100">
                                    <div class="spm-detail-label">Langkah 3</div>
                                    @if($akreditasi->status == 3 && $akreditasi->kartu_kendali && !$errors->has('kartu_kendali_file'))
                                        <x-ui.document-item label="Kartu Kendali" :href="Storage::url($akreditasi->kartu_kendali)" />
                                    @elseif($akreditasi->status == 3)
                                        <x-ui.form-field label="Unggah Kartu Kendali" for="kartu_kendali_file" :error="$errors->get('kartu_kendali_file')">
                                            <x-ui.file-upload
                                                model="kartu_kendali_file"
                                                id="kartu_kendali_file"
                                                accept=".pdf,.docx"
                                                :file="$kartu_kendali_file"
                                                placeholder="Pilih file kartu kendali"
                                                hint="PDF/DOCX maksimal 5MB"
                                            />
                                        </x-ui.form-field>

                                        @if($kartu_kendali_file)
                                            <x-ui.button type="button" @click="confirmUploadKartu($wire)" wire:loading.attr="disabled" class="w-100 justify-content-center">
                                                <span wire:loading.remove wire:target="uploadKartuKendali">Simpan Kartu Kendali</span>
                                                <span wire:loading wire:target="uploadKartuKendali">Mengunggah...</span>
                                            </x-ui.button>
                                        @endif
                                    @else
                                        <div class="text-muted fw-semibold fs-7">Menu unggah muncul saat status pengajuan Validasi.</div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </x-ui.section-card>
            @endif
        </div>
    </x-ui.card>
</x-ui.page>
