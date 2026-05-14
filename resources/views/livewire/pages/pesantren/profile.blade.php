<?php

use App\Models\Pesantren;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;

new #[Layout('layouts.app')] class extends Component {
    use WithFileUploads;

    public $pesantren;

    // Form fields
    public $nama_pesantren;
    public $ns_pesantren;
    public $alamat;
    public $kota_kabupaten;
    public $provinsi;
    public $provinsi_kode;
    public $kabupaten_kode;
    public $tahun_pendirian;
    public $nama_mudir;
    public $jenjang_pendidikan_mudir;
    public $telp_pesantren;
    public $hp_wa;
    public $email_pesantren;
    public $persyarikatan;
    public $visi;
    public $misi;
    public $luas_tanah;
    public $luas_bangunan;

    // DATA PESANTREN
    public $layanan_satuan_pendidikan = [];


    // Dynamic Units Data
    public $units_data = [];

    // DOKUMEN (Uploaded files)
    public $status_kepemilikan_tanah_file;
    public $sertifikat_nsp_file;
    public $rk_anggaran_file;
    public $silabus_rpp_file;
    public $peraturan_kepegawaian_file;
    public $file_lk_iapm_file;
    public $laporan_tahunan_file;

    // DOKUMEN SEKUNDER
    public $dok_profil_file;
    public $dok_nsp_file;
    public $dok_renstra_file;
    public $dok_rk_anggaran_file;
    public $dok_kurikulum_file;
    public $dok_silabus_rpp_file;
    public $dok_kepengasuhan_file;
    public $dok_peraturan_kepegawaian_file;
    public $dok_sarpras_file;
    public $dok_laporan_tahunan_file;
    public $dok_sop_file;

    // Document Definitions
    public $mainDocs = [];
    public $secondaryDocs = [];

    // Existing file paths
    public $existing_files = [];

    // Mode Edit
    public $isEditing = false;

    public function toggleEdit()
    {
        if ($this->pesantren->is_locked) {
            $this->dispatch('show-metronic-alert', type: 'error', title: 'Akses Ditolak', message: 'Data terkunci karena sedang dalam proses akreditasi.');
            return;
        }

        if ($this->isEditing) {
            $this->mount();
        }
        $this->isEditing = !$this->isEditing;
    }

    public function mount()
    {
        if (!auth()->user()->isPesantren()) {
            abort(403);
        }

        $pesantrenService = app(\App\Services\PesantrenService::class);
        $this->pesantren = $pesantrenService->getProfile(auth()->id());

        $this->nama_pesantren = $this->pesantren->nama_pesantren;
        $this->ns_pesantren = $this->pesantren->ns_pesantren;
        $this->alamat = $this->pesantren->alamat;
        $this->kota_kabupaten = $this->pesantren->kota_kabupaten;
        $this->provinsi = $this->pesantren->provinsi;
        $this->provinsi_kode = $this->pesantren->provinsi_kode;
        $this->kabupaten_kode = $this->pesantren->kabupaten_kode;
        $this->tahun_pendirian = $this->pesantren->tahun_pendirian;
        $this->nama_mudir = $this->pesantren->nama_mudir;
        $this->jenjang_pendidikan_mudir = $this->pesantren->jenjang_pendidikan_mudir;
        $this->telp_pesantren = $this->pesantren->telp_pesantren;
        $this->hp_wa = $this->pesantren->hp_wa;
        $this->email_pesantren = $this->pesantren->email_pesantren;
        $this->persyarikatan = $this->pesantren->persyarikatan;
        $this->visi = $this->pesantren->visi;
        $this->misi = $this->pesantren->misi;
        $this->luas_tanah = $this->pesantren->luas_tanah;
        $this->luas_bangunan = $this->pesantren->luas_bangunan;

        $this->layanan_satuan_pendidikan = is_array($this->pesantren->layanan_satuan_pendidikan) ? $this->pesantren->layanan_satuan_pendidikan : [];
        // Initialize units_data
        foreach (['sd', 'mi', 'smp', 'mts', 'sma', 'ma', 'smk', 'satuan_pesantren_muadalah_(SPM)'] as $unit) {
            $this->units_data[$unit] = [
                'jumlah_rombel' => 0
            ];
        }

        // Load existing units data
        foreach ($this->pesantren->units as $unit) {
            if (isset($this->units_data[$unit->unit])) {
                $this->units_data[$unit->unit] = [
                    'jumlah_rombel' => $unit->jumlah_rombel,
                ];
            }
        }

        // Initialize Document Definitions
        $this->mainDocs = [
            'status_kepemilikan_tanah_file' => 'Status Kepemilikan Tanah',
            'sertifikat_nsp_file' => 'Sertifikat Nomor Statistik Pesantren (NSP)',
            'rk_anggaran_file' => 'Rencana Kerja Anggaran Pesantren',
            'silabus_rpp_file' => 'Silabus dan RPP (Dirosah Islamiyah)',
            'peraturan_kepegawaian_file' => 'Peraturan Kepegawaian',
            'file_lk_iapm_file' => 'File Lembar Kerja (LK) Penilaian IAPM2025',
            'laporan_tahunan_file' => 'Laporan Tahunan Pesantren',
        ];

        $this->secondaryDocs = [
            'dok_profil_file' => 'Dokumen Profil Pesantren',
            'dok_nsp_file' => 'Dokumen Sertifikat NSP',
            'dok_renstra_file' => 'Dokumen Renstra Pesantren',
            'dok_rk_anggaran_file' => 'Dokumen Rencana Kerja Anggaran Pesantren',
            'dok_kurikulum_file' => 'Dokumen Kurikulum Pesantren',
            'dok_silabus_rpp_file' => 'Dokumen Silabus dan RPP',
            'dok_kepengasuhan_file' => 'Dokumen Panduan Kepengasuhan Pesantren',
            'dok_peraturan_kepegawaian_file' => 'Dokumen Peraturan Kepegawaian',
            'dok_sarpras_file' => 'Dokumen Sarana dan Prasarana Pesantren',
            'dok_laporan_tahunan_file' => 'Dokumen Laporan Tahunan Pesantren',
            'dok_sop_file' => 'Dokumen SOP Pesantren',
        ];

        // Store existing file paths
        $fileFields = [
            'status_kepemilikan_tanah',
            'sertifikat_nsp',
            'rk_anggaran',
            'silabus_rpp',
            'peraturan_kepegawaian',
            'file_lk_iapm',
            'laporan_tahunan',
            'dok_profil',
            'dok_nsp',
            'dok_renstra',
            'dok_rk_anggaran',
            'dok_kurikulum',
            'dok_silabus_rpp',
            'dok_kepengasuhan',
            'dok_peraturan_kepegawaian',
            'dok_sarpras',
            'dok_laporan_tahunan',
            'dok_sop'
        ];

        foreach ($fileFields as $field) {
            $this->existing_files[$field] = $this->pesantren->$field;
        }
    }

    protected function messages()
    {
        return [
            'required' => ':attribute wajib diisi.',
            'mimes' => ':attribute harus berformat PDF.',
            'max' => 'Ukuran :attribute tidak boleh lebih dari :max KB (2MB).',
            'email' => 'Format :attribute tidak valid.',
            'integer' => ':attribute harus berupa angka.',
            'min' => ':attribute minimal :min.',
            'uploaded' => ':attribute gagal diunggah. Kemungkinan file terlalu besar (Max 2MB) atau koneksi terputus.',
        ];
    }

    protected function validationAttributes()
    {
        return [
            'nama_pesantren' => 'Nama Pesantren',
            'email_pesantren' => 'Email Pesantren',
            'units_data.*.jumlah_rombel' => 'Jumlah Rombel',
            'status_kepemilikan_tanah_file' => 'File Status Kepemilikan Tanah',
            'sertifikat_nsp_file' => 'File Sertifikat NSP',
            'rk_anggaran_file' => 'File RK Anggaran',
            'silabus_rpp_file' => 'File Silabus dan RPP',
            'peraturan_kepegawaian_file' => 'File Peraturan Kepegawaian',
            'file_lk_iapm_file' => 'File LK IAPM',
            'laporan_tahunan_file' => 'File Laporan Tahunan',
            'dok_profil_file' => 'File Dokumen Profil',
            'dok_nsp_file' => 'File Dokumen NSP',
            'dok_renstra_file' => 'File Dokumen Renstra',
            'dok_rk_anggaran_file' => 'File Dokumen RK Anggaran',
            'dok_kurikulum_file' => 'File Dokumen Kurikulum',
            'dok_silabus_rpp_file' => 'File Dokumen Silabus dan RPP',
            'dok_kepengasuhan_file' => 'File Dokumen Kepengasuhan',
            'dok_peraturan_kepegawaian_file' => 'File Dokumen Peraturan Kepegawaian',
            'dok_sarpras_file' => 'File Dokumen Sarpras',
            'dok_laporan_tahunan_file' => 'File Dokumen Laporan Tahunan',
            'dok_sop_file' => 'File Dokumen SOP',
        ];
    }

    public function save()
    {
        $this->validate([
            'nama_pesantren' => 'required|string|max:255',
            'email_pesantren' => 'nullable|email',
            'layanan_satuan_pendidikan' => 'array',

            // Dynamic units validation
            'units_data' => 'array',
            'units_data.*.jumlah_rombel' => 'required_with:units_data|integer|min:0',
            'luas_tanah' => 'nullable|string',
            'luas_bangunan' => 'nullable|string',

            // File validations
            'status_kepemilikan_tanah_file' => 'nullable|mimes:pdf,jpg,jpeg,png|max:2048',
            'sertifikat_nsp_file' => 'nullable|mimes:pdf,jpg,jpeg,png|max:2048',
            'rk_anggaran_file' => 'nullable|mimes:pdf,jpg,jpeg,png|max:2048',
            'silabus_rpp_file' => 'nullable|mimes:pdf,jpg,jpeg,png|max:2048',
            'peraturan_kepegawaian_file' => 'nullable|mimes:pdf,jpg,jpeg,png|max:2048',
            'file_lk_iapm_file' => 'nullable|mimes:pdf,jpg,jpeg,png|max:2048',
            'laporan_tahunan_file' => 'nullable|mimes:pdf,jpg,jpeg,png|max:2048',
            'dok_profil_file' => 'nullable|mimes:pdf,jpg,jpeg,png|max:2048',
            'dok_nsp_file' => 'nullable|mimes:pdf,jpg,jpeg,png|max:2048',
            'dok_renstra_file' => 'nullable|mimes:pdf,jpg,jpeg,png|max:2048',
            'dok_rk_anggaran_file' => 'nullable|mimes:pdf,jpg,jpeg,png|max:2048',
            'dok_kurikulum_file' => 'nullable|mimes:pdf,jpg,jpeg,png|max:2048',
            'dok_silabus_rpp_file' => 'nullable|mimes:pdf,jpg,jpeg,png|max:2048',
            'dok_kepengasuhan_file' => 'nullable|mimes:pdf,jpg,jpeg,png|max:2048',
            'dok_peraturan_kepegawaian_file' => 'nullable|mimes:pdf,jpg,jpeg,png|max:2048',
            'dok_sarpras_file' => 'nullable|mimes:pdf,jpg,jpeg,png|max:2048',
            'dok_laporan_tahunan_file' => 'nullable|mimes:pdf,jpg,jpeg,png|max:2048',
            'dok_sop_file' => 'nullable|mimes:pdf,jpg,jpeg,png|max:2048',
        ]);

        $data = [
            'nama_pesantren' => $this->nama_pesantren,
            'ns_pesantren' => $this->ns_pesantren,
            'alamat' => $this->alamat,
            'kota_kabupaten' => $this->kota_kabupaten,
            'provinsi' => $this->provinsi,
            'provinsi_kode' => $this->provinsi_kode,
            'kabupaten_kode' => $this->kabupaten_kode,
            'tahun_pendirian' => $this->tahun_pendirian,
            'nama_mudir' => $this->nama_mudir,
            'jenjang_pendidikan_mudir' => $this->jenjang_pendidikan_mudir,
            'telp_pesantren' => $this->telp_pesantren,
            'hp_wa' => $this->hp_wa,
            'email_pesantren' => $this->email_pesantren,
            'persyarikatan' => $this->persyarikatan,
            'visi' => $this->visi,
            'misi' => $this->misi,
            'luas_tanah' => $this->luas_tanah,
            'luas_bangunan' => $this->luas_bangunan,
            'layanan_satuan_pendidikan' => $this->layanan_satuan_pendidikan,
        ];

        // Handle file uploads
        $fileFields = [
            'status_kepemilikan_tanah' => 'status_kepemilikan_tanah_file',
            'sertifikat_nsp' => 'sertifikat_nsp_file',
            'rk_anggaran' => 'rk_anggaran_file',
            'silabus_rpp' => 'silabus_rpp_file',
            'peraturan_kepegawaian' => 'peraturan_kepegawaian_file',
            'file_lk_iapm' => 'file_lk_iapm_file',
            'laporan_tahunan' => 'laporan_tahunan_file',
            'dok_profil' => 'dok_profil_file',
            'dok_nsp' => 'dok_nsp_file',
            'dok_renstra' => 'dok_renstra_file',
            'dok_rk_anggaran' => 'dok_rk_anggaran_file',
            'dok_kurikulum' => 'dok_kurikulum_file',
            'dok_silabus_rpp' => 'dok_silabus_rpp_file',
            'dok_kepengasuhan' => 'dok_kepengasuhan_file',
            'dok_peraturan_kepegawaian' => 'dok_peraturan_kepegawaian_file',
            'dok_sarpras' => 'dok_sarpras_file',
            'dok_laporan_tahunan' => 'dok_laporan_tahunan_file',
            'dok_sop' => 'dok_sop_file',
        ];

        foreach ($fileFields as $dbField => $property) {
            if ($this->$property) {
                // Delete old file if exists
                if ($this->pesantren->$dbField) {
                    Storage::disk('public')->delete($this->pesantren->$dbField);
                }
                $data[$dbField] = $this->$property->store('pesantren_docs', 'public');
                $this->existing_files[$dbField] = $data[$dbField];
            }
        }

        $unitsData = [];
        foreach ($this->layanan_satuan_pendidikan as $unitName) {
            $unitsData[] = [
                'unit' => $unitName,
                'jumlah_rombel' => $this->units_data[$unitName]['jumlah_rombel'] ?? 0,
            ];
        }

        $pesantrenService = app(\App\Services\PesantrenService::class);
        if ($pesantrenService->updateProfile(auth()->id(), $data, $unitsData)) {
            $this->dispatch('notification-received', type: 'success', title: 'Berhasil!', message: 'Profil pesantren berhasil diperbarui.');
            $this->isEditing = false;
            $this->mount();
        }
    }
}; ?>

<div class="py-12" x-data="fileManagement()">
    <x-slot name="header">{{ __('Profil Pesantren') }}</x-slot>
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        @if($pesantren->is_locked)
        <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M13.477 14.89A6 6 0 015.11 6.524l8.367 8.368zm1.414-1.414L6.524 5.11a6 6 0 018.367 8.367zM18 10a8 8 0 11-16 0 8 8 0 0116 0z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-red-700">
                        <span class="font-bold">DATA TERKUNCI!</span> Data profil tidak dapat diubah karena sedang dalam proses akreditasi.
                        Hubungi admin jika Anda perlu melakukan perubahan mendesak.
                    </p>
                </div>
            </div>
        </div>
        @endif

        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900">
                <!-- Header with Toggle Button -->
                <div class="flex flex-col md:flex-row justify-between items-center mb-6 border-b pb-4 gap-4">
                    <div>
                        <h2 class="text-xl font-bold text-gray-800">Profil Pesantren</h2>
                        <p class="text-sm text-gray-500">Kelola informasi data pesantren Anda.</p>
                    </div>
                    <div>
                        <button wire:click="toggleEdit" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest transition duration-150">
                            {{ $isEditing ? 'Batal Edit' : 'Edit Profil' }}
                        </button>
                    </div>
                </div>

                @if($isEditing)
                <form @submit.prevent="confirmSave($wire)">
                    <!-- Section: Profil -->
                    <div class="mb-8 border-b pb-4">
                        <h3 class="text-lg font-bold mb-4 text-indigo-600">PROFIL PESANTREN</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="nama_pesantren" value="Nama Pesantren" />
                                <x-text-input wire:model="nama_pesantren" id="nama_pesantren" type="text" class="mt-1 block w-full" />
                            </div>
                            <div>
                                <x-input-label for="ns_pesantren" value="Nomor Statistik Pesantren" />
                                <x-text-input wire:model="ns_pesantren" id="ns_pesantren" type="text" class="mt-1 block w-full" />
                            </div>
                            <div class="md:col-span-2">
                                <x-input-label for="alamat" value="Alamat" />
                                <textarea wire:model="alamat" id="alamat" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"></textarea>
                            </div>
                            <div x-data="wilayahSelector({
                                selectedProvinsiKode: @entangle('provinsi_kode'),
                                selectedKabupatenKode: @entangle('kabupaten_kode'),
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
                                            x-bind:disabled="!selectedProvinsiKode">
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
                            <div>
                                <x-input-label for="tahun_pendirian" value="Tahun Pendirian" />
                                <x-text-input wire:model="tahun_pendirian" id="tahun_pendirian" type="text" class="mt-1 block w-full" />
                            </div>
                            <div>
                                <x-input-label for="nama_mudir" value="Nama Mudir Pesantren" />
                                <x-text-input wire:model="nama_mudir" id="nama_mudir" type="text" class="mt-1 block w-full" />
                            </div>
                            <div>
                                <x-input-label for="jenjang_pendidikan_mudir" value="Jenjang Pendidikan Terakhir Mudir" />
                                <x-text-input wire:model="jenjang_pendidikan_mudir" id="jenjang_pendidikan_mudir" type="text" class="mt-1 block w-full" />
                            </div>
                            <div>
                                <x-input-label for="telp_pesantren" value="No. Telp Pesantren" />
                                <x-text-input wire:model="telp_pesantren" id="telp_pesantren" type="text" class="mt-1 block w-full" />
                            </div>
                            <div>
                                <x-input-label for="hp_wa" value="No. HP/WA" />
                                <x-text-input wire:model="hp_wa" id="hp_wa" type="text" class="mt-1 block w-full" />
                            </div>
                            <div>
                                <x-input-label for="email_pesantren" value="Email Pesantren (G-Mail)" />
                                <x-text-input wire:model="email_pesantren" id="email_pesantren" type="email" class="mt-1 block w-full" />
                            </div>
                            <div>
                                <x-input-label for="persyarikatan" value="Persyarikatan Penyelenggara" />
                                <x-text-input wire:model="persyarikatan" id="persyarikatan" type="text" class="mt-1 block w-full" />
                            </div>
                            <div class="md:col-span-2">
                                <x-input-label for="visi" value="Visi Pesantren" />
                                <textarea wire:model="visi" id="visi" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"></textarea>
                            </div>
                            <div class="md:col-span-2">
                                <x-input-label for="misi" value="Misi Pesantren" />
                                <textarea wire:model="misi" id="misi" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Section: DATA PESANTREN -->
                    <div class="mb-8 border-b pb-4">
                        <h3 class="text-lg font-bold mb-4 text-indigo-600">DATA PESANTREN</h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="md:col-span-3">
                                <x-input-label for="layanan_satuan_pendidikan" value="Layanan Satuan Pendidikan yang Dimiliki" />
                                <div class="mt-2 grid grid-cols-2 md:grid-cols-3 lg:grid-cols-7 gap-4">
                                    @foreach(['sd', 'mi', 'smp', 'mts', 'sma', 'ma', 'smk','satuan_pesantren_muadalah_(SPM)'] as $item)
                                    <label class="inline-flex items-center p-2 border rounded-lg hover:bg-gray-50 cursor-pointer transition-colors {{ in_array($item, (array)$layanan_satuan_pendidikan) ? 'border-indigo-500 bg-indigo-50' : 'border-gray-200' }}">
                                        <input type="checkbox" wire:model.live="layanan_satuan_pendidikan" value="{{ $item }}" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                        <span class="ml-2 uppercase font-medium text-gray-700 text-xs">{{ str_replace('_', ' ', $item) }}</span>
                                    </label>
                                    @endforeach
                                </div>
                            </div>


                            @if(count($layanan_satuan_pendidikan) > 0)
                            <div class="md:col-span-3 mt-4 space-y-4">
                                <h4 class="font-bold text-gray-700 border-b pb-2">Detail Luas Tanah & Bangunan per Unit</h4>
                                @foreach($layanan_satuan_pendidikan as $unit)
                                <div class="grid grid-cols-1 md:grid-cols-1 gap-3 border p-4 rounded-lg bg-gray-50 relative">
                                    <div class="absolute -top-3 left-4 bg-indigo-100 text-indigo-800 text-xs font-bold px-2 py-1 rounded border border-indigo-200 uppercase">
                                        UNIT {{ str_replace('_', ' ', $unit) }}
                                    </div>
                                    <div class="mt-2">
                                        <x-input-label for="units_data.{{ $unit }}.jumlah_rombel" value="Jumlah Rombel" />
                                        <x-text-input wire:model="units_data.{{ $unit }}.jumlah_rombel" type="number" class="mt-1 block w-full" placeholder="0" />
                                    </div>
                                </div>
                                @endforeach
                            </div>
                            @endif
                        </div>
                    </div>

                    <!-- Section: DATA LUAS BANGUNAN -->
                    <div class="mb-8 border-b pb-4">
                        <h3 class="text-lg font-bold mb-4 text-indigo-600">DATA LUAS BANGUNAN</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="luas_tanah" value="Luas Tanah (m┬▓)" />
                                <x-text-input wire:model="luas_tanah" id="luas_tanah" type="text" class="mt-1 block w-full" placeholder="0" />
                            </div>
                            <div>
                                <x-input-label for="luas_bangunan" value="Luas Bangunan (m┬▓)" />
                                <x-text-input wire:model="luas_bangunan" id="luas_bangunan" type="text" class="mt-1 block w-full" placeholder="0" />
                            </div>
                        </div>
                    </div>

                    <!-- Section: DOKUMEN UTAMA -->
                    <div class="mb-8 border-b pb-4">
                        <h3 class="text-lg font-bold mb-4 text-indigo-600">DOKUMEN UTAMA</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            @foreach($mainDocs as $prop => $label)
                            <div>
                                <x-input-label for="{{ $prop }}" value="{{ $label }}" class="font-bold text-gray-700" />
                                @php $dbField = str_replace('_file', '', $prop); @endphp
                                <div class="flex flex-col gap-2 mt-1">
                                    <label class="flex flex-col items-center justify-center h-40 w-full border-2 border-dashed border-gray-200 rounded-xl hover:border-amber-400 hover:bg-amber-50 transition-all cursor-pointer group">
                                        @if(${$prop})
                                        @if(in_array(${$prop}->getMimeType(), ['image/jpeg', 'image/png', 'image/jpg']))
                                        <img src="{{ ${$prop}->temporaryUrl() }}" class="h-32 w-auto object-contain rounded-lg shadow-sm" alt="Preview">
                                        @else
                                        <div class="flex flex-col items-center animate-fadeIn">
                                            <svg class="w-12 h-12 text-red-500 mb-2" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z" />
                                            </svg>
                                            <span class="text-[10px] text-gray-600 font-bold uppercase truncate max-w-[150px]">{{ ${$prop}->getClientOriginalName() }}</span>
                                            <span class="text-[9px] text-emerald-500 font-medium">Siap Diunggah</span>
                                        </div>
                                        @endif
                                        @elseif(isset($existing_files[$dbField]) && $existing_files[$dbField])
                                        <div class="flex flex-col items-center opacity-70 group-hover:opacity-100 transition-opacity">
                                            <svg class="w-12 h-12 text-indigo-500 mb-2" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z" />
                                            </svg>
                                            <span class="text-[10px] text-gray-500 font-bold uppercase">FILE TERUNGGAH</span>
                                            <span class="text-[9px] text-indigo-400 font-medium">Klik untuk Ganti</span>
                                        </div>
                                        @else
                                        <svg class="w-8 h-8 text-gray-400 group-hover:text-amber-500 mb-2 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                        </svg>
                                        <span class="text-xs text-gray-500 group-hover:text-amber-600 font-medium text-center px-4">Upload File</span>
                                        <span class="text-[9px] text-gray-400 mt-1">PDF/IMG (Max 2MB)</span>
                                        @endif
                                        <input type="file"
                                            x-on:change="if(validate($event)) { $wire.upload('{{ $prop }}', $event.target.files[0]) }"
                                            accept="application/pdf,image/png,image/jpeg"
                                            class="hidden" />
                                    </label>
                                    @if(isset($existing_files[$dbField]) && $existing_files[$dbField])
                                    <a href="{{ Storage::url($existing_files[$dbField]) }}" target="_blank" class="flex items-center gap-2 text-[10px] text-emerald-600 font-bold hover:underline justify-center mt-1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                        LIHAT FILE SAAT INI
                                    </a>
                                    @endif
                                    <x-input-error :messages="$errors->get($prop)" />
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Section: DOKUMEN SEKUNDER -->
                    <div class="mb-8 border-b pb-4">
                        <h3 class="text-lg font-bold mb-4 text-indigo-600">DOKUMEN SEKUNDER</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            @foreach($secondaryDocs as $prop => $label)
                            <div>
                                <x-input-label for="{{ $prop }}" value="{{ $label }}" class="font-bold text-gray-700" />
                                @php $dbField = str_replace('_file', '', $prop); @endphp
                                <div class="flex flex-col gap-2 mt-1">
                                    <label class="flex flex-col items-center justify-center h-40 w-full border-2 border-dashed border-gray-200 rounded-xl hover:border-amber-400 hover:bg-amber-50 transition-all cursor-pointer group">
                                        @if(${$prop})
                                        @if(in_array(${$prop}->getMimeType(), ['image/jpeg', 'image/png', 'image/jpg']))
                                        <img src="{{ ${$prop}->temporaryUrl() }}" class="h-32 w-auto object-contain rounded-lg shadow-sm" alt="Preview">
                                        @else
                                        <div class="flex flex-col items-center animate-fadeIn">
                                            <svg class="w-12 h-12 text-red-500 mb-2" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z" />
                                            </svg>
                                            <span class="text-[10px] text-gray-600 font-bold uppercase truncate max-w-[150px]">{{ ${$prop}->getClientOriginalName() }}</span>
                                            <span class="text-[9px] text-emerald-500 font-medium">Siap Diunggah</span>
                                        </div>
                                        @endif
                                        @elseif(isset($existing_files[$dbField]) && $existing_files[$dbField])
                                        <div class="flex flex-col items-center opacity-70 group-hover:opacity-100 transition-opacity">
                                            <svg class="w-12 h-12 text-indigo-500 mb-2" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z" />
                                            </svg>
                                            <span class="text-[10px] text-gray-500 font-bold uppercase">FILE TERUNGGAH</span>
                                            <span class="text-[9px] text-indigo-400 font-medium">Klik untuk Ganti</span>
                                        </div>
                                        @else
                                        <svg class="w-8 h-8 text-gray-400 group-hover:text-amber-500 mb-2 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                        </svg>
                                        <span class="text-xs text-gray-500 group-hover:text-amber-600 font-medium text-center px-4">Upload File</span>
                                        <span class="text-[9px] text-gray-400 mt-1">PDF/IMG (Max 2MB)</span>
                                        @endif
                                        <input type="file"
                                            x-on:change="if(validate($event)) { $wire.upload('{{ $prop }}', $event.target.files[0]) }"
                                            accept="application/pdf,image/png,image/jpeg"
                                            class="hidden" />
                                    </label>
                                    @if(isset($existing_files[$dbField]) && $existing_files[$dbField])
                                    <a href="{{ Storage::url($existing_files[$dbField]) }}" target="_blank" class="flex items-center gap-2 text-[10px] text-emerald-600 font-bold hover:underline justify-center mt-1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                        LIHAT FILE SAAT INI
                                    </a>
                                    @endif
                                    <x-input-error :messages="$errors->get($prop)" />
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="flex items-center justify-end mt-4">
                        <button type="button" wire:click="toggleEdit" class="px-8 py-3 rounded-2xl bg-white border border-slate-200 text-slate-500 text-[11px] font-black uppercase tracking-[0.2em] transition-all mr-3">Batal</button>
                        <button type="button" @click="confirmSave($wire)" wire:loading.attr="disabled"
                            class="px-10 py-3 rounded-2xl bg-gray-900 text-white text-[11px] font-black uppercase tracking-[0.2em] transition-all flex items-center gap-3">
                            <svg wire:loading wire:target="save" class="animate-spin -ml-1 mr-3 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span wire:loading.remove wire:target="save">{{ __('Simpan Perubahan') }}</span>
                            <span wire:loading wire:target="save">{{ __('Memproses...') }}</span>
                        </button>
                    </div>
                </form>
                @else
                <!-- View Mode Content -->
                <div class="space-y-8">
                    <!-- Section A: Profil Pesantren -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-gray-100 p-8">
                        <div class="flex items-center gap-3 mb-6 border-b border-gray-100 pb-4">
                            <div class="p-2 bg-indigo-50 rounded-lg">
                                <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                            </div>
                            <h3 class="text-lg font-bold text-gray-800 uppercase tracking-wide">A. PROFIL PESANTREN</h3>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-y-6 gap-x-12">
                            <div class="space-y-1">
                                <span class="block text-xs font-semibold text-gray-400 uppercase tracking-wider">Nama Pesantren</span>
                                <p class="text-gray-800 font-medium text-lg border-b border-gray-100 pb-2">{{ $nama_pesantren ?: '-' }}</p>
                            </div>
                            <div class="space-y-1">
                                <span class="block text-xs font-semibold text-gray-400 uppercase tracking-wider">NS Pesantren</span>
                                <p class="text-gray-800 font-medium text-lg border-b border-gray-100 pb-2">{{ $ns_pesantren ?: '-' }}</p>
                            </div>
                            <div class="space-y-1 md:col-span-2">
                                <span class="block text-xs font-semibold text-gray-400 uppercase tracking-wider">Alamat</span>
                                <p class="text-gray-800 font-medium text-lg border-b border-gray-100 pb-2">{{ $alamat ?: '-' }}</p>
                            </div>
                            <div class="space-y-1">
                                <span class="block text-xs font-semibold text-gray-400 uppercase tracking-wider">Provinsi</span>
                                <p class="text-gray-800 font-medium text-lg border-b border-gray-100 pb-2">{{ $provinsi ?: '-' }}</p>
                            </div>
                            <div class="space-y-1">
                                <span class="block text-xs font-semibold text-gray-400 uppercase tracking-wider">Kota / Kabupaten</span>
                                <p class="text-gray-800 font-medium text-lg border-b border-gray-100 pb-2">{{ $kota_kabupaten ?: '-' }}</p>
                            </div>
                            <div class="space-y-1">
                                <span class="block text-xs font-semibold text-gray-400 uppercase tracking-wider">Tahun Pendirian</span>
                                <p class="text-gray-800 font-medium text-lg border-b border-gray-100 pb-2">{{ $tahun_pendirian ?: '-' }}</p>
                            </div>
                            <div class="space-y-1">
                                <span class="block text-xs font-semibold text-gray-400 uppercase tracking-wider">Nama Mudir</span>
                                <p class="text-gray-800 font-medium text-lg border-b border-gray-100 pb-2">{{ $nama_mudir ?: '-' }}</p>
                            </div>
                            <div class="space-y-1">
                                <span class="block text-xs font-semibold text-gray-400 uppercase tracking-wider">Jenjang Pendidikan Mudir</span>
                                <p class="text-gray-800 font-medium text-lg border-b border-gray-100 pb-2">{{ $jenjang_pendidikan_mudir ?: '-' }}</p>
                            </div>
                            <div class="space-y-1">
                                <span class="block text-xs font-semibold text-gray-400 uppercase tracking-wider">No. Telp Pesantren</span>
                                <p class="text-gray-800 font-medium text-lg border-b border-gray-100 pb-2">{{ $telp_pesantren ?: '-' }}</p>
                            </div>
                            <div class="space-y-1">
                                <span class="block text-xs font-semibold text-gray-400 uppercase tracking-wider">No. HP / WA</span>
                                <p class="text-gray-800 font-medium text-lg border-b border-gray-100 pb-2">{{ $hp_wa ?: '-' }}</p>
                            </div>
                            <div class="space-y-1">
                                <span class="block text-xs font-semibold text-gray-400 uppercase tracking-wider">Email Pesantren</span>
                                <p class="text-gray-800 font-medium text-lg border-b border-gray-100 pb-2">{{ $email_pesantren ?: '-' }}</p>
                            </div>
                            <div class="space-y-1">
                                <span class="block text-xs font-semibold text-gray-400 uppercase tracking-wider">Persyarikatan</span>
                                <p class="text-gray-800 font-medium text-lg border-b border-gray-100 pb-2">{{ $persyarikatan ?: '-' }}</p>
                            </div>
                            <div class="space-y-1">
                                <span class="block text-xs font-semibold text-gray-400 uppercase tracking-wider">Akreditasi</span>
                                <p class="text-gray-800 font-medium text-lg border-b border-gray-100 pb-2">
                                    @php
                                    $latestAkreditasi = auth()->user()->akreditasis()->latest()->first();
                                    @endphp
                                    @if (!$latestAkreditasi)
                                    -
                                    @elseif ($latestAkreditasi->status == 1)
                                    <span class="px-2 py-0.5 rounded text-sm font-bold bg-indigo-100 text-indigo-700 border border-indigo-200 uppercase">
                                        {{ $latestAkreditasi->peringkat ?? 'Berhasil' }}
                                    </span>
                                    @else
                                    <span class="px-2 py-0.5 rounded text-sm font-bold bg-amber-100 text-amber-700 border border-amber-200 uppercase">
                                        Proses
                                    </span>
                                    @endif
                                </p>
                            </div>
                            <div class="space-y-1 md:col-span-2">
                                <span class="block text-xs font-semibold text-gray-400 uppercase tracking-wider">Visi</span>
                                <p class="text-gray-800 font-medium text-lg border-b border-gray-100 pb-2 whitespace-pre-line">{{ $visi ?: '-' }}</p>
                            </div>
                            <div class="space-y-1 md:col-span-2">
                                <span class="block text-xs font-semibold text-gray-400 uppercase tracking-wider">Misi</span>
                                <p class="text-gray-800 font-medium text-lg border-b border-gray-100 pb-2 whitespace-pre-line">{{ $misi ?: '-' }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Section B: Data & Fasilitas -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-gray-100 p-8">
                        <div class="flex items-center gap-3 mb-6 border-b border-gray-100 pb-4">
                            <div class="p-2 bg-emerald-50 rounded-lg">
                                <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                            </div>
                            <h3 class="text-lg font-bold text-gray-800 uppercase tracking-wide">B. DATA & FASILITAS</h3>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <!-- Satuan Pendidikan -->
                            <div>
                                <h4 class="font-bold text-gray-700 mb-3 flex items-center gap-2"><span class="w-2 h-2 rounded-full bg-emerald-500"></span> Layanan Pendidikan</h4>
                                @if(count($layanan_satuan_pendidikan) > 0)
                                <div class="space-y-3">
                                    @foreach($layanan_satuan_pendidikan as $unit)
                                    <div class="flex justify-between items-center bg-gray-50 px-4 py-3 rounded-lg border border-gray-100">
                                        <span class="text-sm font-bold uppercase text-gray-700">{{ str_replace('_', ' ', $unit) }}</span>
                                        <span class="text-xs font-bold text-emerald-600 bg-emerald-50 px-2 py-1 rounded border border-emerald-100">{{ $units_data[$unit]['jumlah_rombel'] ?? 0 }} Rombel</span>
                                    </div>
                                    @endforeach
                                </div>
                                @else
                                <p class="text-gray-400 italic text-sm">Belum ada data layanan pendidikan.</p>
                                @endif
                            </div>

                            <!-- Luas Tanah & Bangunan -->
                            <div>
                                <h4 class="font-bold text-gray-700 mb-3 flex items-center gap-2"><span class="w-2 h-2 rounded-full bg-emerald-500"></span> Luas Wilayah</h4>
                                <div class="grid grid-cols-1 gap-4">
                                    <div class="p-4 bg-gradient-to-r from-emerald-50 to-white rounded-xl border border-emerald-100">
                                        <div class="flex items-center gap-3">
                                            <div class="p-2 bg-emerald-100 rounded-full text-emerald-600">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                            </div>
                                            <div>
                                                <span class="block text-xs font-bold text-emerald-600 uppercase tracking-wider">Luas Tanah</span>
                                                <p class="text-xl font-bold text-gray-800">{{ $luas_tanah ?: '0' }} <span class="text-sm font-normal text-gray-500">m┬▓</span></p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="p-4 bg-gradient-to-r from-sky-50 to-white rounded-xl border border-sky-100">
                                        <div class="flex items-center gap-3">
                                            <div class="p-2 bg-sky-100 rounded-full text-sky-600">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                                </svg>
                                            </div>
                                            <div>
                                                <span class="block text-xs font-bold text-sky-600 uppercase tracking-wider">Luas Bangunan</span>
                                                <p class="text-xl font-bold text-gray-800">{{ $luas_bangunan ?: '0' }} <span class="text-sm font-normal text-gray-500">m┬▓</span></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
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
                            <h3 class="text-lg font-bold text-gray-800 uppercase tracking-wide">C. DOKUMEN TERSIMPAN</h3>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            @foreach(array_merge($mainDocs ?? [], $secondaryDocs ?? []) as $prop => $label)
                            @php $dbField = str_replace('_file', '', $prop); @endphp
                            @if(isset($existing_files[$dbField]) && $existing_files[$dbField])
                            <a href="{{ Storage::url($existing_files[$dbField]) }}" target="_blank" class="flex items-center p-4 bg-gray-50 border border-gray-200 rounded-xl hover:bg-white hover:shadow-md transition-all group">
                                <div class="w-10 h-10 bg-red-100 text-red-600 rounded-lg flex items-center justify-center mr-3 group-hover:scale-110 transition-transform flex-shrink-0">
                                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zM6 20V4h7v5h5v11H6z" />
                                    </svg>
                                </div>
                                <div class="overflow-hidden">
                                    <p class="font-bold text-gray-700 text-sm truncate" title="{{ $label }}">{{ $label }}</p>
                                    <p class="text-xs text-green-500 font-medium">Tersedia ΓÇó Klik Lihat</p>
                                </div>
                            </a>
                            @endif
                            @endforeach
                        </div>
                        @if(empty(array_filter($existing_files)))
                        <div class="text-center py-8">
                            <svg class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <p class="text-gray-400 italic">Belum ada dokumen yang diunggah.</p>
                        </div>
                        @endif
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
