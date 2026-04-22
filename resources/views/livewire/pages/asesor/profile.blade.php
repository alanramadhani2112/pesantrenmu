<?php

use App\Models\Asesor;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;

new #[Layout('layouts.app')] class extends Component {
    use WithFileUploads;

    public $asesor;

    // Identitas Asesor
    public $foto_upload;
    public $nama_dengan_gelar;
    public $nama_tanpa_gelar;
    public $nbm_nia;
    public $nomor_induk_asesor_pm;
    public $whatsapp;
    public $nik;
    public $tempat_lahir;
    public $tanggal_lahir;
    public $jenis_kelamin;
    public $email_pribadi;
    public $alamat_rumah;
    public $provinsi;
    public $kota_kabupaten;
    public $status_perkawinan;
    public $unit_kerja;
    public $profesi;
    public $jabatan_utama;
    public $pendidikan_terakhir;
    public $alamat_kantor;
    public $telp_kantor;
    public $tahun_terbit_sertifikat;
    public $password;

    // Pengalaman (Arrays)
    public $riwayat_pendidikan = [];
    public $pengalaman_pelatihan = [];
    public $pengalaman_bekerja = [];
    public $pengalaman_berorganisasi = [];
    public $karya_publikasi = [];

    // Dokumen (Uploaded files)
    public $ktp_file_upload;
    public $ijazah_file_upload;
    public $kartu_nbm_file_upload;

    // Existing file paths
    public $existing_files = [];

    // Mode Edit
    public $isEditing = false;

    public function toggleEdit()
    {
        if ($this->isEditing) {
            $this->mount();
        }
        $this->isEditing = !$this->isEditing;
    }

    public function mount()
    {
        if (!auth()->user()->isAsesor()) {
            abort(403);
        }

        $asesorService = app(\App\Services\AsesorService::class);
        $this->asesor = $asesorService->getProfile(auth()->id());

        $this->foto_upload = null;
        $this->nama_dengan_gelar = $this->asesor->nama_dengan_gelar;
        $this->nama_tanpa_gelar = $this->asesor->nama_tanpa_gelar;
        $this->nbm_nia = $this->asesor->nbm_nia;
        $this->nomor_induk_asesor_pm = $this->asesor->nomor_induk_asesor_pm;
        $this->whatsapp = $this->asesor->whatsapp;
        $this->nik = $this->asesor->nik;
        $this->tempat_lahir = $this->asesor->tempat_lahir;
        $this->tanggal_lahir = $this->asesor->tanggal_lahir;
        $this->jenis_kelamin = $this->asesor->jenis_kelamin;
        $this->email_pribadi = $this->asesor->email_pribadi;
        $this->alamat_rumah = $this->asesor->alamat_rumah;
        $this->provinsi = $this->asesor->provinsi;
        $this->kota_kabupaten = $this->asesor->kota_kabupaten;
        $this->status_perkawinan = $this->asesor->status_perkawinan;
        $this->unit_kerja = $this->asesor->unit_kerja;
        $this->profesi = $this->asesor->profesi;
        $this->jabatan_utama = $this->asesor->jabatan_utama;
        $this->pendidikan_terakhir = $this->asesor->pendidikan_terakhir;
        $this->alamat_kantor = $this->asesor->alamat_kantor;
        $this->telp_kantor = $this->asesor->telp_kantor;
        $this->tahun_terbit_sertifikat = $this->asesor->tahun_terbit_sertifikat;

        $this->riwayat_pendidikan = $this->asesor->riwayat_pendidikan ?? [['dimana' => '', 'kapan' => '', 'jenjang' => '']];
        $this->pengalaman_pelatihan = $this->asesor->pengalaman_pelatihan ?? [['dimana' => '', 'kapan' => '', 'sebagai' => '']];
        $this->pengalaman_bekerja = $this->asesor->pengalaman_bekerja ?? [['dimana' => '', 'kapan' => '', 'sebagai' => '']];
        $this->pengalaman_berorganisasi = $this->asesor->pengalaman_berorganisasi ?? [['dimana' => '', 'kapan' => '', 'sebagai' => '']];
        
        $rawKarya = $this->asesor->karya_publikasi ?? [];
        $this->karya_publikasi = [];
        if (empty($rawKarya)) {
            $this->karya_publikasi = [['judul' => '', 'link' => '']];
        } else {
            foreach ($rawKarya as $karya) {
                if (is_array($karya)) {
                    $this->karya_publikasi[] = $karya;
                } else {
                    $this->karya_publikasi[] = ['judul' => $karya, 'link' => ''];
                }
            }
        }

        $this->existing_files = [
            'foto' => $this->asesor->foto,
            'ktp_file' => $this->asesor->ktp_file,
            'ijazah_file' => $this->asesor->ijazah_file,
            'kartu_nbm_file' => $this->asesor->kartu_nbm_file,
        ];
    }

    public function addRow($field)
    {
        if ($field == 'riwayat_pendidikan') {
            $this->riwayat_pendidikan[] = ['dimana' => '', 'kapan' => '', 'jenjang' => ''];
        } elseif ($field == 'pengalaman_pelatihan') {
            $this->pengalaman_pelatihan[] = ['dimana' => '', 'kapan' => '', 'sebagai' => ''];
        } elseif ($field == 'pengalaman_bekerja') {
            $this->pengalaman_bekerja[] = ['dimana' => '', 'kapan' => '', 'sebagai' => ''];
        } elseif ($field == 'pengalaman_berorganisasi') {
            $this->pengalaman_berorganisasi[] = ['dimana' => '', 'kapan' => '', 'sebagai' => ''];
        } elseif ($field == 'karya_publikasi') {
            $this->karya_publikasi[] = ['judul' => '', 'link' => ''];
        }
    }

    public function removeRow($field, $index)
    {
        unset($this->$field[$index]);
        $this->$field = array_values($this->$field);
    }

    protected function messages()
    {
        return [
            'required' => ':attribute wajib diisi.',
            'mimes' => ':attribute harus berformat PDF, JPG, JPEG, atau PNG.',
            'max' => 'Ukuran :attribute tidak boleh lebih dari :max KB (2MB).',
            'email' => 'Format :attribute tidak valid.',
            'uploaded' => ':attribute gagal diunggah. Kemungkinan file terlalu besar (Max 2MB) atau koneksi terputus.',
        ];
    }

    protected function validationAttributes()
    {
        return [
            'nama_dengan_gelar' => 'Nama dengan Gelar',
            'nama_tanpa_gelar' => 'Nama tanpa Gelar',
            'email_pribadi' => 'Email Pribadi',
            'ktp_file_upload' => 'File KTP',
            'ijazah_file_upload' => 'File Ijazah',
            'kartu_nbm_file_upload' => 'File Kartu NBM',
        ];
    }

    public function save()
    {
        $asesorService = app(\App\Services\AsesorService::class);
        
        $this->validate([
            'nama_dengan_gelar' => 'required|string|max:255',
            'nama_tanpa_gelar' => 'required|string|max:255',
            'email_pribadi' => 'nullable|email',
            'foto_upload' => 'nullable|image|max:1024',
            'ktp_file_upload' => 'nullable|mimes:pdf,jpg,jpeg,png|max:2048',
            'ijazah_file_upload' => 'nullable|mimes:pdf,jpg,jpeg,png|max:2048',
            'kartu_nbm_file_upload' => 'nullable|mimes:pdf,jpg,jpeg,png|max:2048',
            'password' => 'nullable|min:8',
        ]);

        $data = [
            'nama_dengan_gelar' => $this->nama_dengan_gelar,
            'nama_tanpa_gelar' => $this->nama_tanpa_gelar,
            'nbm_nia' => $this->nbm_nia,
            'nomor_induk_asesor_pm' => $this->nomor_induk_asesor_pm,
            'whatsapp' => $this->whatsapp,
            'nik' => $this->nik,
            'tempat_lahir' => $this->tempat_lahir,
            'tanggal_lahir' => $this->tanggal_lahir,
            'jenis_kelamin' => $this->jenis_kelamin,
            'email_pribadi' => $this->email_pribadi,
            'alamat_rumah' => $this->alamat_rumah,
            'provinsi' => $this->provinsi,
            'kota_kabupaten' => $this->kota_kabupaten,
            'status_perkawinan' => $this->status_perkawinan,
            'unit_kerja' => $this->unit_kerja,
            'profesi' => $this->profesi,
            'jabatan_utama' => $this->jabatan_utama,
            'pendidikan_terakhir' => $this->pendidikan_terakhir,
            'alamat_kantor' => $this->alamat_kantor,
            'telp_kantor' => $this->telp_kantor,
            'tahun_terbit_sertifikat' => $this->tahun_terbit_sertifikat,

            'riwayat_pendidikan' => $this->riwayat_pendidikan,
            'pengalaman_pelatihan' => $this->pengalaman_pelatihan,
            'pengalaman_bekerja' => $this->pengalaman_bekerja,
            'pengalaman_berorganisasi' => $this->pengalaman_berorganisasi,
            'karya_publikasi' => $this->karya_publikasi,
        ];

        $fileFields = [
            'foto' => 'foto_upload',
            'ktp_file' => 'ktp_file_upload',
            'ijazah_file' => 'ijazah_file_upload',
            'kartu_nbm_file' => 'kartu_nbm_file_upload',
        ];

        foreach ($fileFields as $dbField => $property) {
            if ($this->$property) {
                if ($this->asesor->$dbField) {
                    Storage::disk('public')->delete($this->asesor->$dbField);
                }
                $data[$dbField] = $this->$property->store('asesor_docs', 'public');
                $this->existing_files[$dbField] = $data[$dbField];
            }
        }

        $asesorService->updateProfile(auth()->id(), $data);

        // Update password if provided
        if ($this->password) {
            auth()->user()->update([
                'password' => \Illuminate\Support\Facades\Hash::make($this->password)
            ]);
            $this->password = null;
        }

        $this->dispatch(
            'notification-received',
            type: 'success',
            title: 'Berhasil!',
            message: 'Profil asesor berhasil diperbarui.'
        );
    }
}; ?>


<x-slot name="header">
    <div class="flex items-center justify-between">
        <h2 class="font-semibold text-gray-800 leading-tight">
            {{ __('Profil') }}
        </h2>
    </div>
</x-slot>

<div class="py-12" x-data="fileManagement()">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <!-- Header with Toggle Button -->
        <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
            <div>
                <h2 class="text-xl font-bold text-gray-800">Profil Asesor</h2>
                <p class="text-sm text-gray-500">Kelola informasi data diri dan pengalaman Anda.</p>
            </div>
            <div>
                <button wire:click="toggleEdit" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    {{ $isEditing ? 'Batal Edit' : 'Edit Profil' }}
                </button>
            </div>
        </div>

        @if($isEditing)
        <form x-data="asesorManagement" x-on:submit.prevent="confirmSaveProfile($wire)" class="space-y-8">
            <!-- Section A: DATA DIRI -->
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-xl border border-gray-100">
                <div class="p-8">
                    <div class="flex items-center gap-3 mb-8 border-b border-gray-100 pb-4">
                        <div class="p-2 bg-indigo-50 rounded-lg">
                            <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-800 uppercase tracking-wide">A. IDENTITAS ASESOR</h3>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                        <!-- Poto Profil -->
                        <div class="md:col-span-1 flex flex-col items-center">
                            <div class="relative group">
                                <div class="w-40 h-40 rounded-2xl overflow-hidden bg-gray-100 border-4 border-white shadow-lg group-hover:shadow-xl transition-all duration-300">
                                    @if($foto_upload)
                                    <img src="{{ $foto_upload->temporaryUrl() }}" class="w-full h-full object-cover">
                                    @elseif($existing_files['foto'])
                                    <img src="{{ Storage::url($existing_files['foto']) }}" class="w-full h-full object-cover">
                                    @else
                                    <div class="w-full h-full flex flex-col items-center justify-center text-gray-400">
                                        <svg class="w-12 h-12 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                        <span class="text-xs font-medium">Pas Foto</span>
                                    </div>
                                    @endif
                                </div>
                                <label class="absolute -bottom-2 -right-2 bg-indigo-600 p-2 rounded-xl text-white shadow-lg cursor-pointer hover:bg-indigo-700 transition-colors">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    <input type="file" wire:model="foto_upload" class="hidden" accept="image/*">
                                </label>
                            </div>
                            <p class="mt-4 text-xs text-gray-500 text-center italic font-medium">Format: JPG, PNG (Maks 1MB)</p>
                            <x-input-error :messages="$errors->get('foto_upload')" class="mt-1" />
                        </div>

                        <!-- Form Fields -->
                        <div class="md:col-span-3 grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <x-input-label for="nama_dengan_gelar" value="Nama Lengkap Asesor (dengan Gelar)" />
                                <x-text-input wire:model="nama_dengan_gelar" id="nama_dengan_gelar" type="text" class="mt-1 block w-full bg-gray-50 border-gray-200 focus:bg-white transition-all shadow-sm" placeholder="Contoh: Dr. Jaka Kelana, M.Pd" />
                                <x-input-error :messages="$errors->get('nama_dengan_gelar')" class="mt-1" />
                            </div>
                            <div>
                                <x-input-label for="nama_tanpa_gelar" value="Nama Lengkap Asesor (Tanpa Gelar)" />
                                <x-text-input wire:model="nama_tanpa_gelar" id="nama_tanpa_gelar" type="text" class="mt-1 block w-full bg-gray-50 border-gray-200 focus:bg-white transition-all shadow-sm" />
                                <x-input-error :messages="$errors->get('nama_tanpa_gelar')" class="mt-1" />
                            </div>
                            <div>
                                <x-input-label for="nbm_nia" value="NBM" />
                                <x-text-input wire:model="nbm_nia" id="nbm_nia" type="text" class="mt-1 block w-full bg-gray-50 border-gray-200 focus:bg-white transition-all shadow-sm" />
                            </div>
                            <div>
                                <x-input-label for="nomor_induk_asesor_pm" value="Nomor Induk Asesor PM" />
                                <x-text-input wire:model="nomor_induk_asesor_pm" id="nomor_induk_asesor_pm" type="text" class="mt-1 block w-full bg-gray-50 border-gray-200 focus:bg-white transition-all shadow-sm" />
                            </div>
                            <div>
                                <x-input-label for="whatsapp" value="Nomor WhatsApp" />
                                <x-text-input wire:model="whatsapp" id="whatsapp" type="text" class="mt-1 block w-full bg-gray-50 border-gray-200 focus:bg-white transition-all shadow-sm" placeholder="08xxxxxxxxx" />
                            </div>
                            <div>
                                <x-input-label for="nik" value="NIK / Nomor KTP" />
                                <x-text-input wire:model="nik" id="nik" type="text" class="mt-1 block w-full bg-gray-50 border-gray-200 focus:bg-white transition-all shadow-sm" />
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-6">
                        <div>
                            <x-input-label for="tempat_lahir" value="Tempat Lahir" />
                            <x-text-input wire:model="tempat_lahir" id="tempat_lahir" type="text" class="mt-1 block w-full bg-gray-50 border-gray-200 focus:bg-white transition-all shadow-sm" />
                        </div>
                        <div>
                            <x-input-label for="tanggal_lahir" value="Tanggal Lahir" />
                            <x-text-input wire:model="tanggal_lahir" id="tanggal_lahir" type="date" class="mt-1 block w-full bg-gray-50 border-gray-200 focus:bg-white transition-all shadow-sm" />
                        </div>
                        <div>
                            <x-input-label for="jenis_kelamin" value="Jenis Kelamin" />
                            <select wire:model="jenis_kelamin" id="jenis_kelamin" class="mt-1 block w-full border-gray-200 bg-gray-50 focus:bg-white transition-all shadow-sm focus:border-indigo-500 focus:ring-indigo-500 rounded-md">
                                <option value="">Pilih Jenis Kelamin</option>
                                <option value="Laki-Laki">Laki-Laki</option>
                                <option value="Perempuan">Perempuan</option>
                            </select>
                        </div>
                        <div>
                            <x-input-label for="email_pribadi" value="Email Pribadi" />
                            <x-text-input wire:model="email_pribadi" id="email_pribadi" type="email" class="mt-1 block w-full bg-gray-50 border-gray-200 focus:bg-white transition-all shadow-sm" />
                        </div>
                        <div>
                            <x-input-label for="status_perkawinan" value="Status Perkawinan" />
                            <select wire:model="status_perkawinan" id="status_perkawinan" class="mt-1 block w-full border-gray-200 bg-gray-50 focus:bg-white transition-all shadow-sm focus:border-indigo-500 focus:ring-indigo-500 rounded-md">
                                <option value="">Pilih Status</option>
                                <option value="Belum Kawin">Belum Kawin</option>
                                <option value="Kawin">Kawin</option>
                                <option value="Cerai Hidup">Cerai Hidup</option>
                                <option value="Cerai Mati">Cerai Mati</option>
                            </select>
                        </div>
                        <div>
                            <x-input-label for="pendidikan_terakhir" value="Pendidikan Terakhir" />
                            <x-text-input wire:model="pendidikan_terakhir" id="pendidikan_terakhir" type="text" class="mt-1 block w-full bg-gray-50 border-gray-200 focus:bg-white transition-all shadow-sm" placeholder="Contoh: S2 Pendidikan" />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                        <div class="md:col-span-2">
                            <x-input-label for="alamat_rumah" value="Alamat Rumah" />
                            <textarea wire:model="alamat_rumah" id="alamat_rumah" rows="2" class="mt-1 block w-full border-gray-200 bg-gray-50 focus:bg-white transition-all shadow-sm focus:border-indigo-500 focus:ring-indigo-500 rounded-md"></textarea>
                        </div>
                        <div>
                            <div x-data="wilayahSelector({
                                selectedProvinsiNama: @entangle('provinsi'),
                                selectedKabupatenNama: @entangle('kota_kabupaten')
                            })" class="grid grid-cols-1 md:grid-cols-2 gap-4 col-span-1 md:col-span-2">
                                <div class="relative">
                                    <x-input-label for="provinsi" value="Provinsi" />
                                    <div class="relative mt-1">
                                        <input type="text"
                                            x-model="provinsiSearch"
                                            placeholder="Cari Provinsi..."
                                            @focus="showProvinsiConfig = true"
                                            @click.outside="showProvinsiConfig = false"
                                            class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                        <div x-show="showProvinsiConfig && filteredProvinsi.length > 0" class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-md shadow-lg max-h-60 overflow-y-auto">
                                            <ul>
                                                <template x-for="item in filteredProvinsi" :key="item.kode">
                                                    <li @click="selectProvinsi(item)" class="px-4 py-2 hover:bg-indigo-50 cursor-pointer text-sm" x-text="item.nama"></li>
                                                </template>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                                <div class="relative">
                                    <x-input-label for="kota_kabupaten" value="Kota/Kabupaten" />
                                    <div class="relative mt-1">
                                        <input type="text"
                                            x-model="kabupatenSearch"
                                            placeholder="Cari Kota/Kabupaten..."
                                            @focus="showKabupatenConfig = true"
                                            @click.outside="showKabupatenConfig = false"
                                            class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm disabled:bg-gray-100"
                                            :disabled="!currentProvinsiKode">
                                        <div x-show="showKabupatenConfig && filteredKabupaten.length > 0" class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-md shadow-lg max-h-60 overflow-y-auto">
                                            <ul>
                                                <template x-for="item in filteredKabupaten" :key="item.kode">
                                                    <li @click="selectKabupaten(item)" class="px-4 py-2 hover:bg-indigo-50 cursor-pointer text-sm" x-text="item.nama"></li>
                                                </template>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6 pt-6 border-t border-gray-50">
                        <div>
                            <x-input-label for="unit_kerja" value="Unit Tempat Kerja" />
                            <x-text-input wire:model="unit_kerja" id="unit_kerja" type="text" class="mt-1 block w-full bg-gray-50 border-gray-200 focus:bg-white transition-all shadow-sm" />
                        </div>
                        <div>
                            <x-input-label for="profesi" value="Profesi" />
                            <select wire:model="profesi" id="profesi" class="mt-1 block w-full border-gray-200 bg-gray-50 focus:bg-white transition-all shadow-sm focus:border-indigo-500 focus:ring-indigo-500 rounded-md">
                                <option value="">Pilih Profesi</option>
                                <option value="Guru">Guru</option>
                                <option value="Pengawas">Pengawas</option>
                                <option value="Dosen">Dosen</option>
                                <option value="Kepala Sekolah">Kepala Sekolah</option>
                                <option value="Widyaiswara Pendidikan">Widyaiswara Pendidikan</option>
                                <option value="Widyaprada Kemendikdasmen">Widyaprada Kemendikdasmen</option>
                                <option value="Lainnya">Lainnya</option>
                            </select>
                        </div>
                        <div>
                            <x-input-label for="jabatan_utama" value="Jabatan Utama" />
                            <x-text-input wire:model="jabatan_utama" id="jabatan_utama" type="text" class="mt-1 block w-full bg-gray-50 border-gray-200 focus:bg-white transition-all shadow-sm" />
                        </div>
                        <div>
                            <x-input-label for="tahun_terbit_sertifikat" value="Tahun Terbit Sertifikat" />
                            <x-text-input wire:model="tahun_terbit_sertifikat" id="tahun_terbit_sertifikat" type="text" class="mt-1 block w-full bg-gray-50 border-gray-200 focus:bg-white transition-all shadow-sm" placeholder="Contoh: 2024" />
                        </div>
                        <div class="md:col-span-2">
                            <x-input-label for="alamat_kantor" value="Alamat Kantor" />
                            <textarea wire:model="alamat_kantor" id="alamat_kantor" rows="2" class="mt-1 block w-full border-gray-200 bg-gray-50 focus:bg-white transition-all shadow-sm focus:border-indigo-500 focus:ring-indigo-500 rounded-md"></textarea>
                        </div>
                        <div>
                            <x-input-label for="telp_kantor" value="No. Telp Kantor" />
                            <x-text-input wire:model="telp_kantor" id="telp_kantor" type="text" class="mt-1 block w-full bg-gray-50 border-gray-200 focus:bg-white transition-all shadow-sm" />
                        </div>
                        <div>
                            <x-input-label for="password" value="Password Baru (Kosongkan jika tidak ingin mengubah)" />
                            <x-text-input wire:model="password" id="password" type="password" class="mt-1 block w-full bg-gray-50 border-gray-200 focus:bg-white transition-all shadow-sm" />
                            <x-input-error :messages="$errors->get('password')" class="mt-1" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section B: PENGALAMAN -->
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-xl border border-gray-100">
                <div class="p-8">
                    <div class="flex items-center gap-3 mb-8 border-b border-gray-100 pb-4">
                        <div class="p-2 bg-emerald-50 rounded-lg">
                            <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-800 uppercase tracking-wide">B. PENGALAMAN</h3>
                    </div>

                    <div class="space-y-10">
                        <!-- 1. Riwayat Pendidikan -->
                        <div>
                            <div class="flex items-center justify-between mb-4">
                                <h4 class="text-md font-semibold text-gray-700 flex items-center gap-2">
                                    <span class="flex items-center justify-center w-6 h-6 rounded-full bg-emerald-600 text-white text-[10px]">1</span>
                                    Riwayat Pendidikan
                                </h4>
                                <button type="button" wire:click="addRow('riwayat_pendidikan')" class="text-xs font-bold text-emerald-600 hover:text-emerald-700 flex items-center gap-1 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                    </svg>
                                    Tambah Riwayat
                                </button>
                            </div>
                            <div class="space-y-3">
                                @foreach($riwayat_pendidikan as $index => $item)
                                <div class="grid grid-cols-1 md:grid-cols-10 gap-3 items-end bg-gray-50 p-3 rounded-lg border border-gray-100 relative group animate-fadeIn">
                                    <div class="md:col-span-4">
                                        <x-input-label value="Institusi / Dimana" class="text-[10px] text-gray-400" />
                                        <x-text-input wire:model="riwayat_pendidikan.{{ $index }}.dimana" type="text" class="block w-full text-sm py-1.5" placeholder="Nama Sekolah/Univ" />
                                    </div>
                                    <div class="md:col-span-2">
                                        <x-input-label value="Tahun / Kapan" class="text-[10px] text-gray-400" />
                                        <x-text-input wire:model="riwayat_pendidikan.{{ $index }}.kapan" type="text" class="block w-full text-sm py-1.5" placeholder="Contoh: 2010-2014" />
                                    </div>
                                    <div class="md:col-span-3">
                                        <x-input-label value="Jenjang" class="text-[10px] text-gray-400" />
                                        <x-text-input wire:model="riwayat_pendidikan.{{ $index }}.jenjang" type="text" class="block w-full text-sm py-1.5" placeholder="Contoh: S1 Teknik" />
                                    </div>
                                    <div class="md:col-span-1 flex justify-center">
                                        <button type="button" wire:click="removeRow('riwayat_pendidikan', {{ $index }})" class="p-2 text-red-500 hover:bg-red-50 rounded-lg transition-colors">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>

                        <!-- 2. Pengalaman Pelatihan -->
                        <div>
                            <div class="flex items-center justify-between mb-4">
                                <h4 class="text-md font-semibold text-gray-700 flex items-center gap-2">
                                    <span class="flex items-center justify-center w-6 h-6 rounded-full bg-emerald-600 text-white text-[10px]">2</span>
                                    Pengalaman Pelatihan
                                </h4>
                                <button type="button" wire:click="addRow('pengalaman_pelatihan')" class="text-xs font-bold text-emerald-600 hover:text-emerald-700 flex items-center gap-1 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                    </svg>
                                    Tambah Pelatihan
                                </button>
                            </div>
                            <div class="space-y-3">
                                @foreach($pengalaman_pelatihan as $index => $item)
                                <div class="grid grid-cols-1 md:grid-cols-10 gap-3 items-end bg-gray-50 p-3 rounded-lg border border-gray-100 animate-fadeIn">
                                    <div class="md:col-span-4">
                                        <x-input-label value="Penyelenggara / Dimana" class="text-[10px] text-gray-400" />
                                        <x-text-input wire:model="pengalaman_pelatihan.{{ $index }}.dimana" type="text" class="block w-full text-sm py-1.5" />
                                    </div>
                                    <div class="md:col-span-2">
                                        <x-input-label value="Tahun / Kapan" class="text-[10px] text-gray-400" />
                                        <x-text-input wire:model="pengalaman_pelatihan.{{ $index }}.kapan" type="text" class="block w-full text-sm py-1.5" />
                                    </div>
                                    <div class="md:col-span-3">
                                        <x-input-label value="Sebagai Apa / Peran" class="text-[10px] text-gray-400" />
                                        <x-text-input wire:model="pengalaman_pelatihan.{{ $index }}.sebagai" type="text" class="block w-full text-sm py-1.5" />
                                    </div>
                                    <div class="md:col-span-1 flex justify-center">
                                        <button type="button" wire:click="removeRow('pengalaman_pelatihan', {{ $index }})" class="p-2 text-red-500 hover:bg-red-50 rounded-lg transition-colors">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>

                        <!-- 3. Pengalaman Bekerja -->
                        <div>
                            <div class="flex items-center justify-between mb-4">
                                <h4 class="text-md font-semibold text-gray-700 flex items-center gap-2">
                                    <span class="flex items-center justify-center w-6 h-6 rounded-full bg-emerald-600 text-white text-[10px]">3</span>
                                    Pengalaman Bekerja
                                </h4>
                                <button type="button" wire:click="addRow('pengalaman_bekerja')" class="text-xs font-bold text-emerald-600 hover:text-emerald-700 flex items-center gap-1 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                    </svg>
                                    Tambah Pekerjaan
                                </button>
                            </div>
                            <div class="space-y-3">
                                @foreach($pengalaman_bekerja as $index => $item)
                                <div class="grid grid-cols-1 md:grid-cols-10 gap-3 items-end bg-gray-50 p-3 rounded-lg border border-gray-100 animate-fadeIn">
                                    <div class="md:col-span-4">
                                        <x-input-label value="Instansi / Dimana" class="text-[10px] text-gray-400" />
                                        <x-text-input wire:model="pengalaman_bekerja.{{ $index }}.dimana" type="text" class="block w-full text-sm py-1.5" />
                                    </div>
                                    <div class="md:col-span-2">
                                        <x-input-label value="Tahun / Kapan" class="text-[10px] text-gray-400" />
                                        <x-text-input wire:model="pengalaman_bekerja.{{ $index }}.kapan" type="text" class="block w-full text-sm py-1.5" />
                                    </div>
                                    <div class="md:col-span-3">
                                        <x-input-label value="Sebagai Apa / Jabatan" class="text-[10px] text-gray-400" />
                                        <x-text-input wire:model="pengalaman_bekerja.{{ $index }}.sebagai" type="text" class="block w-full text-sm py-1.5" />
                                    </div>
                                    <div class="md:col-span-1 flex justify-center">
                                        <button type="button" wire:click="removeRow('pengalaman_bekerja', {{ $index }})" class="p-2 text-red-500 hover:bg-red-50 rounded-lg transition-colors">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>

                        <!-- 4. Pengalaman Berorganisasi -->
                        <div>
                            <div class="flex items-center justify-between mb-4">
                                <h4 class="text-md font-semibold text-gray-700 flex items-center gap-2">
                                    <span class="flex items-center justify-center w-6 h-6 rounded-full bg-emerald-600 text-white text-[10px]">4</span>
                                    Pengalaman Berorganisasi
                                </h4>
                                <button type="button" wire:click="addRow('pengalaman_berorganisasi')" class="text-xs font-bold text-emerald-600 hover:text-emerald-700 flex items-center gap-1 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                    </svg>
                                    Tambah Organisasi
                                </button>
                            </div>
                            <div class="space-y-3">
                                @foreach($pengalaman_berorganisasi as $index => $item)
                                <div class="grid grid-cols-1 md:grid-cols-10 gap-3 items-end bg-gray-50 p-3 rounded-lg border border-gray-100 animate-fadeIn">
                                    <div class="md:col-span-4">
                                        <x-input-label value="Nama Organisasi / Lokasi" class="text-[10px] text-gray-400" />
                                        <x-text-input wire:model="pengalaman_berorganisasi.{{ $index }}.dimana" type="text" class="block w-full text-sm py-1.5" />
                                    </div>
                                    <div class="md:col-span-2">
                                        <x-input-label value="Tahun / Kapan" class="text-[10px] text-gray-400" />
                                        <x-text-input wire:model="pengalaman_berorganisasi.{{ $index }}.kapan" type="text" class="block w-full text-sm py-1.5" />
                                    </div>
                                    <div class="md:col-span-3">
                                        <x-input-label value="Sebagai Apa / Jabatan" class="text-[10px] text-gray-400" />
                                        <x-text-input wire:model="pengalaman_berorganisasi.{{ $index }}.sebagai" type="text" class="block w-full text-sm py-1.5" />
                                    </div>
                                    <div class="md:col-span-1 flex justify-center">
                                        <button type="button" wire:click="removeRow('pengalaman_berorganisasi', {{ $index }})" class="p-2 text-red-500 hover:bg-red-50 rounded-lg transition-colors">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>

                        <!-- 5. Karya Publikasi -->
                        <div>
                            <div class="flex items-center justify-between mb-4">
                                <h4 class="text-md font-semibold text-gray-700 flex items-center gap-2">
                                    <span class="flex items-center justify-center w-6 h-6 rounded-full bg-emerald-600 text-white text-[10px]">5</span>
                                    Karya Publikasi
                                </h4>
                                <button type="button" wire:click="addRow('karya_publikasi')" class="text-xs font-bold text-emerald-600 hover:text-emerald-700 flex items-center gap-1 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                    </svg>
                                    Tambah Karya
                                </button>
                            </div>
                            <div class="space-y-3">
                                @foreach($karya_publikasi as $index => $item)
                                <div class="grid grid-cols-1 md:grid-cols-10 gap-3 items-end bg-gray-50 p-3 rounded-lg border border-gray-100 animate-fadeIn">
                                    <div class="md:col-span-5">
                                        <x-input-label value="Judul Karya Publikasi" class="text-[10px] text-gray-400" />
                                        <x-text-input wire:model="karya_publikasi.{{ $index }}.judul" type="text" class="block w-full text-sm py-1.5" placeholder="Judul Buku / Jurnal / Artikel" />
                                    </div>
                                    <div class="md:col-span-4">
                                        <x-input-label value="Link Publikasi (URL)" class="text-[10px] text-gray-400" />
                                        <x-text-input wire:model="karya_publikasi.{{ $index }}.link" type="text" class="block w-full text-sm py-1.5" placeholder="https://..." />
                                    </div>
                                    <div class="md:col-span-1 flex justify-center">
                                        <button type="button" wire:click="removeRow('karya_publikasi', {{ $index }})" class="p-2 text-red-500 hover:bg-red-50 rounded-lg transition-colors">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section C: DOKUMEN -->
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-xl border border-gray-100">
                <div class="p-8">
                    <div class="flex items-center gap-3 mb-8 border-b border-gray-100 pb-4">
                        <div class="p-2 bg-amber-50 rounded-lg">
                            <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-800 uppercase tracking-wide">C. DOKUMEN</h3>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                        <!-- KTP -->
                        <div class="space-y-2">
                            <x-input-label value="Unggahan KTP" class="font-bold text-gray-700" />
                            <div class="flex flex-col gap-2">
                                <label class="flex flex-col items-center justify-center h-40 w-full border-2 border-dashed border-gray-200 rounded-xl hover:border-amber-400 hover:bg-amber-50 transition-all cursor-pointer group">
                                    @if($ktp_file_upload)
                                    @if(in_array($ktp_file_upload->getMimeType(), ['image/jpeg', 'image/png', 'image/jpg']))
                                    <img src="{{ $ktp_file_upload->temporaryUrl() }}" class="h-32 w-auto object-contain rounded-lg" alt="Preview KTP">
                                    @else
                                    <div class="flex flex-col items-center animate-fadeIn">
                                        <svg class="w-12 h-12 text-red-500 mb-2" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z" />
                                        </svg>
                                        <span class="text-[10px] text-gray-600 font-bold uppercase">{{ $ktp_file_upload->getClientOriginalName() }}</span>
                                        <span class="text-[9px] text-emerald-500 font-medium">Siap Diunggah</span>
                                    </div>
                                    @endif
                                    @elseif($existing_files['ktp_file'])
                                    <div class="flex flex-col items-center opacity-70 group-hover:opacity-100 transition-opacity">
                                        <svg class="w-12 h-12 text-indigo-500 mb-2" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z" />
                                        </svg>
                                        <span class="text-[10px] text-gray-500 font-bold uppercase">FILE_KTP_TERUNGGAH</span>
                                        <span class="text-[9px] text-indigo-400 font-medium">Klik untuk Ganti</span>
                                    </div>
                                    @else
                                    <svg class="w-8 h-8 text-gray-400 group-hover:text-amber-500 mb-2 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                    </svg>
                                    <span class="text-xs text-gray-500 group-hover:text-amber-600 font-medium text-center px-4">Klik untuk Unggah KTP</span>
                                    @endif
                                    <input type="file" wire:model="ktp_file_upload" class="hidden" accept="application/pdf,image/png,image/jpeg">
                                </label>
                                @if($existing_files['ktp_file'])
                                <a href="{{ Storage::url($existing_files['ktp_file']) }}" target="_blank" class="flex items-center gap-2 text-[10px] text-emerald-600 font-bold hover:underline justify-center mt-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                    LIHAT DOKUMEN SAAT INI
                                </a>
                                @endif
                                <x-input-error :messages="$errors->get('ktp_file_upload')" />
                            </div>
                        </div>

                        <!-- Ijazah -->
                        <div class="space-y-2">
                            <x-input-label value="Unggahan Ijazah Terakhir" class="font-bold text-gray-700" />
                            <div class="flex flex-col gap-2">
                                <label class="flex flex-col items-center justify-center h-40 w-full border-2 border-dashed border-gray-200 rounded-xl hover:border-amber-400 hover:bg-amber-50 transition-all cursor-pointer group">
                                    @if($ijazah_file_upload)
                                    @if(in_array($ijazah_file_upload->getMimeType(), ['image/jpeg', 'image/png', 'image/jpg']))
                                    <img src="{{ $ijazah_file_upload->temporaryUrl() }}" class="h-32 w-auto object-contain rounded-lg" alt="Preview Ijazah">
                                    @else
                                    <div class="flex flex-col items-center animate-fadeIn">
                                        <svg class="w-12 h-12 text-red-500 mb-2" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z" />
                                        </svg>
                                        <span class="text-[10px] text-gray-600 font-bold uppercase">{{ $ijazah_file_upload->getClientOriginalName() }}</span>
                                        <span class="text-[9px] text-emerald-500 font-medium">Siap Diunggah</span>
                                    </div>
                                    @endif
                                    @elseif($existing_files['ijazah_file'])
                                    <div class="flex flex-col items-center opacity-70 group-hover:opacity-100 transition-opacity">
                                        <svg class="w-12 h-12 text-indigo-500 mb-2" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z" />
                                        </svg>
                                        <span class="text-[10px] text-gray-500 font-bold uppercase">FILE_IJAZAH_TERUNGGAH</span>
                                        <span class="text-[9px] text-indigo-400 font-medium">Klik untuk Ganti</span>
                                    </div>
                                    @else
                                    <svg class="w-8 h-8 text-gray-400 group-hover:text-amber-500 mb-2 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                    </svg>
                                    <span class="text-xs text-gray-500 group-hover:text-amber-600 font-medium text-center px-4">Klik untuk Unggah Ijazah</span>
                                    @endif
                                    <input type="file" wire:model="ijazah_file_upload" class="hidden" accept="application/pdf,image/png,image/jpeg">
                                </label>
                                @if($existing_files['ijazah_file'])
                                <a href="{{ Storage::url($existing_files['ijazah_file']) }}" target="_blank" class="flex items-center gap-2 text-[10px] text-emerald-600 font-bold hover:underline justify-center mt-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                    LIHAT DOKUMEN SAAT INI
                                </a>
                                @endif
                                <x-input-error :messages="$errors->get('ijazah_file_upload')" />
                            </div>
                        </div>

                        <!-- Kartu NBM -->
                        <div class="space-y-2">
                            <x-input-label value="Unggahan Kartu NBM" class="font-bold text-gray-700" />
                            <div class="flex flex-col gap-2">
                                <label class="flex flex-col items-center justify-center h-40 w-full border-2 border-dashed border-gray-200 rounded-xl hover:border-amber-400 hover:bg-amber-50 transition-all cursor-pointer group">
                                    @if($kartu_nbm_file_upload)
                                    @if(in_array($kartu_nbm_file_upload->getMimeType(), ['image/jpeg', 'image/png', 'image/jpg']))
                                    <img src="{{ $kartu_nbm_file_upload->temporaryUrl() }}" class="h-32 w-auto object-contain rounded-lg" alt="Preview Kartu NBM">
                                    @else
                                    <div class="flex flex-col items-center animate-fadeIn">
                                        <svg class="w-12 h-12 text-red-500 mb-2" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z" />
                                        </svg>
                                        <span class="text-[10px] text-gray-600 font-bold uppercase">{{ $kartu_nbm_file_upload->getClientOriginalName() }}</span>
                                        <span class="text-[9px] text-emerald-500 font-medium">Siap Diunggah</span>
                                    </div>
                                    @endif
                                    @elseif($existing_files['kartu_nbm_file'])
                                    <div class="flex flex-col items-center opacity-70 group-hover:opacity-100 transition-opacity">
                                        <svg class="w-12 h-12 text-indigo-500 mb-2" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z" />
                                        </svg>
                                        <span class="text-[10px] text-gray-500 font-bold uppercase">FILE_NBM_TERUNGGAH</span>
                                        <span class="text-[9px] text-indigo-400 font-medium">Klik untuk Ganti</span>
                                    </div>
                                    @else
                                    <svg class="w-8 h-8 text-gray-400 group-hover:text-amber-500 mb-2 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                    </svg>
                                    <span class="text-xs text-gray-500 group-hover:text-amber-600 font-medium text-center px-4">Klik untuk Unggah Kartu NBM</span>
                                    @endif
                                    <input type="file" wire:model="kartu_nbm_file_upload" class="hidden" accept="application/pdf,image/png,image/jpeg">
                                </label>
                                @if($existing_files['kartu_nbm_file'])
                                <a href="{{ Storage::url($existing_files['kartu_nbm_file']) }}" target="_blank" class="flex items-center gap-2 text-[10px] text-emerald-600 font-bold hover:underline justify-center mt-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                    LIHAT DOKUMEN SAAT INI
                                </a>
                                @endif
                                <x-input-error :messages="$errors->get('kartu_nbm_file_upload')" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end gap-6 bg-gray-50 p-6 rounded-2xl border border-gray-100 shadow-inner">
                <button type="button" wire:click="toggleEdit"
                    class="px-6 py-3 rounded-2xl text-gray-500 text-[11px] font-bold uppercase tracking-[0.2em] hover:text-gray-700 transition-colors">
                    Batal
                </button>
                <button type="submit" wire:loading.attr="disabled"
                    class="px-10 py-3 rounded-2xl bg-gray-900 text-white text-[11px] font-black uppercase tracking-[0.2em] transition-all flex items-center justify-center gap-3 hover:bg-[#1e3a5f] active:scale-95 shadow-lg">
                    <svg wire:loading wire:target="save" class="animate-spin -ml-1 mr-1 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span wire:loading.remove wire:target="save">{{ __('SIMPAN PROFIL ASESOR') }}</span>
                    <span wire:loading wire:target="save">{{ __('Memproses...') }}</span>
                </button>
            </div>
        </form>
        @else
        <!-- View Mode Content -->
        <div class="space-y-8">
            <!-- Section A: Identitas -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-gray-100 p-8">
                <div class="flex items-center gap-3 mb-6 border-b border-gray-100 pb-4">
                    <div class="p-2 bg-indigo-50 rounded-lg">
                        <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-gray-800 uppercase tracking-wide">A. IDENTITAS ASESOR</h3>
                </div>
                <div class="flex flex-col md:flex-row gap-8">
                    <!-- Photo -->
                    <div class="flex-shrink-0">
                        <div class="w-32 h-32 rounded-2xl overflow-hidden bg-gray-100 border-4 border-white shadow-lg mx-auto md:mx-0">
                            @if($existing_files['foto'])
                            <img src="{{ Storage::url($existing_files['foto']) }}" class="w-full h-full object-cover">
                            @else
                            <div class="w-full h-full flex items-center justify-center text-gray-400">
                                <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            </div>
                            @endif
                        </div>
                    </div>
                    <!-- Details -->
                    <div class="flex-grow grid grid-cols-1 md:grid-cols-2 gap-y-4 gap-x-8">
                        <div>
                            <span class="block text-xs font-semibold text-gray-400 uppercase tracking-wider">Nama Lengkap (Gelar)</span>
                            <p class="text-gray-800 font-medium border-b border-gray-50 pb-1">{{ $nama_dengan_gelar ?: '-' }}</p>
                        </div>
                        <div>
                            <span class="block text-xs font-semibold text-gray-400 uppercase tracking-wider">Nama Lengkap (Tanpa Gelar)</span>
                            <p class="text-gray-800 font-medium border-b border-gray-50 pb-1">{{ $nama_tanpa_gelar ?: '-' }}</p>
                        </div>
                        <div>
                            <span class="block text-xs font-semibold text-gray-400 uppercase tracking-wider">NBM / NIA</span>
                            <p class="text-gray-800 font-medium border-b border-gray-50 pb-1">{{ $nbm_nia ?: '-' }} / {{ $nomor_induk_asesor_pm ?: '-' }}</p>
                        </div>
                        <div>
                            <span class="block text-xs font-semibold text-gray-400 uppercase tracking-wider">NIK</span>
                            <p class="text-gray-800 font-medium border-b border-gray-50 pb-1">{{ $nik ?: '-' }}</p>
                        </div>
                        <div>
                            <span class="block text-xs font-semibold text-gray-400 uppercase tracking-wider">TTL & Gender</span>
                            <p class="text-gray-800 font-medium border-b border-gray-50 pb-1">{{ $tempat_lahir ?: '-' }}, {{ $tanggal_lahir ?: '-' }} ({{ $jenis_kelamin ?: '-' }})</p>
                        </div>
                        <div>
                            <span class="block text-xs font-semibold text-gray-400 uppercase tracking-wider">Status Perkawinan</span>
                            <p class="text-gray-800 font-medium border-b border-gray-50 pb-1">{{ $status_perkawinan ?: '-' }}</p>
                        </div>
                        <div>
                            <span class="block text-xs font-semibold text-gray-400 uppercase tracking-wider">Email & WhatsApp</span>
                            <p class="text-gray-800 font-medium border-b border-gray-50 pb-1">{{ $email_pribadi ?: '-' }} | {{ $whatsapp ?: '-' }}</p>
                        </div>
                        <div>
                            <span class="block text-xs font-semibold text-gray-400 uppercase tracking-wider">Alamat Rumah</span>
                            <p class="text-gray-800 font-medium border-b border-gray-50 pb-1">{{ $alamat_rumah ?: '-' }}, {{ $kota_kabupaten?:'-' }}, {{ $provinsi?:'-' }}</p>
                        </div>
                        <div>
                            <span class="block text-xs font-semibold text-gray-400 uppercase tracking-wider">Unit Kerja & Profesi</span>
                            <p class="text-gray-800 font-medium border-b border-gray-50 pb-1">{{ $unit_kerja ?: '-' }} ({{ $profesi ?: '-' }})</p>
                        </div>
                        <div>
                            <span class="block text-xs font-semibold text-gray-400 uppercase tracking-wider">Jabatan Utama</span>
                            <p class="text-gray-800 font-medium border-b border-gray-50 pb-1">{{ $jabatan_utama ?: '-' }}</p>
                        </div>
                        <div>
                            <span class="block text-xs font-semibold text-gray-400 uppercase tracking-wider">Pendidikan Terakhir</span>
                            <p class="text-gray-800 font-medium border-b border-gray-50 pb-1">{{ $pendidikan_terakhir ?: '-' }}</p>
                        </div>
                        <div>
                            <span class="block text-xs font-semibold text-gray-400 uppercase tracking-wider">Thn Terbit Sertifikat</span>
                            <p class="text-gray-800 font-medium border-b border-gray-50 pb-1">{{ $tahun_terbit_sertifikat ?: '-' }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section B: Pengalaman -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-gray-100 p-8">
                <div class="flex items-center gap-3 mb-6 border-b border-gray-100 pb-4">
                    <div class="p-2 bg-emerald-50 rounded-lg">
                        <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-gray-800 uppercase tracking-wide">B. PENGALAMAN</h3>
                </div>

                <div class="space-y-8">
                    <!-- 1. Pendidikan -->
                    <div>
                        <h4 class="font-bold text-gray-700 mb-3 flex items-center gap-2"><span class="w-2 h-2 rounded-full bg-emerald-500"></span> Riwayat Pendidikan</h4>
                        @if(empty($riwayat_pendidikan) || (count($riwayat_pendidikan)==1 && empty($riwayat_pendidikan[0]['dimana'])))
                        <p class="text-gray-400 italic text-sm ml-4">Tidak ada data.</p>
                        @else
                        <div class="grid grid-cols-1 gap-3 ml-4">
                            @foreach($riwayat_pendidikan as $item)
                            @if(!empty($item['dimana']))
                            <div class="p-3 bg-gray-50 rounded-lg border border-gray-100">
                                <div class="font-bold text-gray-800">{{ $item['dimana'] }}</div>
                                <div class="text-sm text-gray-600 flex justify-between">
                                    <span>{{ $item['jenjang'] }}</span>
                                    <span class="font-mono text-emerald-600">{{ $item['kapan'] }}</span>
                                </div>
                            </div>
                            @endif
                            @endforeach
                        </div>
                        @endif
                    </div>

                    <!-- 2. Pelatihan -->
                    <div>
                        <h4 class="font-bold text-gray-700 mb-3 flex items-center gap-2"><span class="w-2 h-2 rounded-full bg-emerald-500"></span> Pengalaman Pelatihan</h4>
                        @if(empty($pengalaman_pelatihan) || (count($pengalaman_pelatihan)==1 && empty($pengalaman_pelatihan[0]['dimana'])))
                        <p class="text-gray-400 italic text-sm ml-4">Tidak ada data.</p>
                        @else
                        <div class="grid grid-cols-1 gap-3 ml-4">
                            @foreach($pengalaman_pelatihan as $item)
                            @if(!empty($item['dimana']))
                            <div class="p-3 bg-gray-50 rounded-lg border border-gray-100">
                                <div class="font-bold text-gray-800">{{ $item['dimana'] }}</div>
                                <div class="text-sm text-gray-600 flex justify-between">
                                    <span>{{ $item['sebagai'] }}</span>
                                    <span class="font-mono text-emerald-600">{{ $item['kapan'] }}</span>
                                </div>
                            </div>
                            @endif
                            @endforeach
                        </div>
                        @endif
                    </div>

                    <!-- 3. Bekerja -->
                    <div>
                        <h4 class="font-bold text-gray-700 mb-3 flex items-center gap-2"><span class="w-2 h-2 rounded-full bg-emerald-500"></span> Pengalaman Bekerja</h4>
                        @if(empty($pengalaman_bekerja) || (count($pengalaman_bekerja)==1 && empty($pengalaman_bekerja[0]['dimana'])))
                        <p class="text-gray-400 italic text-sm ml-4">Tidak ada data.</p>
                        @else
                        <div class="grid grid-cols-1 gap-3 ml-4">
                            @foreach($pengalaman_bekerja as $item)
                            @if(!empty($item['dimana']))
                            <div class="p-3 bg-gray-50 rounded-lg border border-gray-100">
                                <div class="font-bold text-gray-800">{{ $item['dimana'] }}</div>
                                <div class="text-sm text-gray-600 flex justify-between">
                                    <span>{{ $item['sebagai'] }}</span>
                                    <span class="font-mono text-emerald-600">{{ $item['kapan'] }}</span>
                                </div>
                            </div>
                            @endif
                            @endforeach
                        </div>
                        @endif
                    </div>

                    <!-- 4. Organisasi -->
                    <div>
                        <h4 class="font-bold text-gray-700 mb-3 flex items-center gap-2"><span class="w-2 h-2 rounded-full bg-emerald-500"></span> Pengalaman Berorganisasi</h4>
                        @if(empty($pengalaman_berorganisasi) || (count($pengalaman_berorganisasi)==1 && empty($pengalaman_berorganisasi[0]['dimana'])))
                        <p class="text-gray-400 italic text-sm ml-4">Tidak ada data.</p>
                        @else
                        <div class="grid grid-cols-1 gap-3 ml-4">
                            @foreach($pengalaman_berorganisasi as $item)
                            @if(!empty($item['dimana']))
                            <div class="p-3 bg-gray-50 rounded-lg border border-gray-100">
                                <div class="font-bold text-gray-800">{{ $item['dimana'] }}</div>
                                <div class="text-sm text-gray-600 flex justify-between">
                                    <span>{{ $item['sebagai'] }}</span>
                                    <span class="font-mono text-emerald-600">{{ $item['kapan'] }}</span>
                                </div>
                            </div>
                            @endif
                            @endforeach
                        </div>
                        @endif
                    </div>

                    <!-- 5. Karya Publikasi -->
                    <div>
                        <h4 class="font-bold text-gray-700 mb-3 flex items-center gap-2"><span class="w-2 h-2 rounded-full bg-emerald-500"></span> Karya Publikasi</h4>
                        @if(empty($karya_publikasi) || (count($karya_publikasi)==1 && empty($karya_publikasi[0]['judul'])))
                        <p class="text-gray-400 italic text-sm ml-4">Tidak ada data.</p>
                        @else
                        <div class="grid grid-cols-1 gap-3 ml-4">
                            @foreach($karya_publikasi as $item)
                            @if(!empty($item['judul']))
                            <div class="p-3 bg-gray-50 rounded-lg border border-gray-100 flex justify-between items-center">
                                <div class="font-bold text-gray-800 text-sm py-1">{{ $item['judul'] }}</div>
                                @if(!empty($item['link']))
                                <a href="{{ $item['link'] }}" target="_blank" class="text-xs bg-indigo-50 text-indigo-600 px-3 py-1 rounded-full hover:bg-indigo-100 transition-colors flex items-center gap-1">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                    </svg>
                                    Link
                                </a>
                                @endif
                            </div>
                            @endif
                            @endforeach
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Section C: Dokumen -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-gray-100 p-8">
                <div class="flex items-center gap-3 mb-6 border-b border-gray-100 pb-4">
                    <div class="p-2 bg-amber-50 rounded-lg">
                        <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-gray-800 uppercase tracking-wide">C. DOKUMEN</h3>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    @php
                    $docs = [
                    'ktp_file' => 'KTP',
                    'ijazah_file' => 'Ijazah Terakhir',
                    'kartu_nbm_file' => 'Kartu NBM'
                    ];
                    @endphp
                    @foreach($docs as $key => $label)
                    @if($existing_files[$key])
                    <a href="{{ Storage::url($existing_files[$key]) }}" target="_blank" class="flex items-center p-4 bg-gray-50 border border-gray-200 rounded-xl hover:bg-white hover:shadow-md transition-all group">
                        <div class="w-10 h-10 bg-red-100 text-red-600 rounded-lg flex items-center justify-center mr-3 group-hover:scale-110 transition-transform">
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zM6 20V4h7v5h5v11H6z" />
                            </svg>
                        </div>
                        <div>
                            <p class="font-bold text-gray-700 text-sm">{{ $label }}</p>
                            <p class="text-xs text-green-500 font-medium">Tersedia • Klik Lihat</p>
                        </div>
                    </a>
                    @endif
                    @endforeach
                </div>
                @if(!($existing_files['ktp_file'] || $existing_files['ijazah_file'] || $existing_files['kartu_nbm_file']))
                <p class="text-gray-400 italic text-center">Belum ada dokumen yang diunggah.</p>
                @endif
            </div>

        </div>
        @endif
    </div>
    <style>
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-fadeIn {
            animation: fadeIn 0.4s ease-out forwards;
        }
    </style>
</div>