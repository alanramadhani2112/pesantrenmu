@extends('layouts.app')

@section('content')
<x-ui.page-header title="Profil Asesor" subtitle="Kelola data profil, pengalaman, dan dokumen pendukung Anda.">
    <x-slot:toolbar>
        @if(!request('edit'))
            <a href="{{ route('asesor.profile', ['edit' => 1]) }}" class="btn btn-primary">
                <x-ui.icon name="pencil" class="fs-4 me-1" />
                Edit Profil
            </a>
        @else
            <a href="{{ route('asesor.profile') }}" class="btn btn-light">
                <x-ui.icon name="arrow-left" class="fs-4 me-1" />
                Batal Edit
            </a>
        @endif
    </x-slot:toolbar>
</x-ui.page-header>

{{-- Profile Photo Upload (inline, no Livewire) --}}
<x-ui.section-card title="Foto Profil" subtitle="Unggah foto profil akun Anda. Maksimal 2MB, format JPG/PNG." class="mb-6">
    <div class="p-6">
        <form method="POST" action="{{ route('profile.photo') }}" enctype="multipart/form-data"
            x-data="{
                preview: '{{ auth()->user()->profile_photo_path ? asset('storage/' . auth()->user()->profile_photo_path) : '' }}',
                changed: false,
                get isEmpty() { return !this.preview; },
            }">
            @csrf
            @method('PUT')

            <div class="image-input image-input-circle image-input-outline"
                :class="{ 'image-input-empty': isEmpty, 'image-input-changed': changed }"
                style="background-color: #f5f8fa;">

                <div class="image-input-wrapper w-125px h-125px"
                    :style="preview ? 'background-image:url(' + preview + ')' : ''">
                </div>

                <label class="btn btn-icon btn-circle btn-color-muted btn-active-color-primary w-25px h-25px bg-body shadow"
                    data-kt-image-input-action="change"
                    title="Ganti foto">
                    <i class="ki-solid ki-pencil fs-6"></i>
                    <input type="file" name="photo" accept=".png,.jpg,.jpeg"
                        @change="
                            const file = $event.target.files[0];
                            if (file) {
                                preview = URL.createObjectURL(file);
                                changed = true;
                            }
                        " />
                </label>

                <span class="btn btn-icon btn-circle btn-color-muted btn-active-color-primary w-25px h-25px bg-body shadow"
                    data-kt-image-input-action="cancel"
                    title="Batal"
                    @click="changed = false; preview = '{{ auth()->user()->profile_photo_path ? asset('storage/' . auth()->user()->profile_photo_path) : '' }}'; $el.closest('form').querySelector('input[type=file]').value = '';">
                    <i class="ki-solid ki-cross fs-3"></i>
                </span>
            </div>

            @error('photo')
                <div class="text-danger fs-8 mt-3">{{ $message }}</div>
            @enderror

            <div class="mt-4 d-flex gap-3" x-show="changed" x-cloak>
                <button type="submit" class="btn btn-sm btn-primary">Simpan Foto</button>
            </div>
        </form>

        @if(auth()->user()->profile_photo_path)
        <form method="POST" action="{{ route('profile.photo.remove') }}" class="mt-3">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-sm btn-light-danger">Hapus Foto</button>
        </form>
        @endif

        @if(session('status') === 'photo-updated')
            <div class="text-success fs-8 fw-semibold mt-3">Foto berhasil diperbarui.</div>
        @elseif(session('status') === 'photo-removed')
            <div class="text-success fs-8 fw-semibold mt-3">Foto berhasil dihapus.</div>
        @endif
    </div>
</x-ui.section-card>

@if(request('edit'))
{{-- ===== EDIT MODE ===== --}}
<div x-data="asesorProfileEdit()" x-init="init()">
    <form method="POST" action="{{ route('asesor.profile.update') }}" enctype="multipart/form-data"
          @submit.prevent="confirmSaveProfile($event)">
        @csrf

        @if($errors->any())
            <x-ui.alert variant="danger" title="Data profil belum valid" class="mb-6">
                <div class="mb-3">Periksa kembali isian yang ditandai sebelum menyimpan.</div>
                <ul class="mb-0 ps-4">
                    @foreach($errors->all() as $message)
                        <li>{{ $message }}</li>
                    @endforeach
                </ul>
            </x-ui.alert>
        @endif

        <div class="d-flex flex-column gap-6">

            {{-- A. Identitas --}}
            <x-ui.section-card title="A. Identitas Asesor" subtitle="Data pribadi, kontak, dan informasi pekerjaan.">
                <div class="p-6">
                    <div class="row g-6">
                        <div class="col-lg-12">
                            <div class="row g-5">
                                <div class="col-md-6">
                                    <x-ui.form-field label="Nama Lengkap (dengan Gelar)" required>
                                        <x-ui.input name="nama_dengan_gelar" :value="old('nama_dengan_gelar', $asesor->nama_dengan_gelar)" placeholder="Dr. Nama, M.Pd" />
                                        @error('nama_dengan_gelar') <div class="text-danger fs-8 mt-1">{{ $message }}</div> @enderror
                                    </x-ui.form-field>
                                </div>
                                <div class="col-md-6">
                                    <x-ui.form-field label="Nama Lengkap (Tanpa Gelar)" required>
                                        <x-ui.input name="nama_tanpa_gelar" :value="old('nama_tanpa_gelar', $asesor->nama_tanpa_gelar)" />
                                        @error('nama_tanpa_gelar') <div class="text-danger fs-8 mt-1">{{ $message }}</div> @enderror
                                    </x-ui.form-field>
                                </div>
                                <div class="col-md-6">
                                    <x-ui.form-field label="NBM / NIA">
                                        <x-ui.input name="nbm_nia" :value="old('nbm_nia', $asesor->nbm_nia)" />
                                    </x-ui.form-field>
                                </div>
                                <div class="col-md-6">
                                    <x-ui.form-field label="No. Induk Asesor PM">
                                        <x-ui.input name="nomor_induk_asesor_pm" :value="old('nomor_induk_asesor_pm', $asesor->nomor_induk_asesor_pm)" />
                                    </x-ui.form-field>
                                </div>
                                <div class="col-md-6">
                                    <x-ui.form-field label="No. WhatsApp">
                                        <x-ui.input name="whatsapp" :value="old('whatsapp', $asesor->whatsapp)" placeholder="08xxxxxxxxxx" />
                                    </x-ui.form-field>
                                </div>
                                <div class="col-md-6">
                                    <x-ui.form-field label="NIK">
                                        <x-ui.input name="nik" :value="old('nik', $asesor->nik)" />
                                    </x-ui.form-field>
                                </div>
                                <div class="col-md-6">
                                    <x-ui.form-field label="Tempat Lahir">
                                        <x-ui.input name="tempat_lahir" :value="old('tempat_lahir', $asesor->tempat_lahir)" />
                                    </x-ui.form-field>
                                </div>
                                <div class="col-md-6">
                                    <x-ui.form-field label="Tanggal Lahir">
                                        <x-ui.input type="date" name="tanggal_lahir" :value="old('tanggal_lahir', $asesor->tanggal_lahir)" />
                                    </x-ui.form-field>
                                </div>
                                <div class="col-md-6">
                                    <x-ui.form-field label="Jenis Kelamin">
                                        <select name="jenis_kelamin" class="form-select">
                                            <option value="">Pilih...</option>
                                            <option value="Laki-laki" @selected(old('jenis_kelamin', $asesor->jenis_kelamin) === 'Laki-laki')>Laki-laki</option>
                                            <option value="Perempuan" @selected(old('jenis_kelamin', $asesor->jenis_kelamin) === 'Perempuan')>Perempuan</option>
                                        </select>
                                    </x-ui.form-field>
                                </div>
                                <div class="col-md-6">
                                    <x-ui.form-field label="Email Pribadi">
                                        <x-ui.input type="email" name="email_pribadi" :value="old('email_pribadi', $asesor->email_pribadi)" />
                                        @error('email_pribadi') <div class="text-danger fs-8 mt-1">{{ $message }}</div> @enderror
                                    </x-ui.form-field>
                                </div>
                                <div class="col-12">
                                    <x-ui.form-field label="Alamat Rumah">
                                        <x-ui.textarea name="alamat_rumah" rows="2">{{ old('alamat_rumah', $asesor->alamat_rumah) }}</x-ui.textarea>
                                    </x-ui.form-field>
                                </div>
                                <div class="col-md-6">
                                    <x-ui.form-field label="Provinsi">
                                        <x-ui.input name="provinsi" :value="old('provinsi', $asesor->provinsi)" />
                                    </x-ui.form-field>
                                </div>
                                <div class="col-md-6">
                                    <x-ui.form-field label="Kota / Kabupaten">
                                        <x-ui.input name="kota_kabupaten" :value="old('kota_kabupaten', $asesor->kota_kabupaten)" />
                                    </x-ui.form-field>
                                </div>
                                <div class="col-md-6">
                                    <x-ui.form-field label="Status Perkawinan">
                                        <select name="status_perkawinan" class="form-select">
                                            <option value="">Pilih...</option>
                                            <option value="Belum Menikah" @selected(old('status_perkawinan', $asesor->status_perkawinan) === 'Belum Menikah')>Belum Menikah</option>
                                            <option value="Menikah" @selected(old('status_perkawinan', $asesor->status_perkawinan) === 'Menikah')>Menikah</option>
                                            <option value="Cerai" @selected(old('status_perkawinan', $asesor->status_perkawinan) === 'Cerai')>Cerai</option>
                                        </select>
                                    </x-ui.form-field>
                                </div>
                                <div class="col-md-6">
                                    <x-ui.form-field label="Profesi">
                                        <x-ui.input name="profesi" :value="old('profesi', $asesor->profesi)" />
                                    </x-ui.form-field>
                                </div>
                                <div class="col-md-6">
                                    <x-ui.form-field label="Pendidikan Terakhir">
                                        <select name="pendidikan_terakhir" class="form-select">
                                            <option value="">Pilih...</option>
                                            @foreach(['SMA/SMK', 'D3', 'S1', 'S2', 'S3'] as $jenjang)
                                                <option value="{{ $jenjang }}" @selected(old('pendidikan_terakhir', $asesor->pendidikan_terakhir) === $jenjang)>{{ $jenjang }}</option>
                                            @endforeach
                                        </select>
                                    </x-ui.form-field>
                                </div>
                                <div class="col-md-6">
                                    <x-ui.form-field label="Tahun Terbit Sertifikat">
                                        <x-ui.input name="tahun_terbit_sertifikat" :value="old('tahun_terbit_sertifikat', $asesor->tahun_terbit_sertifikat)" />
                                    </x-ui.form-field>
                                </div>
                                <div class="col-md-6">
                                    <x-ui.form-field label="Jabatan Utama">
                                        <x-ui.input name="jabatan_utama" :value="old('jabatan_utama', $asesor->jabatan_utama)" />
                                    </x-ui.form-field>
                                </div>
                                <div class="col-md-6">
                                    <x-ui.form-field label="Unit Tempat Kerja">
                                        <x-ui.input name="unit_kerja" :value="old('unit_kerja', $asesor->unit_kerja)" />
                                    </x-ui.form-field>
                                </div>
                                <div class="col-md-6">
                                    <x-ui.form-field label="No. Telp Kantor">
                                        <x-ui.input name="telp_kantor" :value="old('telp_kantor', $asesor->telp_kantor)" />
                                    </x-ui.form-field>
                                </div>
                                <div class="col-12">
                                    <x-ui.form-field label="Alamat Kantor">
                                        <x-ui.textarea name="alamat_kantor" rows="2">{{ old('alamat_kantor', $asesor->alamat_kantor) }}</x-ui.textarea>
                                    </x-ui.form-field>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </x-ui.section-card>

            {{-- B. Pengalaman --}}
            <x-ui.section-card title="B. Pengalaman & Rekam Jejak" subtitle="Riwayat pendidikan, pekerjaan, pelatihan, organisasi, dan publikasi.">
                <div class="p-6">
                    <div class="d-flex flex-column gap-8">

                        {{-- Riwayat Pendidikan --}}
                        <div>
                            <div class="d-flex align-items-center justify-content-between mb-4">
                                <div class="fw-semibold fs-6">Riwayat Pendidikan</div>
                                <button type="button" @click="addRow('riwayat_pendidikan', {dimana:'',kapan:'',jenjang:''})" class="btn btn-light btn-sm">
                                    <x-ui.icon name="plus" class="fs-5 me-1" /> Tambah
                                </button>
                            </div>
                            <template x-for="(item, index) in riwayat_pendidikan" :key="index">
                                <div class="row g-3 align-items-end mb-3 p-3 bg-light rounded">
                                    <div class="col-md-4">
                                        <label class="form-label fs-8">Institusi / Dimana</label>
                                        <input type="text" class="form-control" x-model="item.dimana" placeholder="Nama Sekolah/Univ">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fs-8">Tahun / Kapan</label>
                                        <input type="text" class="form-control" x-model="item.kapan" placeholder="2010-2014">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fs-8">Jenjang</label>
                                        <input type="text" class="form-control" x-model="item.jenjang" placeholder="S1/S2/S3">
                                    </div>
                                    <div class="col-md-2">
                                        <button type="button" @click="removeRow('riwayat_pendidikan', index)" class="btn btn-sm btn-light-danger w-100">
                                            <x-ui.icon name="trash" class="fs-5" />
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </div>

                        {{-- Pengalaman Pelatihan --}}
                        <div>
                            <div class="d-flex align-items-center justify-content-between mb-4">
                                <div class="fw-semibold fs-6">Pengalaman Pelatihan</div>
                                <button type="button" @click="addRow('pengalaman_pelatihan', {dimana:'',kapan:'',sebagai:''})" class="btn btn-light btn-sm">
                                    <x-ui.icon name="plus" class="fs-5 me-1" /> Tambah
                                </button>
                            </div>
                            <template x-for="(item, index) in pengalaman_pelatihan" :key="index">
                                <div class="row g-3 align-items-end mb-3 p-3 bg-light rounded">
                                    <div class="col-md-4">
                                        <label class="form-label fs-8">Lembaga / Dimana</label>
                                        <input type="text" class="form-control" x-model="item.dimana" placeholder="Nama lembaga">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fs-8">Tahun / Kapan</label>
                                        <input type="text" class="form-control" x-model="item.kapan" placeholder="2020">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fs-8">Sebagai / Peran</label>
                                        <input type="text" class="form-control" x-model="item.sebagai" placeholder="Peserta/Pemateri">
                                    </div>
                                    <div class="col-md-2">
                                        <button type="button" @click="removeRow('pengalaman_pelatihan', index)" class="btn btn-sm btn-light-danger w-100">
                                            <x-ui.icon name="trash" class="fs-5" />
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </div>

                        {{-- Pengalaman Bekerja --}}
                        <div>
                            <div class="d-flex align-items-center justify-content-between mb-4">
                                <div class="fw-semibold fs-6">Pengalaman Bekerja</div>
                                <button type="button" @click="addRow('pengalaman_bekerja', {dimana:'',kapan:'',sebagai:''})" class="btn btn-light btn-sm">
                                    <x-ui.icon name="plus" class="fs-5 me-1" /> Tambah
                                </button>
                            </div>
                            <template x-for="(item, index) in pengalaman_bekerja" :key="index">
                                <div class="row g-3 align-items-end mb-3 p-3 bg-light rounded">
                                    <div class="col-md-4">
                                        <label class="form-label fs-8">Perusahaan / Dimana</label>
                                        <input type="text" class="form-control" x-model="item.dimana" placeholder="Nama perusahaan">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fs-8">Tahun / Kapan</label>
                                        <input type="text" class="form-control" x-model="item.kapan" placeholder="2015-2020">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fs-8">Jabatan / Sebagai</label>
                                        <input type="text" class="form-control" x-model="item.sebagai" placeholder="Manager">
                                    </div>
                                    <div class="col-md-2">
                                        <button type="button" @click="removeRow('pengalaman_bekerja', index)" class="btn btn-sm btn-light-danger w-100">
                                            <x-ui.icon name="trash" class="fs-5" />
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </div>

                        {{-- Pengalaman Berorganisasi --}}
                        <div>
                            <div class="d-flex align-items-center justify-content-between mb-4">
                                <div class="fw-semibold fs-6">Pengalaman Berorganisasi</div>
                                <button type="button" @click="addRow('pengalaman_berorganisasi', {dimana:'',kapan:'',sebagai:''})" class="btn btn-light btn-sm">
                                    <x-ui.icon name="plus" class="fs-5 me-1" /> Tambah
                                </button>
                            </div>
                            <template x-for="(item, index) in pengalaman_berorganisasi" :key="index">
                                <div class="row g-3 align-items-end mb-3 p-3 bg-light rounded">
                                    <div class="col-md-4">
                                        <label class="form-label fs-8">Organisasi / Dimana</label>
                                        <input type="text" class="form-control" x-model="item.dimana" placeholder="Nama organisasi">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fs-8">Tahun / Kapan</label>
                                        <input type="text" class="form-control" x-model="item.kapan" placeholder="2018-2022">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fs-8">Jabatan / Sebagai</label>
                                        <input type="text" class="form-control" x-model="item.sebagai" placeholder="Ketua">
                                    </div>
                                    <div class="col-md-2">
                                        <button type="button" @click="removeRow('pengalaman_berorganisasi', index)" class="btn btn-sm btn-light-danger w-100">
                                            <x-ui.icon name="trash" class="fs-5" />
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </div>

                        {{-- Karya Publikasi --}}
                        <div>
                            <div class="d-flex align-items-center justify-content-between mb-4">
                                <div class="fw-semibold fs-6">Karya Publikasi</div>
                                <button type="button" @click="addRow('karya_publikasi', {judul:'',link:''})" class="btn btn-light btn-sm">
                                    <x-ui.icon name="plus" class="fs-5 me-1" /> Tambah
                                </button>
                            </div>
                            <template x-for="(item, index) in karya_publikasi" :key="index">
                                <div class="row g-3 align-items-end mb-3 p-3 bg-light rounded">
                                    <div class="col-md-5">
                                        <label class="form-label fs-8">Judul Karya</label>
                                        <input type="text" class="form-control" x-model="item.judul" placeholder="Judul publikasi">
                                    </div>
                                    <div class="col-md-5">
                                        <label class="form-label fs-8">Link (Opsional)</label>
                                        <input type="url" class="form-control" x-model="item.link" placeholder="https://...">
                                    </div>
                                    <div class="col-md-2">
                                        <button type="button" @click="removeRow('karya_publikasi', index)" class="btn btn-sm btn-light-danger w-100">
                                            <x-ui.icon name="trash" class="fs-5" />
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </div>

                    </div>
                </div>
            </x-ui.section-card>

            {{-- C. Dokumen --}}
            <x-ui.section-card title="C. Dokumen Pendukung" subtitle="Unggah berkas identitas dan sertifikasi.">
                <div class="p-6">
                    <div class="row g-6">
                        @php
                            $uploadDocs = [
                                'ktp_file_upload'       => ['label' => 'KTP / Identitas',    'existing' => $asesor->ktp_file],
                                'ijazah_file_upload'    => ['label' => 'Ijazah Terakhir',     'existing' => $asesor->ijazah_file],
                                'kartu_nbm_file_upload' => ['label' => 'Kartu NBM / NIA',     'existing' => $asesor->kartu_nbm_file],
                            ];
                        @endphp
                        @foreach($uploadDocs as $inputName => $doc)
                        <div class="col-md-4">
                            <x-ui.form-field :label="$doc['label']">
                                <label class="d-flex flex-column align-items-center justify-content-center border border-2 border-dashed rounded p-4 cursor-pointer hover-border-primary" style="min-height:120px;" x-data="{ fileName: '' }">
                                    <input type="file" name="{{ $inputName }}" accept="application/pdf,image/png,image/jpeg"
                                           class="d-none" @change="fileName = $event.target.files[0]?.name || ''">
                                    <template x-if="fileName">
                                        <div class="text-center">
                                            <x-ui.icon name="document" class="fs-2x text-success mb-2" />
                                            <span class="fs-8 fw-semibold text-success d-block" x-text="fileName"></span>
                                            <span class="fs-9 text-muted">Siap diunggah</span>
                                        </div>
                                    </template>
                                    <template x-if="!fileName">
                                        <div class="text-center">
                                            @if($doc['existing'])
                                                <x-ui.icon name="document" class="fs-2x text-primary mb-2" />
                                                <span class="fs-8 fw-semibold text-muted d-block">File terunggah</span>
                                                <span class="fs-9 text-primary">Klik untuk ganti</span>
                                            @else
                                                <x-ui.icon name="cloud-upload" class="fs-2x text-muted mb-2" />
                                                <span class="fs-8 text-muted d-block">Klik untuk unggah</span>
                                                <span class="fs-9 text-muted">PDF/JPG/PNG, maks 2MB</span>
                                            @endif
                                        </div>
                                    </template>
                                </label>
                                @error($inputName) <div class="text-danger fs-8 mt-1">{{ $message }}</div> @enderror
                            </x-ui.form-field>
                        </div>
                        @endforeach
                    </div>
                </div>
            </x-ui.section-card>

            {{-- D. Ganti Password --}}
            <x-ui.section-card title="D. Ganti Password" subtitle="Kosongkan jika tidak ingin mengubah password.">
                <div class="p-6">
                    <div class="row g-5">
                        <div class="col-md-6">
                            <x-ui.form-field label="Password Baru">
                                <x-ui.input type="password" name="password" placeholder="Minimal 8 karakter" />
                                @error('password') <div class="text-danger fs-8 mt-1">{{ $message }}</div> @enderror
                            </x-ui.form-field>
                        </div>
                    </div>
                </div>
            </x-ui.section-card>

            {{-- Hidden JSON fields for arrays --}}
            <input type="hidden" name="riwayat_pendidikan" :value="JSON.stringify(riwayat_pendidikan)">
            <input type="hidden" name="pengalaman_pelatihan" :value="JSON.stringify(pengalaman_pelatihan)">
            <input type="hidden" name="pengalaman_bekerja" :value="JSON.stringify(pengalaman_bekerja)">
            <input type="hidden" name="pengalaman_berorganisasi" :value="JSON.stringify(pengalaman_berorganisasi)">
            <input type="hidden" name="karya_publikasi" :value="JSON.stringify(karya_publikasi)">

            {{-- Save Bar --}}
            <div class="d-flex align-items-center justify-content-end gap-3 p-5 bg-light rounded">
                <a href="{{ route('asesor.profile') }}" class="btn btn-light">Batal</a>
                <button type="submit" class="btn btn-primary">
                    <x-ui.icon name="check" class="fs-5 me-1" /> Simpan Profil
                </button>
            </div>

        </div>
    </form>
</div>

@push('scripts')
<script>
function asesorProfileEdit() {
    return {
        riwayat_pendidikan: [],
        pengalaman_pelatihan: [],
        pengalaman_bekerja: [],
        pengalaman_berorganisasi: [],
        karya_publikasi: [],

        init() {
            this.riwayat_pendidikan = @json($asesor->riwayat_pendidikan ?? [['dimana' => '', 'kapan' => '', 'jenjang' => '']]);
            this.pengalaman_pelatihan = @json($asesor->pengalaman_pelatihan ?? [['dimana' => '', 'kapan' => '', 'sebagai' => '']]);
            this.pengalaman_bekerja = @json($asesor->pengalaman_bekerja ?? [['dimana' => '', 'kapan' => '', 'sebagai' => '']]);
            this.pengalaman_berorganisasi = @json($asesor->pengalaman_berorganisasi ?? [['dimana' => '', 'kapan' => '', 'sebagai' => '']]);

            let rawKarya = @json($asesor->karya_publikasi ?? []);
            if (!rawKarya.length) {
                this.karya_publikasi = [{judul: '', link: ''}];
            } else {
                this.karya_publikasi = rawKarya.map(k => typeof k === 'string' ? {judul: k, link: ''} : k);
            }
        },

        addRow(field, template) {
            this[field].push({...template});
        },

        removeRow(field, index) {
            this[field].splice(index, 1);
        },

        confirmSaveProfile(event) {
            window.SpmSwal.fire({
                title: 'Simpan Profil?',
                text: 'Pastikan data yang Anda isi sudah benar.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Ya, Simpan',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    event.target.submit();
                }
            });
        }
    };
}
</script>
@endpush

@else
{{-- ===== VIEW MODE ===== --}}
<div class="row g-6">
    {{-- Sidebar --}}
    <div class="col-xl-4">
        <div class="d-flex flex-column gap-6">
            <x-ui.card>
                <div class="d-flex flex-column align-items-center text-center">
                    <div class="mb-5">
                        @if(auth()->user()->profile_photo_path && Storage::disk('public')->exists(auth()->user()->profile_photo_path))
                            <img src="{{ Storage::url(auth()->user()->profile_photo_path) }}"
                                 alt="Foto asesor"
                                 loading="lazy"
                                 class="rounded-circle object-fit-cover border border-3 border-light shadow-sm"
                                 style="width:100px;height:100px;">
                        @else
                            <div class="spm-profile-avatar d-flex align-items-center justify-content-center">
                                {{ substr($asesor->nama_dengan_gelar ?? 'A', 0, 1) }}
                            </div>
                        @endif
                    </div>
                    <h2 class="spm-card-title fs-4 mb-1">{{ $asesor->nama_dengan_gelar ?: '-' }}</h2>
                    <div class="text-muted fw-semibold fs-8 text-uppercase mb-1">
                        NIA PM: {{ $asesor->nomor_induk_asesor_pm ?: '-' }}
                    </div>
                    <div class="text-muted fs-8 mb-4">
                        {{ $asesor->profesi ?: '' }}{{ ($asesor->profesi && $asesor->jabatan_utama) ? ' · ' : '' }}{{ $asesor->jabatan_utama ?: '' }}
                    </div>
                    <x-ui.status-badge variant="success">Asesor Aktif</x-ui.status-badge>
                </div>
            </x-ui.card>

            <x-ui.section-card title="Informasi Kontak">
                <div class="p-6">
                    <div class="d-flex flex-column gap-4">
                        <div class="d-flex align-items-center gap-3">
                            <x-ui.icon name="sms" class="fs-3 text-muted" />
                            <div>
                                <div class="fs-8 text-muted">Email</div>
                                <div class="fw-semibold fs-7">{{ auth()->user()->email ?: '-' }}</div>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-3">
                            <x-ui.icon name="whatsapp" class="fs-3 text-muted" />
                            <div>
                                <div class="fs-8 text-muted">WhatsApp</div>
                                <div class="fw-semibold fs-7">{{ $asesor->whatsapp ?: '-' }}</div>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-3">
                            <x-ui.icon name="geolocation" class="fs-3 text-muted" />
                            <div>
                                <div class="fs-8 text-muted">Alamat</div>
                                <div class="fw-semibold fs-7">{{ $asesor->alamat_rumah ?: '-' }}</div>
                                <div class="text-muted fs-8">{{ $asesor->kota_kabupaten }}{{ ($asesor->kota_kabupaten && $asesor->provinsi) ? ', ' : '' }}{{ $asesor->provinsi }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </x-ui.section-card>
        </div>
    </div>

    {{-- Main Content --}}
    <div class="col-xl-8">
        <div class="d-flex flex-column gap-6">

            {{-- A. Identitas --}}
            <x-ui.section-card title="A. Identitas Diri" subtitle="Data pribadi dan informasi pekerjaan.">
                <div class="p-6">
                    <div class="row g-4">
                        @php
                            $identityFields = [
                                'NBM / NIA' => $asesor->nbm_nia,
                                'NIK' => $asesor->nik,
                                'Tempat, Tgl Lahir' => ($asesor->tempat_lahir ? $asesor->tempat_lahir . ', ' : '') . ($asesor->tanggal_lahir ?: '-'),
                                'Jenis Kelamin' => $asesor->jenis_kelamin,
                                'Status Perkawinan' => $asesor->status_perkawinan,
                                'Pendidikan Terakhir' => $asesor->pendidikan_terakhir,
                                'Tahun Sertifikat' => $asesor->tahun_terbit_sertifikat,
                                'Unit Kerja' => $asesor->unit_kerja,
                                'No. Telp Kantor' => $asesor->telp_kantor,
                                'Alamat Kantor' => $asesor->alamat_kantor,
                            ];
                        @endphp
                        @foreach($identityFields as $label => $value)
                        <div class="col-md-6">
                            <div class="detail-item">
                                <div class="fs-8 text-muted mb-1">{{ $label }}</div>
                                <div class="fw-semibold fs-7">{{ $value ?: '-' }}</div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </x-ui.section-card>

            {{-- B. Pengalaman --}}
            <x-ui.section-card title="B. Pengalaman & Rekam Jejak" subtitle="Riwayat pendidikan, pekerjaan, dan aktivitas profesional.">
                <div class="p-6">
                    <div class="d-flex flex-column gap-6">

                        <div>
                            <div class="text-uppercase fw-semibold fs-8 text-muted mb-3">Riwayat Pendidikan</div>
                            @forelse(array_filter($asesor->riwayat_pendidikan ?? [], fn($i) => !empty($i['dimana'])) as $item)
                                <div class="d-flex align-items-center justify-content-between py-3 border-bottom border-dashed">
                                    <div class="d-flex align-items-center gap-3">
                                        <x-ui.badge variant="success">{{ $item['jenjang'] ?? '-' }}</x-ui.badge>
                                        <span class="fw-semibold fs-7">{{ $item['dimana'] }}</span>
                                    </div>
                                    <span class="text-muted fs-8">{{ $item['kapan'] ?? '-' }}</span>
                                </div>
                            @empty
                                <x-ui.empty-state title="Belum Ada Data" description="Riwayat pendidikan belum diisi." />
                            @endforelse
                        </div>

                        <div>
                            <div class="text-uppercase fw-semibold fs-8 text-muted mb-3">Pengalaman Bekerja</div>
                            @forelse(array_filter($asesor->pengalaman_bekerja ?? [], fn($i) => !empty($i['dimana'])) as $item)
                                <div class="d-flex align-items-center justify-content-between py-3 border-bottom border-dashed">
                                    <div>
                                        <div class="fw-semibold fs-7">{{ $item['sebagai'] ?? '-' }}</div>
                                        <div class="text-muted fs-8">{{ $item['dimana'] }}</div>
                                    </div>
                                    <span class="text-muted fs-8">{{ $item['kapan'] ?? '-' }}</span>
                                </div>
                            @empty
                                <x-ui.empty-state title="Belum Ada Data" description="Pengalaman bekerja belum diisi." />
                            @endforelse
                        </div>

                        <div class="row g-6">
                            <div class="col-lg-6">
                                <div class="text-uppercase fw-semibold fs-8 text-muted mb-3">Pelatihan</div>
                                @forelse(array_filter($asesor->pengalaman_pelatihan ?? [], fn($i) => !empty($i['dimana'])) as $item)
                                    <div class="py-3 border-bottom border-dashed">
                                        <div class="fw-semibold fs-7">{{ $item['sebagai'] ?? '-' }}</div>
                                        <div class="d-flex justify-content-between mt-1">
                                            <span class="text-muted fs-8 text-truncate" style="max-width:60%">{{ $item['dimana'] }}</span>
                                            <span class="text-muted fs-8">{{ $item['kapan'] ?? '-' }}</span>
                                        </div>
                                    </div>
                                @empty
                                    <x-ui.empty-state title="Belum Ada Data" description="Data pelatihan belum diisi." />
                                @endforelse
                            </div>
                            <div class="col-lg-6">
                                <div class="text-uppercase fw-semibold fs-8 text-muted mb-3">Organisasi</div>
                                @forelse(array_filter($asesor->pengalaman_berorganisasi ?? [], fn($i) => !empty($i['dimana'])) as $item)
                                    <div class="py-3 border-bottom border-dashed">
                                        <div class="fw-semibold fs-7">{{ $item['sebagai'] ?? '-' }}</div>
                                        <div class="d-flex justify-content-between mt-1">
                                            <span class="text-muted fs-8 text-truncate" style="max-width:60%">{{ $item['dimana'] }}</span>
                                            <span class="text-muted fs-8">{{ $item['kapan'] ?? '-' }}</span>
                                        </div>
                                    </div>
                                @empty
                                    <x-ui.empty-state title="Belum Ada Data" description="Data organisasi belum diisi." />
                                @endforelse
                            </div>
                        </div>

                        <div>
                            <div class="text-uppercase fw-semibold fs-8 text-muted mb-3">Karya Publikasi</div>
                            @forelse(array_filter($asesor->karya_publikasi ?? [], fn($i) => is_array($i) ? !empty($i['judul']) : !empty($i)) as $item)
                                <div class="d-flex align-items-center justify-content-between py-3 border-bottom border-dashed">
                                    <span class="fw-semibold fs-7 text-truncate pe-3">{{ is_array($item) ? $item['judul'] : $item }}</span>
                                    @if(is_array($item) && !empty($item['link']))
                                        <a href="{{ $item['link'] }}" target="_blank" class="btn btn-sm btn-light-primary">
                                            <x-ui.icon name="exit-right" class="fs-5" />
                                        </a>
                                    @endif
                                </div>
                            @empty
                                <x-ui.empty-state title="Belum Ada Data" description="Karya publikasi belum diisi." />
                            @endforelse
                        </div>

                    </div>
                </div>
            </x-ui.section-card>

            {{-- C. Dokumen --}}
            <x-ui.section-card title="C. Dokumen Pendukung" subtitle="Status unggahan berkas identitas dan sertifikasi.">
                <div class="p-6">
                    @php
                        $viewDocs = [
                            'ktp_file'       => 'KTP / Identitas',
                            'ijazah_file'    => 'Ijazah Terakhir',
                            'kartu_nbm_file' => 'Kartu NBM / NIA',
                        ];
                    @endphp
                    <div class="spm-document-list">
                        @foreach($viewDocs as $field => $label)
                            <x-ui.document-item
                                :label="$label"
                                :href="$asesor->$field ? route('secure.asesor-docs', ['asesorId' => $asesor->id, 'field' => $field]) : null"
                            />
                        @endforeach
                    </div>
                </div>
            </x-ui.section-card>

        </div>
    </div>
</div>
@endif

@endsection
