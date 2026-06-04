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
    public $profilePhotoPath;

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
        $this->profilePhotoPath = auth()->user()->profile_photo_path;
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

        try {
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
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->dispatch('show-validation-error');
            throw $e;
        }

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

        // foto stays on public disk (non-sensitive)
        // Store new foto first; only delete old if store succeeds (PM-1 pattern)
        if ($this->foto_upload) {
            $newFotoPath = $this->foto_upload->store('asesor_docs', 'public');
            if ($newFotoPath) {
                $oldFotoPath = $this->asesor->foto;
                $data['foto'] = $newFotoPath;
            }
        }

        // KTP / ijazah / kartu_nbm are PII — stored on local (private) disk
        $privateFields = [
            'ktp_file' => 'ktp_file_upload',
            'ijazah_file' => 'ijazah_file_upload',
            'kartu_nbm_file' => 'kartu_nbm_file_upload',
        ];

        $newPrivatePaths = [];
        foreach ($privateFields as $dbField => $property) {
            if ($this->$property) {
                $newPath = $this->$property->store('asesor_private_docs', 'local');
                if ($newPath) {
                    $newPrivatePaths[$dbField] = [
                        'old' => $this->asesor->$dbField,
                        'new' => $newPath,
                    ];
                    $data[$dbField] = $newPath;
                }
            }
        }

        $success = $asesorService->updateProfile(auth()->id(), $data);

        if ($success) {
            // DB succeeded — now safe to delete old files
            if (isset($newFotoPath) && isset($oldFotoPath) && $oldFotoPath) {
                Storage::disk('public')->delete($oldFotoPath);
            }
            foreach ($newPrivatePaths as ['old' => $oldPath]) {
                if ($oldPath) {
                    Storage::disk('local')->delete($oldPath);
                }
            }

            // Update password if provided
            if ($this->password) {
                auth()->user()->update([
                    'password' => \Illuminate\Support\Facades\Hash::make($this->password)
                ]);
                $this->password = null;
            }

            $this->isEditing = false;
            $this->mount();

            $this->dispatch(
                'notification-received',
                type: 'success',
                title: 'Berhasil!',
                message: 'Profil asesor berhasil diperbarui.'
            );
        } else {
            // DB failed — rollback: delete newly stored files
            if (isset($newFotoPath)) {
                Storage::disk('public')->delete($newFotoPath);
            }
            foreach ($newPrivatePaths as ['new' => $newPath]) {
                Storage::disk('local')->delete($newPath);
            }

            $this->dispatch('show-metronic-alert', type: 'error', title: 'Gagal', message: 'Profil asesor gagal disimpan.');
        }
    }
}; ?>

<x-ui.page title="Profil Asesor" subtitle="Kelola informasi data diri, pengalaman, dan dokumen Anda." class="spm-detail-page">
    <x-slot:toolbar>
        @if($isEditing)
            <x-ui.button type="button" wire:click="toggleEdit" variant="light">
                <x-ui.icon name="cross" class="fs-4 me-1" />
                Batal Edit
            </x-ui.button>
        @else
            <x-ui.button type="button" wire:click="toggleEdit" variant="primary">
                <x-ui.icon name="pencil" class="fs-4 me-1" />
                Edit Profil
            </x-ui.button>
        @endif
    </x-slot:toolbar>

    <livewire:profile.update-profile-photo />

    @if($isEditing)
    {{-- ===== EDIT MODE ===== --}}
    <form x-on:submit.prevent="confirmSaveProfile($wire)" x-data="{ ...fileManagement(), ...asesorManagement() }">
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
                        {{-- Foto profil kini dikelola di komponen terpadu di atas halaman. --}}
                        {{-- Fields --}}
                        <div class="col-lg-12">
                            <div class="row g-5">
                                <div class="col-md-6">
                                    <x-ui.form-field label="Nama Lengkap (dengan Gelar)" required>
                                        <x-ui.input wire:model="nama_dengan_gelar" placeholder="Dr. Nama, M.Pd" />
                                        @error('nama_dengan_gelar') <div class="text-danger fs-8 mt-1">{{ $message }}</div> @enderror
                                    </x-ui.form-field>
                                </div>
                                <div class="col-md-6">
                                    <x-ui.form-field label="Nama Lengkap (Tanpa Gelar)" required>
                                        <x-ui.input wire:model="nama_tanpa_gelar" />
                                        @error('nama_tanpa_gelar') <div class="text-danger fs-8 mt-1">{{ $message }}</div> @enderror
                                    </x-ui.form-field>
                                </div>
                                <div class="col-md-6">
                                    <x-ui.form-field label="NBM / NIA">
                                        <x-ui.input wire:model="nbm_nia" />
                                    </x-ui.form-field>
                                </div>
                                <div class="col-md-6">
                                    <x-ui.form-field label="Nomor Induk Asesor PM">
                                        <x-ui.input wire:model="nomor_induk_asesor_pm" />
                                    </x-ui.form-field>
                                </div>
                                <div class="col-md-6">
                                    <x-ui.form-field label="NIK / Nomor KTP">
                                        <x-ui.input wire:model="nik" />
                                    </x-ui.form-field>
                                </div>
                                <div class="col-md-6">
                                    <x-ui.form-field label="No. WhatsApp">
                                        <x-ui.input wire:model="whatsapp" placeholder="08xxxxxxxxx" />
                                    </x-ui.form-field>
                                </div>
                                <div class="col-md-6">
                                    <x-ui.form-field label="Email Pribadi">
                                        <x-ui.input wire:model="email_pribadi" type="email" />
                                        @error('email_pribadi') <div class="text-danger fs-8 mt-1">{{ $message }}</div> @enderror
                                    </x-ui.form-field>
                                </div>
                                <div class="col-md-6">
                                    <x-ui.form-field label="Password Baru">
                                        <x-ui.input wire:model="password" type="password" placeholder="Kosongkan jika tidak diubah" />
                                        @error('password') <div class="text-danger fs-8 mt-1">{{ $message }}</div> @enderror
                                    </x-ui.form-field>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="separator my-6"></div>

                    <div class="row g-5">
                        <div class="col-md-4">
                            <x-ui.form-field label="Tempat Lahir">
                                <x-ui.input wire:model="tempat_lahir" />
                            </x-ui.form-field>
                        </div>
                        <div class="col-md-4">
                            <x-ui.form-field label="Tanggal Lahir">
                                <x-ui.input wire:model="tanggal_lahir" type="date" />
                            </x-ui.form-field>
                        </div>
                        <div class="col-md-4">
                            <x-ui.form-field label="Jenis Kelamin">
                                <x-ui.select wire:model="jenis_kelamin">
                                    <option value="">Pilih</option>
                                    <option value="Laki-Laki">Laki-Laki</option>
                                    <option value="Perempuan">Perempuan</option>
                                </x-ui.select>
                            </x-ui.form-field>
                        </div>
                        <div class="col-md-4">
                            <x-ui.form-field label="Status Perkawinan">
                                <x-ui.select wire:model="status_perkawinan">
                                    <option value="">Pilih</option>
                                    <option value="Belum Kawin">Belum Kawin</option>
                                    <option value="Kawin">Kawin</option>
                                    <option value="Cerai Hidup">Cerai Hidup</option>
                                    <option value="Cerai Mati">Cerai Mati</option>
                                </x-ui.select>
                            </x-ui.form-field>
                        </div>
                        <div class="col-md-4">
                            <x-ui.form-field label="Pendidikan Terakhir">
                                <x-ui.input wire:model="pendidikan_terakhir" placeholder="Contoh: S2 Pendidikan" />
                            </x-ui.form-field>
                        </div>
                        <div class="col-md-4">
                            <x-ui.form-field label="Tahun Terbit Sertifikat">
                                <x-ui.input wire:model="tahun_terbit_sertifikat" placeholder="Contoh: 2024" />
                            </x-ui.form-field>
                        </div>
                        <div class="col-12">
                            <x-ui.form-field label="Alamat Rumah">
                                <x-ui.textarea wire:model="alamat_rumah" rows="2" />
                            </x-ui.form-field>
                        </div>

                        {{-- Wilayah selector --}}
                        <div class="col-12" x-data="wilayahSelector({
                            selectedProvinsiNama: $wire.entangle('provinsi'),
                            selectedKabupatenNama: $wire.entangle('kota_kabupaten')
                        })">
                            <div class="row g-5">
                                <div class="col-md-6">
                                    <x-ui.form-field label="Provinsi">
                                        <div class="position-relative">
                                            <x-ui.input type="text"
                                                x-model="provinsiSearch"
                                                placeholder="Cari Provinsi..."
                                                @focus="showProvinsiConfig = true"
                                                @click.outside="showProvinsiConfig = false" />
                                            <div x-show="showProvinsiConfig && filteredProvinsi.length > 0"
                                                 class="position-absolute w-100 mt-1 bg-white border rounded shadow-sm"
                                                 style="z-index:50;max-height:200px;overflow-y:auto;">
                                                <template x-for="item in filteredProvinsi" :key="item.kode">
                                                    <div @click="selectProvinsi(item)"
                                                         class="px-4 py-2 cursor-pointer fs-7 hover-bg-light"
                                                         x-text="item.nama"></div>
                                                </template>
                                            </div>
                                        </div>
                                    </x-ui.form-field>
                                </div>
                                <div class="col-md-6">
                                    <x-ui.form-field label="Kota / Kabupaten">
                                        <div class="position-relative">
                                            <x-ui.input type="text"
                                                x-model="kabupatenSearch"
                                                placeholder="Cari Kota/Kabupaten..."
                                                @focus="showKabupatenConfig = true"
                                                @click.outside="showKabupatenConfig = false"
                                                x-bind:disabled="!currentProvinsiKode" />
                                            <div x-show="showKabupatenConfig && filteredKabupaten.length > 0"
                                                 class="position-absolute w-100 mt-1 bg-white border rounded shadow-sm"
                                                 style="z-index:50;max-height:200px;overflow-y:auto;">
                                                <template x-for="item in filteredKabupaten" :key="item.kode">
                                                    <div @click="selectKabupaten(item)"
                                                         class="px-4 py-2 cursor-pointer fs-7 hover-bg-light"
                                                         x-text="item.nama"></div>
                                                </template>
                                            </div>
                                        </div>
                                    </x-ui.form-field>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="separator my-6"></div>

                    <div class="row g-5">
                        <div class="col-md-6">
                            <x-ui.form-field label="Profesi">
                                <x-ui.select wire:model="profesi">
                                    <option value="">Pilih Profesi</option>
                                    <option value="Guru">Guru</option>
                                    <option value="Pengawas">Pengawas</option>
                                    <option value="Dosen">Dosen</option>
                                    <option value="Kepala Sekolah">Kepala Sekolah</option>
                                    <option value="Widyaiswara Pendidikan">Widyaiswara Pendidikan</option>
                                    <option value="Widyaprada">Widyaprada</option>
                                    <option value="Lainnya">Lainnya</option>
                                </x-ui.select>
                            </x-ui.form-field>
                        </div>
                        <div class="col-md-6">
                            <x-ui.form-field label="Jabatan Utama">
                                <x-ui.input wire:model="jabatan_utama" />
                            </x-ui.form-field>
                        </div>
                        <div class="col-md-6">
                            <x-ui.form-field label="Unit Tempat Kerja">
                                <x-ui.input wire:model="unit_kerja" />
                            </x-ui.form-field>
                        </div>
                        <div class="col-md-6">
                            <x-ui.form-field label="No. Telp Kantor">
                                <x-ui.input wire:model="telp_kantor" />
                            </x-ui.form-field>
                        </div>
                        <div class="col-12">
                            <x-ui.form-field label="Alamat Kantor">
                                <x-ui.textarea wire:model="alamat_kantor" rows="2" />
                            </x-ui.form-field>
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
                                <x-ui.button type="button" wire:click="addRow('riwayat_pendidikan')" variant="light" size="sm">
                                    <x-ui.icon name="plus" class="fs-5 me-1" /> Tambah
                                </x-ui.button>
                            </div>
                            @foreach($riwayat_pendidikan as $index => $item)
                            <div class="row g-3 align-items-end mb-3 p-3 bg-light rounded">
                                <div class="col-md-4">
                                    <x-ui.form-field label="Institusi / Dimana">
                                        <x-ui.input wire:model="riwayat_pendidikan.{{ $index }}.dimana" placeholder="Nama Sekolah/Univ" />
                                    </x-ui.form-field>
                                </div>
                                <div class="col-md-3">
                                    <x-ui.form-field label="Jenjang">
                                        <x-ui.input wire:model="riwayat_pendidikan.{{ $index }}.jenjang" placeholder="S1, S2, dll" />
                                    </x-ui.form-field>
                                </div>
                                <div class="col-md-3">
                                    <x-ui.form-field label="Tahun">
                                        <x-ui.input wire:model="riwayat_pendidikan.{{ $index }}.kapan" placeholder="2010-2014" />
                                    </x-ui.form-field>
                                </div>
                                <div class="col-md-2 d-flex align-items-end pb-1">
                                    <x-ui.icon-button type="button" wire:click="removeRow('riwayat_pendidikan', {{ $index }})" variant="light-danger" icon="trash" label="Hapus" />
                                </div>
                            </div>
                            @endforeach
                        </div>

                        <div class="separator"></div>

                        {{-- Pengalaman Bekerja --}}
                        <div>
                            <div class="d-flex align-items-center justify-content-between mb-4">
                                <div class="fw-semibold fs-6">Pengalaman Bekerja</div>
                                <x-ui.button type="button" wire:click="addRow('pengalaman_bekerja')" variant="light" size="sm">
                                    <x-ui.icon name="plus" class="fs-5 me-1" /> Tambah
                                </x-ui.button>
                            </div>
                            @foreach($pengalaman_bekerja as $index => $item)
                            <div class="row g-3 align-items-end mb-3 p-3 bg-light rounded">
                                <div class="col-md-4">
                                    <x-ui.form-field label="Instansi / Dimana">
                                        <x-ui.input wire:model="pengalaman_bekerja.{{ $index }}.dimana" />
                                    </x-ui.form-field>
                                </div>
                                <div class="col-md-3">
                                    <x-ui.form-field label="Jabatan / Sebagai">
                                        <x-ui.input wire:model="pengalaman_bekerja.{{ $index }}.sebagai" />
                                    </x-ui.form-field>
                                </div>
                                <div class="col-md-3">
                                    <x-ui.form-field label="Tahun">
                                        <x-ui.input wire:model="pengalaman_bekerja.{{ $index }}.kapan" />
                                    </x-ui.form-field>
                                </div>
                                <div class="col-md-2 d-flex align-items-end pb-1">
                                    <x-ui.icon-button type="button" wire:click="removeRow('pengalaman_bekerja', {{ $index }})" variant="light-danger" icon="trash" label="Hapus" />
                                </div>
                            </div>
                            @endforeach
                        </div>

                        <div class="separator"></div>

                        {{-- Pelatihan & Organisasi --}}
                        <div class="row g-6">
                            <div class="col-lg-6">
                                <div class="d-flex align-items-center justify-content-between mb-4">
                                    <div class="fw-semibold fs-6">Pengalaman Pelatihan</div>
                                    <x-ui.button type="button" wire:click="addRow('pengalaman_pelatihan')" variant="light" size="sm">
                                        <x-ui.icon name="plus" class="fs-5 me-1" /> Tambah
                                    </x-ui.button>
                                </div>
                                @foreach($pengalaman_pelatihan as $index => $item)
                                <div class="p-3 bg-light rounded mb-3">
                                    <div class="row g-3">
                                        <div class="col-12">
                                            <x-ui.form-field label="Penyelenggara">
                                                <x-ui.input wire:model="pengalaman_pelatihan.{{ $index }}.dimana" />
                                            </x-ui.form-field>
                                        </div>
                                        <div class="col-8">
                                            <x-ui.form-field label="Peran / Sebagai">
                                                <x-ui.input wire:model="pengalaman_pelatihan.{{ $index }}.sebagai" />
                                            </x-ui.form-field>
                                        </div>
                                        <div class="col-4">
                                            <x-ui.form-field label="Tahun">
                                                <x-ui.input wire:model="pengalaman_pelatihan.{{ $index }}.kapan" />
                                            </x-ui.form-field>
                                        </div>
                                        <div class="col-12 d-flex justify-content-end">
                                            <x-ui.icon-button type="button" wire:click="removeRow('pengalaman_pelatihan', {{ $index }})" variant="light-danger" icon="trash" label="Hapus" />
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>

                            <div class="col-lg-6">
                                <div class="d-flex align-items-center justify-content-between mb-4">
                                    <div class="fw-semibold fs-6">Pengalaman Berorganisasi</div>
                                    <x-ui.button type="button" wire:click="addRow('pengalaman_berorganisasi')" variant="light" size="sm">
                                        <x-ui.icon name="plus" class="fs-5 me-1" /> Tambah
                                    </x-ui.button>
                                </div>
                                @foreach($pengalaman_berorganisasi as $index => $item)
                                <div class="p-3 bg-light rounded mb-3">
                                    <div class="row g-3">
                                        <div class="col-12">
                                            <x-ui.form-field label="Nama Organisasi">
                                                <x-ui.input wire:model="pengalaman_berorganisasi.{{ $index }}.dimana" />
                                            </x-ui.form-field>
                                        </div>
                                        <div class="col-8">
                                            <x-ui.form-field label="Jabatan / Sebagai">
                                                <x-ui.input wire:model="pengalaman_berorganisasi.{{ $index }}.sebagai" />
                                            </x-ui.form-field>
                                        </div>
                                        <div class="col-4">
                                            <x-ui.form-field label="Tahun">
                                                <x-ui.input wire:model="pengalaman_berorganisasi.{{ $index }}.kapan" />
                                            </x-ui.form-field>
                                        </div>
                                        <div class="col-12 d-flex justify-content-end">
                                            <x-ui.icon-button type="button" wire:click="removeRow('pengalaman_berorganisasi', {{ $index }})" variant="light-danger" icon="trash" label="Hapus" />
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="separator"></div>

                        {{-- Karya Publikasi --}}
                        <div>
                            <div class="d-flex align-items-center justify-content-between mb-4">
                                <div class="fw-semibold fs-6">Karya Publikasi</div>
                                <x-ui.button type="button" wire:click="addRow('karya_publikasi')" variant="light" size="sm">
                                    <x-ui.icon name="plus" class="fs-5 me-1" /> Tambah
                                </x-ui.button>
                            </div>
                            @foreach($karya_publikasi as $index => $item)
                            <div class="row g-3 align-items-end mb-3 p-3 bg-light rounded">
                                <div class="col-md-6">
                                    <x-ui.form-field label="Judul Karya">
                                        <x-ui.input wire:model="karya_publikasi.{{ $index }}.judul" />
                                    </x-ui.form-field>
                                </div>
                                <div class="col-md-4">
                                    <x-ui.form-field label="Link (opsional)">
                                        <x-ui.input wire:model="karya_publikasi.{{ $index }}.link" placeholder="https://..." />
                                    </x-ui.form-field>
                                </div>
                                <div class="col-md-2 d-flex align-items-end pb-1">
                                    <x-ui.icon-button type="button" wire:click="removeRow('karya_publikasi', {{ $index }})" variant="light-danger" icon="trash" label="Hapus" />
                                </div>
                            </div>
                            @endforeach
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
                                'ktp_file_upload'       => ['label' => 'KTP / Identitas',    'existing_key' => 'ktp_file'],
                                'ijazah_file_upload'    => ['label' => 'Ijazah Terakhir',     'existing_key' => 'ijazah_file'],
                                'kartu_nbm_file_upload' => ['label' => 'Kartu NBM / NIA',     'existing_key' => 'kartu_nbm_file'],
                            ];
                        @endphp
                        @foreach($uploadDocs as $prop => $doc)
                        <div class="col-md-4">
                            <x-ui.form-field :label="$doc['label']">
                                <x-ui.file-upload
                                    :model="$prop"
                                    :id="$prop"
                                    accept="application/pdf,image/png,image/jpeg"
                                    :file="$$prop"
                                    placeholder="Klik untuk unggah"
                                    label-class="d-flex flex-column align-items-center justify-content-center border border-2 border-dashed rounded p-4 cursor-pointer hover-border-primary"
                                    style="min-height:120px;"
                                >
                                    @if($$prop)
                                        <x-ui.icon name="document" class="fs-2x text-success mb-2" />
                                        <span class="fs-8 fw-semibold text-success">{{ $$prop->getClientOriginalName() }}</span>
                                        <span class="fs-9 text-muted">Siap diunggah</span>
                                    @elseif($existing_files[$doc['existing_key']])
                                        <x-ui.icon name="document" class="fs-2x text-primary mb-2" />
                                        <span class="fs-8 fw-semibold text-muted">File terunggah</span>
                                        <span class="fs-9 text-primary">Klik untuk ganti</span>
                                    @else
                                        <x-ui.icon name="cloud-upload" class="fs-2x text-muted mb-2" />
                                        <span class="fs-8 text-muted">Klik untuk unggah</span>
                                        <span class="fs-9 text-muted">PDF/JPG/PNG, maks 2MB</span>
                                    @endif
                                </x-ui.file-upload>
                                @if($existing_files[$doc['existing_key']])
                                <x-ui.button :href="Storage::url($existing_files[$doc['existing_key']])" target="_blank" variant="light-primary" size="sm" class="mt-2">
                                    <x-ui.icon name="eye" class="fs-6 me-1" /> Lihat file saat ini
                                </x-ui.button>
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
        {{-- Sidebar --}}
        <div class="col-xl-4">
            <div class="d-flex flex-column gap-6">
                <x-ui.card>
                    <div class="d-flex flex-column align-items-center text-center">
                        <div class="mb-5">
                            @if($profilePhotoPath && Storage::disk('public')->exists($profilePhotoPath))
                                <img src="{{ Storage::url($profilePhotoPath) }}"
                                     alt="Foto asesor"
                                     loading="lazy"
                                     class="rounded-circle object-fit-cover border border-3 border-light shadow-sm"
                                     style="width:100px;height:100px;">
                            @else
                                <div class="spm-profile-avatar d-flex align-items-center justify-content-center">
                                    {{ substr($nama_dengan_gelar ?? 'A', 0, 1) }}
                                </div>
                            @endif
                        </div>
                        <h2 class="spm-card-title fs-4 mb-1">{{ $nama_dengan_gelar ?: '-' }}</h2>
                        <div class="text-muted fw-semibold fs-8 text-uppercase mb-1">
                            NIA PM: {{ $nomor_induk_asesor_pm ?: '-' }}
                        </div>
                        <div class="text-muted fs-8 mb-4">
                            {{ $profesi ?: '' }}{{ ($profesi && $jabatan_utama) ? ' · ' : '' }}{{ $jabatan_utama ?: '' }}
                        </div>
                        <x-ui.status-badge variant="success">Asesor Aktif</x-ui.status-badge>
                    </div>
                </x-ui.card>

                <x-ui.section-card title="Informasi Kontak">
                    <div class="p-6">
                        <div class="row g-5">
                            <x-ui.detail-item label="Email Pribadi" :value="$email_pribadi ?: '-'" span="2" />
                            <x-ui.detail-item label="No. WhatsApp" :value="$whatsapp ?: '-'" span="2" />
                            <x-ui.detail-item label="Domisili"
                                :value="($kota_kabupaten ?: '-') . ', ' . ($provinsi ?: '-')" span="2" />
                        </div>
                    </div>
                </x-ui.section-card>
            </div>
        </div>

        {{-- Main --}}
        <div class="col-xl-8">
            <div class="d-flex flex-column gap-6">

                <x-ui.section-card title="A. Identitas Diri" subtitle="Data pribadi dan informasi pekerjaan.">
                    <div class="p-6">
                        <div class="row g-5">
                            <x-ui.detail-item label="Nama Lengkap (Tanpa Gelar)" :value="$nama_tanpa_gelar ?: '-'" />
                            <x-ui.detail-item label="NIK / Nomor KTP" :value="$nik ?: '-'" />
                            <x-ui.detail-item label="NBM / NIA" :value="($nbm_nia ?: '-') . ' / ' . ($nomor_induk_asesor_pm ?: '-')" />
                            <x-ui.detail-item label="Tempat, Tanggal Lahir"
                                :value="($tempat_lahir ?: '-') . ', ' . ($tanggal_lahir ? \Carbon\Carbon::parse($tanggal_lahir)->translatedFormat('d F Y') : '-')" />
                            <x-ui.detail-item label="Jenis Kelamin" :value="$jenis_kelamin ?: '-'" />
                            <x-ui.detail-item label="Status Perkawinan" :value="$status_perkawinan ?: '-'" />
                            <x-ui.detail-item label="Pendidikan Terakhir" :value="$pendidikan_terakhir ?: '-'" />
                            <x-ui.detail-item label="Tahun Sertifikat Terbit" :value="$tahun_terbit_sertifikat ?: '-'" />
                            <x-ui.detail-item label="Alamat Rumah" span="2">
                                <div class="spm-detail-block spm-detail-value-muted">{{ $alamat_rumah ?: '-' }}</div>
                            </x-ui.detail-item>
                        </div>
                        <div class="separator my-6"></div>
                        <div class="row g-5">
                            <x-ui.detail-item label="Profesi" :value="$profesi ?: '-'" />
                            <x-ui.detail-item label="Jabatan Utama" :value="$jabatan_utama ?: '-'" />
                            <x-ui.detail-item label="Unit Kerja" :value="$unit_kerja ?: '-'" />
                            <x-ui.detail-item label="Telp Kantor" :value="$telp_kantor ?: '-'" />
                            <x-ui.detail-item label="Alamat Kantor" span="2">
                                <div class="spm-detail-block spm-detail-value-muted">{{ $alamat_kantor ?: '-' }}</div>
                            </x-ui.detail-item>
                        </div>
                    </div>
                </x-ui.section-card>

                <x-ui.section-card title="B. Pengalaman & Rekam Jejak" subtitle="Riwayat pendidikan, pekerjaan, pelatihan, organisasi, dan publikasi.">
                    <div class="p-6">
                        <div class="d-flex flex-column gap-8">

                            <div>
                                <div class="text-uppercase fw-semibold fs-8 text-muted mb-3">Riwayat Pendidikan</div>
                                @forelse(array_filter($riwayat_pendidikan, fn($i) => !empty($i['dimana'])) as $item)
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
                                @forelse(array_filter($pengalaman_bekerja, fn($i) => !empty($i['dimana'])) as $item)
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
                                    @forelse(array_filter($pengalaman_pelatihan, fn($i) => !empty($i['dimana'])) as $item)
                                        <div class="py-3 border-bottom border-dashed">
                                            <div class="fw-semibold fs-7">{{ $item['sebagai'] ?? '-' }}</div>
                                            <div class="d-flex justify-content-between mt-1">
                                                <span class="text-muted fs-8 text-truncate" style="max-width:160px">{{ $item['dimana'] }}</span>
                                                <span class="fw-semibold fs-8 text-primary">{{ $item['kapan'] ?? '-' }}</span>
                                            </div>
                                        </div>
                                    @empty
                                        <x-ui.empty-state title="Belum Ada Data" description="Data pelatihan belum diisi." />
                                    @endforelse
                                </div>
                                <div class="col-lg-6">
                                    <div class="text-uppercase fw-semibold fs-8 text-muted mb-3">Organisasi</div>
                                    @forelse(array_filter($pengalaman_berorganisasi, fn($i) => !empty($i['dimana'])) as $item)
                                        <div class="py-3 border-bottom border-dashed">
                                            <div class="fw-semibold fs-7">{{ $item['sebagai'] ?? '-' }}</div>
                                            <div class="d-flex justify-content-between mt-1">
                                                <span class="text-muted fs-8 text-truncate" style="max-width:160px">{{ $item['dimana'] }}</span>
                                                <span class="fw-semibold fs-8 text-primary">{{ $item['kapan'] ?? '-' }}</span>
                                            </div>
                                        </div>
                                    @empty
                                        <x-ui.empty-state title="Belum Ada Data" description="Data organisasi belum diisi." />
                                    @endforelse
                                </div>
                            </div>

                            <div>
                                <div class="text-uppercase fw-semibold fs-8 text-muted mb-3">Karya Publikasi</div>
                                @forelse(array_filter($karya_publikasi, fn($i) => !empty($i['judul'])) as $item)
                                    <div class="d-flex align-items-center justify-content-between py-3 border-bottom border-dashed">
                                        <span class="fw-semibold fs-7 text-truncate pe-3">{{ $item['judul'] }}</span>
                                        @if(!empty($item['link']))
                                            <x-ui.button :href="$item['link']" target="_blank" variant="light-primary" size="sm">
                                                <x-ui.icon name="exit-right" class="fs-5" />
                                            </x-ui.button>
                                        @endif
                                    </div>
                                @empty
                                    <x-ui.empty-state title="Belum Ada Data" description="Karya publikasi belum diisi." />
                                @endforelse
                            </div>

                        </div>
                    </div>
                </x-ui.section-card>

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
                                    :href="$existing_files[$field] ? route('secure.asesor-docs', ['asesorId' => $asesor->id, 'field' => $field]) : null"
                                />
                            @endforeach
                        </div>
                    </div>
                </x-ui.section-card>

            </div>
        </div>
    </div>
    @endif

</x-ui.page>
