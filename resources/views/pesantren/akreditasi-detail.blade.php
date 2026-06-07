@extends('layouts.app')

@section('header', 'Detail Akreditasi')

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
@endphp

<x-ui.page
    title="Detail Akreditasi"
    subtitle="Pantau detail pengajuan akreditasi pesantren Anda."
>
    <x-slot:toolbar>
        <x-ui.button :href="route('pesantren.akreditasi')" variant="light">
            <i class="ki-solid ki-arrow-left fs-4 me-1"></i>
            Kembali
        </x-ui.button>
    </x-slot:toolbar>

    @if(session('success'))
        <x-ui.alert variant="success" title="Berhasil" class="mb-4">{{ session('success') }}</x-ui.alert>
    @endif
    @if(session('error'))
        <x-ui.alert variant="danger" title="Gagal" class="mb-4">{{ session('error') }}</x-ui.alert>
    @endif

    {{-- Info Bar --}}
    <div class="row g-5 mb-6">
        <div class="col-lg-4">
            <x-ui.stat-card label="Periode" value="{{ $akreditasi->periode ?? '-' }}" variant="primary" icon="calendar" />
        </div>
        <div class="col-lg-4">
            <x-ui.stat-card label="Status" value="{{ ucfirst($akreditasi->status ?? '-') }}" variant="info" icon="information-3" />
        </div>
        <div class="col-lg-4">
            <x-ui.stat-card label="Tahapan" value="{{ ucfirst($akreditasi->tahapan ?? '-') }}" variant="success" icon="check-circle" />
        </div>
    </div>

    {{-- Kartu Kendali Upload --}}
    <x-ui.section-card title="Kartu Kendali" subtitle="Unggah kartu kendali yang telah ditandatangani" class="mb-6">
        <div class="p-6">
            @if(!empty($akreditasi->kartu_kendali))
                <div class="d-flex align-items-center gap-3 mb-4">
                    <i class="ki-solid ki-file text-success fs-2"></i>
                    <div>
                        <div class="fw-semibold text-success">Dokumen Terunggah</div>
                        <a href="{{ Storage::url($akreditasi->kartu_kendali) }}" target="_blank" class="text-muted fs-8">Lihat Dokumen</a>
                    </div>
                </div>
            @endif

            @if(!in_array($akreditasi->status, ['rejected', 'cancelled', 'withdrawn']))
                <form action="{{ route('pesantren.akreditasi.upload-kartu-kendali') }}" method="POST" enctype="multipart/form-data" id="kartuKendaliForm">
                    @csrf
                    <input type="hidden" name="akreditasi_id" value="{{ $akreditasi->id }}">
                    <div class="row align-items-end">
                        <div class="col-md-8">
                            <x-ui.form-field label="{{ !empty($akreditasi->kartu_kendali) ? 'Ganti File' : 'Unggah File' }}">
                                <input type="file" name="kartu_kendali_file" class="form-control form-control-sm @error('kartu_kendali_file') is-invalid @enderror" accept="application/pdf,image/png,image/jpeg">
                                @error('kartu_kendali_file')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="text-muted fs-8 mt-1">PDF/Gambar (Maks. 2MB)</div>
                            </x-ui.form-field>
                        </div>
                        <div class="col-md-4">
                            <x-ui.button type="submit" variant="primary" size="sm" class="w-100 mb-3" id="btnUploadKartuKendali">
                                <i class="ki-solid ki-cloud-add fs-4 me-1"></i> Upload
                            </x-ui.button>
                        </div>
                    </div>
                </form>
            @endif
        </div>
    </x-ui.section-card>

    {{-- Tabs --}}
    <x-ui.tabs class="mb-6">
        @foreach($tabs as $key => $label)
            @if($key !== 'banding' || !empty($akreditasi->banding))
                <a href="{{ request()->fullUrlWithQuery(['tab' => $key]) }}" class="nav-link {{ $activeTab === $key ? 'active' : '' }}">
                    {{ $label }}
                </a>
            @endif
        @endforeach
    </x-ui.tabs>

    {{-- Tab Content --}}
    @if($activeTab === 'profil')
        {{-- Profil Tab --}}
        <x-ui.section-card title="A. Identitas Pesantren" class="mb-6">
            <div class="p-6">
                <div class="row g-4">
                    <div class="col-md-6"><strong>Nama Pesantren:</strong> {{ $profil->nama_pesantren ?? '-' }}</div>
                    <div class="col-md-6"><strong>NSP:</strong> {{ $profil->ns_pesantren ?? '-' }}</div>
                    <div class="col-12"><strong>Alamat:</strong> {{ $profil->alamat ?? '-' }}</div>
                    <div class="col-md-6"><strong>Provinsi:</strong> {{ $profil->provinsi ?? '-' }}</div>
                    <div class="col-md-6"><strong>Kota/Kabupaten:</strong> {{ $profil->kota_kabupaten ?? '-' }}</div>
                    <div class="col-md-6"><strong>Tahun Pendirian:</strong> {{ $profil->tahun_pendirian ?? '-' }}</div>
                    <div class="col-md-6"><strong>Nama Mudir:</strong> {{ $profil->nama_mudir ?? '-' }}</div>
                    <div class="col-md-6"><strong>Jenjang Pendidikan Mudir:</strong> {{ $profil->jenjang_pendidikan_mudir ?? '-' }}</div>
                    <div class="col-md-6"><strong>Telp:</strong> {{ $profil->telp_pesantren ?? '-' }}</div>
                    <div class="col-md-6"><strong>HP/WA:</strong> {{ $profil->hp_wa ?? '-' }}</div>
                    <div class="col-md-6"><strong>Email:</strong> {{ $profil->email_pesantren ?? '-' }}</div>
                    <div class="col-md-6"><strong>Persyarikatan:</strong> {{ $profil->persyarikatan ?? '-' }}</div>
                </div>
            </div>
        </x-ui.section-card>

        <x-ui.section-card title="B. Layanan Satuan Pendidikan" class="mb-6">
            <div class="p-6">
                @if(!empty($profil->units) && count($profil->units) > 0)
                    <table class="table table-bordered table-row-dashed">
                        <thead><tr><th>Unit</th><th>Jumlah Rombel</th></tr></thead>
                        <tbody>
                            @foreach($profil->units as $unit)
                                <tr><td>{{ $unit->unit }}</td><td>{{ $unit->jumlah_rombel }}</td></tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="text-muted">Belum ada layanan yang dipilih.</div>
                @endif
            </div>
        </x-ui.section-card>

        <x-ui.section-card title="C. Visi & Misi" class="mb-6">
            <div class="p-6">
                <div class="mb-4"><strong>Visi:</strong><br>{{ $profil->visi ?? '-' }}</div>
                <div><strong>Misi:</strong><br>{{ $profil->misi ?? '-' }}</div>
            </div>
        </x-ui.section-card>

        <x-ui.section-card title="D. Dokumen" class="mb-6">
            <div class="p-6">
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
                <table class="table table-bordered table-row-dashed">
                    <thead><tr><th>Dokumen</th><th>Status</th></tr></thead>
                    <tbody>
                        @foreach($mainDocs as $field => $label)
                            <tr>
                                <td>{{ $label }}</td>
                                <td>
                                    @if(!empty($profil->$field))
                                        <a href="{{ Storage::url($profil->$field) }}" target="_blank" class="text-success">Lihat Dokumen</a>
                                    @else
                                        <span class="text-muted">Belum diunggah</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-ui.section-card>

    @elseif($activeTab === 'ipm')
        {{-- IPM Tab --}}
        @php
            $ipmCriteria = [
                'nsp_file' => '1. NSP yang masih berlaku',
                'lulus_santri_file' => '2. Santri lulus minimal satu angkatan',
                'kurikulum_file' => '3. Kurikulum Dirasah Islamiyah',
                'buku_ajar_file' => '4. Buku ajar terbitan LP2 PPM',
            ];
        @endphp
        <x-ui.section-card title="Kriteria IPM" class="mb-6">
            <div class="p-6">
                <table class="table table-bordered table-row-dashed">
                    <thead><tr><th>Kriteria</th><th>Status</th></tr></thead>
                    <tbody>
                        @foreach($ipmCriteria as $field => $label)
                            <tr>
                                <td>{{ $label }}</td>
                                <td>
                                    @if(!empty($ipm->$field))
                                        <div>
                                            <a href="{{ Storage::url($ipm->$field) }}" target="_blank" class="text-success">Lihat Dokumen</a>
                                            <span class="badge badge-light-success ms-2">Terpenuhi</span>
                                        </div>
                                    @else
                                        <span class="badge badge-light-danger">Belum Terpenuhi</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-ui.section-card>

    @elseif($activeTab === 'sdm')
        {{-- SDM Tab --}}
        <x-ui.section-card title="Data SDM" class="mb-6">
            <div class="p-6">
                @if(!empty($sdm) && count($sdm) > 0)
                    <div class="table-responsive">
                        <table class="table table-bordered table-row-dashed">
                            <thead>
                                <tr><th>Tingkat</th><th>Santri L</th><th>Santri P</th><th>Ust. Dirosah L</th><th>Ust. Dirosah P</th><th>Ust. Non Dirosah L</th><th>Ust. Non Dirosah P</th><th>Pamong L</th><th>Pamong P</th><th>Musyrif L</th><th>Musyrif P</th><th>Tendik L</th><th>Tendik P</th></tr>
                            </thead>
                            <tbody>
                                @foreach($sdm as $row)
                                    <tr>
                                        <td>{{ $row->tingkat }}</td>
                                        <td>{{ $row->santri_l }}</td><td>{{ $row->santri_p }}</td>
                                        <td>{{ $row->ustadz_dirosah_l }}</td><td>{{ $row->ustadz_dirosah_p }}</td>
                                        <td>{{ $row->ustadz_non_dirosah_l }}</td><td>{{ $row->ustadz_non_dirosah_p }}</td>
                                        <td>{{ $row->pamong_l }}</td><td>{{ $row->pamong_p }}</td>
                                        <td>{{ $row->musyrif_l }}</td><td>{{ $row->musyrif_p }}</td>
                                        <td>{{ $row->tendik_l }}</td><td>{{ $row->tendik_p }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-muted">Belum ada data SDM.</div>
                @endif
            </div>
        </x-ui.section-card>

    @elseif($activeTab === 'edpm')
        {{-- EDPM Tab --}}
        @if(!empty($edpm) && count($edpm) > 0)
            @foreach($edpm as $komponen => $butirs)
                <x-ui.section-card :title="$komponen" class="mb-6">
                    <div class="p-6">
                        <div class="table-responsive">
                            <table class="table table-bordered table-row-dashed">
                                <thead>
                                    <tr><th style="width:50px">No</th><th>Butir</th><th>Nilai</th><th>Tautan</th></tr>
                                </thead>
                                <tbody>
                                    @foreach($butirs as $butir)
                                        <tr>
                                            <td>{{ $butir->nomor_butir }}</td>
                                            <td>{{ $butir->indikator }}</td>
                                            <td>{{ $butir->isian ?? '-' }}</td>
                                            <td>
                                                @if(!empty($butir->link))
                                                    <a href="{{ $butir->link }}" target="_blank" class="text-primary">Lihat</a>
                                                @else
                                                    -
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </x-ui.section-card>
            @endforeach
        @else
            <x-ui.alert variant="info" title="Tidak Ada Data">Belum ada data EDPM.</x-ui.alert>
        @endif

    @elseif($activeTab === 'asesor')
        {{-- Asesor Tab --}}
        <x-ui.section-card title="Informasi Asesor" class="mb-6">
            <div class="p-6">
                @if(!empty($asesor))
                    <div class="row g-4">
                        <div class="col-md-6"><strong>Nama:</strong> {{ $asesor->name ?? '-' }}</div>
                        <div class="col-md-6"><strong>Email:</strong> {{ $asesor->email ?? '-' }}</div>
                        <div class="col-md-6"><strong>No. HP:</strong> {{ $asesor->hp ?? '-' }}</div>
                        <div class="col-md-6"><strong>Peran:</strong> {{ $asesor->peran ?? '-' }}</div>
                    </div>
                    @if(!empty($akreditasi->jadwal_visitasi))
                        <div class="mt-4">
                            <strong>Jadwal Visitasi:</strong><br>
                            {{ \Carbon\Carbon::parse($akreditasi->jadwal_visitasi)->format('d M Y') }}
                            @if(!empty($akreditasi->jadwal_visitasi_selesai))
                                - {{ \Carbon\Carbon::parse($akreditasi->jadwal_visitasi_selesai)->format('d M Y') }}
                            @endif
                        </div>
                    @endif
                @else
                    <div class="text-muted">Asesor belum ditugaskan.</div>
                @endif
            </div>
        </x-ui.section-card>

    @elseif($activeTab === 'hasil')
        {{-- Hasil Tab --}}
        <x-ui.section-card title="Hasil Akreditasi" class="mb-6">
            <div class="p-6">
                @if(!empty($hasil))
                    <div class="row g-4">
                        <div class="col-md-6"><strong>Nilai:</strong> {{ $hasil->nilai ?? '-' }}</div>
                        <div class="col-md-6"><strong>Grade:</strong> {{ $hasil->grade ?? '-' }}</div>
                        <div class="col-md-6"><strong>Status:</strong> {{ $hasil->status ?? '-' }}</div>
                        <div class="col-md-6">
                            <strong>Sertifikat:</strong>
                            @if(!empty($hasil->sertifikat))
                                <a href="{{ Storage::url($hasil->sertifikat) }}" target="_blank" class="text-primary">Unduh</a>
                            @else
                                -
                            @endif
                        </div>
                    </div>
                @else
                    <div class="text-muted">Hasil akreditasi belum tersedia.</div>
                @endif
            </div>
        </x-ui.section-card>

    @elseif($activeTab === 'banding')
        {{-- Banding Tab --}}
        <x-ui.section-card title="Informasi Banding" class="mb-6">
            <div class="p-6">
                @if(!empty($akreditasi->banding))
                    <div class="row g-4">
                        <div class="col-12"><strong>Alasan:</strong><br>{{ $akreditasi->banding->alasan ?? '-' }}</div>
                        <div class="col-md-6"><strong>Status:</strong> {{ $akreditasi->banding->status ?? '-' }}</div>
                        <div class="col-md-6"><strong>Tanggal:</strong> {{ $akreditasi->banding->created_at ? $akreditasi->banding->created_at->format('d M Y H:i') : '-' }}</div>
                        @if(!empty($akreditasi->banding->hasil))
                            <div class="col-12"><strong>Hasil:</strong><br>{{ $akreditasi->banding->hasil }}</div>
                        @endif
                    </div>
                @else
                    <div class="text-muted">Belum ada pengajuan banding.</div>
                @endif
            </div>
        </x-ui.section-card>
    @endif
</x-ui.page>

@push('scripts')
<script>
document.getElementById('btnUploadKartuKendali')?.addEventListener('click', function(e) {
    e.preventDefault();
    Swal.fire({
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
