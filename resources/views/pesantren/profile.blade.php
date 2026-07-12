@extends('layouts.app')

@section('header', 'Profil Pesantren')

@section('content')
@php
    $isLocked = $pesantren->is_locked ?? false;
    $layananOptions = [
        'sd' => 'SD',
        'mi' => 'MI',
        'smp' => 'SMP',
        'mts' => 'MTs',
        'sma' => 'SMA',
        'ma' => 'MA',
        'smk' => 'SMK',
        'satuan_pesantren_muadalah_(SPM)' => 'SPM',
    ];
    $selectedLayanan = is_array($pesantren->layanan_satuan_pendidikan)
        ? $pesantren->layanan_satuan_pendidikan
        : [];

    $mainDocs = [
        'status_kepemilikan_tanah_file' => ['label' => 'Status Kepemilikan Tanah', 'field' => 'status_kepemilikan_tanah'],
        'sertifikat_nsp_file' => ['label' => 'Sertifikat NSP', 'field' => 'sertifikat_nsp'],
        'rk_anggaran_file' => ['label' => 'Rencana Kerja Anggaran', 'field' => 'rk_anggaran'],
        'silabus_rpp_file' => ['label' => 'Silabus dan RPP (Dirosah Islamiyah)', 'field' => 'silabus_rpp'],
        'peraturan_kepegawaian_file' => ['label' => 'Peraturan Kepegawaian', 'field' => 'peraturan_kepegawaian'],
        'file_lk_iapm_file' => ['label' => 'LK Penilaian IAPM', 'field' => 'file_lk_iapm'],
        'laporan_tahunan_file' => ['label' => 'Laporan Tahunan', 'field' => 'laporan_tahunan'],
    ];

    $secondaryDocs = [
        'dok_profil_file' => ['label' => 'Dokumen Profil', 'field' => 'dok_profil'],
        'dok_nsp_file' => ['label' => 'Dokumen NSP', 'field' => 'dok_nsp'],
        'dok_renstra_file' => ['label' => 'Dokumen Renstra', 'field' => 'dok_renstra'],
        'dok_rk_anggaran_file' => ['label' => 'Dokumen RK Anggaran', 'field' => 'dok_rk_anggaran'],
        'dok_kurikulum_file' => ['label' => 'Dokumen Kurikulum', 'field' => 'dok_kurikulum'],
        'dok_silabus_rpp_file' => ['label' => 'Dokumen Silabus & RPP', 'field' => 'dok_silabus_rpp'],
        'dok_kepengasuhan_file' => ['label' => 'Dokumen Kepengasuhan', 'field' => 'dok_kepengasuhan'],
        'dok_peraturan_kepegawaian_file' => ['label' => 'Dokumen Peraturan Kepegawaian', 'field' => 'dok_peraturan_kepegawaian'],
        'dok_sarpras_file' => ['label' => 'Dokumen Sarpras', 'field' => 'dok_sarpras'],
        'dok_laporan_tahunan_file' => ['label' => 'Dokumen Laporan Tahunan', 'field' => 'dok_laporan_tahunan'],
        'dok_sop_file' => ['label' => 'Dokumen SOP', 'field' => 'dok_sop'],
    ];

    $provinsiMap = [
        '11' => 'Aceh', '12' => 'Sumatera Utara', '13' => 'Sumatera Barat',
        '14' => 'Riau', '15' => 'Jambi', '16' => 'Sumatera Selatan',
        '17' => 'Bengkulu', '18' => 'Lampung', '19' => 'Kepulauan Bangka Belitung',
        '21' => 'Kepulauan Riau', '31' => 'DKI Jakarta', '32' => 'Jawa Barat',
        '33' => 'Jawa Tengah', '34' => 'DI Yogyakarta', '35' => 'Jawa Timur',
        '36' => 'Banten', '51' => 'Bali', '52' => 'Nusa Tenggara Barat',
        '53' => 'Nusa Tenggara Timur', '61' => 'Kalimantan Barat',
        '62' => 'Kalimantan Tengah', '63' => 'Kalimantan Selatan',
        '64' => 'Kalimantan Timur', '65' => 'Kalimantan Utara',
        '71' => 'Sulawesi Utara', '72' => 'Sulawesi Tengah',
        '73' => 'Sulawesi Selatan', '74' => 'Sulawesi Tenggara',
        '75' => 'Gorontalo', '76' => 'Sulawesi Barat',
        '81' => 'Maluku', '82' => 'Maluku Utara',
        '91' => 'Papua Barat', '92' => 'Papua Barat Daya',
        '93' => 'Papua Selatan', '94' => 'Papua',
        '95' => 'Papua Tengah', '96' => 'Papua Pegunungan',
    ];

    $filledCount = 0;
    $totalFields = count($mainDocs) + count($secondaryDocs);
    foreach (array_merge($mainDocs, $secondaryDocs) as $doc) {
        if (filled($pesantren->{$doc['field']} ?? null)) {
            $filledCount++;
        }
    }
@endphp

<x-ui.page
    title="Profil Pesantren"
    subtitle="Kelola data profil, unit layanan, dan dokumen pendukung pesantren."
    data-module-page="pesantren-profile"
    class="spm-pesantren-form-page"
>
    <x-slot:toolbar>
        <x-ui.status-badge :variant="$isLocked ? 'warning' : 'success'">
            {{ $isLocked ? 'Terkunci' : 'Aktif' }}
        </x-ui.status-badge>
    </x-slot:toolbar>

    <div class="row g-5 mb-8">
        <div class="col-lg-4">
            <x-ui.stat-card label="Status Profil" value="{{ $isLocked ? 'Terkunci' : 'Aktif' }}" variant="{{ $isLocked ? 'warning' : 'success' }}" icon="shield-tick" />
        </div>
        <div class="col-lg-4">
            <x-ui.stat-card label="Dokumen Terunggah" value="{{ $filledCount }} / {{ $totalFields }}" variant="info" icon="document" />
        </div>
        <div class="col-lg-4">
            <x-ui.stat-card label="Layanan Satuan" value="{{ count($selectedLayanan) }} Unit" variant="primary" icon="building" />
        </div>
    </div>

    @if($isLocked)
        <x-ui.alert variant="warning" icon="shield-tick" title="Data Terkunci — Akreditasi Berlangsung" class="mb-6">
            Profil pesantren tidak dapat diubah karena sedang dalam proses akreditasi. Hubungi admin untuk informasi lebih lanjut.
        </x-ui.alert>
    @endif

    @if(session('success'))
        <x-ui.alert variant="success" title="Berhasil" class="mb-6">{{ session('success') }}</x-ui.alert>
    @endif
    @if(session('error'))
        <x-ui.alert variant="danger" title="Gagal" class="mb-6">{{ session('error') }}</x-ui.alert>
    @endif

    @if($errors->any())
        <x-ui.alert variant="danger" title="Data profil belum valid" class="mb-6">
            <ul class="mb-0 ps-4">
                @foreach($errors->all() as $message)
                    <li>{{ $message }}</li>
                @endforeach
            </ul>
        </x-ui.alert>
    @endif

    @if(!$isLocked)
        <form action="{{ route('pesantren.profile.save') }}" method="POST" enctype="multipart/form-data" id="profileForm">
            @csrf
    @endif

    {{-- DATA PESANTREN --}}
    <x-ui.section-card title="Data Pesantren" subtitle="Informasi identitas dan kontak pesantren." class="mb-6">
        <x-slot:toolbar>
            <x-ui.icon name="building" class="fs-2x text-primary" />
        </x-slot:toolbar>
        <div class="p-6">
            <div class="row g-6">
                <div class="col-lg-6">
                    <x-ui.form-field label="Nama Pesantren" for="nama_pesantren" :required="true">
                        <input type="text" name="nama_pesantren" id="nama_pesantren"
                            class="form-control form-control-solid @error('nama_pesantren') is-invalid @enderror"
                            value="{{ old('nama_pesantren', $pesantren->nama_pesantren) }}"
                            {{ $isLocked ? 'disabled' : '' }}>
                        @error('nama_pesantren') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </x-ui.form-field>
                </div>
                <div class="col-lg-6">
                    <x-ui.form-field label="Nomor Statistik Pesantren (NSP)" for="ns_pesantren" :required="true">
                        <input type="text" name="ns_pesantren" id="ns_pesantren"
                            class="form-control form-control-solid @error('ns_pesantren') is-invalid @enderror"
                            value="{{ old('ns_pesantren', $pesantren->ns_pesantren) }}"
                            {{ $isLocked ? 'disabled' : '' }}>
                        @error('ns_pesantren') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </x-ui.form-field>
                </div>
                <div class="col-12">
                    <x-ui.form-field label="Alamat Pesantren" for="alamat" :required="true">
                        <textarea name="alamat" id="alamat" rows="3"
                            class="form-control form-control-solid @error('alamat') is-invalid @enderror"
                            {{ $isLocked ? 'disabled' : '' }}>{{ old('alamat', $pesantren->alamat) }}</textarea>
                        @error('alamat') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </x-ui.form-field>
                </div>
                <div class="col-lg-6">
                    <x-ui.form-field label="Provinsi" for="provinsi_kode">
                        <select name="provinsi_kode" id="provinsi_kode"
                            class="form-select form-select-solid @error('provinsi_kode') is-invalid @enderror"
                            {{ $isLocked ? 'disabled' : '' }}>
                            <option value="">Pilih Provinsi</option>
                            @foreach($provinsiMap as $kode => $nama)
                                <option value="{{ $kode }}" {{ old('provinsi_kode', $pesantren->provinsi_kode) == $kode ? 'selected' : '' }}>{{ $nama }}</option>
                            @endforeach
                        </select>
                        @error('provinsi_kode') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </x-ui.form-field>
                </div>
                <div class="col-lg-6">
                    <x-ui.form-field label="Kota / Kabupaten" for="kota_kabupaten">
                        <input type="text" name="kota_kabupaten" id="kota_kabupaten"
                            class="form-control form-control-solid @error('kota_kabupaten') is-invalid @enderror"
                            value="{{ old('kota_kabupaten', $pesantren->kota_kabupaten) }}"
                            {{ $isLocked ? 'disabled' : '' }}>
                        @error('kota_kabupaten') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </x-ui.form-field>
                </div>
                <div class="col-lg-6">
                    <x-ui.form-field label="Tahun Pendirian" for="tahun_pendirian" :required="true">
                        <input type="number" name="tahun_pendirian" id="tahun_pendirian"
                            class="form-control form-control-solid @error('tahun_pendirian') is-invalid @enderror"
                            value="{{ old('tahun_pendirian', $pesantren->tahun_pendirian) }}"
                            min="1900" max="{{ date('Y') }}"
                            {{ $isLocked ? 'disabled' : '' }}>
                        @error('tahun_pendirian') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </x-ui.form-field>
                </div>
                <div class="col-lg-6">
                    <x-ui.form-field label="Luas Tanah" for="luas_tanah">
                        <input type="text" name="luas_tanah" id="luas_tanah"
                            class="form-control form-control-solid @error('luas_tanah') is-invalid @enderror"
                            value="{{ old('luas_tanah', $pesantren->luas_tanah) }}"
                            placeholder="m²" {{ $isLocked ? 'disabled' : '' }}>
                        @error('luas_tanah') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </x-ui.form-field>
                </div>
                <div class="col-lg-6">
                    <x-ui.form-field label="Luas Bangunan" for="luas_bangunan">
                        <input type="text" name="luas_bangunan" id="luas_bangunan"
                            class="form-control form-control-solid @error('luas_bangunan') is-invalid @enderror"
                            value="{{ old('luas_bangunan', $pesantren->luas_bangunan) }}"
                            placeholder="m²" {{ $isLocked ? 'disabled' : '' }}>
                        @error('luas_bangunan') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </x-ui.form-field>
                </div>
            </div>
        </div>
    </x-ui.section-card>

    {{-- KONTAK & PIMPINAN --}}
    <x-ui.section-card title="Kontak & Pimpinan" subtitle="Informasi mudir, kontak, dan persyarikatan." class="mb-6">
        <x-slot:toolbar>
            <x-ui.icon name="profile-user" class="fs-2x text-primary" />
        </x-slot:toolbar>
        <div class="p-6">
            <div class="row g-6">
                <div class="col-lg-6">
                    <x-ui.form-field label="Nama Mudir / Pimpinan" for="nama_mudir">
                        <input type="text" name="nama_mudir" id="nama_mudir"
                            class="form-control form-control-solid @error('nama_mudir') is-invalid @enderror"
                            value="{{ old('nama_mudir', $pesantren->nama_mudir) }}"
                            {{ $isLocked ? 'disabled' : '' }}>
                        @error('nama_mudir') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </x-ui.form-field>
                </div>
                <div class="col-lg-6">
                    <x-ui.form-field label="Jenjang Pendidikan Mudir" for="jenjang_pendidikan_mudir">
                        <input type="text" name="jenjang_pendidikan_mudir" id="jenjang_pendidikan_mudir"
                            class="form-control form-control-solid @error('jenjang_pendidikan_mudir') is-invalid @enderror"
                            value="{{ old('jenjang_pendidikan_mudir', $pesantren->jenjang_pendidikan_mudir) }}"
                            placeholder="S1, S2, S3" {{ $isLocked ? 'disabled' : '' }}>
                        @error('jenjang_pendidikan_mudir') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </x-ui.form-field>
                </div>
                <div class="col-lg-6">
                    <x-ui.form-field label="Telepon Pesantren" for="telp_pesantren">
                        <input type="text" name="telp_pesantren" id="telp_pesantren"
                            class="form-control form-control-solid @error('telp_pesantren') is-invalid @enderror"
                            value="{{ old('telp_pesantren', $pesantren->telp_pesantren) }}"
                            {{ $isLocked ? 'disabled' : '' }}>
                        @error('telp_pesantren') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </x-ui.form-field>
                </div>
                <div class="col-lg-6">
                    <x-ui.form-field label="No. HP / WhatsApp" for="hp_wa">
                        <input type="text" name="hp_wa" id="hp_wa"
                            class="form-control form-control-solid @error('hp_wa') is-invalid @enderror"
                            value="{{ old('hp_wa', $pesantren->hp_wa) }}"
                            {{ $isLocked ? 'disabled' : '' }}>
                        @error('hp_wa') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </x-ui.form-field>
                </div>
                <div class="col-lg-6">
                    <x-ui.form-field label="Email Pesantren" for="email_pesantren">
                        <input type="email" name="email_pesantren" id="email_pesantren"
                            class="form-control form-control-solid @error('email_pesantren') is-invalid @enderror"
                            value="{{ old('email_pesantren', $pesantren->email_pesantren) }}"
                            {{ $isLocked ? 'disabled' : '' }}>
                        @error('email_pesantren') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </x-ui.form-field>
                </div>
                <div class="col-lg-6">
                    <x-ui.form-field label="Persyarikatan" for="persyarikatan">
                        <input type="text" name="persyarikatan" id="persyarikatan"
                            class="form-control form-control-solid @error('persyarikatan') is-invalid @enderror"
                            value="{{ old('persyarikatan', $pesantren->persyarikatan) }}"
                            placeholder="Muhammadiyah, NU, ..." {{ $isLocked ? 'disabled' : '' }}>
                        @error('persyarikatan') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </x-ui.form-field>
                </div>
                <div class="col-12">
                    <x-ui.form-field label="Visi" for="visi">
                        <textarea name="visi" id="visi" rows="3"
                            class="form-control form-control-solid @error('visi') is-invalid @enderror"
                            {{ $isLocked ? 'disabled' : '' }}>{{ old('visi', $pesantren->visi) }}</textarea>
                        @error('visi') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </x-ui.form-field>
                </div>
                <div class="col-12">
                    <x-ui.form-field label="Misi" for="misi">
                        <textarea name="misi" id="misi" rows="3"
                            class="form-control form-control-solid @error('misi') is-invalid @enderror"
                            {{ $isLocked ? 'disabled' : '' }}>{{ old('misi', $pesantren->misi) }}</textarea>
                        @error('misi') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </x-ui.form-field>
                </div>
            </div>
        </div>
    </x-ui.section-card>

    {{-- LAYANAN SATUAN PENDIDIKAN --}}
    <x-ui.section-card title="Layanan Satuan Pendidikan" subtitle="Pilih layanan satuan pendidikan yang tersedia." class="mb-6">
        <x-slot:toolbar>
            <x-ui.icon name="book" class="fs-2x text-primary" />
        </x-slot:toolbar>
        <div class="p-6">
            @if($errors->has('layanan_satuan_pendidikan'))
                <div class="text-danger fw-semibold mb-4">{{ $errors->first('layanan_satuan_pendidikan') }}</div>
            @endif
            <div class="row g-4">
                @foreach($layananOptions as $value => $label)
                    <div class="col-md-3 col-sm-6">
                        <label class="form-check form-check-custom form-check-solid cursor-pointer {{ $isLocked ? 'opacity-50 pe-none' : '' }}">
                            <input class="form-check-input" type="checkbox" name="layanan_satuan_pendidikan[]" value="{{ $value }}"
                                {{ in_array($value, old('layanan_satuan_pendidikan', $selectedLayanan)) ? 'checked' : '' }}
                                {{ $isLocked ? 'disabled' : '' }} />
                            <span class="form-check-label">
                                <span class="fw-semibold fs-7">{{ $label }}</span>
                            </span>
                        </label>
                    </div>
                @endforeach
            </div>
        </div>
    </x-ui.section-card>

    {{-- JUMLAH ROMBEL --}}
    @if(count($selectedLayanan) > 0)
    <x-ui.section-card title="Rombongan Belajar" subtitle="Input jumlah rombel per unit layanan." class="mb-6">
        <x-slot:toolbar>
            <x-ui.icon name="chart" class="fs-2x text-primary" />
        </x-slot:toolbar>
        <div class="p-6">
            <div class="row g-6">
                @foreach($layananOptions as $unitValue => $unitLabel)
                    @if(in_array($unitValue, $selectedLayanan))
                        @php
                            $rombel = old("units_data.{$unitValue}.jumlah_rombel", $pesantren->units()->where('unit', $unitValue)->first()?->jumlah_rombel ?? 0);
                        @endphp
                        <div class="col-lg-4 col-md-6">
                            <x-ui.form-field label="{{ $unitLabel }}" for="units_data_{{ $unitValue }}_jumlah_rombel">
                                <input type="number" name="units_data[{{ $unitValue }}][jumlah_rombel]" id="units_data_{{ $unitValue }}_jumlah_rombel"
                                    class="form-control form-control-solid @error('units_data.{{ $unitValue }}.jumlah_rombel') is-invalid @enderror"
                                    value="{{ $rombel }}"
                                    min="0" max="9999"
                                    {{ $isLocked ? 'disabled' : '' }}>
                                @error("units_data.{$unitValue}.jumlah_rombel") <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </x-ui.form-field>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
    </x-ui.section-card>
    @endif

    {{-- DOKUMENTASI UTAMA --}}
    <x-ui.section-card title="Dokumentasi Pendukung Utama" subtitle="Unggah dokumen utama pendukung profil pesantren." class="mb-6">
        <x-slot:toolbar>
            <x-ui.icon name="document" class="fs-2x text-primary" />
        </x-slot:toolbar>
        <div class="p-6">
            <div class="row g-6">
                @foreach($mainDocs as $inputName => $doc)
                    @php
                        $existingPath = $pesantren->{$doc['field']} ?? null;
                        $hasFile = filled($existingPath);
                    @endphp
                    <div class="col-lg-6">
                        <div class="spm-profile-upload-card {{ $hasFile ? 'is-complete' : '' }}">
                            <x-ui.form-field label="{{ $doc['label'] }}" for="{{ $inputName }}">
                                @if($hasFile)
                                    <div class="d-flex align-items-center gap-2 mb-3">
                                        <x-ui.icon name="check-circle" class="fs-5 text-success" />
                                        <a href="{{ Storage::url($existingPath) }}" target="_blank"
                                            class="fw-semibold text-success fs-7 text-hover-primary">Lihat Dokumen</a>
                                    </div>
                                @endif
                                @if(!$isLocked)
                                    <input type="file" name="{{ $inputName }}" id="{{ $inputName }}"
                                        class="form-control form-control-solid spm-pesantren-file-control @error($inputName) is-invalid @enderror"
                                        accept=".pdf,.jpg,.jpeg,.png">
                                    <div class="text-muted fs-8 mt-2">PDF, JPG, PNG. Maks 2MB.</div>
                                    @error($inputName) <div class="invalid-feedback">{{ $message }}</div> @enderror
                                @endif
                            </x-ui.form-field>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </x-ui.section-card>

    {{-- DOKUMENTASI SEKUNDER --}}
    <x-ui.section-card title="Dokumentasi Pendukung Sekunder" subtitle="Unggah dokumen tambahan pendukung profil." class="mb-6">
        <x-slot:toolbar>
            <x-ui.icon name="folder" class="fs-2x text-primary" />
        </x-slot:toolbar>
        <div class="p-6">
            <div class="row g-6">
                @foreach($secondaryDocs as $inputName => $doc)
                    @php
                        $existingPath = $pesantren->{$doc['field']} ?? null;
                        $hasFile = filled($existingPath);
                    @endphp
                    <div class="col-lg-6">
                        <div class="spm-profile-upload-card {{ $hasFile ? 'is-complete' : '' }}">
                            <x-ui.form-field label="{{ $doc['label'] }}" for="{{ $inputName }}">
                                @if($hasFile)
                                    <div class="d-flex align-items-center gap-2 mb-3">
                                        <x-ui.icon name="check-circle" class="fs-5 text-success" />
                                        <a href="{{ Storage::url($existingPath) }}" target="_blank"
                                            class="fw-semibold text-success fs-7 text-hover-primary">Lihat Dokumen</a>
                                    </div>
                                @endif
                                @if(!$isLocked)
                                    <input type="file" name="{{ $inputName }}" id="{{ $inputName }}"
                                        class="form-control form-control-solid spm-pesantren-file-control @error($inputName) is-invalid @enderror"
                                        accept=".pdf,.jpg,.jpeg,.png">
                                    <div class="text-muted fs-8 mt-2">PDF, JPG, PNG. Maks 2MB.</div>
                                    @error($inputName) <div class="invalid-feedback">{{ $message }}</div> @enderror
                                @endif
                            </x-ui.form-field>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </x-ui.section-card>

    {{-- SUBMIT BUTTONS --}}
    @if(!$isLocked)
        <div class="spm-pesantren-form-actions d-flex align-items-center justify-content-end gap-3">
            <button type="submit" name="draft" formaction="{{ route('pesantren.profile.save-draft') }}"
                class="btn btn-light-primary fw-semibold">
                <x-ui.icon name="document" class="fs-4 me-1" />
                Simpan Draft
            </button>
            <button type="submit" formaction="{{ route('pesantren.profile.save') }}"
                class="btn btn-primary fw-semibold">
                <x-ui.icon name="check-circle" class="fs-4 me-1" />
                Simpan & Submit
            </button>
        </div>
        </form>
    @endif

</x-ui.page>
@endsection
