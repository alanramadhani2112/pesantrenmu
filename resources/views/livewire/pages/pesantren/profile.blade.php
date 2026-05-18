<?php

use App\Models\Pesantren;
use App\Traits\ChecksSectionLock;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;

new #[Layout('layouts.app')] class extends Component {
    use WithFileUploads;
    use ChecksSectionLock;

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
        if (!$this->isSectionEditable('profil')) {
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
            'file_lk_iapm_file' => 'File LK Penilaian IAPM',
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

<x-slot name="header">{{ __('Profil Pesantren') }}</x-slot>

<x-ui.page title="Profil Pesantren" subtitle="Kelola informasi data pesantren Anda" x-data="fileManagement()">
    <x-slot:toolbar>
        <x-ui.button
            type="button"
            wire:click="toggleEdit"
            :variant="$isEditing ? 'light' : 'primary'"
        >
            @if($isEditing)
                <i class="ki-outline ki-cross fs-4 me-1"></i>
                Batal Edit
            @else
                <i class="ki-outline ki-pencil fs-4 me-1"></i>
                Edit Profil
            @endif
        </x-ui.button>
    </x-slot:toolbar>

    <x-ui.page-help
        title="Panduan Profil Pesantren"
        :items="[
            'Isi semua data identitas pesantren dengan lengkap dan akurat',
            'Upload foto pesantren jika tersedia untuk memperkuat profil',
            'Simpan perubahan sebelum berpindah ke halaman lain',
            'Profil akan terkunci otomatis saat proses akreditasi sedang berjalan',
        ]"
        dismiss-key="help-pesantren-profil"
    />

        @if($pesantren->is_locked)
            <div class="alert alert-warning d-flex align-items-center p-5 mb-6">
                <i class="ki-outline ki-shield-tick fs-2hx text-warning me-4"></i>
                <div class="d-flex flex-column">
                    <h4 class="mb-1 text-warning">Profil Terkunci</h4>
                    <span class="text-gray-700">Profil ini terkunci karena sedang dalam proses akreditasi atau telah disetujui. Beberapa data tidak dapat diubah.</span>
                </div>
            </div>
        @endif

        @if($isEditing)
            <form @submit.prevent="confirmSave($wire)" class="d-flex flex-column gap-6">

                {{-- A. PROFIL PESANTREN --}}
                <x-ui.section-card title="A. Profil Pesantren" subtitle="Identitas dan informasi dasar pesantren">
                    <div class="p-6">
                    <div class="row g-5">
                        <div class="col-md-6">
                            <x-ui.form-field label="Nama Pesantren" required :error="$errors->get('nama_pesantren')">
                                <x-ui.input wire:model="nama_pesantren" placeholder="Masukkan nama pesantren" />
                            </x-ui.form-field>
                        </div>
                        <div class="col-md-6">
                            <x-ui.form-field label="Nomor Statistik Pesantren (NSP)" :error="$errors->get('ns_pesantren')">
                                <x-ui.input wire:model="ns_pesantren" placeholder="Masukkan NSP" />
                            </x-ui.form-field>
                        </div>

                        <div class="col-12">
                            <x-ui.form-field label="Alamat Pesantren" :error="$errors->get('alamat')">
                                <x-ui.textarea wire:model="alamat" rows="3" placeholder="Alamat lengkap pesantren" />
                            </x-ui.form-field>
                        </div>

                        {{-- Wilayah Selector --}}
                        <div class="col-12" x-data="wilayahSelector()" x-init="init()">
                            <div class="row g-5">
                                <div class="col-md-6">
                                    <div class="fv-row spm-form-field position-relative">
                                        <label class="form-label fw-semibold text-gray-700 fs-7">Provinsi</label>
                                        <input
                                            type="text"
                                            class="form-control form-control-solid"
                                            placeholder="Cari provinsi..."
                                            x-model="provinsiSearch"
                                            @focus="showProvinsiDropdown = true"
                                            @click.away="showProvinsiDropdown = false"
                                        />
                                        <input type="hidden" x-model="selectedProvinsiKode" @entangle('provinsi_kode')>
                                        <input type="hidden" x-model="selectedProvinsiNama" @entangle('provinsi')>
                                        <div
                                            x-show="showProvinsiDropdown && filteredProvinsi.length > 0"
                                            class="position-absolute w-100 mt-1 bg-white border border-gray-300 rounded shadow-sm overflow-auto"
                                            style="z-index: 1050; max-height: 240px;"
                                        >
                                            <template x-for="provinsi in filteredProvinsi" :key="provinsi.kode">
                                                <div
                                                    @click="selectProvinsi(provinsi)"
                                                    class="px-4 py-2 cursor-pointer text-gray-800 fs-7"
                                                    style="cursor: pointer;"
                                                    onmouseover="this.style.backgroundColor='#f5f8fa'"
                                                    onmouseout="this.style.backgroundColor='transparent'"
                                                    x-text="provinsi.nama"
                                                ></div>
                                            </template>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="fv-row spm-form-field position-relative">
                                        <label class="form-label fw-semibold text-gray-700 fs-7">Kota / Kabupaten</label>
                                        <input
                                            type="text"
                                            class="form-control form-control-solid"
                                            placeholder="Cari kota/kabupaten..."
                                            x-model="kabupatenSearch"
                                            @focus="showKabupatenDropdown = true"
                                            @click.away="showKabupatenDropdown = false"
                                            :disabled="!selectedProvinsiKode"
                                        />
                                        <input type="hidden" x-model="selectedKabupatenKode" @entangle('kabupaten_kode')>
                                        <input type="hidden" x-model="selectedKabupatenNama" @entangle('kota_kabupaten')>
                                        <div
                                            x-show="showKabupatenDropdown && filteredKabupaten.length > 0"
                                            class="position-absolute w-100 mt-1 bg-white border border-gray-300 rounded shadow-sm overflow-auto"
                                            style="z-index: 1050; max-height: 240px;"
                                        >
                                            <template x-for="kabupaten in filteredKabupaten" :key="kabupaten.kode">
                                                <div
                                                    @click="selectKabupaten(kabupaten)"
                                                    class="px-4 py-2 cursor-pointer text-gray-800 fs-7"
                                                    style="cursor: pointer;"
                                                    onmouseover="this.style.backgroundColor='#f5f8fa'"
                                                    onmouseout="this.style.backgroundColor='transparent'"
                                                    x-text="kabupaten.nama"
                                                ></div>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <x-ui.form-field label="Tahun Pendirian" :error="$errors->get('tahun_pendirian')">
                                <x-ui.input wire:model="tahun_pendirian" type="number" placeholder="Contoh: 1995" />
                            </x-ui.form-field>
                        </div>
                        <div class="col-md-6">
                            <x-ui.form-field label="Persyarikatan" :error="$errors->get('persyarikatan')">
                                <x-ui.input wire:model="persyarikatan" placeholder="Contoh: Muhammadiyah" />
                            </x-ui.form-field>
                        </div>

                        <div class="col-md-6">
                            <x-ui.form-field label="Nama Mudir / Pimpinan" :error="$errors->get('nama_mudir')">
                                <x-ui.input wire:model="nama_mudir" placeholder="Nama lengkap mudir" />
                            </x-ui.form-field>
                        </div>
                        <div class="col-md-6">
                            <x-ui.form-field label="Jenjang Pendidikan Mudir" :error="$errors->get('jenjang_pendidikan_mudir')">
                                <x-ui.input wire:model="jenjang_pendidikan_mudir" placeholder="Contoh: S1, S2, S3" />
                            </x-ui.form-field>
                        </div>

                        <div class="col-md-6">
                            <x-ui.form-field label="Telepon Pesantren" :error="$errors->get('telp_pesantren')">
                                <x-ui.input wire:model="telp_pesantren" placeholder="Nomor telepon kantor" />
                            </x-ui.form-field>
                        </div>
                        <div class="col-md-6">
                            <x-ui.form-field label="No. HP / WhatsApp" :error="$errors->get('hp_wa')">
                                <x-ui.input wire:model="hp_wa" placeholder="Contoh: 08123456789" />
                            </x-ui.form-field>
                        </div>

                        <div class="col-12">
                            <x-ui.form-field label="Email Pesantren" :error="$errors->get('email_pesantren')">
                                <x-ui.input wire:model="email_pesantren" type="email" placeholder="email@pesantren.sch.id" />
                            </x-ui.form-field>
                        </div>

                        <div class="col-12">
                            <x-ui.form-field label="Visi" :error="$errors->get('visi')">
                                <x-ui.textarea wire:model="visi" rows="3" placeholder="Visi pesantren" />
                            </x-ui.form-field>
                        </div>
                        <div class="col-12">
                            <x-ui.form-field label="Misi" :error="$errors->get('misi')">
                                <x-ui.textarea wire:model="misi" rows="4" placeholder="Misi pesantren" />
                            </x-ui.form-field>
                        </div>
                    </div>
                    </div>
                </x-ui.section-card>

                {{-- B. DATA PESANTREN --}}
                <x-ui.section-card title="B. Data Pesantren" subtitle="Layanan satuan pendidikan dan jumlah rombongan belajar">
                    <div class="p-6">
                    <label class="form-label fw-semibold text-gray-700 fs-7 mb-3">Layanan Satuan Pendidikan</label>
                    <div class="row g-3 mb-6">
                        @foreach (['sd', 'mi', 'smp', 'mts', 'sma', 'ma', 'smk', 'satuan_pesantren_muadalah_(SPM)'] as $item)
                            <div class="col-md-3 col-sm-6">
                                <label class="btn btn-outline btn-outline-dashed btn-active-light-primary d-flex align-items-center w-100 p-4 mb-0 cursor-pointer"
                                       :class="@js(in_array($item, $layanan_satuan_pendidikan ?? [])) ? 'active border-primary bg-light-primary' : ''">
                                    <input
                                        type="checkbox"
                                        class="form-check-input me-3"
                                        wire:model.live="layanan_satuan_pendidikan"
                                        value="{{ $item }}"
                                    />
                                    <span class="fw-semibold text-gray-800 text-uppercase fs-7">
                                        {{ str_replace('_', ' ', $item) }}
                                    </span>
                                </label>
                            </div>
                        @endforeach
                    </div>

                    @if(count($layanan_satuan_pendidikan ?? []) > 0)
                        <div class="separator separator-dashed my-5"></div>
                        <label class="form-label fw-semibold text-gray-700 fs-7 mb-3">Jumlah Rombongan Belajar (Rombel)</label>
                        <div class="row g-5">
                            @foreach ($layanan_satuan_pendidikan as $unit)
                                <div class="col-md-4 col-sm-6">
                                    <div class="border border-gray-300 rounded p-4 bg-light-primary">
                                        <x-ui.form-field
                                            :label="'Rombel ' . strtoupper(str_replace('_', ' ', $unit))"
                                            :error="$errors->get('units_data.' . $unit . '.jumlah_rombel')"
                                        >
                                            <x-ui.input
                                                wire:model="units_data.{{ $unit }}.jumlah_rombel"
                                                type="number"
                                                placeholder="0"
                                            />
                                        </x-ui.form-field>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="separator separator-dashed my-5"></div>
                        <div class="notice d-flex bg-light-warning rounded border-warning border border-dashed p-6 align-items-start">
                            <i class="ki-outline ki-information-5 fs-2tx text-warning me-4 mt-1"></i>
                            <div class="d-flex flex-stack flex-grow-1 flex-wrap flex-md-nowrap">
                                <div class="mb-3 mb-md-0 fw-semibold">
                                    <h4 class="text-gray-900 fw-bold mb-2">Belum ada layanan satuan pendidikan dipilih</h4>
                                    <div class="fs-7 text-gray-700 pe-7">
                                        Centang minimal satu satuan pendidikan di atas (SD, MI, SMP, MTS, SMA, MA, SMK, atau SPM) agar Anda dapat:
                                        <ul class="mt-2 mb-0 ps-4">
                                            <li>Mengisi <strong>Jumlah Rombongan Belajar</strong> per satuan</li>
                                            <li>Mengisi <strong>Data SDM Pesantren</strong> (santri, ustadz, pamong, musyrif, tendik) di menu SDM</li>
                                            <li>Memenuhi syarat akreditasi pesantren</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                    </div>
                </x-ui.section-card>

                {{-- C. DATA LUAS BANGUNAN --}}
                <x-ui.section-card title="C. Data Luas Bangunan" subtitle="Informasi luas tanah dan bangunan pesantren">
                    <div class="p-6">
                    <div class="row g-5">
                        <div class="col-md-6">
                            <x-ui.form-field label="Luas Tanah (m²)" :error="$errors->get('luas_tanah')">
                                <x-ui.input wire:model="luas_tanah" placeholder="Contoh: 5000" />
                            </x-ui.form-field>
                        </div>
                        <div class="col-md-6">
                            <x-ui.form-field label="Luas Bangunan (m²)" :error="$errors->get('luas_bangunan')">
                                <x-ui.input wire:model="luas_bangunan" placeholder="Contoh: 2500" />
                            </x-ui.form-field>
                        </div>
                    </div>
                    </div>
                </x-ui.section-card>

                {{-- D. DOKUMEN UTAMA --}}
                <x-ui.section-card title="D. Dokumen Utama" subtitle="Unggah dokumen utama pesantren (PDF/IMG, max 2MB)">
                    <div class="p-6">
                    <div class="row g-5">
                        @foreach ($mainDocs as $prop => $label)
                            @php $dbField = str_replace('_file', '', $prop); @endphp
                            <div class="col-md-6 col-lg-4">
                                <label class="form-label fw-semibold text-gray-700 fs-7 mb-2">{{ $label }}</label>
                                @if($prop === 'file_lk_iapm_file')
                                    <a href="{{ route('documents.index', ['doc' => 'iapm']) }}"
                                       class="text-primary fs-8 d-inline-flex align-items-center mb-2"
                                       target="_blank"
                                       title="Buka halaman dokumen template IAPM">
                                        <i class="ki-outline ki-cloud-download fs-7 me-1"></i>Unduh Template IAPM
                                    </a>
                                @endif
                                <label
                                    for="{{ $prop }}"
                                    class="d-flex flex-column align-items-center justify-content-center border border-2 border-gray-300 border-dashed rounded p-5 cursor-pointer position-relative"
                                    style="min-height: 160px; background-color: #f9f9f9; transition: all 0.2s;"
                                    onmouseover="this.style.borderColor='#009ef7'; this.style.backgroundColor='#f1faff';"
                                    onmouseout="this.style.borderColor='#e1e3ea'; this.style.backgroundColor='#f9f9f9';"
                                >
                                    <div wire:loading.remove wire:target="{{ $prop }}" class="text-center w-100">
                                        @if ($$prop)
                                            @if (str_contains($$prop->getMimeType(), 'image'))
                                                <img src="{{ $$prop->temporaryUrl() }}" class="rounded mb-2" style="max-height: 80px; max-width: 100%; object-fit: contain;" />
                                            @else
                                                <i class="ki-outline ki-file-up fs-3hx text-success mb-2"></i>
                                            @endif
                                            <div class="fw-bold text-success fs-7 text-truncate w-100">{{ $$prop->getClientOriginalName() }}</div>
                                            <div class="text-muted fs-8 mt-1">Siap Diunggah</div>
                                        @elseif (!empty($existing_files[$dbField]))
                                            <i class="ki-outline ki-file fs-3hx text-primary mb-2"></i>
                                            <div class="fw-bold text-primary fs-7">FILE TERUNGGAH</div>
                                            <div class="text-muted fs-8 mt-1">Klik untuk Ganti</div>
                                        @else
                                            <i class="ki-outline ki-cloud-add fs-3hx text-gray-500 mb-2"></i>
                                            <div class="fw-bold text-gray-700 fs-7">Upload File</div>
                                            <div class="text-muted fs-8 mt-1">PDF/IMG (Max 2MB)</div>
                                        @endif
                                    </div>
                                    <div wire:loading wire:target="{{ $prop }}" class="text-center">
                                        <span class="spinner-border spinner-border-sm text-primary"></span>
                                        <div class="text-muted fs-8 mt-2">Mengunggah...</div>
                                    </div>
                                    <input
                                        type="file"
                                        id="{{ $prop }}"
                                        x-on:change="if(validate($event)) { $wire.upload('{{ $prop }}', $event.target.files[0]) }"
                                        accept="application/pdf,image/png,image/jpeg"
                                        class="d-none"
                                    />
                                </label>

                                @if (!empty($existing_files[$dbField]))
                                    <a href="{{ Storage::url($existing_files[$dbField]) }}" target="_blank" class="d-inline-flex align-items-center text-primary fw-semibold fs-8 mt-2">
                                        <i class="ki-outline ki-eye fs-7 me-1"></i>
                                        LIHAT FILE SAAT INI
                                    </a>
                                @endif
                                @error($prop) <div class="text-danger fs-8 mt-1">{{ $message }}</div> @enderror
                            </div>
                        @endforeach
                    </div>
                    </div>
                </x-ui.section-card>

                {{-- E. DOKUMEN SEKUNDER --}}
                <x-ui.section-card title="E. Dokumen Sekunder" subtitle="Unggah dokumen pendukung pesantren (PDF/IMG, max 2MB)">
                    <div class="p-6">
                    <div class="row g-5">
                        @foreach ($secondaryDocs as $prop => $label)
                            @php $dbField = str_replace('_file', '', $prop); @endphp
                            <div class="col-md-6 col-lg-4">
                                <label class="form-label fw-semibold text-gray-700 fs-7 mb-2">{{ $label }}</label>
                                <label
                                    for="{{ $prop }}"
                                    class="d-flex flex-column align-items-center justify-content-center border border-2 border-gray-300 border-dashed rounded p-5 cursor-pointer position-relative"
                                    style="min-height: 160px; background-color: #f9f9f9; transition: all 0.2s;"
                                    onmouseover="this.style.borderColor='#009ef7'; this.style.backgroundColor='#f1faff';"
                                    onmouseout="this.style.borderColor='#e1e3ea'; this.style.backgroundColor='#f9f9f9';"
                                >
                                    <div wire:loading.remove wire:target="{{ $prop }}" class="text-center w-100">
                                        @if ($$prop)
                                            @if (str_contains($$prop->getMimeType(), 'image'))
                                                <img src="{{ $$prop->temporaryUrl() }}" class="rounded mb-2" style="max-height: 80px; max-width: 100%; object-fit: contain;" />
                                            @else
                                                <i class="ki-outline ki-file-up fs-3hx text-success mb-2"></i>
                                            @endif
                                            <div class="fw-bold text-success fs-7 text-truncate w-100">{{ $$prop->getClientOriginalName() }}</div>
                                            <div class="text-muted fs-8 mt-1">Siap Diunggah</div>
                                        @elseif (!empty($existing_files[$dbField]))
                                            <i class="ki-outline ki-file fs-3hx text-primary mb-2"></i>
                                            <div class="fw-bold text-primary fs-7">FILE TERUNGGAH</div>
                                            <div class="text-muted fs-8 mt-1">Klik untuk Ganti</div>
                                        @else
                                            <i class="ki-outline ki-cloud-add fs-3hx text-gray-500 mb-2"></i>
                                            <div class="fw-bold text-gray-700 fs-7">Upload File</div>
                                            <div class="text-muted fs-8 mt-1">PDF/IMG (Max 2MB)</div>
                                        @endif
                                    </div>
                                    <div wire:loading wire:target="{{ $prop }}" class="text-center">
                                        <span class="spinner-border spinner-border-sm text-primary"></span>
                                        <div class="text-muted fs-8 mt-2">Mengunggah...</div>
                                    </div>
                                    <input
                                        type="file"
                                        id="{{ $prop }}"
                                        x-on:change="if(validate($event)) { $wire.upload('{{ $prop }}', $event.target.files[0]) }"
                                        accept="application/pdf,image/png,image/jpeg"
                                        class="d-none"
                                    />
                                </label>

                                @if (!empty($existing_files[$dbField]))
                                    <a href="{{ Storage::url($existing_files[$dbField]) }}" target="_blank" class="d-inline-flex align-items-center text-primary fw-semibold fs-8 mt-2">
                                        <i class="ki-outline ki-eye fs-7 me-1"></i>
                                        LIHAT FILE SAAT INI
                                    </a>
                                @endif
                                @error($prop) <div class="text-danger fs-8 mt-1">{{ $message }}</div> @enderror
                            </div>
                        @endforeach
                    </div>
                    </div>
                </x-ui.section-card>

                {{-- ACTION BUTTONS --}}
                <div class="d-flex justify-content-end gap-3">
                    <x-ui.button type="button" variant="light" wire:click="toggleEdit">
                        Batal
                    </x-ui.button>
                    <x-ui.button
                        type="button"
                        variant="primary"
                        @click="confirmSave($wire)"
                        wire:loading.attr="disabled"
                    >
                        <span wire:loading.remove wire:target="save">
                            <i class="ki-outline ki-check fs-5 me-1"></i>
                            Simpan Perubahan
                        </span>
                        <span wire:loading wire:target="save">
                            <span class="spinner-border spinner-border-sm me-2"></span>
                            Memproses...
                        </span>
                    </x-ui.button>
                </div>
            </form>
        @else
            {{-- ============================== VIEW MODE ============================== --}}
            <div class="d-flex flex-column gap-6">

                {{-- A. PROFIL PESANTREN --}}
                <x-ui.section-card title="A. Profil Pesantren" subtitle="Informasi identitas dan dasar pesantren">
                    <div class="p-6">
                        <div class="row g-6">
                            <div class="col-md-6">
                                <div class="text-gray-500 fw-semibold fs-8 text-uppercase mb-1">Nama Pesantren</div>
                                <div class="fs-5 fw-bold text-gray-800">{{ $nama_pesantren ?: '-' }}</div>
                            </div>
                            <div class="col-md-6">
                                <div class="text-gray-500 fw-semibold fs-8 text-uppercase mb-1">Nomor Statistik Pesantren (NSP)</div>
                                <div class="fs-5 fw-bold text-gray-800">{{ $ns_pesantren ?: '-' }}</div>
                            </div>
                            <div class="col-12">
                                <div class="text-gray-500 fw-semibold fs-8 text-uppercase mb-1">Alamat</div>
                                <div class="fs-6 fw-semibold text-gray-800">{{ $alamat ?: '-' }}</div>
                            </div>
                            <div class="col-md-6">
                                <div class="text-gray-500 fw-semibold fs-8 text-uppercase mb-1">Provinsi</div>
                                <div class="fs-6 fw-semibold text-gray-800">{{ $provinsi ?: '-' }}</div>
                            </div>
                            <div class="col-md-6">
                                <div class="text-gray-500 fw-semibold fs-8 text-uppercase mb-1">Kota / Kabupaten</div>
                                <div class="fs-6 fw-semibold text-gray-800">{{ $kota_kabupaten ?: '-' }}</div>
                            </div>
                            <div class="col-md-6">
                                <div class="text-gray-500 fw-semibold fs-8 text-uppercase mb-1">Tahun Pendirian</div>
                                <div class="fs-6 fw-semibold text-gray-800">{{ $tahun_pendirian ?: '-' }}</div>
                            </div>
                            <div class="col-md-6">
                                <div class="text-gray-500 fw-semibold fs-8 text-uppercase mb-1">Persyarikatan</div>
                                <div class="fs-6 fw-semibold text-gray-800">{{ $persyarikatan ?: '-' }}</div>
                            </div>
                            <div class="col-md-6">
                                <div class="text-gray-500 fw-semibold fs-8 text-uppercase mb-1">Nama Mudir</div>
                                <div class="fs-6 fw-semibold text-gray-800">{{ $nama_mudir ?: '-' }}</div>
                            </div>
                            <div class="col-md-6">
                                <div class="text-gray-500 fw-semibold fs-8 text-uppercase mb-1">Jenjang Pendidikan Mudir</div>
                                <div class="fs-6 fw-semibold text-gray-800">{{ $jenjang_pendidikan_mudir ?: '-' }}</div>
                            </div>
                            <div class="col-md-6">
                                <div class="text-gray-500 fw-semibold fs-8 text-uppercase mb-1">Telepon</div>
                                <div class="fs-6 fw-semibold text-gray-800">{{ $telp_pesantren ?: '-' }}</div>
                            </div>
                            <div class="col-md-6">
                                <div class="text-gray-500 fw-semibold fs-8 text-uppercase mb-1">No. HP / WhatsApp</div>
                                <div class="fs-6 fw-semibold text-gray-800">{{ $hp_wa ?: '-' }}</div>
                            </div>
                            <div class="col-md-6">
                                <div class="text-gray-500 fw-semibold fs-8 text-uppercase mb-1">Email</div>
                                <div class="fs-6 fw-semibold text-gray-800">{{ $email_pesantren ?: '-' }}</div>
                            </div>
                            <div class="col-md-6">
                                <div class="text-gray-500 fw-semibold fs-8 text-uppercase mb-1">Akreditasi</div>
                                <div>
                                    @php $akreditasi = auth()->user()->akreditasis()->latest()->first(); @endphp
                                    @if($akreditasi && $akreditasi->status == 1)
                                        <span class="badge badge-light-primary fs-7 fw-bold">{{ $akreditasi->peringkat ?? 'Terakreditasi' }}</span>
                                    @elseif($akreditasi)
                                        <span class="badge badge-light-warning fs-7 fw-bold">Proses</span>
                                    @else
                                        <span class="text-gray-600">-</span>
                                    @endif
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="text-gray-500 fw-semibold fs-8 text-uppercase mb-1">Visi</div>
                                <div class="fs-6 text-gray-800" style="white-space: pre-line;">{{ $visi ?: '-' }}</div>
                            </div>
                            <div class="col-12">
                                <div class="text-gray-500 fw-semibold fs-8 text-uppercase mb-1">Misi</div>
                                <div class="fs-6 text-gray-800" style="white-space: pre-line;">{{ $misi ?: '-' }}</div>
                            </div>
                        </div>
                    </div>
                </x-ui.section-card>

                {{-- B. DATA & FASILITAS --}}
                <x-ui.section-card title="B. Data & Fasilitas" subtitle="Layanan pendidikan dan luas wilayah pesantren">
                    <div class="p-6">
                        <div class="mb-6">
                            <div class="text-gray-500 fw-semibold fs-8 text-uppercase mb-3">Layanan Satuan Pendidikan</div>
                            @if($pesantren->units && $pesantren->units->count() > 0)
                                <div class="row g-3">
                                    @foreach($pesantren->units as $unit)
                                        <div class="col-md-4 col-sm-6">
                                            <div class="border border-gray-300 rounded p-4 bg-light d-flex justify-content-between align-items-center">
                                                <span class="fw-bold text-gray-800 text-uppercase fs-7">{{ str_replace('_', ' ', $unit->unit) }}</span>
                                                <span class="badge badge-light-primary fw-bold">{{ $unit->jumlah_rombel ?? 0 }} Rombel</span>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="text-gray-600 fs-7">Belum ada layanan pendidikan yang dipilih.</div>
                            @endif
                        </div>

                        <div class="row g-5">
                            <div class="col-md-6">
                                <div class="border border-gray-300 rounded p-5 bg-light-success">
                                    <div class="text-gray-500 fw-semibold fs-8 text-uppercase mb-2">Luas Tanah</div>
                                    <div class="fs-2 fw-bold text-gray-800">{{ $luas_tanah ?: '0' }} <span class="fs-6 fw-semibold text-gray-600">m²</span></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="border border-gray-300 rounded p-5 bg-light-info">
                                    <div class="text-gray-500 fw-semibold fs-8 text-uppercase mb-2">Luas Bangunan</div>
                                    <div class="fs-2 fw-bold text-gray-800">{{ $luas_bangunan ?: '0' }} <span class="fs-6 fw-semibold text-gray-600">m²</span></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </x-ui.section-card>

                {{-- C. DOKUMEN TERSIMPAN --}}
                <x-ui.section-card title="C. Dokumen Tersimpan" subtitle="Daftar dokumen yang sudah diunggah">
                    <div class="p-6">
                        @php $allDocs = array_merge($mainDocs ?? [], $secondaryDocs ?? []); @endphp
                        <div class="row g-4">
                            @foreach($allDocs as $prop => $label)
                                @php $dbField = str_replace('_file', '', $prop); @endphp
                                @if(!empty($existing_files[$dbField]))
                                    <div class="col-md-6 col-lg-4">
                                        <a href="{{ Storage::url($existing_files[$dbField]) }}" target="_blank" class="d-flex align-items-center border border-gray-300 rounded p-4 text-decoration-none text-gray-800 hover-bg-light h-100">
                                            <i class="ki-outline ki-file fs-2hx text-primary me-3"></i>
                                            <div class="d-flex flex-column">
                                                <span class="fw-bold fs-7 text-gray-800">{{ $label }}</span>
                                                <span class="text-muted fs-8">Klik untuk lihat dokumen</span>
                                            </div>
                                        </a>
                                    </div>
                                @endif
                            @endforeach
                            @if(empty(array_filter($existing_files)))
                                <div class="col-12">
                                    <div class="text-gray-600 fs-7 text-center py-5">Belum ada dokumen yang diunggah.</div>
                                </div>
                            @endif
                        </div>
                    </div>
                </x-ui.section-card>
            </div>
        @endif
</x-ui.page>
