<?php

use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Livewire\Attributes\Layout;
use App\Models\Document;
use App\Models\DocumentCategory;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

new #[Layout('layouts.app')] class extends Component {
    use \Livewire\WithPagination;
    use WithFileUploads;

    public $title = '';
    public $status = 1;
    public $category_id = null;
    public $description = null;
    public $file;
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
        if (!auth()->user()->canAccessAdminArea()) {
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

    public function getCategoriesProperty()
    {
        return DocumentCategory::active()->ordered()->get();
    }

    public function openModal()
    {
        $this->resetValidation();
        $this->reset(['title', 'status', 'category_id', 'description', 'file', 'documentId', 'currentFile']);
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
        $this->category_id = $doc->category_id;
        $this->description = $doc->description;
        $this->currentFile = $doc->file_path;
        $this->dispatch('open-modal', 'document-modal');
    }

    public function save()
    {
        Gate::authorize('master.dokumen');

        $rules = [
            'title' => 'required|string|max:255',
            'status' => 'required|integer|in:0,1',
            'category_id' => 'required|integer|exists:document_categories,id',
            'description' => 'nullable|string|max:1000',
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
        $this->reset(['title', 'status', 'category_id', 'description', 'file', 'documentId', 'currentFile']);
    }

    public function delete($id)
    {
        Gate::authorize('master.dokumen');

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
        <x-datatable.layout title="Master Dokumen" :records="$this->documents">
            <x-slot name="filters">
                <x-datatable.search placeholder="Cari Dokumen..." />
            </x-slot>

            <x-slot name="toolbar">
                <x-ui.button wire:click="openModal" variant="primary" size="sm" icon="plus">
                    Tambah Dokumen
                </x-ui.button>
            </x-slot>

            <x-slot name="thead">
                <x-datatable.th field="title" :sortField="$sortField" :sortAsc="$sortAsc">
                    Nama Dokumen
                </x-datatable.th>
                <x-ui.table-th align="center">Kategori</x-ui.table-th>
                <x-ui.table-th align="center">Visibilitas</x-ui.table-th>
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
                        @if($doc->description)
                        <div class="text-muted fs-8 fw-normal">{{ \Illuminate\Support\Str::limit($doc->description, 80) }}</div>
                        @endif
                    </td>
                    <td class="text-center">
                        @if($doc->category)
                            <x-ui.status-badge variant="warning">{{ $doc->category->name }}</x-ui.status-badge>
                        @else
                            <x-ui.status-badge variant="secondary">Tanpa Kategori</x-ui.status-badge>
                        @endif
                    </td>
                    <td class="text-center">
                        @if($doc->category && $doc->category->visibility === \App\Models\DocumentCategory::VISIBILITY_PUBLIC)
                            <x-ui.status-badge variant="success">Publik</x-ui.status-badge>
                        @elseif($doc->category && $doc->category->visibility === \App\Models\DocumentCategory::VISIBILITY_PESANTREN_SECRET)
                            <x-ui.status-badge variant="primary">Pesantren Only</x-ui.status-badge>
                        @elseif($doc->category && $doc->category->visibility === \App\Models\DocumentCategory::VISIBILITY_ASESOR_SECRET)
                            <x-ui.status-badge variant="info">Asesor Only</x-ui.status-badge>
                        @else
                            <x-ui.status-badge variant="secondary">-</x-ui.status-badge>
                        @endif
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
        </x-datatable.layout>
    </x-ui.index-layout>

    <!-- Modal Form -->
    <x-ui.modal name="document-modal" maxWidth="lg">
        <form x-on:submit.prevent="confirmSave($wire)">
            <x-ui.modal-header
                :title="$documentId ? 'Edit Dokumen' : 'Tambah Dokumen Baru'"
                subtitle="Kelola template dokumen. Akses pengguna mengikuti kategori."
                icon="document"
            />

            <x-ui.modal-body>
                <x-ui.form-field label="Nama Dokumen" for="title" :error="$errors->get('title')">
                    <x-ui.input model="title" id="title" placeholder="Contoh: Panduan Review Asesor" required />
                </x-ui.form-field>

                <x-ui.form-field label="Kategori" for="category_id" :error="$errors->get('category_id')">
                    <x-ui.select model="category_id" id="category_id" placeholder="Pilih Kategori..." required>
                        @foreach($this->categories as $cat)
                            <option value="{{ $cat->id }}">
                                {{ $cat->name }} ({{ $cat->visibility_label }})
                            </option>
                        @endforeach
                    </x-ui.select>
                    <small class="text-muted fs-8 d-block mt-1">
                        Kategori menentukan siapa yang dapat melihat dokumen ini.
                        Atur kategori di
                        <a href="{{ route('admin.master-kategori-dokumen') }}" class="text-primary">Master Kategori Dokumen</a>.
                    </small>
                </x-ui.form-field>

                <x-ui.form-field label="Deskripsi (opsional)" for="description" :error="$errors->get('description')">
                    <x-ui.textarea model="description" id="description" rows="2" placeholder="Penjelasan singkat tentang dokumen..." />
                </x-ui.form-field>

                @if($documentId && $currentFile)
                    <x-ui.form-field label="Dokumen Saat Ini">
                        <div class="d-flex align-items-center gap-3 p-3 rounded bg-light">
                            <span class="symbol symbol-35px">
                                <span class="symbol-label bg-white text-primary">
                                    <x-ui.icon name="document" class="fs-4" />
                                </span>
                            </span>
                            <span class="fw-semibold text-gray-600 text-truncate">{{ basename($currentFile) }}</span>
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
                        <x-ui.progress
                            x-show="isUploading"
                            dynamic-value="progress"
                            variant="primary"
                            height="5px"
                            class="mt-3"
                        />
                    </x-ui.file-upload>
                </x-ui.form-field>

                <x-ui.form-field label="Status Dokumen" class="mb-0">
                    <div class="d-flex align-items-center gap-6">
                        <x-ui.radio model="status" value="1" label="Aktif" />
                        <x-ui.radio model="status" value="0" label="Non Aktif" />
                    </div>
                </x-ui.form-field>
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
