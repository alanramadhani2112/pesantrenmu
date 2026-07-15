@extends('layouts.app')

@section('content')
@php
    $profileName = $asesor?->nama_dengan_gelar ?? $user->name;

    $profileSummary = [
        ['label' => 'NIA PM', 'value' => $asesor?->nomor_induk_asesor_pm ?? '-'],
        ['label' => 'Profesi', 'value' => $asesor?->profesi ?? '-'],
        ['label' => 'Jabatan', 'value' => $asesor?->jabatan_utama ?? '-'],
    ];

    $contactSummary = [
        ['label' => 'Email Utama', 'value' => $user->email],
        ['label' => 'WhatsApp', 'value' => $asesor?->whatsapp ?? '-'],
        ['label' => 'Domisili', 'value' => ($asesor?->kota_kabupaten ?? '-') . ', ' . ($asesor?->provinsi ?? '-')],
    ];

    $experienceCards = [
        [
            'title' => 'Riwayat Pendidikan',
            'description' => 'Jejak pendidikan formal yang pernah ditempuh.',
            'items' => $asesor?->riwayat_pendidikan ?? [],
            'mode' => 'education',
            'empty' => 'Riwayat pendidikan belum diisi.',
        ],
        [
            'title' => 'Pengalaman Bekerja',
            'description' => 'Pengalaman profesional dan jabatan sebelumnya.',
            'items' => $asesor?->pengalaman_bekerja ?? [],
            'mode' => 'work',
            'empty' => 'Pengalaman bekerja belum diisi.',
        ],
        [
            'title' => 'Pelatihan',
            'description' => 'Pelatihan dan pengembangan kompetensi.',
            'items' => $asesor?->pengalaman_pelatihan ?? [],
            'mode' => 'timeline',
            'empty' => 'Data pelatihan belum diisi.',
        ],
        [
            'title' => 'Organisasi',
            'description' => 'Keterlibatan dalam organisasi dan komunitas.',
            'items' => $asesor?->pengalaman_berorganisasi ?? [],
            'mode' => 'timeline',
            'empty' => 'Data organisasi belum diisi.',
        ],
    ];
@endphp

<x-ui.page title="Detail Asesor" subtitle="Profil lengkap, rekam jejak, dan dokumen pendukung asesor." class="spm-detail-page">
    <x-slot:toolbar>
        <x-ui.button :href="route('admin.asesor.index')" variant="light">
            <x-ui.icon name="exit-right" class="fs-4 me-1" />
            Kembali
        </x-ui.button>
    </x-slot:toolbar>

    @if($asesor)
        <div class="row g-6">
            <div class="col-xl-4">
                <div class="d-flex flex-column gap-6 spm-asesor-sidebar">
                    <x-ui.card class="spm-asesor-summary-card">
                        <div class="d-flex align-items-start gap-4">
                            <div class="spm-asesor-avatar-wrap">
                                @if($asesor->foto)
                                    <img src="{{ Storage::url($asesor->foto) }}" class="spm-asesor-avatar-image" alt="{{ $profileName }}" loading="lazy">
                                @else
                                    <div class="spm-profile-avatar spm-asesor-avatar-fallback d-flex align-items-center justify-content-center">
                                        {{ substr($profileName, 0, 1) }}
                                    </div>
                                @endif
                            </div>

                            <div class="min-w-0 flex-grow-1">
                                <div class="d-flex align-items-start justify-content-between gap-3">
                                    <div class="min-w-0">
                                        <h2 class="spm-asesor-name mb-1">{{ $profileName }}</h2>
                                        <div class="spm-asesor-meta">NIA PM: {{ $asesor?->nomor_induk_asesor_pm ?? '-' }}</div>
                                    </div>
                                </div>

                                <div class="d-flex flex-wrap gap-2 mt-3">
                                    <x-ui.status-badge :variant="$user->status == 1 ? 'success' : 'danger'">
                                        {{ $user->status == 1 ? 'Aktif' : 'Tidak Aktif' }}
                                    </x-ui.status-badge>
                                    <x-ui.status-badge variant="primary">
                                        {{ $asesor?->profesi ?? 'Profil Asesor' }}
                                    </x-ui.status-badge>
                                </div>
                            </div>
                        </div>

                        <div class="spm-asesor-summary-list mt-5">
                            @foreach($profileSummary as $item)
                                <div class="spm-asesor-summary-row">
                                    <span>{{ $item['label'] }}</span>
                                    <strong>{{ $item['value'] }}</strong>
                                </div>
                            @endforeach
                        </div>
                    </x-ui.card>

                    <x-ui.section-card title="Informasi Kontak" subtitle="Akses cepat untuk menghubungi asesor.">
                        <div class="p-6">
                            <div class="d-flex flex-column gap-4">
                                @foreach($contactSummary as $item)
                                    <div class="spm-contact-row">
                                        <span>{{ $item['label'] }}</span>
                                        <strong>{{ $item['value'] }}</strong>
                                    </div>
                                @endforeach
                                @if($asesor && $asesor->email_pribadi)
                                    <div class="spm-contact-row">
                                        <span>Email Pribadi</span>
                                        <strong>{{ $asesor->email_pribadi }}</strong>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </x-ui.section-card>
                </div>
            </div>

            <div class="col-xl-8">
                <div class="d-flex flex-column gap-6">
                    <x-ui.section-card title="A. Identitas Diri" subtitle="Data inti asesor yang paling sering dipakai untuk verifikasi.">
                        <div class="p-6">
                            <div class="row g-5">
                                <x-ui.detail-item label="Nama Lengkap (Tanpa Gelar)" value="{{ $asesor->nama_tanpa_gelar ?: '-' }}" />
                                <x-ui.detail-item label="NIK / Nomor KTP" value="{{ $asesor->nik ?: '-' }}" />
                                <x-ui.detail-item label="NBM / NIA" value="{{ $asesor->nbm_nia ?: '-' }}" />
                                <x-ui.detail-item label="Tempat, Tanggal Lahir" value="{{ ($asesor->tempat_lahir ?? '-') . ', ' . ($asesor->tanggal_lahir ? \Carbon\Carbon::parse($asesor->tanggal_lahir)->translatedFormat('d F Y') : '-') }}" />
                                <x-ui.detail-item label="Jenis Kelamin" value="{{ $asesor->jenis_kelamin ?: '-' }}" />
                                <x-ui.detail-item label="Status Perkawinan" value="{{ $asesor->status_perkawinan ?: '-' }}" />
                                <x-ui.detail-item label="Pendidikan Terakhir" value="{{ $asesor->pendidikan_terakhir ?: '-' }}" />
                                <x-ui.detail-item label="Tahun Sertifikat Terbit" value="{{ $asesor->tahun_terbit_sertifikat ?: '-' }}" />

                                <x-ui.detail-item label="Alamat Rumah" span="2">
                                    <div class="spm-detail-block spm-detail-value-muted">{{ $asesor->alamat_rumah ?: '-' }}</div>
                                </x-ui.detail-item>

                                <x-ui.detail-item label="Profesi" value="{{ $asesor->profesi ?: '-' }}" />
                                <x-ui.detail-item label="Jabatan Utama" value="{{ $asesor->jabatan_utama ?: '-' }}" />
                                <x-ui.detail-item label="Unit Kerja" value="{{ $asesor->unit_kerja ?: '-' }}" />
                                <x-ui.detail-item label="Telp Kantor" value="{{ $asesor->telp_kantor ?: '-' }}" />

                                <x-ui.detail-item label="Alamat Kantor" span="2">
                                    <div class="spm-detail-block spm-detail-value-muted">{{ $asesor->alamat_kantor ?: '-' }}</div>
                                </x-ui.detail-item>
                            </div>
                        </div>
                    </x-ui.section-card>

                    <x-ui.section-card title="B. Pengalaman & Rekam Jejak" subtitle="Setiap bagian dipisahkan agar lebih mudah dipindai dan dibandingkan.">
                        <div class="p-6">
                            <div class="row g-5">
                                @foreach($experienceCards as $card)
                                    <div class="col-lg-6">
                                        <x-ui.card :title="$card['title']" :subtitle="$card['description']" flush class="spm-asesor-experience-card h-100">
                                            <div class="px-0 pt-0">
                                                @forelse($card['items'] as $item)
                                                    @if($card['mode'] === 'education')
                                                        <div class="spm-asesor-experience-row">
                                                            <div class="d-flex align-items-center gap-3 min-w-0">
                                                                <x-ui.badge variant="success">{{ $item['jenjang'] ?? '-' }}</x-ui.badge>
                                                                <span class="fw-semibold fs-7 text-gray-800 text-truncate">{{ $item['dimana'] ?? '-' }}</span>
                                                            </div>
                                                            <span class="text-muted fs-8">{{ $item['kapan'] ?? '-' }}</span>
                                                        </div>
                                                    @elseif($card['mode'] === 'work')
                                                        <div class="spm-asesor-experience-row">
                                                            <div class="min-w-0">
                                                                <div class="fw-semibold fs-7 text-gray-800">{{ $item['sebagai'] ?? '-' }}</div>
                                                                <div class="text-muted fs-8 text-truncate">{{ $item['dimana'] ?? '-' }}</div>
                                                            </div>
                                                            <span class="text-muted fs-8">{{ $item['kapan'] ?? '-' }}</span>
                                                        </div>
                                                    @else
                                                        <div class="spm-asesor-experience-row">
                                                            <div class="min-w-0">
                                                                <div class="fw-semibold fs-7 text-gray-800">{{ $item['sebagai'] ?? '-' }}</div>
                                                                <div class="text-muted fs-8 text-truncate">{{ $item['dimana'] ?? '-' }}</div>
                                                            </div>
                                                            <span class="fw-semibold fs-8 text-primary">{{ $item['kapan'] ?? '-' }}</span>
                                                        </div>
                                                    @endif
                                                @empty
                                                    <div class="spm-compact-empty-state">
                                                        <x-ui.empty-state title="Belum Ada Data" description="{{ $card['empty'] }}" class="py-6" />
                                                    </div>
                                                @endforelse
                                            </div>
                                        </x-ui.card>
                                    </div>
                                @endforeach

                                <div class="col-12">
                                    <x-ui.card title="Karya Publikasi" subtitle="Publikasi dan karya ilmiah yang terkait dengan asesor.">
                                        <div class="px-0 pt-0">
                                            @forelse($asesor->karya_publikasi ?? [] as $item)
                                                <div class="spm-asesor-experience-row">
                                                    <div class="min-w-0">
                                                        <div class="fw-semibold fs-7 text-gray-800">{{ $item['judul'] ?? '-' }}</div>
                                                        <div class="text-muted fs-8 text-truncate">{{ $item['penerbit'] ?? '-' }}</div>
                                                    </div>
                                                    <span class="fw-semibold fs-8 text-primary">{{ $item['tahun'] ?? '-' }}</span>
                                                </div>
                                            @empty
                                                <div class="spm-compact-empty-state">
                                                    <x-ui.empty-state title="Belum Ada Data" description="Karya publikasi belum diisi." class="py-6" />
                                                </div>
                                            @endforelse
                                        </div>
                                    </x-ui.card>
                                </div>
                            </div>
                        </div>
                    </x-ui.section-card>

                    <x-ui.section-card title="C. Dokumen Pendukung" subtitle="Dokumen resmi yang diunggah oleh asesor.">
                        <div class="p-6">
                            <div class="row g-5">
                                @foreach([
                                    'ktp' => 'KTP',
                                    'foto' => 'Foto',
                                    'npwp' => 'NPWP',
                                    'cv' => 'CV',
                                    'ijazah_terakhir' => 'Ijazah Terakhir',
                                    'sertifikat_pendidik' => 'Sertifikat Pendidik',
                                    'sertifikat_asesor' => 'Sertifikat Asesor',
                                    'surat_tugas' => 'Surat Tugas',
                                ] as $field => $label)
                                    <div class="col-md-6">
                                        <x-ui.document-item
                                            :label="$label"
                                            :href="$asesor->$field ? Storage::url($asesor->$field) : null"
                                        />
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </x-ui.section-card>
                </div>
            </div>
        </div>
    @endif
</x-ui.page>
@endsection
