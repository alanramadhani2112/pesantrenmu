@extends('layouts.app')

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
    $selectedLayanan = old('layanan_satuan_pendidikan', is_array($pesantren->layanan_satuan_pendidikan)
        ? $pesantren->layanan_satuan_pendidikan
        : []);
    $unitRombels = $pesantren->units->pluck('jumlah_rombel', 'unit');

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

    $requiredProfileFields = [
        'nama_pesantren', 'ns_pesantren', 'alamat', 'provinsi_kode', 'kota_kabupaten',
        'tahun_pendirian', 'luas_tanah', 'luas_bangunan', 'nama_mudir',
        'jenjang_pendidikan_mudir', 'telp_pesantren', 'hp_wa', 'email_pesantren',
        'persyarikatan', 'visi', 'misi',
    ];
    $filledProfileCount = collect($requiredProfileFields)->filter(fn ($field) => filled(old($field, $pesantren->{$field} ?? null)))->count();
    $rombelFilledCount = collect($selectedLayanan)->filter(fn ($unit) => (int) old("units_data.{$unit}.jumlah_rombel", $unitRombels[$unit] ?? 0) > 0)->count();
    $filledDocCount = 0;
    foreach (array_merge($mainDocs, $secondaryDocs) as $doc) {
        if (filled($pesantren->{$doc['field']} ?? null)) {
            $filledDocCount++;
        }
    }
    $filledCount = $filledProfileCount + (count($selectedLayanan) > 0 ? 1 : 0) + $rombelFilledCount + $filledDocCount;
    $totalFields = count($requiredProfileFields) + 1 + count($selectedLayanan) + count($mainDocs) + count($secondaryDocs);
@endphp

<x-ui.page
    title="Profil Pesantren"
    subtitle="Kelola data profil, unit layanan, dan dokumen pendukung pesantren."
    data-module-page="pesantren-profile"
>
    <x-slot:toolbar>
        <x-ui.status-badge :variant="$isLocked ? 'warning' : 'success'">
            {{ $isLocked ? 'Terkunci' : 'Aktif' }}
        </x-ui.status-badge>
        <x-ui.button :href="route('pesantren.ipm')" variant="light">
            <x-ui.icon name="arrow-right" class="fs-4 me-1" />
            Lanjut IPM
        </x-ui.button>
    </x-slot:toolbar>

    <div class="row g-5 mb-8">
        <div class="col-lg-4">
            <x-ui.stat-card label="Status Profil" value="{{ $isLocked ? 'Terkunci' : 'Aktif' }}" variant="{{ $isLocked ? 'warning' : 'success' }}" icon="shield-tick" />
        </div>
        <div class="col-lg-4">
            <x-ui.stat-card label="Kelengkapan Profil" value="{{ $filledCount }} / {{ $totalFields }}" variant="info" icon="document" />
        </div>
        <div class="col-lg-4">
            <x-ui.stat-card label="Layanan Satuan" value="{{ count($selectedLayanan) }} Unit" variant="primary" icon="building" />
        </div>
    </div>

    <x-ui.progress id="profileProgress" :value="$totalFields > 0 ? round(($filledCount / $totalFields) * 100) : 0" :variant="$filledCount >= $totalFields ? 'success' : 'info'" :label="'Kelengkapan Profil'" :meta="$filledCount . '/' . $totalFields" class="mb-5" />

    @if($isLocked)
        <x-ui.alert variant="warning" icon="shield-tick" title="Data Terkunci — Akreditasi Berlangsung" class="mb-5">
            Profil pesantren tidak dapat diubah karena sedang dalam proses akreditasi. Hubungi admin untuk informasi lebih lanjut.
        </x-ui.alert>
    @endif

    @if(session('success'))
        <x-ui.alert variant="success" title="Berhasil" class="mb-5">{{ session('success') }}</x-ui.alert>
    @endif
    @if(session('error'))
        <x-ui.alert variant="danger" title="Gagal" class="mb-5">{{ session('error') }}</x-ui.alert>
    @endif

    @if($errors->any())
        <x-ui.alert variant="danger" title="Data profil belum valid" class="mb-5">
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
    <x-ui.section-card title="Data Pesantren" subtitle="Informasi identitas dan kontak pesantren." class="mb-5">
        <x-slot:toolbar>
            <x-ui.icon name="building" class="fs-2x text-primary" />
        </x-slot:toolbar>
        <div class="p-5">
            <div class="row g-5">
                <div class="col-lg-6">
                    <x-ui.form-field label="Nama Pesantren" for="nama_pesantren" :required="true">
                        <input type="text" name="nama_pesantren" id="nama_pesantren"
                            class="form-control form-control-solid @error('nama_pesantren') is-invalid @enderror"
                            value="{{ old('nama_pesantren', $pesantren->nama_pesantren) }}"
                            placeholder="Contoh: Pesantren Al Hikmah"
                            required {{ $isLocked ? 'disabled' : '' }}>
                        @error('nama_pesantren') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </x-ui.form-field>
                </div>
                <div class="col-lg-6">
                    <x-ui.form-field label="Nomor Statistik Pesantren (NSP)" for="ns_pesantren" :required="true">
                        <input type="text" name="ns_pesantren" id="ns_pesantren"
                            class="form-control form-control-solid @error('ns_pesantren') is-invalid @enderror"
                            value="{{ old('ns_pesantren', $pesantren->ns_pesantren) }}"
                            placeholder="Contoh: 510032710001"
                            required {{ $isLocked ? 'disabled' : '' }}>
                        @error('ns_pesantren') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </x-ui.form-field>
                </div>
                <div class="col-12">
                    <x-ui.form-field label="Alamat Pesantren" for="alamat" :required="true">
                        <textarea name="alamat" id="alamat" rows="3"
                            class="form-control form-control-solid @error('alamat') is-invalid @enderror"
                            placeholder="Tulis alamat lengkap pesantren"
                            required {{ $isLocked ? 'disabled' : '' }}>{{ old('alamat', $pesantren->alamat) }}</textarea>
                        @error('alamat') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </x-ui.form-field>
                </div>
                <div class="col-lg-6">
                    <x-ui.form-field label="Provinsi" for="provinsi_kode" :required="true">
                        <select name="provinsi_kode" id="provinsi_kode"
                            class="form-select form-select-solid @error('provinsi_kode') is-invalid @enderror"
                            required {{ $isLocked ? 'disabled' : '' }}>
                            <option value="">Pilih Provinsi</option>
                            @foreach($provinsiMap as $kode => $nama)
                                <option value="{{ $kode }}" {{ old('provinsi_kode', $pesantren->provinsi_kode) == $kode ? 'selected' : '' }}>{{ $nama }}</option>
                            @endforeach
                        </select>
                        @error('provinsi_kode') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </x-ui.form-field>
                </div>
                <div class="col-lg-6">
                    <x-ui.form-field label="Kota / Kabupaten" for="kota_kabupaten" :required="true">
                        <input type="text" name="kota_kabupaten" id="kota_kabupaten"
                            class="form-control form-control-solid @error('kota_kabupaten') is-invalid @enderror"
                            value="{{ old('kota_kabupaten', $pesantren->kota_kabupaten) }}"
                            placeholder="Contoh: Kabupaten Sleman"
                            required {{ $isLocked ? 'disabled' : '' }}>
                        @error('kota_kabupaten') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </x-ui.form-field>
                </div>
                <div class="col-lg-6">
                    <x-ui.form-field label="Tahun Pendirian" for="tahun_pendirian" :required="true">
                        <input type="number" name="tahun_pendirian" id="tahun_pendirian"
                            class="form-control form-control-solid @error('tahun_pendirian') is-invalid @enderror"
                            value="{{ old('tahun_pendirian', $pesantren->tahun_pendirian) }}"
                            min="1900" max="{{ date('Y') }}"
                            placeholder="Contoh: 1998"
                            required {{ $isLocked ? 'disabled' : '' }}>
                        @error('tahun_pendirian') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </x-ui.form-field>
                </div>
                <div class="col-lg-6">
                    <x-ui.form-field label="Luas Tanah" for="luas_tanah" :required="true">
                        <input type="text" name="luas_tanah" id="luas_tanah"
                            class="form-control form-control-solid @error('luas_tanah') is-invalid @enderror"
                            value="{{ old('luas_tanah', $pesantren->luas_tanah) }}"
                            placeholder="m²" required {{ $isLocked ? 'disabled' : '' }}>
                        @error('luas_tanah') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </x-ui.form-field>
                </div>
                <div class="col-lg-6">
                    <x-ui.form-field label="Luas Bangunan" for="luas_bangunan" :required="true">
                        <input type="text" name="luas_bangunan" id="luas_bangunan"
                            class="form-control form-control-solid @error('luas_bangunan') is-invalid @enderror"
                            value="{{ old('luas_bangunan', $pesantren->luas_bangunan) }}"
                            placeholder="m²" required {{ $isLocked ? 'disabled' : '' }}>
                        @error('luas_bangunan') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </x-ui.form-field>
                </div>
            </div>
        </div>
    </x-ui.section-card>

    {{-- KONTAK & PIMPINAN --}}
    <x-ui.section-card title="Kontak & Pimpinan" subtitle="Informasi mudir, kontak, dan persyarikatan." class="mb-5">
        <x-slot:toolbar>
            <x-ui.icon name="profile-user" class="fs-2x text-primary" />
        </x-slot:toolbar>
        <div class="p-5">
            <div class="row g-5">
                <div class="col-lg-6">
                    <x-ui.form-field label="Nama Mudir / Pimpinan" for="nama_mudir" :required="true">
                        <input type="text" name="nama_mudir" id="nama_mudir"
                            class="form-control form-control-solid @error('nama_mudir') is-invalid @enderror"
                            value="{{ old('nama_mudir', $pesantren->nama_mudir) }}"
                            placeholder="Contoh: KH Ahmad Fauzi"
                            required {{ $isLocked ? 'disabled' : '' }}>
                        @error('nama_mudir') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </x-ui.form-field>
                </div>
                <div class="col-lg-6">
                    <x-ui.form-field label="Jenjang Pendidikan Mudir" for="jenjang_pendidikan_mudir" :required="true">
                        <input type="text" name="jenjang_pendidikan_mudir" id="jenjang_pendidikan_mudir"
                            class="form-control form-control-solid @error('jenjang_pendidikan_mudir') is-invalid @enderror"
                            value="{{ old('jenjang_pendidikan_mudir', $pesantren->jenjang_pendidikan_mudir) }}"
                            placeholder="S1, S2, S3" required {{ $isLocked ? 'disabled' : '' }}>
                        @error('jenjang_pendidikan_mudir') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </x-ui.form-field>
                </div>
                <div class="col-lg-6">
                    <x-ui.form-field label="Telepon Pesantren" for="telp_pesantren" :required="true">
                        <input type="text" name="telp_pesantren" id="telp_pesantren"
                            class="form-control form-control-solid @error('telp_pesantren') is-invalid @enderror"
                            value="{{ old('telp_pesantren', $pesantren->telp_pesantren) }}"
                            placeholder="Contoh: 0274xxxxxx"
                            required {{ $isLocked ? 'disabled' : '' }}>
                        @error('telp_pesantren') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </x-ui.form-field>
                </div>
                <div class="col-lg-6">
                    <x-ui.form-field label="No. HP / WhatsApp" for="hp_wa" :required="true">
                        <input type="text" name="hp_wa" id="hp_wa"
                            class="form-control form-control-solid @error('hp_wa') is-invalid @enderror"
                            value="{{ old('hp_wa', $pesantren->hp_wa) }}"
                            placeholder="Contoh: 08xxxxxxxxxx"
                            required {{ $isLocked ? 'disabled' : '' }}>
                        @error('hp_wa') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </x-ui.form-field>
                </div>
                <div class="col-lg-6">
                    <x-ui.form-field label="Email Pesantren" for="email_pesantren" :required="true">
                        <input type="email" name="email_pesantren" id="email_pesantren"
                            class="form-control form-control-solid @error('email_pesantren') is-invalid @enderror"
                            value="{{ old('email_pesantren', $pesantren->email_pesantren) }}"
                            placeholder="Contoh: pesantren@domain.com"
                            required {{ $isLocked ? 'disabled' : '' }}>
                        @error('email_pesantren') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </x-ui.form-field>
                </div>
                <div class="col-lg-6">
                    <x-ui.form-field label="Persyarikatan" for="persyarikatan" :required="true">
                        <input type="text" name="persyarikatan" id="persyarikatan"
                            class="form-control form-control-solid @error('persyarikatan') is-invalid @enderror"
                            value="{{ old('persyarikatan', $pesantren->persyarikatan) }}"
                            placeholder="Muhammadiyah, NU, ..." required {{ $isLocked ? 'disabled' : '' }}>
                        @error('persyarikatan') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </x-ui.form-field>
                </div>
                <div class="col-12">
                    <x-ui.form-field label="Visi" for="visi" :required="true">
                        <textarea name="visi" id="visi" rows="3"
                            class="form-control form-control-solid @error('visi') is-invalid @enderror"
                            placeholder="Tulis visi utama pesantren"
                            required {{ $isLocked ? 'disabled' : '' }}>{{ old('visi', $pesantren->visi) }}</textarea>
                        @error('visi') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </x-ui.form-field>
                </div>
                <div class="col-12">
                    <x-ui.form-field label="Misi" for="misi" :required="true">
                        <textarea name="misi" id="misi" rows="3"
                            class="form-control form-control-solid @error('misi') is-invalid @enderror"
                            placeholder="Tulis misi pesantren secara ringkas"
                            required {{ $isLocked ? 'disabled' : '' }}>{{ old('misi', $pesantren->misi) }}</textarea>
                        @error('misi') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </x-ui.form-field>
                </div>
            </div>
        </div>
    </x-ui.section-card>

    {{-- LAYANAN SATUAN PENDIDIKAN --}}
    <x-ui.section-card title="Layanan Satuan Pendidikan" subtitle="Pilih layanan satuan pendidikan yang tersedia." class="mb-5">
        <x-slot:toolbar>
            <x-ui.icon name="book" class="fs-2x text-primary" />
        </x-slot:toolbar>
        <div class="p-5">
            @if($errors->has('layanan_satuan_pendidikan'))
                <div class="text-danger fw-semibold mb-4">{{ $errors->first('layanan_satuan_pendidikan') }}</div>
            @endif
            <div class="row g-4">
                @foreach($layananOptions as $value => $label)
                    <div class="col-md-3 col-sm-6">
                        <label class="form-check form-check-custom form-check-solid cursor-pointer {{ $isLocked ? 'opacity-50 pe-none' : '' }}">
                            <input class="form-check-input js-layanan" type="checkbox" name="layanan_satuan_pendidikan[]" value="{{ $value }}"
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
    <x-ui.section-card title="Rombongan Belajar" subtitle="Input jumlah rombel per unit layanan." class="mb-5">
        <x-slot:toolbar>
            <x-ui.icon name="chart" class="fs-2x text-primary" />
        </x-slot:toolbar>
        <div class="p-5">
            <div class="row g-5">
                @foreach($layananOptions as $unitValue => $unitLabel)
                    @php
                        $checked = in_array($unitValue, $selectedLayanan);
                        $rombel = old("units_data.{$unitValue}.jumlah_rombel", $unitRombels[$unitValue] ?? '');
                    @endphp
                    <div class="col-lg-4 col-md-6 js-rombel" data-unit="{{ $unitValue }}" @if(! $checked) style="display:none" @endif>
                            <x-ui.form-field label="{{ $unitLabel }}" for="units_data_{{ $unitValue }}_jumlah_rombel" :required="true">
                                <input type="number" name="units_data[{{ $unitValue }}][jumlah_rombel]" id="units_data_{{ $unitValue }}_jumlah_rombel"
                                    class="form-control form-control-solid @error('units_data.{{ $unitValue }}.jumlah_rombel') is-invalid @enderror"
                                    value="{{ $rombel }}"
                                    min="1" max="9999" @if($checked) required @endif
                                    placeholder="Contoh: 6"
                                    {{ $isLocked ? 'disabled' : '' }}>
                                @error("units_data.{$unitValue}.jumlah_rombel") <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </x-ui.form-field>
                        </div>
                @endforeach
            </div>
        </div>
    </x-ui.section-card>

    {{-- DOKUMENTASI UTAMA --}}
    <x-ui.section-card title="Dokumentasi Pendukung Utama" subtitle="Unggah dokumen utama pendukung profil pesantren." class="mb-5">
        <x-slot:toolbar>
            <x-ui.icon name="document" class="fs-2x text-primary" />
        </x-slot:toolbar>
        <div class="p-5">
            <div class="row g-5">
                @foreach($mainDocs as $inputName => $doc)
                    @php
                        $existingPath = $pesantren->{$doc['field']} ?? null;
                        $hasFile = filled($existingPath);
                    @endphp
                    <div class="col-lg-6">
                        <x-ui.form-field label="{{ $doc['label'] }}" for="{{ $inputName }}" :required="!$hasFile">
                            @if($hasFile)
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <x-ui.icon name="check-circle" class="fs-5 text-success" />
                                    <a href="{{ Storage::url($existingPath) }}" target="_blank"
                                        class="fw-semibold text-success fs-7 text-hover-primary">Lihat Dokumen</a>
                                </div>
                            @endif
                            @if(!$isLocked)
                                <input type="file" name="{{ $inputName }}" id="{{ $inputName }}"
                                    class="form-control form-control-solid @error($inputName) is-invalid @enderror"
                                    accept=".pdf,.jpg,.jpeg,.png" {{ $hasFile ? '' : 'required' }}>
                                <div class="text-muted fs-8 mt-1">PDF, JPG, PNG. Maks 2MB.</div>
                                @error($inputName) <div class="invalid-feedback">{{ $message }}</div> @enderror
                            @endif
                        </x-ui.form-field>
                    </div>
                @endforeach
            </div>
        </div>
    </x-ui.section-card>

    {{-- DOKUMENTASI SEKUNDER --}}
    <x-ui.section-card title="Dokumentasi Pendukung Sekunder" subtitle="Unggah dokumen tambahan pendukung profil." class="mb-5">
        <x-slot:toolbar>
            <x-ui.icon name="folder" class="fs-2x text-primary" />
        </x-slot:toolbar>
        <div class="p-5">
            <div class="row g-5">
                @foreach($secondaryDocs as $inputName => $doc)
                    @php
                        $existingPath = $pesantren->{$doc['field']} ?? null;
                        $hasFile = filled($existingPath);
                    @endphp
                    <div class="col-lg-6">
                        <x-ui.form-field label="{{ $doc['label'] }}" for="{{ $inputName }}" :required="!$hasFile">
                            @if($hasFile)
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <x-ui.icon name="check-circle" class="fs-5 text-success" />
                                    <a href="{{ Storage::url($existingPath) }}" target="_blank"
                                        class="fw-semibold text-success fs-7 text-hover-primary">Lihat Dokumen</a>
                                </div>
                            @endif
                            @if(!$isLocked)
                                <input type="file" name="{{ $inputName }}" id="{{ $inputName }}"
                                    class="form-control form-control-solid @error($inputName) is-invalid @enderror"
                                    accept=".pdf,.jpg,.jpeg,.png" {{ $hasFile ? '' : 'required' }}>
                                <div class="text-muted fs-8 mt-1">PDF, JPG, PNG. Maks 2MB.</div>
                                @error($inputName) <div class="invalid-feedback">{{ $message }}</div> @enderror
                            @endif
                        </x-ui.form-field>
                    </div>
                @endforeach
            </div>
        </div>
    </x-ui.section-card>

    {{-- SUBMIT BUTTONS --}}
    @if(!$isLocked)
        <div class="d-flex align-items-center justify-content-end gap-3">
            <x-ui.button type="submit" variant="light-primary" name="draft" formnovalidate formaction="{{ route('pesantren.profile.save-draft') }}">
                <x-ui.icon name="document" class="fs-4 me-1" />
                Simpan Draft
            </x-ui.button>
            <x-ui.button type="submit" variant="primary" formaction="{{ route('pesantren.profile.save') }}">
                <x-ui.icon name="check-circle" class="fs-4 me-1" />
                Simpan & Submit
            </x-ui.button>
        </div>
        </form>
    @endif

</x-ui.page>

@push('scripts')
<script>
const profileRequiredFields = @js($requiredProfileFields);
const profileExistingDocCount = {{ $filledDocCount }};
const profileMissingDocInputNames = @js(collect(array_merge($mainDocs, $secondaryDocs))->filter(fn ($doc) => blank($pesantren->{$doc['field']} ?? null))->keys()->values());
const profileBaseTotal = profileRequiredFields.length + 1 + {{ count($mainDocs) + count($secondaryDocs) }};

function updateProfileProgress() {
    const form = document.getElementById('profileForm');
    const progress = document.getElementById('profileProgress');
    if (!form || !progress) return;

    const checkedLayanan = [...form.querySelectorAll('.js-layanan:checked')];
    const total = profileBaseTotal + checkedLayanan.length;
    let filled = profileExistingDocCount;

    profileRequiredFields.forEach((name) => {
        const field = form.elements[name];
        if (field && String(field.value || '').trim() !== '') filled++;
    });

    if (checkedLayanan.length > 0) filled++;

    checkedLayanan.forEach((checkbox) => {
        const input = form.querySelector(`.js-rombel[data-unit="${CSS.escape(checkbox.value)}"] input`);
        if (Number(input?.value) > 0) filled++;
    });

    profileMissingDocInputNames.forEach((name) => {
        const input = form.elements[name];
        if (input?.files?.length > 0) filled++;
    });

    const pct = total > 0 ? Math.round((filled / total) * 100) : 0;
    progress.querySelector('.progress-bar')?.style.setProperty('width', `${pct}%`);
    progress.querySelector('.progress-bar')?.setAttribute('aria-valuenow', pct);
    progress.querySelector('.spm-progress-meta')?.replaceChildren(document.createTextNode(`${filled}/${total}`));
}

document.querySelectorAll('.js-layanan').forEach((checkbox) => {
    checkbox.addEventListener('change', () => {
        const row = document.querySelector(`.js-rombel[data-unit="${CSS.escape(checkbox.value)}"]`);
        const input = row?.querySelector('input');
        if (!row || !input) return;
        row.style.display = checkbox.checked ? '' : 'none';
        input.required = checkbox.checked;
        if (!checkbox.checked) input.value = '';
        updateProfileProgress();
    });
});

document.getElementById('profileForm')?.addEventListener('input', updateProfileProgress);
document.getElementById('profileForm')?.addEventListener('change', updateProfileProgress);
updateProfileProgress();
</script>
@endpush
@endsection
