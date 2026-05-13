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

<div x-data="{ ...deleteConfirmation(), ...fileManagement() }" data-module-page="master-dokumen">
    <x-ui.index-layout
        title="Master Dokumen"
        subtitle="Kelola template dan dokumen pendukung untuk pesantren dan asesor."
    >
        <x-ui.table title="Master Dokumen" :records="$this->documents">
            <x-slot name="filters">
                <x-datatable.search placeholder="Cari Dokumen..." />
            </x-slot>

            <x-slot name="toolbar">
                <x-ui.button wire:click="openModal" variant="primary" size="sm">
                    <x-ui.icon name="plus" class="fs-4 me-1" />
                    Tambah Dokumen
                </x-ui.button>
            </x-slot>

            <x-slot name="thead">
                <x-datatable.th field="title" :sortField="$sortField" :sortAsc="$sortAsc">
                    Nama Dokumen
                </x-datatable.th>
                <x-datatable.th field="type" :sortField="$sortField" :sortAsc="$sortAsc" class="text-center">
                    Tipe Dokumen
                </x-datatable.th>
                <x-ui.table-th align="center">Akses</x-ui.table-th>
                <x-ui.table-th align="center">Status</x-ui.table-th>
                <x-datatable.th field="updated_at" :sortField="$sortField" :sortAsc="$sortAsc" class="text-center">
                    Terakhir Diperbarui
                </x-datatable.th>
                <x-ui.table-th align="end">Aksi</x-ui.table-th>
            </x-slot>

            <x-slot name="tbody">
                @forelse ($this->documents as $doc)
                <tr wire:key="doc-{{ $doc->id }}">
                    <td class="fw-semibold text-gray-900 fs-6">
                        {{ $doc->title }}
                    </td>
                    <td class="text-center">
                        @if($doc->type === 'iapm')
                        <x-ui.status-badge variant="warning">IAPM</x-ui.status-badge>
                        @elseif($doc->type === 'kartu_kendali')
                        <x-ui.status-badge variant="primary">Kartu Kendali</x-ui.status-badge>
                        @elseif($doc->type === 'visitasi')
                        <x-ui.status-badge variant="info">Visitasi</x-ui.status-badge>
                        @else
                        <x-ui.status-badge variant="secondary">-</x-ui.status-badge>
                        @endif
                    </td>
                    <td class="text-center">
                        <div class="d-flex align-items-center justify-content-center gap-2 flex-wrap">
                            @if($doc->is_pesantren)
                            <x-ui.status-badge variant="primary">Pesantren</x-ui.status-badge>
                            @endif
                            @if($doc->is_asesor)
                            <x-ui.status-badge variant="info">Asesor</x-ui.status-badge>
                            @endif
                            @if(!$doc->is_pesantren && !$doc->is_asesor)
                            <x-ui.status-badge variant="secondary">NONE</x-ui.status-badge>
                            @endif
                        </div>
                    </td>
                    <td class="text-center">
                        <x-ui.status-badge :variant="$doc->status == 1 ? 'success' : 'danger'">
                            {{ $doc->status == 1 ? 'Aktif' : 'Non-Aktif' }}
                        </x-ui.status-badge>
                    </td>
                    <td class="text-center">
                        <span class="text-muted fw-semibold text-nowrap">{{ $doc->updated_at->translatedFormat('d M Y • H:i') }} WIB</span>
                    </td>
                    <td class="text-end">
                        <x-ui.action-menu>
                            <x-ui.action-menu-item href="{{ Storage::url($doc->file_path) }}" target="_blank">
                                <x-ui.icon name="eye" class="fs-5 text-gray-500" />
                                Lihat Detail
                            </x-ui.action-menu-item>

                            <x-ui.action-menu-item wire:click="edit({{ $doc->id }})">
                                <x-ui.icon name="pencil" class="fs-5 text-gray-500" />
                                Edit Dokumen
                            </x-ui.action-menu-item>

                            <x-ui.action-menu-item
                                variant="danger"
                                x-on:click="confirmDelete({{ $doc->id }}, 'delete', 'Dokumen ini akan dihapus secara permanen!')"
                            >
                                <x-ui.icon name="trash" class="fs-5" />
                                Hapus Dokumen
                            </x-ui.action-menu-item>
                        </x-ui.action-menu>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6">
                        <x-ui.empty-state title="Data tidak ditemukan" class="py-15" />
                    </td>
                </tr>
                @endforelse
            </x-slot>
        </x-ui.table>
    </x-ui.index-layout>

    <!-- Modal Form -->
    <x-ui.modal name="document-modal" maxWidth="lg">
        <form x-on:submit.prevent="confirmSave($wire)">
            <x-ui.modal-header
                :title="$documentId ? 'Edit Dokumen' : 'Tambah Dokumen Baru'"
                subtitle="Kelola template dokumen dan akses pengguna."
                icon="document"
            />

            <x-ui.modal-body>
                <x-ui.form-field label="Nama Dokumen" for="title" :error="$errors->get('title')">
                    <x-ui.input model="title" id="title" placeholder="Contoh: Panduan Assessment" required />
                </x-ui.form-field>

                <x-ui.form-field label="Tipe Dokumen" for="type" :error="$errors->get('type')">
                    <x-ui.select model="type" id="type" placeholder="Pilih Tipe Dokumen..." required>
                        <option value="iapm">IAPM</option>
                        <option value="kartu_kendali">KARTU KENDALI</option>
                        <option value="visitasi">VISITASI</option>
                    </x-ui.select>
                </x-ui.form-field>

                @if($documentId && $currentFile)
                    <x-ui.form-field label="Dokumen Saat Ini">
                        <div class="d-flex align-items-center gap-3 p-3 rounded bg-light">
                            <span class="symbol symbol-35px">
                                <span class="symbol-label bg-white text-primary">
                                    <x-ui.icon name="document" class="fs-4" />
                                </span>
                            </span>
                            <span class="fw-bold text-gray-600 text-truncate">{{ basename($currentFile) }}</span>
                        </div>
                    </x-ui.form-field>
                @endif

                <x-ui.form-field label="Upload File" :error="$errors->get('file')">
                    <x-ui.file-upload
                        model="file"
                        id="file"
                        accept=".pdf,.doc,.docx,.xls,.xlsx"
                        :file="$file"
                        placeholder="Belum ada file"
                        hint="Format yang diizinkan: .pdf, .doc, .docx, .xls, .xlsx (Max 10MB)"
                        x-data="{ isUploading: false, progress: 0 }"
                        x-on:livewire-upload-start="isUploading = true"
                        x-on:livewire-upload-finish="isUploading = false"
                        x-on:livewire-upload-error="isUploading = false"
                        x-on:livewire-upload-progress="progress = $event.detail.progress"
                    >
                        <div x-show="isUploading" class="progress h-5px mt-3">
                            <div class="progress-bar bg-primary" :style="'width: ' + progress + '%'"></div>
                        </div>
                    </x-ui.file-upload>
                </x-ui.form-field>

                <div class="row g-6 pt-2">
                    <div class="col-md-6">
                        <x-ui.form-field label="Hak Akses" class="mb-0">
                            <div class="d-flex align-items-center gap-6">
                                <x-ui.checkbox model="is_pesantren" label="Pesantren" />
                                <x-ui.checkbox model="is_asesor" label="Asesor" />
                            </div>
                        </x-ui.form-field>
                    </div>

                    <div class="col-md-6">
                        <x-ui.form-field label="Status Dokumen" class="mb-0">
                            <div class="d-flex align-items-center gap-6">
                                <x-ui.radio model="status" value="1" label="Aktif" />
                                <x-ui.radio model="status" value="0" label="Non Aktif" />
                            </div>
                        </x-ui.form-field>
                    </div>
                </div>
            </x-ui.modal-body>

            <x-ui.modal-footer>
                <x-ui.button type="button" variant="light" x-on:click="$dispatch('close')">
                    Batal
                </x-ui.button>
                <x-ui.button type="submit" variant="primary" wire:loading.attr="disabled">
                    <span wire:loading.remove>Simpan</span>
                    <span wire:loading>Menyimpan...</span>
                </x-ui.button>
            </x-ui.modal-footer>
        </form>
    </x-ui.modal>
</div>
