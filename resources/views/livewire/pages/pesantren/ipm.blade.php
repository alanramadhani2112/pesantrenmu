<?php

use App\Models\Ipm;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;

new #[Layout('layouts.app')] class extends Component {
    use WithFileUploads;

    public $ipm;

    public $nsp_file_upload;
    public $lulus_santri_file_upload;
    public $kurikulum_file_upload;
    public $buku_ajar_file_upload;

    public $existing_files = [];

    public function mount()
    {
        if (!auth()->user()->isPesantren()) {
            abort(403);
        }

        $pesantrenService = app(\App\Services\PesantrenService::class);
        $this->ipm = $pesantrenService->getIpm(auth()->id());

        $this->existing_files = [
            'nsp_file' => $this->ipm->nsp_file,
            'lulus_santri_file' => $this->ipm->lulus_santri_file,
            'kurikulum_file' => $this->ipm->kurikulum_file,
            'buku_ajar_file' => $this->ipm->buku_ajar_file,
        ];
    }

    protected function messages()
    {
        return [
            'required' => ':attribute wajib diisi.',
            'mimes' => ':attribute harus berformat PDF.',
            'max' => 'Ukuran :attribute tidak boleh lebih dari :max KB (2MB).',
            'uploaded' => ':attribute gagal diunggah. Kemungkinan file terlalu besar (Max 2MB) atau koneksi terputus.',
        ];
    }

    protected function validationAttributes()
    {
        return [
            'nsp_file_upload' => 'File NSP',
            'lulus_santri_file_upload' => 'File Lulus Santri',
            'kurikulum_file_upload' => 'File Kurikulum',
            'buku_ajar_file_upload' => 'File Buku Ajar',
        ];
    }

    public function save()
    {
        $pesantrenService = app(\App\Services\PesantrenService::class);
        if (auth()->user()->pesantren->is_locked) {
            $this->dispatch('show-metronic-alert', type: 'error', title: 'Akses Ditolak', message: 'Data terkunci karena sedang dalam proses akreditasi.');
            return;
        }

        $this->validate([
            'nsp_file_upload' => 'nullable|mimes:pdf|max:2048',
            'lulus_santri_file_upload' => 'nullable|mimes:pdf|max:2048',
            'kurikulum_file_upload' => 'nullable|mimes:pdf|max:2048',
            'buku_ajar_file_upload' => 'nullable|mimes:pdf|max:2048',
        ]);

        $data = [];
        $fileFields = [
            'nsp_file' => 'nsp_file_upload',
            'lulus_santri_file' => 'lulus_santri_file_upload',
            'kurikulum_file' => 'kurikulum_file_upload',
            'buku_ajar_file' => 'buku_ajar_file_upload',
        ];

        foreach ($fileFields as $dbField => $property) {
            if ($this->$property) {
                if ($this->ipm->$dbField) {
                    Storage::disk('public')->delete($this->ipm->$dbField);
                }

                $data[$dbField] = $this->$property->store('ipm_docs', 'public');
                $this->existing_files[$dbField] = $data[$dbField];
            }
        }

        if (!empty($data)) {
            if ($pesantrenService->updateIpm(auth()->id(), $data)) {
                $this->dispatch('notification-received', type: 'success', title: 'Berhasil!', message: 'Data IPM berhasil diperbarui.');
            }
        }
    }
}; ?>

@php
    $isLocked = auth()->user()->pesantren->is_locked;
    $criteria = [
        [
            'property' => 'nsp_file_upload',
            'label' => '1. Pesantren telah memiliki izin operasional Kementerian Agama (Nomor Statistik Pesantren / NSP).',
            'description' => 'Unggah bukti legalitas operasional yang berlaku.',
            'field' => 'nsp_file',
        ],
        [
            'property' => 'lulus_santri_file_upload',
            'label' => '2. Pesantren pernah meluluskan santri dan/atau memiliki santri kelas akhir.',
            'description' => 'Unggah bukti kelulusan atau kelas akhir.',
            'field' => 'lulus_santri_file',
        ],
        [
            'property' => 'kurikulum_file_upload',
            'label' => '3. Pesantren menyelenggarakan kurikulum Dirasah Islamiyah sesuai standar LP2 PPM.',
            'description' => 'Unggah dokumen kurikulum yang digunakan.',
            'field' => 'kurikulum_file',
        ],
        [
            'property' => 'buku_ajar_file_upload',
            'label' => '4. Pesantren menggunakan buku ajar Dirasah Islamiyah terbitan LP2 PPM.',
            'description' => 'Unggah buku ajar atau referensi resmi yang dipakai.',
            'field' => 'buku_ajar_file',
        ],
    ];
@endphp

<x-slot name="header">{{ __('Indek Pemenuhan Mutlak (IPM)') }}</x-slot>

<x-ui.page
    title="Indikator Pemenuhan Mutlak (IPM)"
    subtitle="Unggah dokumen pendukung untuk empat kriteria pemenuhan mutlak."
    data-module-page="pesantren-ipm"
    x-data="ipmManagement"
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

    @php
        $completedCriteria = collect($criteria)->filter(fn ($item) => filled($existing_files[$item['field']] ?? null))->count();
    @endphp

    <div class="row g-5 mb-6">
        <div class="col-lg-4">
            <x-ui.stat-card label="Status IPM" value="{{ $isLocked ? 'Terkunci' : 'Aktif' }}" variant="{{ $isLocked ? 'warning' : 'success' }}">
                <x-slot:icon><x-ui.icon name="shield-tick" class="fs-2" /></x-slot:icon>
            </x-ui.stat-card>
        </div>

        <div class="col-lg-4">
            <x-ui.stat-card label="Kriteria Terisi" value="{{ $completedCriteria }} / {{ count($criteria) }}" variant="info">
                <x-slot:icon><x-ui.icon name="document" class="fs-2" /></x-slot:icon>
            </x-ui.stat-card>
        </div>

        <div class="col-lg-4">
            <x-ui.stat-card label="Langkah Berikut" value="{{ $completedCriteria === count($criteria) ? 'Siap Simpan' : 'Lanjut Unggah' }}" variant="primary">
                <x-slot:icon><x-ui.icon name="arrow-right" class="fs-2" /></x-slot:icon>
            </x-ui.stat-card>
        </div>
    </div>

    @if($isLocked)
        <div class="spm-inline-alert">
            <x-ui.icon name="shield-tick" class="fs-2 text-warning" />
            <div>
                <div class="spm-inline-alert-title">Data Terkunci</div>
                <div class="spm-inline-alert-text">Data IPM tidak dapat diubah karena pesantren sedang dalam proses akreditasi.</div>
            </div>
        </div>
    @endif

    <form x-on:submit.prevent="confirmSave($wire)" class="d-flex flex-column gap-6">
        <x-ui.section-card
            title="Dokumen IPM"
            subtitle="Setiap kriteria menggunakan satu unggahan PDF dengan ukuran maksimal 2 MB."
        >
            <div class="p-6">
                <div class="spm-input-grid">
                    @foreach($criteria as $item)
                        @php
                            $existingFile = $existing_files[$item['field']] ?? null;
                            $inputId = 'ipm-' . $item['property'];
                        @endphp

                        <div class="spm-input-card">
                            <div class="d-flex flex-column gap-4">
                                <x-ui.form-field
                                    :label="$item['label']"
                                    :for="$inputId"
                                    :error="$errors->get($item['property'])"
                                    :hint="$item['description']"
                                >
                                    <x-ui.file-upload
                                        :model="$item['property']"
                                        :id="$inputId"
                                        accept="application/pdf"
                                        :placeholder="$existingFile ? basename($existingFile) : 'Belum ada file'"
                                        :disabled="$isLocked"
                                        hint="PDF maksimal 2 MB"
                                    />
                                </x-ui.form-field>

                                @if($existingFile)
                                    <div class="spm-document-list">
                                        <x-ui.document-item
                                            label="Berkas saat ini"
                                            :href="Storage::url($existingFile)"
                                            description="Klik untuk membuka file yang sudah tersimpan."
                                        />
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </x-ui.section-card>

        <div class="spm-action-panel d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-4">
            <div>
                <h3 class="spm-card-title mb-1">Simpan Perubahan</h3>
                <div class="text-muted fw-semibold fs-7">Seluruh kontrol memakai komponen Metronic yang reusable.</div>
            </div>

            <div class="d-flex align-items-center gap-2">
                @if($isLocked)
                    <x-ui.button type="button" variant="warning" disabled>
                        <x-ui.icon name="lock" class="fs-4 me-1" />
                        Data Terkunci
                    </x-ui.button>
                @else
                    <x-ui.button type="submit" variant="primary" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="save">Simpan Perubahan</span>
                        <span wire:loading wire:target="save">Memproses...</span>
                    </x-ui.button>
                @endif
            </div>
        </div>
    </form>
</x-ui.page>
