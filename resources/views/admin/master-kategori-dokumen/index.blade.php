@extends('layouts.app')

@section('content')
<div x-data="kategoriDokumen()" data-module-page="master-kategori-dokumen">
    <x-ui.index-layout
        title="Kategori Dokumen"
        subtitle="Kelola kategori untuk pengelompokan dokumen pesantren dan asesor."
    >
        <x-slot name="toolbar">
            <x-ui.button variant="primary" size="sm" icon="plus" x-on:click="openCreateModal()">
                Tambah Kategori
            </x-ui.button>
        </x-slot>

        {{-- Search & PerPage --}}
        <div class="d-flex align-items-center gap-3 mb-5">
            <form method="GET" action="{{ route('admin.master-kategori-dokumen.index') }}" class="d-flex align-items-center gap-3 flex-grow-1">
                <div class="position-relative flex-grow-1" style="max-width: 320px;">
                    <input type="text" name="search" value="{{ $search }}" class="form-control form-control-sm ps-10"
                           placeholder="Cari kategori..." x-on:input.debounce.400ms="$el.closest('form').submit()">
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
                        <a href="{{ route('admin.master-kategori-dokumen.index', array_merge(request()->query(), ['sort' => 'name', 'direction' => ($sortField === 'name' && $sortAsc) ? 'desc' : 'asc'])) }}" class="text-dark text-hover-primary">
                            Nama @if($sortField === 'name') <i class="ki-outline ki-arrow-{{ $sortAsc ? 'up' : 'down' }} fs-7"></i> @endif
                        </a>
                    </x-ui.table-th>
                    <x-ui.table-th>Slug</x-ui.table-th>
                    <x-ui.table-th align="center">Visibility</x-ui.table-th>
                    <x-ui.table-th align="center">Status</x-ui.table-th>
                    <x-ui.table-th align="center">
                        <a href="{{ route('admin.master-kategori-dokumen.index', array_merge(request()->query(), ['sort' => 'sort_order', 'direction' => ($sortField === 'sort_order' && $sortAsc) ? 'desc' : 'asc'])) }}" class="text-dark text-hover-primary">
                            Urutan @if($sortField === 'sort_order') <i class="ki-outline ki-arrow-{{ $sortAsc ? 'up' : 'down' }} fs-7"></i> @endif
                        </a>
                    </x-ui.table-th>
                    <x-ui.table-th align="end">Aksi</x-ui.table-th>
                </tr>
            </thead>
            <tbody>
                @forelse($categories as $cat)
                    <tr>
                        <td class="fw-semibold">
                            <i class="ki-outline ki-{{ $cat->icon }} fs-6 me-2 text-primary"></i>
                            {{ $cat->name }}
                        </td>
                        <td><code>{{ $cat->slug }}</code></td>
                        <td class="text-center">
                            <span class="badge badge-light-{{ $cat->visibility === 'public' ? 'success' : 'warning' }}">
                                {{ ucfirst($cat->visibility) }}
                            </span>
                        </td>
                        <td class="text-center">
                            <span class="badge badge-light-{{ $cat->is_active ? 'success' : 'danger' }}">
                                {{ $cat->is_active ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </td>
                        <td class="text-center">{{ $cat->sort_order }}</td>
                        <td class="text-end">
                            <div class="d-flex align-items-center justify-content-end gap-2">
                                <x-ui.icon-button
                                    icon="pencil"
                                    label="Edit"
                                    variant="primary"
                                    x-on:click="openEditModal(@js($cat))"
                                />
                                <form method="POST" action="{{ route('admin.master-kategori-dokumen.destroy', $cat) }}" class="d-inline"
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
                        <td colspan="6">
                            <x-ui.empty-state title="Belum ada kategori" description="Tambahkan kategori dokumen untuk mulai mengorganisir file." class="py-10" />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </x-ui.simple-table>

        <div class="mt-5">
            {{ $categories->links() }}
        </div>
    </x-ui.index-layout>

    {{-- Modal Create/Edit --}}
    <x-ui.modal name="kategori-modal" focusable>
        <form method="POST" x-bind:action="formAction" x-on:submit.prevent="submitForm($event)">
            @csrf
            <template x-if="isEditing">
                <input type="hidden" name="_method" value="PUT">
            </template>

            <x-ui.modal-header
                x-bind:title="isEditing ? 'Edit Kategori' : 'Tambah Kategori'"
                subtitle="Lengkapi data kategori dokumen."
                icon="folder"
            />

            <x-ui.modal-body>
                <div class="row g-5">
                    <div class="col-md-8">
                        <label class="form-label required" for="cat_name">Nama Kategori</label>
                        <input type="text" name="name" id="cat_name" x-model="form.name"
                               x-on:input="autoSlug()" class="form-control" placeholder="Nama kategori" required>
                        @error('name') <div class="text-danger fs-8 mt-1">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label required" for="cat_sort_order">Urutan</label>
                        <input type="number" name="sort_order" id="cat_sort_order" x-model="form.sort_order"
                               class="form-control" min="0" max="9999">
                    </div>
                </div>

                <div class="row g-5 mt-1">
                    <div class="col-md-8">
                        <label class="form-label required" for="cat_slug">Slug</label>
                        <input type="text" name="slug" id="cat_slug" x-model="form.slug"
                               class="form-control" placeholder="auto_generated" required pattern="^[a-z0-9_]+$">
                        <div class="text-muted fs-8 mt-1">Huruf kecil, angka, dan underscore saja.</div>
                        @error('slug') <div class="text-danger fs-8 mt-1">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label required" for="cat_icon">Icon</label>
                        <input type="text" name="icon" id="cat_icon" x-model="form.icon"
                               class="form-control" placeholder="document" required>
                    </div>
                </div>

                <div class="mt-5">
                    <label class="form-label" for="cat_description">Deskripsi</label>
                    <textarea name="description" id="cat_description" x-model="form.description"
                              class="form-control" rows="2" placeholder="Opsional"></textarea>
                </div>

                <div class="row g-5 mt-1">
                    <div class="col-md-6">
                        <label class="form-label required">Visibility</label>
                        <select name="visibility" x-model="form.visibility" class="form-select">
                            @foreach(\App\Models\DocumentCategory::VISIBILITIES as $v)
                                <option value="{{ $v }}">{{ ucfirst($v) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6 d-flex align-items-end">
                        <label class="form-check">
                            <input type="checkbox" name="is_active" value="1" x-model="form.is_active" class="form-check-input">
                            <span class="form-check-label">Aktif</span>
                        </label>
                    </div>
                </div>

                <div class="row g-5 mt-1">
                    <div class="col-md-6">
                        <label class="form-check">
                            <input type="checkbox" name="pesantren_can_upload" value="1" x-model="form.pesantren_can_upload" class="form-check-input">
                            <span class="form-check-label">Pesantren dapat upload</span>
                        </label>
                    </div>
                    <div class="col-md-6">
                        <label class="form-check">
                            <input type="checkbox" name="asesor_can_upload" value="1" x-model="form.asesor_can_upload" class="form-check-input">
                            <span class="form-check-label">Asesor dapat upload</span>
                        </label>
                    </div>
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
    function kategoriDokumen() {
        return {
            isEditing: false,
            formAction: '{{ route("admin.master-kategori-dokumen.store") }}',
            form: {
                name: '', slug: '', description: '', icon: 'document',
                visibility: 'public', pesantren_can_upload: false,
                asesor_can_upload: false, is_active: true, sort_order: 0
            },

            autoSlug() {
                if (!this.isEditing) {
                    this.form.slug = this.form.name.toLowerCase()
                        .replace(/[^a-z0-9\s]/g, '')
                        .replace(/\s+/g, '_')
                        .substring(0, 100);
                }
            },

            openCreateModal() {
                this.isEditing = false;
                this.formAction = '{{ route("admin.master-kategori-dokumen.store") }}';
                this.form = {
                    name: '', slug: '', description: '', icon: 'document',
                    visibility: 'public', pesantren_can_upload: false,
                    asesor_can_upload: false, is_active: true, sort_order: 0
                };
                this.$dispatch('open-modal', 'kategori-modal');
            },

            openEditModal(cat) {
                this.isEditing = true;
                this.formAction = '{{ url("admin/master-kategori-dokumen") }}/' + cat.id;
                this.form = {
                    name: cat.name,
                    slug: cat.slug,
                    description: cat.description || '',
                    icon: cat.icon,
                    visibility: cat.visibility,
                    pesantren_can_upload: !!cat.pesantren_can_upload,
                    asesor_can_upload: !!cat.asesor_can_upload,
                    is_active: !!cat.is_active,
                    sort_order: cat.sort_order || 0
                };
                this.$dispatch('open-modal', 'kategori-modal');
            },

            submitForm(e) {
                e.target.submit();
            },

            confirmDelete(e) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: 'Hapus kategori ini?',
                        text: 'Kategori yang masih memiliki dokumen tidak bisa dihapus.',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Ya, hapus',
                        cancelButtonText: 'Batal',
                    }).then((result) => {
                        if (result.isConfirmed) e.target.submit();
                    });
                } else {
                    if (confirm('Hapus kategori ini?')) e.target.submit();
                }
            }
        };
    }
</script>
@endsection
