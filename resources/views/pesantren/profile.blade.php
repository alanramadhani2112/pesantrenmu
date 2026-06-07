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
        'sertifikat_nsp_file' => ['label' => 'Sertifikat Nomor Statistik Pesantren (NSP)', 'field' => 'sertifikat_nsp'],
        'rk_anggaran_file' => ['label' => 'Rencana Kerja Anggaran Pesantren', 'field' => 'rk_anggaran'],
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
>
    <x-slot:toolbar>
        <x-ui.status-badge :variant="$isLocked ? 'warning' : 'success'">
            {{ $isLocked ? 'Terkunci' : 'Aktif' }}
        </x-ui.status-badge>
    </x-slot:toolbar>

    <div class="row g-5 mb-6">
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
        <x-ui.alert variant="warning" icon="shield-tick" title="Data Terkunci" class="mb-4">
            Data profil terkunci karena sedang dalam proses akreditasi.
        </x-ui.alert>
    @endif

    @if(session('success'))
        <x-ui.alert variant="success" title="Berhasil" class="mb-4">{{ session('success') }}</x-ui.alert>
    @endif
    @if(session('error'))
        <x-ui.alert variant="danger" title="Gagal" class="mb-4">{{ session('error') }}</x-ui.alert>
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
        <div class="p-6">
            <div class="row g-4">
                <div class="col-lg-6">
                    <label class="form-label fw-semibold required" for="nama_pesantren">Nama Pesantren</label>
                    <input type="text" name="nama_pesantren" id="nama_pesantren"
                        class="form-control form-control-solid @error('nama_pesantren') is-invalid @enderror"
                        value="{{ old('nama_pesantren', $pesantren->nama_pesantren) }}"
                        placeholder="Nama Pesantren" {{ $isLocked ? 'disabled' : '' }}>
                    @error('nama_pesantren') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-lg-6">
                    <label class="form-label fw-semibold required" for="ns_pesantren">Nomor Statistik Pesantren (NSP)</label>
                    <input type="text" name="ns_pesantren" id="ns_pesantren"
                        class="form-control form-control-solid @error('ns_pesantren') is-invalid @enderror"
                        value="{{ old('ns_pesantren', $pesantren->ns_pesantren) }}"
                        placeholder="NSP" {{ $isLocked ? 'disabled' : '' }}>
                    @error('ns_pesantren') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold required" for="alamat">Alamat Pesantren</label>
                    <textarea name="alamat" id="alamat" rows="3"
                        class="form-control form-control-solid @error('alamat') is-invalid @enderror"
                        placeholder="Alamat lengkap pesantren"
                        {{ $isLocked ? 'disabled' : '' }}>{{ old('alamat', $pesantren->alamat) }}</textarea>
                    @error('alamat') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-lg-6">
                    <label class="form-label fw-semibold" for="provinsi_kode">Provinsi</label>
                    <select name="provinsi_kode" id="provinsi_kode"
                        class="form-select form-select-solid @error('provinsi_kode') is-invalid @enderror"
                        {{ $isLocked ? 'disabled' : '' }}>
                        <option value="">Pilih Provinsi</option>
                        @foreach($provinsiMap as $kode => $nama)
                            <option value="{{ $kode }}" {{ old('provinsi_kode', $pesantren->provinsi_kode) == $kode ? 'selected' : '' }}>{{ $nama }}</option>
                        @endforeach
                    </select>
                    @error('provinsi_kode') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-lg-6">
                    <label class="form-label fw-semibold" for="kota_kabupaten">Kota / Kabupaten</label>
                    <input type="text" name="kota_kabupaten" id="kota_kabupaten"
                        class="form-control form-control-solid @error('kota_kabupaten') is-invalid @enderror"
                        value="{{ old('kota_kabupaten', $pesantren->kota_kabupaten) }}"
                        placeholder="Kota / Kabupaten" {{ $isLocked ? 'disabled' : '' }}>
                    @error('kota_kabupaten') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-lg-6">
                    <label class="form-label fw-semibold required" for="tahun_pendirian">Tahun Pendirian</label>
                    <input type="number" name="tahun_pendirian" id="tahun_pendirian"
                        class="form-control form-control-solid @error('tahun_pendirian') is-invalid @enderror"
                        value="{{ old('tahun_pendirian', $pesantren->tahun_pendirian) }}"
                        placeholder="Contoh: 1990" min="1900" max="{{ date('Y') }}"
                        {{ $isLocked ? 'disabled' : '' }}>
                    @error('tahun_pendirian') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-lg-6">
                    <label class="form-label fw-semibold" for="luas_tanah">Luas Tanah</label>
                    <input type="text" name="luas_tanah" id="luas_tanah"
                        class="form-control form-control-solid @error('luas_tanah') is-invalid @enderror"
                        value="{{ old('luas_tanah', $pesantren->luas_tanah) }}"
                        placeholder="Contoh: 5000 m2" {{ $isLocked ? 'disabled' : '' }}>
                    @error('luas_tanah') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-lg-6">
                    <label class="form-label fw-semibold" for="luas_bangunan">Luas Bangunan</label>
                    <input type="text" name="luas_bangunan" id="luas_bangunan"
                        class="form-control form-control-solid @error('luas_bangunan') is-invalid @enderror"
                        value="{{ old('luas_bangunan', $pesantren->luas_bangunan) }}"
                        placeholder="Contoh: 2000 m2" {{ $isLocked ? 'disabled' : '' }}>
                    @error('luas_bangunan') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>
        </div>
    </x-ui.section-card>

    {{-- KONTAK & PIMPINAN --}}
    <x-ui.section-card title="Kontak & Pimpinan" subtitle="Informasi mudir, kontak, dan persyarikatan." class="mb-6">
        <div class="p-6">
            <div class="row g-4">
                <div class="col-lg-6">
                    <label class="form-label fw-semibold" for="nama_mudir">Nama Mudir / Pimpinan</label>
                    <input type="text" name="nama_mudir" id="nama_mudir"
                        class="form-control form-control-solid @error('nama_mudir') is-invalid @enderror"
                        value="{{ old('nama_mudir', $pesantren->nama_mudir) }}"
                        placeholder="Nama Mudir" {{ $isLocked ? 'disabled' : '' }}>
                    @error('nama_mudir') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-lg-6">
                    <label class="form-label fw-semibold" for="jenjang_pendidikan_mudir">Jenjang Pendidikan Mudir</label>
                    <input type="text" name="jenjang_pendidikan_mudir" id="jenjang_pendidikan_mudir"
                        class="form-control form-control-solid @error('jenjang_pendidikan_mudir') is-invalid @enderror"
                        value="{{ old('jenjang_pendidikan_mudir', $pesantren->jenjang_pendidikan_mudir) }}"
                        placeholder="Contoh: S2" {{ $isLocked ? 'disabled' : '' }}>
                    @error('jenjang_pendidikan_mudir') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-lg-6">
                    <label class="form-label fw-semibold" for="telp_pesantren">Telepon Pesantren</label>
                    <input type="text" name="telp_pesantren" id="telp_pesantren"
                        class="form-control form-control-solid @error('telp_pesantren') is-invalid @enderror"
                        value="{{ old('telp_pesantren', $pesantren->telp_pesantren) }}"
                        placeholder="021-xxxxxx" {{ $isLocked ? 'disabled' : '' }}>
                    @error('telp_pesantren') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-lg-6">
                    <label class="form-label fw-semibold" for="hp_wa">No. HP / WhatsApp</label>
                    <input type="text" name="hp_wa" id="hp_wa"
                        class="form-control form-control-solid @error('hp_wa') is-invalid @enderror"
                        value="{{ old('hp_wa', $pesantren->hp_wa) }}"
                        placeholder="08xxxxxxxxxx" {{ $isLocked ? 'disabled' : '' }}>
                    @error('hp_wa') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-lg-6">
                    <label class="form-label fw-semibold" for="email_pesantren">Email Pesantren</label>
                    <input type="email" name="email_pesantren" id="email_pesantren"
                        class="form-control form-control-solid @error('email_pesantren') is-invalid @enderror"
                        value="{{ old('email_pesantren', $pesantren->email_pesantren) }}"
                        placeholder="pesantren@email.com" {{ $isLocked ? 'disabled' : '' }}>
                    @error('email_pesantren') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-lg-6">
                    <label class="form-label fw-semibold" for="persyarikatan">Persyarikatan</label>
                    <input type="text" name="persyarikatan" id="persyarikatan"
                        class="form-control form-control-solid @error('persyarikatan') is-invalid @enderror"
                        value="{{ old('persyarikatan', $pesantren->persyarikatan) }}"
                        placeholder="Contoh: Muhammadiyah" {{ $isLocked ? 'disabled' : '' }}>
                    @error('persyarikatan') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold" for="visi">Visi</label>
                    <textarea name="visi" id="visi" rows="3"
                        class="form-control form-control-solid @error('visi') is-invalid @enderror"
                        placeholder="Visi pesantren"
                        {{ $isLocked ? 'disabled' : '' }}>{{ old('visi', $pesantren->visi) }}</textarea>
                    @error('visi') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold" for="misi">Misi</label>
                    <textarea name="misi" id="misi" rows="3"
                        class="form-control form-control-solid @error('misi') is-invalid @enderror"
                        placeholder="Misi pesantren"
                        {{ $isLocked ? 'disabled' : '' }}>{{ old('misi', $pesantren->misi) }}</textarea>
                    @error('misi') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>
        </div>
    </x-ui.section-card>

    {{-- LAYANAN SATUAN PENDIDIKAN --}}
    <x-ui.section-card title="Layanan Satuan Pendidikan" subtitle="Pilih layanan satuan pendidikan yang tersedia." class="mb-6">
        <div class="p-6">
            @if($errors->has('layanan_satuan_pendidikan'))
                <div class="text-danger fw-semibold mb-3">{{ $errors->first('layanan_satuan_pendidikan') }}</div>
            @endif
            <div class="row g-3">
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
        <div class="p-6">
            <div class="row g-4">
                @foreach($layananOptions as $unitValue => $unitLabel)
                    @if(in_array($unitValue, $selectedLayanan))
                        @php
                            $rombel = old("units_data.{$unitValue}.jumlah_rombel", $pesantren->units()->where('unit', $unitValue)->first()?->jumlah_rombel ?? 0);
                        @endphp
                        <div class="col-lg-4 col-md-6">
                            <label class="form-label fw-semibold" for="units_data_{{ $unitValue }}_jumlah_rombel">{{ $unitLabel }}</label>
                            <input type="number" name="units_data[{{ $unitValue }}][jumlah_rombel]" id="units_data_{{ $unitValue }}_jumlah_rombel"
                                class="form-control form-control-solid @error('units_data.{{ $unitValue }}.jumlah_rombel') is-invalid @enderror"
                                value="{{ $rombel }}"
                                placeholder="Jumlah Rombel" min="0" max="9999"
                                {{ $isLocked ? 'disabled' : '' }}>
                            @error("units_data.{$unitValue}.jumlah_rombel") <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
    </x-ui.section-card>
    @endif

    {{-- DOKUMENTASI UTAMA --}}
    <x-ui.section-card title="Dokumentasi Pendukung Utama" subtitle="Unggah dokumen-dokumen utama pendukung profil pesantren." class="mb-6">
        <div class="p-6">
            <div class="row g-4">
                @foreach($mainDocs as $inputName => $doc)
                    @php
                        $existingPath = $pesantren->{$doc['field']} ?? null;
                        $hasFile = filled($existingPath);
                    @endphp
                    <div class="col-lg-6">
                        <label class="form-label fw-semibold" for="{{ $inputName }}">{{ $doc['label'] }}</label>
                        @if($hasFile)
                            <div class="d-flex align-items-center gap-3 mb-2">
                                <i class="ki-solid ki-file text-success fs-4"></i>
                                <div>
                                    <a href="{{ Storage::url($existingPath) }}" target="_blank" class="fw-semibold text-success fs-7">Lihat Dokumen</a>
                                </div>
                            </div>
                        @endif
                        @if(!$isLocked)
                            <input type="file" name="{{ $inputName }}" id="{{ $inputName }}"
                                class="form-control form-control-solid @error($inputName) is-invalid @enderror"
                                accept=".pdf,.jpg,.jpeg,.png">
                            <div class="text-muted fs-8 mt-1">Format: PDF, JPG, PNG. Maks 2MB.</div>
                            @error($inputName) <div class="invalid-feedback">{{ $message }}</div> @enderror
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </x-ui.section-card>

    {{-- DOKUMENTASI SEKUNDER --}}
    <x-ui.section-card title="Dokumentasi Pendukung Sekunder" subtitle="Unggah dokumen-dokumen tambahan pendukung profil." class="mb-6">
        <div class="p-6">
            <div class="row g-4">
                @foreach($secondaryDocs as $inputName => $doc)
                    @php
                        $existingPath = $pesantren->{$doc['field']} ?? null;
                        $hasFile = filled($existingPath);
                    @endphp
                    <div class="col-lg-6">
                        <label class="form-label fw-semibold" for="{{ $inputName }}">{{ $doc['label'] }}</label>
                        @if($hasFile)
                            <div class="d-flex align-items-center gap-3 mb-2">
                                <i class="ki-solid ki-file text-success fs-4"></i>
                                <div>
                                    <a href="{{ Storage::url($existingPath) }}" target="_blank" class="fw-semibold text-success fs-7">Lihat Dokumen</a>
                                </div>
                            </div>
                        @endif
                        @if(!$isLocked)
                            <input type="file" name="{{ $inputName }}" id="{{ $inputName }}"
                                class="form-control form-control-solid @error($inputName) is-invalid @enderror"
                                accept=".pdf,.jpg,.jpeg,.png">
                            <div class="text-muted fs-8 mt-1">Format: PDF, JPG, PNG. Maks 2MB.</div>
                            @error($inputName) <div class="invalid-feedback">{{ $message }}</div> @enderror
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </x-ui.section-card>

    {{-- SUBMIT BUTTONS --}}
    @if(!$isLocked)
        <div class="d-flex align-items-center justify-content-end gap-3 mb-6">
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