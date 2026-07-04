@extends('layouts.app')

@section('header', 'Indikator Pemenuhan Mutlak (IPM)')

@section('content')
@php
    $criteria = [
        [
            'input' => 'nsp_file_upload',
            'label' => '1. Pesantren memiliki Nomor Statistik Pesantren (NSP) yang masih berlaku.',
            'description' => 'Unggah sertifikat NSP yang masih berlaku.',
            'field' => 'nsp_file',
        ],
        [
            'input' => 'lulus_santri_file_upload',
            'label' => '2. Pesantren memiliki santri yang telah lulus minimal satu angkatan.',
            'description' => 'Unggah dokumen bukti kelulusan santri.',
            'field' => 'lulus_santri_file',
        ],
        [
            'input' => 'kurikulum_file_upload',
            'label' => '3. Pesantren menyelenggarakan kurikulum Dirasah Islamiyah sesuai standar LP2 PPM.',
            'description' => 'Unggah dokumen kurikulum yang digunakan.',
            'field' => 'kurikulum_file',
        ],
        [
            'input' => 'buku_ajar_file_upload',
            'label' => '4. Pesantren menggunakan buku ajar Dirasah Islamiyah terbitan LP2 PPM.',
            'description' => 'Unggah buku ajar atau referensi resmi yang dipakai.',
            'field' => 'buku_ajar_file',
        ],
    ];
    $completedCriteria = collect($criteria)->filter(fn ($item) => filled($existingFiles[$item['field']] ?? null))->count();
    $isLocked = $pesantren->is_locked ?? false;
@endphp

<x-ui.page
    title="Indikator Pemenuhan Mutlak (IPM)"
    subtitle="Unggah dokumen pendukung untuk empat kriteria pemenuhan mutlak."
    data-module-page="pesantren-ipm"
>
    <x-slot:toolbar>
        <x-ui.status-badge :variant="$isLocked ? 'warning' : 'success'">
            {{ $isLocked ? 'Terkunci' : 'Aktif' }}
        </x-ui.status-badge>

        <x-ui.button :href="route('pesantren.profile')" variant="light">
            <x-ui.icon name="exit-right" class="fs-4 me-1" />
            Kembali
        </x-ui.button>
    </x-slot:toolbar>

    <div class="row g-5 mb-6">
        <div class="col-lg-4">
            <x-ui.stat-card label="Status IPM" value="{{ $isLocked ? 'Terkunci' : 'Aktif' }}" variant="{{ $isLocked ? 'warning' : 'success' }}" icon="shield-tick" />
        </div>

        <div class="col-lg-4">
            <x-ui.stat-card label="Kriteria Terisi" value="{{ $completedCriteria }} / {{ count($criteria) }}" variant="info" icon="document" />
        </div>

        <div class="col-lg-4">
            <x-ui.stat-card label="Langkah Berikut" value="{{ $completedCriteria === count($criteria) ? 'Siap Simpan' : 'Lanjut Unggah' }}" variant="primary" icon="arrow-right" />
        </div>
    </div>

    @if($isLocked)
        <x-ui.alert variant="warning" icon="shield-tick" title="Data Terkunci" class="mb-4">
            Data IPM terkunci karena sedang dalam proses akreditasi.
        </x-ui.alert>
    @endif

    @if(session('success'))
        <x-ui.alert variant="success" title="Berhasil" class="mb-4">{{ session('success') }}</x-ui.alert>
    @endif
    @if(session('error'))
        <x-ui.alert variant="danger" title="Gagal" class="mb-4">{{ session('error') }}</x-ui.alert>
    @endif
    @if(session('info'))
        <x-ui.alert variant="info" title="Info" class="mb-4">{{ session('info') }}</x-ui.alert>
    @endif

    <form action="{{ route('pesantren.ipm.update') }}" method="POST" enctype="multipart/form-data" id="ipmForm">
        @csrf

        @if($errors->any())
            <x-ui.alert variant="danger" title="Data IPM belum valid" class="mb-6">
                <ul class="mb-0 ps-4">
                    @foreach($errors->all() as $message)
                        <li>{{ $message }}</li>
                    @endforeach
                </ul>
            </x-ui.alert>
        @endif

        <div class="d-flex flex-column gap-5">
            @foreach($criteria as $index => $item)
                @php
                    $hasFile = filled($existingFiles[$item['field']] ?? null);
                    $cardVariant = $hasFile ? 'success' : 'light';
                @endphp
                <x-ui.section-card :title="$item['label']" :subtitle="$item['description']">
                    <div class="p-6">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                @if($hasFile)
                                    <div class="d-flex align-items-center gap-3 mb-3">
                                        <i class="ki-solid ki-file text-success fs-2"></i>
                                        <div>
                                            <div class="fw-semibold text-success fs-7">Dokumen Terunggah</div>
                                            <a data-ui-document-item="metronic" href="{{ Storage::url($existingFiles[$item['field']]) }}" target="_blank" class="text-muted fs-8">Lihat Dokumen</a>
                                        </div>
                                    </div>
                                @endif

                                @if(!$isLocked)
                                    <div data-ui-form-field="metronic">
                                    <label class="form-label fw-semibold text-gray-700 fs-7">
                                        {{ $hasFile ? 'Ganti Dokumen' : 'Unggah Dokumen' }}
                                    </label>
                                    <input
                                        data-ui-file-upload="metronic"
                                        type="file"
                                        name="{{ $item['input'] }}"
                                        class="form-control form-control-sm @error($item['input']) is-invalid @enderror"
                                        accept="application/pdf,image/png,image/jpeg"
                                    />
                                    @error($item['input'])
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <div class="text-muted fs-8 mt-1">PDF/Gambar (Maks. 2MB)</div>
                                    </div>
                                @endif
                            </div>
                            <div class="col-md-4 text-end">
                                @if($hasFile)
                                    <span class="badge badge-light-success fs-8">
                                        <i class="ki-solid ki-check-circle fs-7 me-1"></i> Terpenuhi
                                    </span>
                                @else
                                    <span class="badge badge-light-warning fs-8">
                                        <i class="ki-solid ki-information-3 fs-7 me-1"></i> Belum Lengkap
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                </x-ui.section-card>
            @endforeach
        </div>

        @if(!$isLocked)
            <div class="d-flex justify-content-end gap-3 mt-6">
                <x-ui.button type="submit" variant="primary" id="btnSaveIpm">
                    <i class="ki-solid ki-check fs-4 me-1"></i>
                    Simpan IPM
                </x-ui.button>
            </div>
        @endif
    </form>
</x-ui.page>

@push('scripts')
<script>
document.getElementById('btnSaveIpm')?.addEventListener('click', function(e) {
    e.preventDefault();
    window.SpmSwal.confirm({
        title: 'Simpan Data IPM?',
        text: 'Pastikan dokumen yang diunggah sudah benar.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Ya, Simpan',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('ipmForm').requestSubmit();
        }
    });
});
</script>
@endpush
@endsection
