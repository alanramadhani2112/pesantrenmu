<?php

use App\Models\Ipm;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;

new #[Layout('layouts.app')] class extends Component {
    use WithFileUploads;

    public $ipm;

    // Form fields (files)
    public $nsp_file_upload;
    public $lulus_santri_file_upload;
    public $kurikulum_file_upload;
    public $buku_ajar_file_upload;

    // Existing file paths
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
            $this->js("Swal.fire({
                icon: 'error',
                title: 'Akses Ditolak',
                text: 'Data terkunci karena sedang dalam proses akreditasi.',
                confirmButtonColor: '#d33'
            })");
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

<div class="py-12">
    <x-slot name="header">{{ __('Indek Pemenuhan Mutlak (IPM)') }}</x-slot>
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        @php $isLocked = auth()->user()->pesantren->is_locked; @endphp

        @if($isLocked)
        <div class="mb-6 flex items-center gap-4 bg-amber-50 border border-amber-200 rounded-2xl px-5 py-4 shadow-sm">
            <div class="flex-shrink-0 w-10 h-10 bg-amber-100 rounded-xl flex items-center justify-center">
                <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
            </div>
            <div>
                <p class="text-sm font-black text-amber-800 uppercase tracking-wide">Data Terkunci</p>
                <p class="text-xs text-amber-700 mt-0.5">Data IPM tidak dapat diubah karena pesantren sedang dalam proses akreditasi.</p>
            </div>
        </div>
        @endif

        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg {{ $isLocked ? 'opacity-90' : '' }}">
            <div class="p-6 text-gray-900">
                <header class="mb-6">
                    <h2 class="text-lg font-medium text-gray-900">
                        {{ __('Indek Pemenuhan Mutlak (IPM)') }}
                    </h2>
                    <p class="mt-1 text-sm text-gray-600">
                        {{ __('Silakan unggah dokumen pendukung untuk setiap kriteria pemenuhan mutlak di bawah ini.') }}
                    </p>
                </header>

                <form x-on:submit.prevent="{{ $isLocked ? '' : 'confirmSave($wire)' }}" class="space-y-6" x-data="ipmManagement">
                    <p class="text-xs text-red-600 italic font-medium">* Format Berkas wajib PDF dan Ukuran Maksimal 2MB</p>

                    <!-- Kriteria 1 -->
                    <div class="p-4 border rounded-lg {{ $isLocked ? 'bg-gray-100 border-gray-200' : 'bg-gray-50' }}">
                        <label class="block text-sm font-medium {{ $isLocked ? 'text-gray-400' : 'text-gray-700' }} mb-2">
                            1. Pesantren telah memiliki izin operasional Kementerian Agama (Nomor Statistik Pesantren – NSP) yang dibuktikan dengan mengunggah dalam SPM-PesantrenMu.
                        </label>
                        <input type="file"
                            accept="application/pdf"
                            @if(!$isLocked) x-on:change="if(validate($event)) { $wire.upload('nsp_file_upload', $event.target.files[0]) }" @endif
                            {{ $isLocked ? 'disabled' : '' }}
                            class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold {{ $isLocked ? 'opacity-40 cursor-not-allowed file:cursor-not-allowed file:bg-gray-100 file:text-gray-400 pointer-events-none' : 'file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100' }}" />
                        <x-input-error :messages="$errors->get('nsp_file_upload')" class="mt-2" />
                        @if($existing_files['nsp_file'])
                        <div class="mt-2 text-xs text-green-600">
                            Berkas terunggah: <a href="{{ Storage::url($existing_files['nsp_file']) }}" target="_blank" class="underline font-bold">Lihat Dokumen</a>
                        </div>
                        @endif
                    </div>

                    <!-- Kriteria 2 -->
                    <div class="p-4 border rounded-lg {{ $isLocked ? 'bg-gray-100 border-gray-200' : 'bg-gray-50' }}">
                        <label class="block text-sm font-medium {{ $isLocked ? 'text-gray-400' : 'text-gray-700' }} mb-2">
                            2. Pesantren pernah meluluskan santri dan/atau memiliki santri kelas akhir.
                        </label>
                        <input type="file"
                            accept="application/pdf"
                            @if(!$isLocked) x-on:change="if(validate($event)) { $wire.upload('lulus_santri_file_upload', $event.target.files[0]) }" @endif
                            {{ $isLocked ? 'disabled' : '' }}
                            class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold {{ $isLocked ? 'opacity-40 cursor-not-allowed file:cursor-not-allowed file:bg-gray-100 file:text-gray-400 pointer-events-none' : 'file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100' }}" />
                        <x-input-error :messages="$errors->get('lulus_santri_file_upload')" class="mt-2" />
                        @if($existing_files['lulus_santri_file'])
                        <div class="mt-2 text-xs text-green-600">
                            Berkas terunggah: <a href="{{ Storage::url($existing_files['lulus_santri_file']) }}" target="_blank" class="underline font-bold">Lihat Dokumen</a>
                        </div>
                        @endif
                    </div>

                    <!-- Kriteria 3 -->
                    <div class="p-4 border rounded-lg {{ $isLocked ? 'bg-gray-100 border-gray-200' : 'bg-gray-50' }}">
                        <label class="block text-sm font-medium {{ $isLocked ? 'text-gray-400' : 'text-gray-700' }} mb-2">
                            3. Pesantren memiliki dan menyelenggarakan kurikulum Dirasah Islamiyah sesuai standar kurikulum LP2 PPM di seluruh kelas.
                        </label>
                        <input type="file"
                            accept="application/pdf"
                            @if(!$isLocked) x-on:change="if(validate($event)) { $wire.upload('kurikulum_file_upload', $event.target.files[0]) }" @endif
                            {{ $isLocked ? 'disabled' : '' }}
                            class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold {{ $isLocked ? 'opacity-40 cursor-not-allowed file:cursor-not-allowed file:bg-gray-100 file:text-gray-400 pointer-events-none' : 'file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100' }}" />
                        <x-input-error :messages="$errors->get('kurikulum_file_upload')" class="mt-2" />
                        @if($existing_files['kurikulum_file'])
                        <div class="mt-2 text-xs text-green-600">
                            Berkas terunggah: <a href="{{ Storage::url($existing_files['kurikulum_file']) }}" target="_blank" class="underline font-bold">Lihat Dokumen</a>
                        </div>
                        @endif
                    </div>

                    <!-- Kriteria 4 -->
                    <div class="p-4 border rounded-lg {{ $isLocked ? 'bg-gray-100 border-gray-200' : 'bg-gray-50' }}">
                        <label class="block text-sm font-medium {{ $isLocked ? 'text-gray-400' : 'text-gray-700' }} mb-2">
                            4. Pesantren menggunakan buku ajar Dirasah Islamiyah terbitan LP2 PPM.
                        </label>
                        <input type="file"
                            accept="application/pdf"
                            @if(!$isLocked) x-on:change="if(validate($event)) { $wire.upload('buku_ajar_file_upload', $event.target.files[0]) }" @endif
                            {{ $isLocked ? 'disabled' : '' }}
                            class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold {{ $isLocked ? 'opacity-40 cursor-not-allowed file:cursor-not-allowed file:bg-gray-100 file:text-gray-400 pointer-events-none' : 'file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100' }}" />
                        <x-input-error :messages="$errors->get('buku_ajar_file_upload')" class="mt-2" />
                        @if($existing_files['buku_ajar_file'])
                        <div class="mt-2 text-xs text-green-600">
                            Berkas terunggah: <a href="{{ Storage::url($existing_files['buku_ajar_file']) }}" target="_blank" class="underline font-bold">Lihat Dokumen</a>
                        </div>
                        @endif
                    </div>

                    <div class="flex items-center gap-4">
                        @if($isLocked)
                        <button type="button" disabled
                            class="px-10 py-3 rounded-2xl bg-gray-300 text-gray-400 text-[11px] font-black uppercase tracking-[0.2em] cursor-not-allowed flex items-center justify-center gap-3 select-none">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                            Data Terkunci
                        </button>
                        @else
                        <button type="submit" wire:loading.attr="disabled"
                            class="px-10 py-3 rounded-2xl bg-gray-900 text-white text-[11px] font-black uppercase tracking-[0.2em] transition-all flex items-center justify-center gap-3">
                            <svg wire:loading wire:target="save" class="animate-spin -ml-1 mr-3 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span wire:loading.remove wire:target="save">{{ __('Simpan Perubahan') }}</span>
                            <span wire:loading wire:target="save">{{ __('Memproses...') }}</span>
                        </button>
                        @endif
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>