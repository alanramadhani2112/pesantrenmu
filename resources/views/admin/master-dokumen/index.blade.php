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

        {{-- Search & PerPage --}}
        <div class="d-flex align-items-center gap-3 mb-5">
            <form method="GET" action="{{ route('admin.master-dokumen.index') }}" class="d-flex align-items-center gap-3 flex-grow-1">
                <div class="position-relative flex-grow-1" style="max-width: 320px;">
                    <input type="text" name="search" value="{{ $search }}" class="form-control form-control-sm ps-10"
                           placeholder="Cari dokumen..." x-on:input.debounce.400ms="$el.closest('form').submit()">
                    <span class="position-absolute top-50 start-0 translate-middle-y ms-3">
                        <i class="ki-outline ki-magnifier fs-6 text-muted"></i>
                    </span>
                </div>
                <select name="perPage" class="form-select form-select-sm" style="width: 80px;" onchange="this.form.submit()">
                    @foreach([10, 25, 50] as $pp)
                        <option value="{{ $pp }}" @selected($perPage == $pp)>{{ $pp }}</option>
                    @endforeach
                </select>
                <input type="hidden" name="sort" value="{{ $sortField }}">
                <input type="hidden" name="direction" value="{{ $sortAsc ? 'asc' : 'desc' }}">
            </form>
        </div>

        {{-- Table --}}
        <x-ui.simple-table>
            <thead>
                <tr>
                    <x-ui.table-th>
                        <a href="{{ route('admin.master-dokumen.index', array_merge(request()->query(), ['sort' => 'title', 'direction' => ($sortField === 'title' && $sortAsc) ? 'desc' : 'asc'])) }}" class="text-dark text-hover-primary">
                            Nama Dokumen @if($sortField === 'title') <i class="ki-outline ki-arrow-{{ $sortAsc ? 'up' : 'down' }} fs-7"></i> @endif
                        </a>
                    </x-ui.table-th>
                    <x-ui.table-th align="center">Kategori</x-ui.table-th>
                    <x-ui.table-th align="center">Status</x-ui.table-th>
                    <x-ui.table-th>
                        <a href="{{ route('admin.master-dokumen.index', array_merge(request()->query(), ['sort' => 'created_at', 'direction' => ($sortField === 'created_at' && $sortAsc) ? 'desc' : 'asc'])) }}" class="text-dark text-hover-primary">
                            Tanggal Upload @if($sortField === 'created_at') <i class="ki-outline ki-arrow-{{ $sortAsc ? 'up' : 'down' }} fs-7"></i> @endif
                        </a>
                    </x-ui.table-th>
                    <x-ui.table-th align="end">Aksi</x-ui.table-th>
                </tr>
            </thead>
            <tbody>
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
                                <span class="badge badge-light-primary">{{ $doc->category->name }}</span>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <span class="badge badge-light-{{ $doc->status ? 'success' : 'danger' }}">
                                {{ $doc->status ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </td>
                        <td>{{ $doc->created_at->format('d M Y') }}</td>
                        <td class="text-end">
                            <div class="d-flex align-items-center justify-content-end gap-2">
                                @if($doc->file_path)
                                    <a href="{{ Storage::url($doc->file_path) }}" target="_blank" class="btn btn-icon btn-sm btn-light-info" title="Download">
                                        <i class="ki-outline ki-cloud-download fs-6"></i>
                                    </a>
                                @endif
                                <x-ui.icon-button
                                    icon="pencil"
                                    label="Edit"
                                    variant="primary"
                                    x-on:click="openEditModal(@js($doc))"
                                />
                                <form method="POST" action="{{ route('admin.master-dokumen.destroy', $doc->id) }}" class="d-inline"
                                      x-on:submit.prevent="confirmDelete($event)">
                                    @csrf
                                    @method('DELETE')
                                    <x-ui.icon-button type="submit" icon="trash" label="Hapus" variant="danger" />
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">
                            <x-ui.empty-state title="Belum ada dokumen" description="Tambahkan dokumen template untuk pesantren dan asesor." class="py-10" />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </x-ui.simple-table>

        <div class="mt-5">
            {{ $documents->links() }}
        </div>
    </x-ui.index-layout>

    {{-- Modal Create/Edit --}}
    <x-ui.modal name="document-modal" focusable>
        <form method="POST" x-bind:action="formAction" enctype="multipart/form-data" x-on:submit.prevent="submitForm($event)">
            @csrf
            <template x-if="isEditing">
                <input type="hidden" name="_method" value="PUT">
            </template>

            <x-ui.modal-header
                x-bind:title="isEditing ? 'Edit Dokumen' : 'Tambah Dokumen'"
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

            confirmDelete(e) {
                if (typeof Swal !== 'undefined') {
                    window.SpmSwal.confirm({
                        title: 'Hapus dokumen ini?',
                        text: 'File terkait juga akan dihapus.',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Ya, hapus',
                        cancelButtonText: 'Batal',
                    }).then((result) => {
                        if (result.isConfirmed) e.target.submit();
                    });
                } else {
                    if (confirm('Hapus dokumen ini?')) e.target.submit();
                }
            }
        };
    }
</script>
@endsection
