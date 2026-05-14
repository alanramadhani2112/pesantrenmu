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

    public function getAllDocFields(): array
    {
        $result = [];
        foreach (array_merge($this->mainDocs, $this->secondaryDocs) as $prop => $label) {
            $result[str_replace('_file', '', $prop)] = $label;
        }
        return $result;
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

<x-slot name="header">{{ __('Profil Pesantren') }}</x-slot>

<x-ui.page title="Profil Pesantren" subtitle="Kelola informasi data pesantren, layanan pendidikan, dan dokumen.">
    <x-slot:toolbar>
        @if($pesantren->is_locked)
            <x-ui.status-badge variant="danger">
                <x-ui.icon name="lock" class="fs-6 me-1" /> Data Terkunci
            </x-ui.status-badge>
        @endif
        @if($isEditing)
            <x-ui.button type="button" wire:click="toggleEdit" variant="light">
                <x-ui.icon name="cross" class="fs-4 me-1" /> Batal Edit
            </x-ui.button>
        @else
            <x-ui.button type="button" wire:click="toggleEdit" variant="primary"
                @disabled($pesantren->is_locked)>
                <x-ui.icon name="pencil" class="fs-4 me-1" /> Edit Profil
            </x-ui.button>
        @endif
    </x-slot:toolbar>

    @if($pesantren->is_locked && !$isEditing)
    <div class="alert alert-danger d-flex align-items-center gap-3 mb-6">
        <x-ui.icon name="shield-cross" class="fs-2x text-danger" />
        <div>
            <div class="fw-bold">DATA TERKUNCI</div>
            <div class="fs-7">Data profil tidak dapat diubah karena sedang dalam proses akreditasi.</div>
        </div>
    </div>
    @endif

    @if($isEditing)
    {{-- ===== EDIT MODE ===== --}}
    <form wire:submit="save">
        <div class="d-flex flex-column gap-6">

            {{-- Profil Pesantren --}}
            <x-ui.section-card title="Profil Pesantren" subtitle="Identitas utama dan narasi kelembagaan.">
                <div class="p-6">
                    <div class="row g-5">
                        <div class="col-md-6">
                            <x-ui.form-field label="Nama Pesantren" required>
                                <x-ui.input wire:model="nama_pesantren" />
                                @error('nama_pesantren') <div class="text-danger fs-8 mt-1">{{ $message }}</div> @enderror
                            </x-ui.form-field>
                        </div>
                        <div class="col-md-6">
                            <x-ui.form-field label="Nomor Statistik Pesantren (NSP)">
                                <x-ui.input wire:model="ns_pesantren" />
                            </x-ui.form-field>
                        </div>
                        <div class="col-12">
                            <x-ui.form-field label="Alamat Lengkap">
                                <x-ui.textarea wire:model="alamat" rows="2" />
                            </x-ui.form-field>
                        </div>

                        {{-- Wilayah Selector --}}
                        <div class="col-12"
                            x-data="wilayahSelector({
                                selectedProvinsiKode: $wire.entangle('provinsi_kode'),
                                selectedKabupatenKode: $wire.entangle('kabupaten_kode'),
                                selectedProvinsiNama: $wire.entangle('provinsi'),
                                selectedKabupatenNama: $wire.entangle('kota_kabupaten')
                            })">
                            <div class="row g-5">
                                <div class="col-md-6">
                                    <x-ui.form-field label="Provinsi">
                                        <div class="position-relative">
                                            <input type="text" x-model="provinsiSearch"
                                                placeholder="Cari Provinsi..."
                                                @focus="showProvinsiConfig = true"
                                                @click.outside="showProvinsiConfig = false"
                                                class="form-control form-control-solid" />
                                            <div x-show="showProvinsiConfig && filteredProvinsi.length > 0"
                                                 class="position-absolute w-100 mt-1 bg-white border rounded shadow-sm"
                                                 style="z-index:50;max-height:200px;overflow-y:auto;">
                                                <template x-for="item in filteredProvinsi" :key="item.kode">
                                                    <div @click="selectProvinsi(item)" class="px-4 py-2 cursor-pointer fs-7 hover-bg-light" x-text="item.nama"></div>
                                                </template>
                                            </div>
                                        </div>
                                    </x-ui.form-field>
                                </div>
                                <div class="col-md-6">
                                    <x-ui.form-field label="Kota / Kabupaten">
                                        <div class="position-relative">
                                            <input type="text" x-model="kabupatenSearch"
                                                placeholder="Cari Kota/Kabupaten..."
                                                @focus="showKabupatenConfig = true"
                                                @click.outside="showKabupatenConfig = false"
                                                x-bindx-bind:disabled="!currentProvinsiKode"
                                                class="form-control form-control-solid" />
                                            <div x-show="showKabupatenConfig && filteredKabupaten.length > 0"
                                                 class="position-absolute w-100 mt-1 bg-white border rounded shadow-sm"
                                                 style="z-index:50;max-height:200px;overflow-y:auto;">
                                                <template x-for="item in filteredKabupaten" :key="item.kode">
                                                    <div @click="selectKabupaten(item)" class="px-4 py-2 cursor-pointer fs-7 hover-bg-light" x-text="item.nama"></div>
                                                </template>
                                            </div>
                                        </div>
                                    </x-ui.form-field>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <x-ui.form-field label="Tahun Pendirian">
                                <x-ui.input wire:model="tahun_pendirian" />
                            </x-ui.form-field>
                        </div>
                        <div class="col-md-4">
                            <x-ui.form-field label="Nama Mudir">
                                <x-ui.input wire:model="nama_mudir" />
                            </x-ui.form-field>
                        </div>
                        <div class="col-md-4">
                            <x-ui.form-field label="Pendidikan Terakhir Mudir">
                                <x-ui.input wire:model="jenjang_pendidikan_mudir" />
                            </x-ui.form-field>
                        </div>
                        <div class="col-md-4">
                            <x-ui.form-field label="No. Telp Pesantren">
                                <x-ui.input wire:model="telp_pesantren" />
                            </x-ui.form-field>
                        </div>
                        <div class="col-md-4">
                            <x-ui.form-field label="No. HP / WA">
                                <x-ui.input wire:model="hp_wa" />
                            </x-ui.form-field>
                        </div>
                        <div class="col-md-4">
                            <x-ui.form-field label="Email Pesantren">
                                <x-ui.input wire:model="email_pesantren" type="email" />
                                @error('email_pesantren') <div class="text-danger fs-8 mt-1">{{ $message }}</div> @enderror
                            </x-ui.form-field>
                        </div>
                        <div class="col-md-6">
                            <x-ui.form-field label="Persyarikatan Penyelenggara">
                                <x-ui.input wire:model="persyarikatan" />
                            </x-ui.form-field>
                        </div>
                        <div class="col-12">
                            <x-ui.form-field label="Visi Pesantren">
                                <x-ui.textarea wire:model="visi" rows="3" />
                            </x-ui.form-field>
                        </div>
                        <div class="col-12">
                            <x-ui.form-field label="Misi Pesantren">
                                <x-ui.textarea wire:model="misi" rows="3" />
                            </x-ui.form-field>
                        </div>
                    </div>
                </div>
            </x-ui.section-card>

            {{-- Data Pesantren --}}
            <x-ui.section-card title="Data Pesantren" subtitle="Layanan satuan pendidikan dan kapasitas sarana.">
                <div class="p-6">
                    <div class="row g-5">
                        <div class="col-12">
                            <x-ui.form-field label="Layanan Satuan Pendidikan yang Dimiliki">
                                <div class="d-flex flex-wrap gap-3 mt-2">
                                    @foreach(['sd','mi','smp','mts','sma','ma','smk','satuan_pesantren_muadalah_(SPM)'] as $item)
                                    <label class="d-flex align-items-center gap-2 border rounded px-3 py-2 cursor-pointer {{ in_array($item, (array)$layanan_satuan_pendidikan) ? 'border-primary bg-light-primary' : 'border-gray-300' }}">
                                        <input type="checkbox" wire:model.live="layanan_satuan_pendidikan" value="{{ $item }}" class="form-check-input">
                                        <span class="fw-bold fs-8 text-uppercase">{{ str_replace('_', ' ', $item) }}</span>
                                    </label>
                                    @endforeach
                                </div>
                            </x-ui.form-field>
                        </div>

                        @if(count($layanan_satuan_pendidikan) > 0)
                        <div class="col-12">
                            <div class="fw-bold fs-7 mb-3">Jumlah Rombel per Unit</div>
                            <div class="row g-4">
                                @foreach($layanan_satuan_pendidikan as $unit)
                                <div class="col-md-3">
                                    <x-ui.form-field :label="'Unit ' . strtoupper(str_replace('_', ' ', $unit))">
                                        <x-ui.input wire:model="units_data.{{ $unit }}.jumlah_rombel" type="number" placeholder="0" />
                                    </x-ui.form-field>
                                </div>
                                @endforeach
                            </div>
                        </div>
                        @endif

                        <div class="col-md-6">
                            <x-ui.form-field label="Luas Tanah (m²)">
                                <x-ui.input wire:model="luas_tanah" placeholder="0" />
                            </x-ui.form-field>
                        </div>
                        <div class="col-md-6">
                            <x-ui.form-field label="Luas Bangunan (m²)">
                                <x-ui.input wire:model="luas_bangunan" placeholder="0" />
                            </x-ui.form-field>
                        </div>
                    </div>
                </div>
            </x-ui.section-card>

            {{-- Dokumen Utama --}}
            <x-ui.section-card title="Dokumen Utama" subtitle="Berkas wajib untuk proses akreditasi.">
                <div class="p-6">
                    <div class="row g-5">
                        @foreach($mainDocs as $prop => $label)
                        @php $dbField = str_replace('_file', '', $prop); @endphp
                        <div class="col-md-6">
                            <x-ui.form-field :label="$label">
                                <label for="doc_{{ $prop }}" class="d-flex flex-column align-items-center justify-content-center border border-2 border-dashed rounded p-4 cursor-pointer hover-border-primary" style="min-height:100px;">
                                    @if($$prop)
                                        <x-ui.icon name="document" class="fs-2x text-success mb-2" />
                                        <span class="fs-8 fw-bold text-success">{{ $$prop->getClientOriginalName() }}</span>
                                        <span class="fs-9 text-muted">Siap diunggah</span>
                                    @elseif(!empty($existing_files[$dbField]))
                                        <x-ui.icon name="document" class="fs-2x text-primary mb-2" />
                                        <span class="fs-8 fw-bold text-muted">File terunggah</span>
                                        <span class="fs-9 text-primary">Klik untuk ganti</span>
                                    @else
                                        <x-ui.icon name="cloud-upload" class="fs-2x text-muted mb-2" />
                                        <span class="fs-8 text-muted">Klik untuk unggah</span>
                                        <span class="fs-9 text-muted">PDF/JPG/PNG, maks 2MB</span>
                                    @endif
                                    <input type="file" id="doc_{{ $prop }}"
                                        wire:model="{{ $prop }}"
                                        accept="application/pdf,image/png,image/jpeg"
                                        class="d-none" />
                                </label>
                                @if(!empty($existing_files[$dbField]))
                                <a href="{{ Storage::url($existing_files[$dbField]) }}" target="_blank"
                                   class="d-flex align-items-center gap-1 fs-8 text-primary fw-bold mt-2">
                                    <x-ui.icon name="eye" class="fs-6" /> Lihat file saat ini
                                </a>
                                @endif
                                @error($prop) <div class="text-danger fs-8 mt-1">{{ $message }}</div> @enderror
                            </x-ui.form-field>
                        </div>
                        @endforeach
                    </div>
                </div>
            </x-ui.section-card>

            {{-- Dokumen Sekunder --}}
            <x-ui.section-card title="Dokumen Sekunder" subtitle="Dokumen pendukung kelembagaan pesantren.">
                <div class="p-6">
                    <div class="row g-5">
                        @foreach($secondaryDocs as $prop => $label)
                        @php $dbField = str_replace('_file', '', $prop); @endphp
                        <div class="col-md-6">
                            <x-ui.form-field :label="$label">
                                <label for="doc2_{{ $prop }}" class="d-flex flex-column align-items-center justify-content-center border border-2 border-dashed rounded p-4 cursor-pointer hover-border-primary" style="min-height:100px;">
                                    @if($$prop)
                                        <x-ui.icon name="document" class="fs-2x text-success mb-2" />
                                        <span class="fs-8 fw-bold text-success">{{ $$prop->getClientOriginalName() }}</span>
                                        <span class="fs-9 text-muted">Siap diunggah</span>
                                    @elseif(!empty($existing_files[$dbField]))
                                        <x-ui.icon name="document" class="fs-2x text-primary mb-2" />
                                        <span class="fs-8 fw-bold text-muted">File terunggah</span>
                                        <span class="fs-9 text-primary">Klik untuk ganti</span>
                                    @else
                                        <x-ui.icon name="cloud-upload" class="fs-2x text-muted mb-2" />
                                        <span class="fs-8 text-muted">Klik untuk unggah</span>
                                        <span class="fs-9 text-muted">PDF/JPG/PNG, maks 2MB</span>
                                    @endif
                                    <input type="file" id="doc2_{{ $prop }}"
                                        wire:model="{{ $prop }}"
                                        accept="application/pdf,image/png,image/jpeg"
                                        class="d-none" />
                                </label>
                                @if(!empty($existing_files[$dbField]))
                                <a href="{{ Storage::url($existing_files[$dbField]) }}" target="_blank"
                                   class="d-flex align-items-center gap-1 fs-8 text-primary fw-bold mt-2">
                                    <x-ui.icon name="eye" class="fs-6" /> Lihat file saat ini
                                </a>
                                @endif
                                @error($prop) <div class="text-danger fs-8 mt-1">{{ $message }}</div> @enderror
                            </x-ui.form-field>
                        </div>
                        @endforeach
                    </div>
                </div>
            </x-ui.section-card>

            {{-- Save Bar --}}
            <div class="d-flex align-items-center justify-content-end gap-3 p-5 bg-light rounded">
                <x-ui.button type="button" wire:click="toggleEdit" variant="light">Batal</x-ui.button>
                <x-ui.button type="submit" variant="primary"
                    wire:loading.attr="disabled" wire:target="save">
                    <span wire:loading.remove wire:target="save">
                        <x-ui.icon name="check" class="fs-5 me-1" /> Simpan Profil
                    </span>
                    <span wire:loading wire:target="save">Memproses...</span>
                </x-ui.button>
            </div>

        </div>
    </form>

    @else
    {{-- ===== VIEW MODE ===== --}}
    <div class="row g-6">
        <div class="col-xl-4">
            <div class="d-flex flex-column gap-6">
                <x-ui.card>
                    <div class="d-flex flex-column align-items-center text-center">
                        <div class="spm-profile-avatar d-flex align-items-center justify-content-center mb-5">
                            {{ substr($nama_pesantren ?? 'P', 0, 1) }}
                        </div>
                        <h2 class="spm-card-title fs-4 mb-1">{{ $nama_pesantren ?: '-' }}</h2>
                        <div class="text-muted fw-bold fs-8 text-uppercase mb-4">NSP: {{ $ns_pesantren ?: '-' }}</div>
                        <div class="d-flex flex-wrap justify-content-center gap-2">
                            <x-ui.status-badge variant="success">Aktif</x-ui.status-badge>
                            @if($pesantren->is_locked)
                                <x-ui.status-badge variant="danger">
                                    <x-ui.icon name="lock" class="fs-7 me-1" /> Terkunci
                                </x-ui.status-badge>
                            @endif
                        </div>
                    </div>
                </x-ui.card>

                <x-ui.section-card title="Informasi Kontak">
                    <div class="p-6">
                        <div class="row g-5">
                            <x-ui.detail-item label="Email Pesantren" :value="$email_pesantren ?: '-'" span="2" />
                            <x-ui.detail-item label="No. Telp / WA" :value="($telp_pesantren ?: '-') . ' / ' . ($hp_wa ?: '-')" span="2" />
                            <x-ui.detail-item label="Lokasi" :value="($kota_kabupaten ?: '-') . ', ' . ($provinsi ?: '-')" span="2" />
                        </div>
                    </div>
                </x-ui.section-card>
            </div>
        </div>

        <div class="col-xl-8">
            <div class="d-flex flex-column gap-6">
                <x-ui.section-card title="Profil Pesantren" subtitle="Identitas utama dan narasi kelembagaan.">
                    <div class="p-6">
                        <div class="row g-5">
                            <x-ui.detail-item label="Tahun Pendirian" :value="$tahun_pendirian ?: '-'" />
                            <x-ui.detail-item label="Nama Mudir" :value="$nama_mudir ?: '-'" />
                            <x-ui.detail-item label="Pendidikan Mudir" :value="$jenjang_pendidikan_mudir ?: '-'" />
                            <x-ui.detail-item label="Persyarikatan" :value="$persyarikatan ?: '-'" />
                            <x-ui.detail-item label="Alamat Lengkap" span="2">
                                <div class="spm-detail-block spm-detail-value-muted">{{ $alamat ?: '-' }}</div>
                            </x-ui.detail-item>
                            <x-ui.detail-item label="Visi" span="2">
                                <div class="spm-detail-block spm-detail-value-muted whitespace-pre-line">{{ $visi ?: '-' }}</div>
                            </x-ui.detail-item>
                            <x-ui.detail-item label="Misi" span="2">
                                <div class="spm-detail-block spm-detail-value-muted whitespace-pre-line">{{ $misi ?: '-' }}</div>
                            </x-ui.detail-item>
                        </div>
                    </div>
                </x-ui.section-card>

                <x-ui.section-card title="Data & Fasilitas" subtitle="Layanan pendidikan dan kapasitas sarana.">
                    <div class="p-6">
                        <div class="row g-6">
                            <div class="col-lg-7">
                                <div class="spm-detail-label mb-3">Layanan Pendidikan</div>
                                @if($pesantren->units && $pesantren->units->count() > 0)
                                    <x-ui.simple-table dense>
                                        <thead>
                                            <tr>
                                                <th class="ps-4">Unit</th>
                                                <th class="text-end pe-4">Jumlah Rombel</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($pesantren->units as $unit)
                                            <tr>
                                                <td class="ps-4 text-uppercase fw-bold">{{ str_replace('_', ' ', $unit->unit) }}</td>
                                                <td class="text-end pe-4">
                                                    <x-ui.badge variant="success">{{ $unit->jumlah_rombel ?? 0 }} Rombel</x-ui.badge>
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </x-ui.simple-table>
                                @else
                                    <x-ui.empty-state title="Belum Ada Unit" description="Data unit satuan pendidikan belum diisi." />
                                @endif
                            </div>
                            <div class="col-lg-5">
                                <div class="d-flex flex-column gap-4">
                                    <x-ui.stat-card label="Luas Tanah" value="{{ $luas_tanah ?: '0' }} m²" variant="success">
                                        <x-slot:icon><x-ui.icon name="geolocation" class="fs-2" /></x-slot:icon>
                                    </x-ui.stat-card>
                                    <x-ui.stat-card label="Luas Bangunan" value="{{ $luas_bangunan ?: '0' }} m²" variant="info">
                                        <x-slot:icon><x-ui.icon name="category" class="fs-2" /></x-slot:icon>
                                    </x-ui.stat-card>
                                </div>
                            </div>
                        </div>
                    </div>
                </x-ui.section-card>

                <x-ui.section-card title="Dokumen Pesantren" subtitle="Status unggahan dokumen pendukung.">
                    <div class="p-6">
                        @php
                            $allDocFields = $this->getAllDocFields();
                            $docChunks = array_chunk($allDocFields, (int)ceil(count($allDocFields)/2), true);
                        @endphp
                        <div class="row g-5">
                            @foreach($docChunks as $chunk)
                            <div class="col-lg-6">
                                <div class="spm-document-list">
                                    @foreach($chunk as $field => $label)
                                        <x-ui.document-item
                                            :label="$label"
                                            :href="!empty($existing_files[$field]) ? Storage::url($existing_files[$field]) : null"
                                        />
                                    @endforeach
                                </div>
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
