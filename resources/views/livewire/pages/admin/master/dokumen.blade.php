<?php

use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Livewire\Attributes\Layout;
use App\Models\Document;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

new #[Layout('layouts.app')] class extends Component {
    use \Livewire\WithPagination;
    use WithFileUploads;

    public $title = '';
    public $status = 1;
    public $is_pesantren = false;
    public $is_asesor = false;
    public $file;
    public $type = '';
    public $documentId = null;
    public $currentFile = null;


    public $search = '';
    public $perPage = 10;
    public $sortField = 'created_at';
    public $sortAsc = false;

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedPerPage()
    {
        $this->resetPage();
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortAsc = !$this->sortAsc;
        } else {
            $this->sortAsc = true;
        }

        $this->sortField = $field;
    }

    public function mount()
    {
        if (!auth()->user()->isAdmin()) {
            abort(403);
        }
    }

    public function getDocumentsProperty()
    {
        $documentService = app(\App\Services\DocumentService::class);
        return $documentService->getPaginatedDocuments(
            $this->search,
            $this->perPage,
            $this->sortField,
            $this->sortAsc
        );
    }

    public function openModal()
    {
        $this->resetValidation();
        $this->reset(['title', 'status', 'is_pesantren', 'is_asesor', 'type', 'file', 'documentId', 'currentFile']);
        $this->status = 1;
        $this->dispatch('open-modal', 'document-modal');
    }

    public function edit($id)
    {
        $this->resetValidation();
        $documentService = app(\App\Services\DocumentService::class);
        $doc = $documentService->findDocument($id);
        
        if (!$doc) {
            $this->dispatch('notification-received', type: 'error', title: 'Gagal', message: 'Dokumen tidak ditemukan.');
            return;
        }

        $this->documentId = $doc->id;
        $this->title = $doc->title;
        $this->status = $doc->status;
        $this->is_pesantren = (bool) $doc->is_pesantren;
        $this->is_asesor = (bool) $doc->is_asesor;
        $this->type = $doc->type;
        $this->currentFile = $doc->file_path;
        $this->dispatch('open-modal', 'document-modal');
    }

    public function save()
    {
        $rules = [
            'title' => 'required|string|max:255',
            'status' => 'required|integer',
            'is_pesantren' => 'boolean',
            'is_asesor' => 'boolean',
            'type' => 'required|string|in:iapm,kartu_kendali,visitasi',
        ];

        if (!$this->documentId) {
            $rules['file'] = 'required|file|mimes:pdf,doc,docx,xls,xlsx|max:10240';
        } else {
            $rules['file'] = 'nullable|file|mimes:pdf,doc,docx,xls,xlsx|max:10240';
        }

        $validatedData = $this->validate($rules);

        $documentService = app(\App\Services\DocumentService::class);
        $documentService->saveDocument($validatedData, $this->documentId, $this->file);

        $this->dispatch('close-modal', 'document-modal');
        $this->dispatch('notification-received', type: 'success', title: 'Berhasil', message: 'Dokumen berhasil disimpan.');
        $this->reset(['title', 'status', 'is_pesantren', 'is_asesor', 'type', 'file', 'documentId', 'currentFile']);
    }

    public function delete($id)
    {
        $documentService = app(\App\Services\DocumentService::class);
        if ($documentService->deleteDocument($id)) {
            $this->dispatch('notification-received', type: 'success', title: 'Berhasil', message: 'Dokumen berhasil dihapus.');
        } else {
            $this->dispatch('notification-received', type: 'error', title: 'Gagal', message: 'Dokumen tidak dapat dihapus.');
        }
    }
}; ?>

<div class="py-12">
    <x-slot name="header">{{ __('Master Dokumen') }}</x-slot>

    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <x-datatable.layout title="Master Dokumen" :records="$this->documents">
            <x-slot name="filters">
                <x-datatable.search placeholder="Cari Dokumen..." />

                <button wire:click="openModal" class="bg-[#1e3a5f] text-white px-4 py-2 rounded-lg text-xs font-bold flex items-center gap-2 hover:bg-[#162d4a] transition-all shadow-sm active:scale-95">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Tambah Dokumen
                </button>
            </x-slot>

            <x-slot name="thead">
                <x-datatable.th field="title" :sortField="$sortField" :sortAsc="$sortAsc">
                    NAMA DOKUMEN
                </x-datatable.th>
                <x-datatable.th field="type" :sortField="$sortField" :sortAsc="$sortAsc" class="text-center">
                    TIPE DOKUMEN
                </x-datatable.th>
                <th class="py-3 px-4 text-center text-[11px] font-bold text-gray-400 uppercase tracking-widest">AKSES</th>
                <th class="py-3 px-4 text-center text-[11px] font-bold text-gray-400 uppercase tracking-widest">STATUS</th>
                <x-datatable.th field="updated_at" :sortField="$sortField" :sortAsc="$sortAsc" class="text-center">
                    TERAKHIR DIPERBARUI
                </x-datatable.th>
                <th class="py-3 px-4 text-right text-[11px] font-bold text-gray-400 uppercase tracking-widest pr-8">AKSI</th>
            </x-slot>

            <x-slot name="tbody">
                @forelse ($this->documents as $doc)
                <tr class="hover:bg-gray-50/50 transition-colors duration-150 group border-b border-gray-50 last:border-0" wire:key="doc-{{ $doc->id }}">
                    <td class="py-5 px-4 font-bold text-[#374151] text-sm tracking-tight">
                        {{ $doc->title }}
                    </td>
                    <td class="py-5 px-4 text-center">
                        @if($doc->type === 'iapm')
                        <span class="px-2.5 py-1 rounded-md text-[10px] font-bold bg-amber-50 text-amber-700 uppercase tracking-tight border border-amber-200 shadow-sm">IAPM</span>
                        @elseif($doc->type === 'kartu_kendali')
                        <span class="px-2.5 py-1 rounded-md text-[10px] font-bold bg-purple-50 text-purple-700 uppercase tracking-tight border border-purple-200 shadow-sm">Kartu Kendali</span>
                        @elseif($doc->type === 'visitasi')
                        <span class="px-2.5 py-1 rounded-md text-[10px] font-bold bg-blue-50 text-blue-700 uppercase tracking-tight border border-blue-200 shadow-sm">Visitasi</span>
                        @else
                        <span class="px-2.5 py-1 rounded-md text-[10px] font-bold bg-gray-50 text-gray-600 uppercase tracking-tight border border-gray-200 shadow-sm">-</span>
                        @endif
                    </td>
                    <td class="py-5 px-4 text-center">
                        <div class="flex items-center justify-center gap-1.5 flex-wrap">
                            @if($doc->is_pesantren)
                            <span class="px-2.5 py-1 rounded-full text-[9px] font-bold bg-blue-50 text-blue-600 uppercase tracking-tight border border-blue-100 shadow-sm">Pesantren</span>
                            @endif
                            @if($doc->is_asesor)
                            <span class="px-2.5 py-1 rounded-full text-[9px] font-bold bg-indigo-50 text-indigo-600 uppercase tracking-tight border border-indigo-100 shadow-sm">Asesor</span>
                            @endif
                            @if(!$doc->is_pesantren && !$doc->is_asesor)
                            <span class="text-[10px] font-bold text-gray-300 italic">NONE</span>
                            @endif
                        </div>
                    </td>
                    <td class="py-5 px-4 text-center">
                        @if($doc->status == 1)
                        <span class="flex items-center justify-center gap-1.5 text-green-600 uppercase tracking-tight">
                            <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>
                            <span class="text-[10px] font-bold tracking-tight">Aktif</span>
                        </span>
                        @else
                        <span class="flex items-center justify-center gap-1.5 text-rose-600 uppercase tracking-tight">
                            <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                            <span class="text-[10px] font-bold tracking-tight">Non-Aktif</span>
                        </span>
                        @endif
                    </td>
                    <td class="py-5 px-4 text-center">
                        <span class="text-[11px] font-bold text-gray-500 tracking-tight whitespace-nowrap">{{ $doc->updated_at->translatedFormat('d M Y • H:i') }} WIB</span>
                    </td>
                    <td class="py-5 px-4 text-right pr-6">
                        <div class="inline-block text-left" x-data="{ 
                            open: false,
                            dropdownPosition: { top: 0, left: 0 },
                            updatePosition() {
                                let rect = this.$refs.btn.getBoundingClientRect();
                                this.dropdownPosition = { 
                                    top: (rect.bottom + 5) + 'px', 
                                    left: (rect.right - 176) + 'px' 
                                };
                            }
                        }">
                            <button x-ref="btn" @click="open = !open; if(open) updatePosition()" @click.away="open = false"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-[11px] font-bold text-gray-400 hover:text-gray-700 transition-colors bg-gray-50/50 rounded-lg group-hover:bg-gray-100">
                                Aksi
                                <svg class="w-3 h-3 transition-transform duration-200" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                            <template x-teleport="body">
                                <div x-show="open"
                                    x-transition:enter="transition ease-out duration-100"
                                    x-transition:enter-start="opacity-0 scale-95"
                                    x-transition:enter-end="opacity-100 scale-100"
                                    x-transition:leave="transition ease-in duration-75"
                                    x-transition:leave-start="opacity-100 scale-100"
                                    x-transition:leave-end="opacity-0 scale-95"
                                    :style="`position: fixed; top: ${dropdownPosition.top}; left: ${dropdownPosition.left}; z-index: 9999;`"
                                    class="w-44 bg-white rounded-xl shadow-2xl border border-gray-100 py-2 origin-top-right overflow-hidden shadow-slate-200/50" x-cloak>
                                    <a href="{{ Storage::url($doc->file_path) }}" target="_blank"
                                        class="flex items-center w-full px-4 py-2.5 text-[11px] font-bold text-slate-700 hover:bg-slate-50 transition-colors gap-3">
                                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                        Lihat Detail
                                    </a>
                                    <button wire:click="edit({{ $doc->id }})" @click="open = false"
                                        class="flex items-center w-full px-4 py-2.5 text-[11px] font-bold text-slate-700 hover:bg-slate-50 transition-colors gap-3 border-t border-gray-50/50">
                                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                        Edit Dokumen
                                    </button>
                                    <button @click="open = false; 
                                        Swal.fire({
                                            title: 'Apakah Anda yakin?',
                                            text: 'Dokumen ini akan dihapus secara permanen!',
                                            icon: 'warning',
                                            showCancelButton: true,
                                            confirmButtonColor: '#ef4444',
                                            cancelButtonColor: '#6b7280',
                                            confirmButtonText: 'Ya, Hapus!',
                                            cancelButtonText: 'Batal'
                                        }).then((result) => {
                                            if (result.isConfirmed) {
                                                $wire.delete({{ $doc->id }})
                                            }
                                        })
                                    "
                                        class="flex items-center w-full px-4 py-2.5 text-[11px] font-bold text-rose-600 hover:bg-rose-50 transition-colors gap-3 border-t border-gray-50/50">
                                        <svg class="w-4 h-4 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                        Hapus Dokumen
                                    </button>
                                </div>
                            </template>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="py-16 text-center">
                        <div class="flex flex-col items-center gap-2">
                            <svg class="w-10 h-10 text-gray-400/30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <p class="text-xs text-gray-400 font-bold">Data tidak ditemukan.</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </x-slot>
        </x-datatable.layout>
    </div>

    <!-- Modal Form -->
    <x-modal name="document-modal" maxWidth="lg">
        <form wire:submit="save" class="p-8">
            <h2 class="text-xl font-extrabold text-gray-900 mb-6">
                {{ $documentId ? 'Edit Dokumen' : 'Tambah Dokumen Baru' }}
            </h2>

            <div class="space-y-6">
                <!-- Nama Dokumen -->
                <div>
                    <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-2">Nama Dokumen</label>
                    <input type="text" wire:model="title" required placeholder="Contoh: Panduan Assessment"
                        class="w-full text-xs border-gray-100 rounded-lg bg-gray-50/50 py-2.5 focus:ring-1 focus:ring-green-500 focus:border-green-500 placeholder-gray-400">
                    <x-input-error :messages="$errors->get('title')" class="mt-2" />
                </div>

                <!-- Tipe Dokumen -->
                <div>
                    <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-2">Tipe Dokumen</label>
                    <select wire:model="type" required
                        class="w-full text-xs border-gray-100 rounded-lg bg-gray-50/50 py-2.5 focus:ring-1 focus:ring-green-500 focus:border-green-500 placeholder-gray-400 text-gray-700">
                        <option value="">Pilih Tipe Dokumen...</option>
                        <option value="iapm">IAPM</option>
                        <option value="kartu_kendali">KARTU KENDALI</option>
                        <option value="visitasi">VISITASI</option>
                    </select>
                    <x-input-error :messages="$errors->get('type')" class="mt-2" />
                </div>

                <!-- Dokumen Saat Ini (If Editing) -->
                @if($documentId && $currentFile)
                <div>
                    <label class="block text-[11px] font-bold text-gray-400 uppercase tracking-widest mb-2">Dokumen Saat Ini</label>
                    <div class="flex items-center gap-3 p-2.5 bg-gray-50/50 border border-gray-100 rounded-lg">
                        <div class="p-1.5 bg-white rounded border border-gray-100 shadow-sm">
                            <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 9h1m1 3h1m1 3h-2" />
                            </svg>
                        </div>
                        <span class="text-[11px] font-bold text-gray-500 truncate flex-1 uppercase tracking-tight">{{ basename($currentFile) }}</span>
                    </div>
                </div>
                @endif

                <!-- Upload File -->
                <div x-data="{ isUploading: false, progress: 0 }"
                    x-on:livewire-upload-start="isUploading = true"
                    x-on:livewire-upload-finish="isUploading = false"
                    x-on:livewire-upload-error="isUploading = false"
                    x-on:livewire-upload-progress="progress = $event.detail.progress">

                    <label class="block text-[11px] font-bold text-gray-400 uppercase tracking-widest mb-2">Upload File</label>
                    <div class="relative group">
                        <input type="file" wire:model="file" id="file" accept=".pdf,.doc,.docx,.xls,.xlsx" class="hidden">
                        <label for="file" class="flex items-center gap-3 p-2.5 bg-white border border-gray-100 rounded-lg hover:border-indigo-200 hover:bg-indigo-50/30 transition-all cursor-pointer shadow-sm group">
                            <div class="p-1.5 bg-slate-50 rounded border border-gray-100 group-hover:bg-white group-hover:text-indigo-600 transition-colors">
                                <svg class="w-3.5 h-3.5 text-gray-400 group-hover:text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                </svg>
                            </div>
                            <span class="text-[11px] font-bold text-gray-400 group-hover:text-indigo-700 transition-colors">{{ $file ? $file->getClientOriginalName() : 'No File Choosen' }}</span>
                        </label>
                    </div>
                    <div class="mt-2 text-[10px] text-gray-400 font-medium tracking-tight">Format yang diizinkan: .doc, .docx (Max 10MB)</div>

                    <div x-show="isUploading" class="mt-2 h-1 w-full bg-gray-100 rounded-full overflow-hidden">
                        <div class="h-full bg-indigo-500 transition-all duration-300" :style="'width: ' + progress + '%'"></div>
                    </div>

                    <x-input-error :messages="$errors->get('file')" class="mt-2" />
                </div>

                <div class="grid grid-cols-2 gap-8 pt-4">
                    <!-- Hak Akses -->
                    <div>
                        <label class="block text-[11px] font-bold text-gray-400 uppercase tracking-widest mb-4">Hak Akses</label>
                        <div class="flex items-center gap-6">
                            <label class="flex items-center gap-2 cursor-pointer group">
                                <div class="relative flex items-center">
                                    <input type="checkbox" wire:model="is_pesantren" class="peer h-5 w-5 cursor-pointer appearance-none rounded border border-gray-200 bg-white checked:bg-[#1e3a5f] transition-all shadow-sm">
                                    <svg class="absolute w-3.5 h-3.5 text-white opacity-0 peer-checked:opacity-100 pointer-events-none left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                                    </svg>
                                </div>
                                <span class="text-[11px] font-bold text-gray-600 transition-colors group-hover:text-[#1e3a5f]">Pesantren</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer group">
                                <div class="relative flex items-center">
                                    <input type="checkbox" wire:model="is_asesor" class="peer h-5 w-5 cursor-pointer appearance-none rounded border border-gray-200 bg-white checked:bg-[#1e3a5f] transition-all shadow-sm">
                                    <svg class="absolute w-3.5 h-3.5 text-white opacity-0 peer-checked:opacity-100 pointer-events-none left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                                    </svg>
                                </div>
                                <span class="text-[11px] font-bold text-gray-600 transition-colors group-hover:text-[#1e3a5f]">Asesor</span>
                            </label>
                        </div>
                    </div>

                    <!-- Status Dokumen -->
                    <div>
                        <label class="block text-[11px] font-bold text-gray-400 uppercase tracking-widest mb-4">Status Dokuemn</label>
                        <div class="flex items-center gap-6">
                            <label class="flex items-center gap-2 cursor-pointer group">
                                <div class="relative flex items-center">
                                    <input type="radio" wire:model="status" value="1" class="peer h-5 w-5 cursor-pointer appearance-none rounded-full border border-gray-200 bg-white checked:bg-[#1e3a5f] transition-all shadow-sm border-2">
                                    <div class="absolute w-2 h-2 bg-white rounded-full opacity-0 peer-checked:opacity-100 pointer-events-none left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2"></div>
                                </div>
                                <span class="text-[11px] font-bold text-gray-600 group-hover:text-[#1e3a5f] transition-colors">Aktif</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer group">
                                <div class="relative flex items-center">
                                    <input type="radio" wire:model="status" value="0" class="peer h-5 w-5 cursor-pointer appearance-none rounded-full border border-gray-200 bg-white checked:bg-slate-300 transition-all shadow-sm border-2">
                                    <div class="absolute w-2 h-2 bg-white rounded-full opacity-0 peer-checked:opacity-100 pointer-events-none left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2"></div>
                                </div>
                                <span class="text-[11px] font-bold text-gray-600 group-hover:text-slate-500 transition-colors">Non Aktif</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-10 flex justify-end gap-3">
                <button type="button" x-on:click="$dispatch('close')" class="px-6 py-2.5 rounded-lg text-[11px] font-bold text-gray-400 bg-gray-50 hover:bg-gray-100 transition-colors">
                    Batal
                </button>
                <button type="submit" wire:loading.attr="disabled" class="bg-[#1e3a5f] text-white px-8 py-2.5 rounded-lg text-[11px] font-bold hover:bg-[#162d4a] transition-colors shadow-sm disabled:opacity-50">
                    <span wire:loading.remove>Simpan</span>
                    <span wire:loading>Menyimpan...</span>
                </button>
            </div>
        </form>
    </x-modal>
</div>