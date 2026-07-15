@extends('layouts.app')

@section('content')
<div x-data="masterDokumen()" data-module-page="master-dokumen">
    <x-ui.index-layout
        title="Master Dokumen"
        subtitle="Kelola template dan dokumen pendukung untuk pesantren dan asesor."
    >
        <x-slot name="toolbar">
            <x-ui.button variant="primary" size="sm" icon="plus" x-on:click="openCreateModal()">
                Tambah Dokumen
            </x-ui.button>
        </x-slot>

        {{-- Table --}}
        <x-ui.table title="Daftar Dokumen Wajib" subtitle="Template dan dokumen pendukung untuk pesantren dan asesor." :records="$documents">
            <x-slot name="filters">
                <form method="GET" action="{{ route('admin.master-dokumen.index') }}" id="master-dokumen-filter-form">
                    <div class="d-flex align-items-center gap-3 flex-wrap">
                        <x-datatable.search name="search" placeholder="Cari dokumen..." :value="$search" form="master-dokumen-filter-form" />
                        <input type="hidden" name="sortField" value="{{ $sortField }}">
                        <input type="hidden" name="sortAsc" value="{{ $sortAsc ? 'true' : 'false' }}">
                    </div>
                </form>
            </x-slot>

            <x-slot name="thead">
                <x-ui.table-th field="title" :sortField="$sortField" :sortAsc="$sortAsc" form="master-dokumen-filter-form">Nama Dokumen</x-ui.table-th>
                <x-ui.table-th align="center">Kategori</x-ui.table-th>
                <x-ui.table-th align="center">Status</x-ui.table-th>
                <x-ui.table-th field="created_at" :sortField="$sortField" :sortAsc="$sortAsc" form="master-dokumen-filter-form">Tanggal Upload</x-ui.table-th>
                <x-ui.table-th align="end">Aksi</x-ui.table-th>
            </x-slot>

            <x-slot name="tbody">
                @forelse($documents as $doc)
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <i class="ki-outline ki-file fs-4 text-primary"></i>
                                <div>
                                    <div class="fw-semibold">{{ $doc->title }}</div>
                                    @if($doc->description)
                                        <div class="text-muted fs-8">{{ Str::limit($doc->description, 60) }}</div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="text-center">
                            @if($doc->category)
                                <x-ui.badge variant="primary">{{ $doc->category->name }}</x-ui.badge>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <x-ui.status-badge :variant="$doc->status ? 'success' : 'danger'">
                                {{ $doc->status ? 'Aktif' : 'Nonaktif' }}
                            </x-ui.status-badge>
                        </td>
                        <td>{{ $doc->created_at->format('d M Y') }}</td>
                        <td class="text-end">
                            <x-ui.action-menu>
                                @if($doc->file_path)
                                    <x-ui.action-menu-item :href="route('documents.download', $doc)" target="_blank" variant="primary">
                                        <x-ui.icon name="document" class="fs-5" />
                                        Download
                                    </x-ui.action-menu-item>
                                @endif

                                <x-ui.action-menu-item variant="primary" x-on:click="openEditModal(@js($doc))">
                                    <x-ui.icon name="pencil" class="fs-5" />
                                    Edit Dokumen
                                </x-ui.action-menu-item>

                                <x-ui.action-menu-item
                                    variant="danger"
                                    data-delete-url="{{ route('admin.master-dokumen.destroy', $doc->id) }}"
                                    x-on:click="confirmDelete($el.dataset.deleteUrl)"
                                >
                                    <x-ui.icon name="trash" class="fs-5" />
                                    Hapus Dokumen
                                </x-ui.action-menu-item>
                            </x-ui.action-menu>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">
                            <x-ui.empty-state title="Belum ada dokumen" description="Tambahkan dokumen template untuk pesantren dan asesor." class="py-10" />
                        </td>
                    </tr>
                @endforelse
            </x-slot>
        </x-ui.table>
    </x-ui.index-layout>

    <form id="document-delete-form" method="POST" class="d-none">
        @csrf
        @method('DELETE')
    </form>

    {{-- Modal Create/Edit --}}
    <x-ui.modal name="document-modal" focusable>
        <form method="POST" x-bind:action="formAction" enctype="multipart/form-data" x-on:submit.prevent="submitForm($event)">
            @csrf
            <template x-if="isEditing">
                <input type="hidden" name="_method" value="PUT">
            </template>

            <x-ui.modal-header
                title="Kelola Dokumen"
                subtitle="Lengkapi informasi dokumen."
                icon="document"
            />

            <x-ui.modal-body>
                <div class="mb-5">
                    <label class="form-label required" for="doc_title">Nama Dokumen</label>
                    <input type="text" name="title" id="doc_title" x-model="form.title"
                           class="form-control" placeholder="Judul dokumen" required>
                    @error('title') <div class="text-danger fs-8 mt-1">{{ $message }}</div> @enderror
                </div>

                <div class="row g-5">
                    <div class="col-md-8">
                        <label class="form-label required" for="doc_category">Kategori</label>
                        <select name="category_id" id="doc_category" x-model="form.category_id" class="form-select" required>
                            <option value="">-- Pilih Kategori --</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                        @error('category_id') <div class="text-danger fs-8 mt-1">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label required">Status</label>
                        <select name="status" x-model="form.status" class="form-select">
                            <option value="1">Aktif</option>
                            <option value="0">Nonaktif</option>
                        </select>
                    </div>
                </div>

                <div class="mt-5">
                    <label class="form-label" for="doc_description">Deskripsi</label>
                    <textarea name="description" id="doc_description" x-model="form.description"
                              class="form-control" rows="2" placeholder="Opsional"></textarea>
                </div>

                <div class="mt-5">
                    <label class="form-label" x-bind:class="{ 'required': !isEditing }" for="doc_file">File</label>
                    <input type="file" name="file" id="doc_file" class="form-control"
                           accept=".pdf,.doc,.docx,.xls,.xlsx" x-bind:required="!isEditing">
                    <div class="text-muted fs-8 mt-1">PDF, DOC, DOCX, XLS, XLSX. Maks 10MB.</div>
                    <template x-if="isEditing && currentFile">
                        <div class="mt-2 text-muted fs-8">
                            <i class="ki-outline ki-paperclip fs-8"></i>
                            File saat ini: <span x-text="currentFile" class="fw-semibold"></span>
                        </div>
                    </template>
                    @error('file') <div class="text-danger fs-8 mt-1">{{ $message }}</div> @enderror
                </div>
            </x-ui.modal-body>

            <x-ui.modal-footer>
                <x-ui.button type="button" variant="light" x-on:click="$dispatch('close')">Batal</x-ui.button>
                <x-ui.button type="submit" variant="primary" x-text="isEditing ? 'Perbarui' : 'Simpan'"></x-ui.button>
            </x-ui.modal-footer>
        </form>
    </x-ui.modal>
</div>

@if(session('success'))
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            window.dispatchEvent(new CustomEvent('notification-received', {
                detail: { type: 'success', title: 'Berhasil!', message: @json(session('success')) }
            }));
        });
    </script>
@endif

@if(session('error'))
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            window.dispatchEvent(new CustomEvent('notification-received', {
                detail: { type: 'error', title: 'Gagal', message: @json(session('error')) }
            }));
        });
    </script>
@endif

<script>
    function masterDokumen() {
        return {
            isEditing: false,
            currentFile: null,
            formAction: '{{ route("admin.master-dokumen.store") }}',
            form: {
                title: '', category_id: '', description: '', status: '1'
            },

            openCreateModal() {
                this.isEditing = false;
                this.currentFile = null;
                this.formAction = '{{ route("admin.master-dokumen.store") }}';
                this.form = { title: '', category_id: '', description: '', status: '1' };
                // Reset file input
                const fileInput = document.getElementById('doc_file');
                if (fileInput) fileInput.value = '';
                this.$dispatch('open-modal', 'document-modal');
            },

            openEditModal(doc) {
                this.isEditing = true;
                this.currentFile = doc.file_path ? doc.file_path.split('/').pop() : null;
                this.formAction = '{{ url("admin/master-dokumen") }}/' + doc.id;
                this.form = {
                    title: doc.title,
                    category_id: doc.category_id ? String(doc.category_id) : '',
                    description: doc.description || '',
                    status: String(doc.status)
                };
                // Reset file input
                const fileInput = document.getElementById('doc_file');
                if (fileInput) fileInput.value = '';
                this.$dispatch('open-modal', 'document-modal');
            },

            submitForm(e) {
                e.target.submit();
            },

            confirmDelete(url) {
                window.SpmSwal.confirm({
                    title: 'Hapus dokumen ini?',
                    text: 'File terkait juga akan dihapus.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, hapus',
                    cancelButtonText: 'Batal',
                }).then((result) => {
                    if (result.isConfirmed) {
                        const form = document.getElementById('document-delete-form');
                        form.action = url;
                        form.requestSubmit();
                    }
                });
            }
        };
    }
</script>
@endsection
