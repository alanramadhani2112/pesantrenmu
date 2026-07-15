@extends('layouts.app')

@section('content')
@php
    $tabs = [
        'profil' => 'Profil',
        'ipm' => 'IPM',
        'sdm' => 'SDM',
        'edpm' => 'EDPM',
        'asesor' => 'Asesor',
        'hasil' => 'Hasil',
        'banding' => 'Banding',
    ];
    $statusLabel = \App\Models\Akreditasi::getStatusLabel($akreditasi->status);
    $statusVariant = match ((int) $akreditasi->status) {
        \App\Models\Akreditasi::STATUS_SELESAI => 'success',
        \App\Models\Akreditasi::STATUS_DITOLAK => 'danger',
        \App\Models\Akreditasi::STATUS_BANDING => 'warning',
        \App\Models\Akreditasi::STATUS_PENGAJUAN => 'primary',
        default => 'info',
    };
    $asesorAssignments = $akreditasi->assessments
        ->sortBy('tipe')
        ->values();
@endphp

<x-ui.page
    title="Detail Akreditasi"
    subtitle="Pantau detail pengajuan akreditasi pesantren Anda."
    data-module-page="pesantren-akreditasi-detail"
>
    <x-slot:toolbar>
        <x-ui.button :href="route('pesantren.akreditasi')" variant="light">
            <x-ui.icon name="arrow-left" class="fs-4 me-1" />
            Kembali
        </x-ui.button>
    </x-slot:toolbar>

    @if(session('success'))
        <x-ui.alert variant="success" title="Berhasil" class="mb-4">{{ session('success') }}</x-ui.alert>
    @endif
    @if(session('error'))
        <x-ui.alert variant="danger" title="Gagal" class="mb-4">{{ session('error') }}</x-ui.alert>
    @endif

    
    @php $activeRejection = $rejectionStatus['active'] ?? null; @endphp
    @if($activeRejection && $activeRejection->status === 'pending')
        <div class="alert alert-dismissible bg-body border border-dashed border-gray-300 d-flex flex-wrap align-items-center justify-content-between gap-3 p-4 mb-6">
            <div class="d-flex align-items-center gap-3">
                <x-ui.icon name="information-5" class="fs-2 text-warning" />
                <div>
                    <div class="fw-semibold text-gray-900">Perbaikan Diperlukan</div>
                    <div class="fs-7 text-muted">
                        Batas perbaikan: {{ $activeRejection->perbaikan_deadline?->format('d M Y') ?? 'Segera' }}.
                        Silakan perbaiki bagian yang ditolak, lalu klik <strong>Kirim Perbaikan</strong>.
                    </div>
                </div>
            </div>
            <form method="POST" action="{{ route('pesantren.akreditasi.submit-perbaikan') }}" data-swal-confirm="true" data-swal-title="Kirim perbaikan?" data-swal-text="Pastikan semua bagian yang ditolak sudah diperbaiki." data-swal-icon="question" data-swal-confirm-button="Ya, kirim">
                @csrf
                <input type="hidden" name="akreditasi_id" value="{{ $akreditasi->id }}">
                <x-ui.button type="submit" variant="warning">Kirim Perbaikan</x-ui.button>
            </form>
        </div>
    @endif
    <div class="spm-detail-hero card mb-5">
        <div class="card-body p-5 p-lg-6 d-flex flex-column flex-lg-row justify-content-between gap-4">
            <div class="min-w-0">
                <div class="d-flex align-items-center flex-wrap gap-2 mb-3">
                    <x-ui.badge :variant="$statusVariant">{{ $statusLabel }}</x-ui.badge>
                    <x-ui.status-badge variant="secondary">ID #{{ $akreditasi->id }}</x-ui.status-badge>
                    <x-ui.status-badge variant="secondary">Periode {{ $akreditasi->created_at?->format('Y') ?? '-' }}</x-ui.status-badge>
                </div>
                <h2 class="text-gray-900 fw-semibold mb-2">{{ $profil->nama_pesantren ?? 'Detail Akreditasi' }}</h2>
                <div class="text-muted fw-semibold mw-700px">Pantau profil, dokumen, EDPM/IPR, asesor, dan hasil dalam satu alur pengajuan.</div>
            </div>
            <div class="spm-detail-hero-meta">
                <div class="spm-detail-hero-label">Tanggal Pengajuan</div>
                <div class="spm-detail-hero-value">{{ $akreditasi->created_at?->format('d M Y') ?? '-' }}</div>
            </div>
        </div>
    </div>
    {{-- Info Bar --}}
    <div class="row g-4 mb-5 spm-detail-summary-grid">
        <div class="col-lg-4">
            <x-ui.stat-card label="Periode" value="{{ $akreditasi->created_at?->format('Y') ?? '-' }}" variant="primary" icon="calendar" />
        </div>
        <div class="col-lg-4">
            <x-ui.stat-card label="Status" value="{{ $statusLabel }}" variant="info" icon="information-3" />
        </div>
        <div class="col-lg-4">
            <x-ui.stat-card label="Tanggal Pengajuan" value="{{ $akreditasi->created_at?->format('d M Y') ?? '-' }}" variant="success" icon="calendar" />
        </div>
    </div>

    <x-akreditasi.workflow-stepper
        :status="$akreditasi->status"
        title="Tahapan Akreditasi LP2M"
        subtitle="Pantau posisi pengajuan dari review awal, review asesor, visitasi, penilaian pasca visitasi, validasi admin, sampai hasil akhir."
        class="mb-5"
    />

    @if((int) $akreditasi->status === \App\Models\Akreditasi::STATUS_PASCA_VISITASI || !empty($akreditasi->kartu_kendali))
    {{-- Kartu Kendali Upload --}}
    <x-ui.section-card title="Kartu Kendali" subtitle="Unggah kartu kendali yang telah ditandatangani" class="mb-5">
        <div class="p-5">
            @if(!empty($akreditasi->kartu_kendali))
                <div class="d-flex align-items-center gap-3 mb-4">
                    <x-ui.icon name="document" class="fs-2 text-success" />
                    <div>
                        <div class="fw-semibold text-success">Dokumen Terunggah</div>
                        <a data-ui-document-item="metronic" href="{{ Storage::url($akreditasi->kartu_kendali) }}" target="_blank" class="text-muted fs-8">Lihat Dokumen</a>
                    </div>
                </div>
            @endif

            @if((int) $akreditasi->status === \App\Models\Akreditasi::STATUS_PASCA_VISITASI)
                <form action="{{ route('pesantren.akreditasi.upload-kartu-kendali') }}" method="POST" enctype="multipart/form-data" id="kartuKendaliForm">
                    @csrf
                    <input type="hidden" name="akreditasi_id" value="{{ $akreditasi->id }}">
                    <div class="row align-items-end">
                        <div class="col-md-8">
                            <x-ui.form-field label="{{ !empty($akreditasi->kartu_kendali) ? 'Ganti File' : 'Unggah File' }}">
                                <x-ui.input type="file" name="kartu_kendali_file" class="form-control-sm" :invalid="$errors->has('kartu_kendali_file')" accept="application/pdf,image/png,image/jpeg" />
                                @error('kartu_kendali_file')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="text-muted fs-8 mt-1">PDF/Gambar (Maks. 2MB)</div>
                            </x-ui.form-field>
                        </div>
                        <div class="col-md-4">
                            <x-ui.button type="submit" variant="primary" size="sm" class="w-100 mb-3" id="btnUploadKartuKendali">
                                <x-ui.icon name="file-up" class="fs-4 me-1" /> Upload
                            </x-ui.button>
                        </div>
                    </div>
                </form>
            @endif
        </div>
    </x-ui.section-card>

    @endif

    {{-- Tabs --}}
    <div class="spm-detail-tabs-shell spm-tab-spacing" aria-label="Navigasi detail akreditasi">
    <x-ui.tabs class="mb-5">
        @foreach($tabs as $key => $label)
            @if($key !== 'banding' || !empty($akreditasi->banding))
                <x-ui.tab :href="request()->fullUrlWithQuery(['tab' => $key])" :active="$activeTab === $key">
                    {{ $label }}
                </x-ui.tab>
            @endif
        @endforeach
    </x-ui.tabs>
    </div>

    {{-- Tab Content --}}
    <div class="spm-detail-tab-content spm-detail-alignment">
    @if($activeTab === 'profil')
        {{-- Profil Tab --}}
        <x-ui.section-card title="Identitas Pesantren" subtitle="Ringkasan data utama yang menjadi dasar pengajuan." class="mb-5 spm-detail-panel">
            <div class="p-5">
                <div class="row g-4 spm-detail-grid">
                    <x-ui.detail-item label="Nama Pesantren" :value="$profil->nama_pesantren ?? '-'" />
                    <x-ui.detail-item label="NSP" :value="$profil->ns_pesantren ?? '-'" />
                    <x-ui.detail-item label="Alamat" span="2" :value="$profil->alamat ?? '-'" />
                    <x-ui.detail-item label="Provinsi" :value="$profil->provinsi ?? '-'" />
                    <x-ui.detail-item label="Kota/Kabupaten" :value="$profil->kota_kabupaten ?? '-'" />
                    <x-ui.detail-item label="Tahun Pendirian" :value="$profil->tahun_pendirian ?? '-'" />
                    <x-ui.detail-item label="Nama Mudir" :value="$profil->nama_mudir ?? '-'" />
                    <x-ui.detail-item label="Pendidikan Mudir" :value="$profil->jenjang_pendidikan_mudir ?? '-'" />
                    <x-ui.detail-item label="Persyarikatan" :value="$profil->persyarikatan ?? '-'" />
                    <x-ui.detail-item label="Telepon" :value="$profil->telp_pesantren ?? '-'" />
                    <x-ui.detail-item label="HP/WA" :value="$profil->hp_wa ?? '-'" />
                    <x-ui.detail-item label="Email" :value="$profil->email_pesantren ?? '-'" />
                </div>
            </div>
        </x-ui.section-card>

        <div class="row g-5 mb-5">
            <div class="col-lg-5">
                <x-ui.section-card title="Layanan Satuan Pendidikan" subtitle="Unit pendidikan yang tercatat." class="h-100 spm-detail-panel">
                    <div class="p-5">
                        @if(!empty($profil->units) && count($profil->units) > 0)
                            <x-ui.simple-table table-class="table-row-gray-200">
                                <thead>
                                    <tr class="fw-semibold text-muted">
                                        <th>Unit</th>
                                        <th class="text-end">Rombel</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($profil->units as $unit)
                                        <tr>
                                            <td class="fw-semibold text-gray-900">{{ $unit->unit }}</td>
                                            <td class="text-end">
                                                <x-ui.status-badge variant="primary">{{ $unit->jumlah_rombel }} rombel</x-ui.status-badge>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </x-ui.simple-table>
                        @else
                            <x-ui.empty-state title="Belum ada layanan" description="Layanan satuan pendidikan belum dipilih pada profil pesantren." variant="secondary" class="py-6" />
                        @endif
                    </div>
                </x-ui.section-card>
            </div>
            <div class="col-lg-7">
                <x-ui.section-card title="Visi & Misi" subtitle="Arah kelembagaan pesantren." class="h-100 spm-detail-panel">
                    <div class="p-5 d-flex flex-column gap-4">
                        <div class="spm-detail-text-block">
                            <div class="spm-detail-label mb-2">Visi</div>
                            <div class="spm-detail-value">{{ filled($profil->visi ?? null) ? $profil->visi : '-' }}</div>
                        </div>
                        <div class="spm-detail-text-block">
                            <div class="spm-detail-label mb-2">Misi</div>
                            <div class="spm-detail-value">{{ filled($profil->misi ?? null) ? $profil->misi : '-' }}</div>
                        </div>
                    </div>
                </x-ui.section-card>
            </div>
        </div>

        <x-ui.section-card title="Dokumen Profil" subtitle="Dokumen pendukung utama pengajuan." class="mb-5 spm-detail-panel">
            <div class="p-5">
                @php
                    $mainDocs = [
                        'status_kepemilikan_tanah' => 'Status Kepemilikan Tanah',
                        'sertifikat_nsp' => 'Sertifikat NSP',
                        'rk_anggaran' => 'Rencana Kerja Anggaran',
                        'silabus_rpp' => 'Silabus dan RPP',
                        'peraturan_kepegawaian' => 'Peraturan Kepegawaian',
                        'file_lk_iapm' => 'File LK IAPM',
                        'laporan_tahunan' => 'Laporan Tahunan',
                    ];
                @endphp
                <div class="spm-document-grid">
                    @foreach($mainDocs as $field => $label)
                        <x-ui.document-item :label="$label" :href="!empty($profil->$field) ? Storage::url($profil->$field) : null" />
                    @endforeach
                </div>
            </div>
        </x-ui.section-card>

    @elseif($activeTab === 'ipm')
        {{-- IPM Tab --}}
        @php
            $ipmCriteria = [
                'nsp_file' => 'NSP yang masih berlaku',
                'lulus_santri_file' => 'Santri lulus minimal satu angkatan',
                'kurikulum_file' => 'Kurikulum Dirasah Islamiyah',
                'buku_ajar_file' => 'Buku ajar terbitan LP2 PPM',
            ];
            $ipmFilled = collect($ipmCriteria)->keys()->filter(fn ($field) => !empty($ipm?->$field))->count();
        @endphp
        <x-ui.section-card title="Kriteria IPM" subtitle="{{ $ipmFilled }}/{{ count($ipmCriteria) }} dokumen terpenuhi." class="mb-5 spm-detail-panel">
            <div class="p-5">
                <div class="spm-document-grid">
                    @foreach($ipmCriteria as $field => $label)
                        <x-ui.document-item
                            :label="$label"
                            :href="!empty($ipm?->$field) ? Storage::url($ipm->$field) : null"
                            :description="!empty($ipm?->$field) ? 'Terpenuhi' : 'Belum terpenuhi'"
                        />
                    @endforeach
                </div>
            </div>
        </x-ui.section-card>

    @elseif($activeTab === 'sdm')
        {{-- SDM Tab --}}
        <x-ui.section-card title="Data SDM" subtitle="Rekap santri, ustadz, pamong, musyrif, dan tenaga kependidikan." class="mb-5 spm-detail-panel">
            <div class="p-5">
                @if(!empty($sdm) && count($sdm) > 0)
                    <x-ui.simple-table dense table-class="spm-sdm-matrix-table">
                        <thead>
                            <tr class="spm-sdm-group-row">
                                <th rowspan="2" class="ps-5 align-middle min-w-120px">Tingkat</th>
                                <th colspan="2" class="text-center">Santri</th>
                                <th colspan="2" class="text-center">Ust. Dirosah</th>
                                <th colspan="2" class="text-center">Ust. Non Dirosah</th>
                                <th colspan="2" class="text-center">Pamong</th>
                                <th colspan="2" class="text-center">Musyrif</th>
                                <th colspan="2" class="text-center pe-5">Tendik</th>
                            </tr>
                            <tr class="spm-sdm-sex-row">
                                @foreach(range(1, 6) as $i)
                                    <th class="text-center">Laki-laki</th>
                                    <th class="text-center {{ $i === 6 ? 'pe-5' : '' }}">Perempuan</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($sdm as $row)
                                @php
                                    $metrics = [
                                        [$row->santri_l, $row->santri_p],
                                        [$row->ustadz_dirosah_l, $row->ustadz_dirosah_p],
                                        [$row->ustadz_non_dirosah_l, $row->ustadz_non_dirosah_p],
                                        [$row->pamong_l, $row->pamong_p],
                                        [$row->musyrif_l, $row->musyrif_p],
                                        [$row->tendik_l, $row->tendik_p],
                                    ];
                                @endphp
                                <tr>
                                    <td class="ps-5 spm-sdm-level-cell">
                                        <div class="d-flex align-items-center gap-3">
                                            <span class="symbol symbol-35px">
                                                <span class="symbol-label bg-light-primary text-primary fw-semibold">{{ strtoupper(substr((string) $row->tingkat, 0, 2)) }}</span>
                                            </span>
                                            <span class="fw-semibold text-gray-900">{{ strtoupper($row->tingkat) }}</span>
                                        </div>
                                    </td>
                                    @foreach($metrics as [$male, $female])
                                        <td class="text-center spm-sdm-number spm-sdm-male">{{ $male ?? 0 }}</td>
                                        <td class="text-center spm-sdm-number spm-sdm-female {{ $loop->last ? 'pe-5' : '' }}">{{ $female ?? 0 }}</td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </x-ui.simple-table>
                @else
                    <x-ui.empty-state title="Belum ada data SDM" description="Data SDM akan muncul setelah dilengkapi di menu persiapan akreditasi." variant="secondary" class="py-6" />
                @endif
            </div>
        </x-ui.section-card>    @elseif($activeTab === 'edpm')
        {{-- EDPM Tab --}}
        <x-akreditasi.edpm-review
            :komponens="$komponens"
            :evaluasis="$pesantren_edpm['evaluasis'] ?? []"
            :links="$pesantren_edpm['links'] ?? []"
            :catatans="$pesantren_edpm['catatans'] ?? []"
            title="EDPM/IPR Pesantren"
            subtitle="Detail komponen EDPM dan IPR, isian evaluasi diri, tautan bukti, dan catatan pesantren."
        />

    @elseif($activeTab === 'asesor')
        {{-- Asesor Tab --}}
        <x-ui.section-card title="Informasi Asesor" subtitle="Tim asesor yang ditugaskan pada pengajuan ini." class="mb-5 spm-detail-panel">
            <div class="p-5">
                @if($asesorAssignments->isNotEmpty())
                    <div class="row g-4">
                        @foreach($asesorAssignments as $assignment)
                            @php
                                $asesorProfile = $assignment->asesor;
                                $asesorUser = $asesorProfile?->user;
                                $roleLabel = (int) $assignment->tipe === 1 ? 'Ketua Kelompok' : 'Anggota Kelompok';
                            @endphp
                            <div class="col-lg-6">
                                <div class="spm-asesor-card h-100">
                                    <div class="d-flex align-items-start gap-4">
                                        <div class="symbol symbol-55px flex-shrink-0">
                                            <div class="symbol-label bg-light-primary text-primary">
                                                <x-ui.icon name="profile-user" class="fs-2x" />
                                            </div>
                                        </div>
                                        <div class="min-w-0 flex-grow-1">
                                            <div class="d-flex align-items-center flex-wrap gap-2 mb-2">
                                                <x-ui.badge variant="primary">{{ $roleLabel }}</x-ui.badge>
                                                <x-ui.status-badge variant="secondary">Tipe {{ $assignment->tipe }}</x-ui.status-badge>
                                            </div>
                                            <div class="fw-semibold text-gray-900 fs-5 mb-1">{{ $asesorProfile->nama_dengan_gelar ?? $asesorUser?->name ?? '-' }}</div>
                                            <div class="text-muted fw-semibold fs-7">{{ $asesorUser?->email ?? $asesorProfile?->email_pribadi ?? '-' }}</div>
                                            <div class="text-muted fw-semibold fs-7 mt-1">{{ $asesorProfile?->whatsapp ?? '-' }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <x-ui.empty-state title="Asesor belum ditugaskan" description="Tim asesor akan tampil setelah admin menyelesaikan penugasan pada tahap review." variant="secondary" class="py-6" />
                @endif
            </div>
        </x-ui.section-card>

    @elseif($activeTab === 'hasil')
        {{-- Hasil Tab --}}
        <x-ui.section-card title="Hasil Akreditasi" subtitle="Nilai, peringkat, SK, sertifikat, dan rekomendasi akhir." class="mb-5 spm-detail-panel">
            <div class="p-5">
                @if(in_array((int) $akreditasi->status, [0, -1, -2, 1], true))
                    <div class="row g-4 spm-detail-grid">
                        <x-ui.detail-item label="Nilai Akhir" :value="$akreditasi->nilai ?? '-'" />
                        <x-ui.detail-item label="Peringkat" :value="$akreditasi->peringkat ?? '-'" />
                        <x-ui.detail-item label="Nomor SK" :value="$akreditasi->nomor_sk ?? '-'" />
                        <x-ui.detail-item label="Masa Berlaku" :value="$akreditasi->masa_berlaku_akhir ? \Carbon\Carbon::parse($akreditasi->masa_berlaku_akhir)->format('d M Y') : '-'" />
                        <x-ui.detail-item label="Rekomendasi" span="2" :value="$akreditasi->catatan_rekomendasi_admin ?: '-'" />
                        <div class="col-md-12">
                            <x-ui.document-item label="Sertifikat Akreditasi" :href="!empty($akreditasi->sertifikat_path) ? Storage::url($akreditasi->sertifikat_path) : null" />
                        </div>
                    </div>
                @else
                    <x-ui.empty-state title="Hasil belum tersedia" description="Hasil akhir akan muncul setelah validasi admin dan penerbitan SK selesai." variant="secondary" class="py-6" />
                @endif
            </div>
        </x-ui.section-card>
    @elseif($activeTab === 'banding')
        {{-- Banding Tab --}}
        @php
            $banding = $akreditasi->activeBanding ?? $akreditasi->bandings()->latest()->first();
        @endphp
        <x-ui.section-card title="Informasi Banding" class="mb-5">
            <div class="p-5">
                @if(!empty($banding))
                    <div class="row g-4">
                        <div class="col-12"><strong>Alasan:</strong><br>{{ $banding->alasan ?? '-' }}</div>
                        <div class="col-md-6"><strong>Status:</strong> {{ $banding->status ?? '-' }}</div>
                        <div class="col-md-6"><strong>Tanggal:</strong> {{ $banding->created_at ? $banding->created_at->format('d M Y H:i') : '-' }}</div>
                        @if(!empty($banding->keputusan))
                            <div class="col-12"><strong>Hasil:</strong><br>{{ $banding->keputusan }}</div>
                        @endif
                    </div>
                @else
                    <div class="text-muted">Belum ada pengajuan banding.</div>
                @endif
            </div>
        </x-ui.section-card>
    @endif
    </div>
</x-ui.page>

@push('scripts')
<script>
document.getElementById('btnUploadKartuKendali')?.addEventListener('click', function(e) {
    e.preventDefault();
    window.SpmSwal.confirm({
        title: 'Upload Kartu Kendali?',
        text: 'Pastikan file sudah sesuai.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Ya, Upload',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('kartuKendaliForm').submit();
        }
    });
});
</script>
@endpush
@endsection
