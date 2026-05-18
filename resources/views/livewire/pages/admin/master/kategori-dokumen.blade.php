<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\WithPagination;
use App\Models\DocumentCategory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Gate;

new #[Layout('layouts.app')] class extends Component {
    use WithPagination;

    // Form fields
    public ?int $categoryId = null;
    public string $name = '';
    public string $slug = '';
    public ?string $description = null;
    public string $icon = 'document';
    public string $visibility = DocumentCategory::VISIBILITY_PUBLIC;
    public bool $pesantren_can_upload = false;
    public bool $asesor_can_upload = false;
    public bool $is_active = true;
    public int $sort_order = 0;

    // Datatable
    public string $search = '';
    public int $perPage = 10;
    public string $sortField = 'sort_order';
    public bool $sortAsc = true;

    public function mount(): void
    {
        if (!auth()->user()->canAccessAdminArea()) {
                    abort(403);
                }
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    public function updatedName(string $value): void
    {
        // Auto-generate slug only when creating (not edit)
        if (!$this->categoryId) {
            $this->slug = Str::slug($value, '_');
        }
    }

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortAsc = !$this->sortAsc;
        } else {
            $this->sortAsc = true;
        }
        $this->sortField = $field;
    }

    public function getCategoriesProperty()
    {
        return DocumentCategory::query()
            ->when($this->search, fn($q) => $q->where(function ($qq) {
                $qq->where('name', 'like', '%' . $this->search . '%')
                   ->orWhere('slug', 'like', '%' . $this->search . '%')
                   ->orWhere('description', 'like', '%' . $this->search . '%');
            }))
            ->orderBy($this->sortField, $this->sortAsc ? 'asc' : 'desc')
            ->paginate($this->perPage);
    }

    public function openModal(): void
    {
        $this->resetValidation();
        $this->reset(['categoryId', 'name', 'slug', 'description', 'icon', 'visibility',
            'pesantren_can_upload', 'asesor_can_upload', 'is_active', 'sort_order']);
        $this->icon = 'document';
        $this->visibility = DocumentCategory::VISIBILITY_PUBLIC;
        $this->is_active = true;
        $this->sort_order = 0;
        $this->dispatch('open-modal', 'kategori-modal');
    }

    public function edit(int $id): void
    {
        $this->resetValidation();
        $cat = DocumentCategory::findOrFail($id);

        $this->categoryId = $cat->id;
        $this->name = $cat->name;
        $this->slug = $cat->slug;
        $this->description = $cat->description;
        $this->icon = $cat->icon ?: 'document';
        $this->visibility = $cat->visibility;
        $this->pesantren_can_upload = (bool) $cat->pesantren_can_upload;
        $this->asesor_can_upload = (bool) $cat->asesor_can_upload;
        $this->is_active = (bool) $cat->is_active;
        $this->sort_order = (int) $cat->sort_order;

        $this->dispatch('open-modal', 'kategori-modal');
    }

    public function save(): void
    {
        Gate::authorize('master.kategori');

        $rules = [
            'name' => 'required|string|max:150',
            'slug' => [
                'required',
                'string',
                'max:100',
                'regex:/^[a-z0-9_]+$/',
                \Illuminate\Validation\Rule::unique('document_categories', 'slug')->ignore($this->categoryId),
            ],
            'description' => 'nullable|string|max:1000',
            'icon' => 'required|string|max:50',
            'visibility' => 'required|in:' . implode(',', DocumentCategory::VISIBILITIES),
            'pesantren_can_upload' => 'boolean',
            'asesor_can_upload' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer|min:0|max:9999',
        ];

        $data = $this->validate($rules, [
            'slug.regex' => 'Slug hanya boleh huruf kecil, angka, dan underscore.',
        ]);

        if ($this->categoryId) {
            DocumentCategory::findOrFail($this->categoryId)->update($data);
            $msg = 'Kategori dokumen berhasil diperbarui.';
        } else {
            DocumentCategory::create($data);
            $msg = 'Kategori dokumen berhasil dibuat.';
        }

        $this->dispatch('close-modal', 'kategori-modal');
        $this->dispatch('notification-received', type: 'success', title: 'Berhasil', message: $msg);
        $this->reset(['categoryId', 'name', 'slug', 'description', 'icon', 'visibility',
            'pesantren_can_upload', 'asesor_can_upload', 'is_active', 'sort_order']);
        $this->icon = 'document';
        $this->visibility = DocumentCategory::VISIBILITY_PUBLIC;
        $this->is_active = true;
    }

    public function delete(int $id): void
    {
        Gate::authorize('master.kategori');

        $cat = DocumentCategory::find($id);
        if (!$cat) {
            $this->dispatch('notification-received', type: 'error', title: 'Gagal', message: 'Kategori tidak ditemukan.');
            return;
        }

        // Block delete if any document still attached
        $hasDocs = $cat->documents()->exists();
        if ($hasDocs) {
            $this->dispatch('notification-received', type: 'error', title: 'Gagal',
                message: 'Kategori masih dipakai oleh dokumen. Hapus atau pindahkan dokumen terkait dulu.');
            return;
        }

        $cat->delete();
        $this->dispatch('notification-received', type: 'success', title: 'Berhasil', message: 'Kategori berhasil dihapus.');
    }
}; ?>

<div x-data="{ ...deleteConfirmation() }" data-module-page="master-kategori-dokumen">
    <x-ui.index-layout
        title="Master Kategori Dokumen"
        subtitle="Kelola kategori dokumen, hak akses per role, dan urutan tampil di sidebar."
    >
        <x-datatable.layout title="Kategori Dokumen" :records="$this->categories">
            <x-slot name="filters">
                <x-datatable.search placeholder="Cari kategori..." />
            </x-slot>

            <x-slot name="toolbar">
                <x-ui.button wire:click="openModal" variant="primary" size="sm">
                    <x-ui.icon name="plus" class="fs-4 me-1" />
                    Tambah Kategori
                </x-ui.button>
            </x-slot>

            <x-slot name="thead">
                <x-datatable.th field="sort_order" :sortField="$sortField" :sortAsc="$sortAsc" class="text-center" style="width: 80px;">
                    Urutan
                </x-datatable.th>
                <x-datatable.th field="name" :sortField="$sortField" :sortAsc="$sortAsc">
                    Nama Kategori
                </x-datatable.th>
                <x-datatable.th field="slug" :sortField="$sortField" :sortAsc="$sortAsc">
                    Slug
                </x-datatable.th>
                <x-ui.table-th align="center">Visibilitas</x-ui.table-th>
                <x-ui.table-th align="center">Upload</x-ui.table-th>
                <x-ui.table-th align="center">Status</x-ui.table-th>
                <x-ui.table-th align="end">Aksi</x-ui.table-th>
            </x-slot>

            <x-slot name="tbody">
                @forelse ($this->categories as $cat)
                <tr wire:key="cat-{{ $cat->id }}">
                    <td class="text-center fw-bold text-gray-700">{{ $cat->sort_order }}</td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <span class="symbol symbol-30px">
                                <span class="symbol-label bg-light-primary text-primary">
                                    <x-ui.icon :name="$cat->icon ?: 'document'" class="fs-5" />
                                </span>
                            </span>
                            <div>
                                <div class="fw-semibold text-gray-900 fs-6">{{ $cat->name }}</div>
                                @if($cat->description)
                                <div class="text-muted fs-8">{{ Str::limit($cat->description, 60) }}</div>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td>
                        <code class="fs-8 text-gray-700">{{ $cat->slug }}</code>
                    </td>
                    <td class="text-center">
                        @if($cat->visibility === \App\Models\DocumentCategory::VISIBILITY_PUBLIC)
                            <x-ui.status-badge variant="success">Publik</x-ui.status-badge>
                        @elseif($cat->visibility === \App\Models\DocumentCategory::VISIBILITY_PESANTREN_SECRET)
                            <x-ui.status-badge variant="primary">Pesantren Only</x-ui.status-badge>
                        @elseif($cat->visibility === \App\Models\DocumentCategory::VISIBILITY_ASESOR_SECRET)
                            <x-ui.status-badge variant="info">Asesor Only</x-ui.status-badge>
                        @else
                            <x-ui.status-badge variant="secondary">{{ $cat->visibility }}</x-ui.status-badge>
                        @endif
                    </td>
                    <td class="text-center">
                        <div class="d-flex align-items-center justify-content-center gap-2 flex-wrap">
                            @if($cat->pesantren_can_upload)
                                <x-ui.status-badge variant="primary">Pesantren</x-ui.status-badge>
                            @endif
                            @if($cat->asesor_can_upload)
                                <x-ui.status-badge variant="info">Asesor</x-ui.status-badge>
                            @endif
                            @if(!$cat->pesantren_can_upload && !$cat->asesor_can_upload)
                                <span class="text-muted fs-8">Admin saja</span>
                            @endif
                        </div>
                    </td>
                    <td class="text-center">
                        <x-ui.status-badge :variant="$cat->is_active ? 'success' : 'danger'">
                            {{ $cat->is_active ? 'Aktif' : 'Non-Aktif' }}
                        </x-ui.status-badge>
                    </td>
                    <td class="text-end">
                        <x-ui.action-menu>
                            <x-ui.action-menu-item wire:click="edit({{ $cat->id }})">
                                <x-ui.icon name="pencil" class="fs-5 text-gray-500" />
                                Edit Kategori
                            </x-ui.action-menu-item>

                            <x-ui.action-menu-item
                                variant="danger"
                                x-on:click="confirmDelete({{ $cat->id }}, 'delete', 'Kategori ini akan dihapus. Pastikan tidak ada dokumen terkait.')"
                            >
                                <x-ui.icon name="trash" class="fs-5" />
                                Hapus Kategori
                            </x-ui.action-menu-item>
                        </x-ui.action-menu>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7">
                        <x-ui.empty-state title="Belum ada kategori dokumen" class="py-15" />
                    </td>
                </tr>
                @endforelse
            </x-slot>
        </x-datatable.layout>
    </x-ui.index-layout>

    <!-- Modal Form -->
    <x-ui.modal name="kategori-modal" maxWidth="lg">
        <form wire:submit.prevent="save">
            <x-ui.modal-header
                :title="$categoryId ? 'Edit Kategori Dokumen' : 'Tambah Kategori Dokumen'"
                subtitle="Atur visibilitas dan hak upload per role."
                icon="setting-2"
            />

            <x-ui.modal-body>
                <div class="row g-5">
                    <div class="col-md-8">
                        <x-ui.form-field label="Nama Kategori" for="name" :error="$errors->get('name')">
                            <x-ui.input model="name" id="name" placeholder="Contoh: Laporan Visitasi" required />
                        </x-ui.form-field>
                    </div>
                    <div class="col-md-4">
                        <x-ui.form-field label="Urutan" for="sort_order" :error="$errors->get('sort_order')">
                            <x-ui.input model="sort_order" id="sort_order" type="number" min="0" max="9999" />
                        </x-ui.form-field>
                    </div>
                </div>

                <x-ui.form-field label="Slug (huruf kecil, angka, underscore)" for="slug" :error="$errors->get('slug')">
                    <x-ui.input model="slug" id="slug" placeholder="contoh_kategori" required />
                </x-ui.form-field>

                <x-ui.form-field label="Deskripsi" for="description" :error="$errors->get('description')">
                    <x-ui.textarea model="description" id="description" rows="3" placeholder="Penjelasan singkat tentang kategori ini..." />
                </x-ui.form-field>

                <x-ui.form-field label="Icon (KeenIcons)" for="icon" :error="$errors->get('icon')">
                    <x-ui.input model="icon" id="icon" placeholder="document" />
                    <small class="text-muted fs-8 mt-1 d-block">Contoh: document, document-stack, clipboard-check, document-up, file. Lihat dokumentasi KeenIcons.</small>
                </x-ui.form-field>

                <x-ui.form-field label="Visibilitas (siapa yang bisa LIHAT dokumen di kategori ini)" :error="$errors->get('visibility')">
                    <div class="d-flex flex-column gap-3">
                        <label class="d-flex align-items-start gap-3 p-3 rounded border cursor-pointer" :class="$wire.visibility === 'public' ? 'border-success bg-light-success' : 'border-gray-300'">
                            <input type="radio" wire:model.live="visibility" value="public" class="form-check-input mt-1" />
                            <div>
                                <div class="fw-bold text-gray-800">Publik</div>
                                <div class="fs-8 text-muted">Bisa dilihat oleh semua role (Pesantren + Asesor + Admin). Cocok untuk panduan, IAPM, template umum.</div>
                            </div>
                        </label>
                        <label class="d-flex align-items-start gap-3 p-3 rounded border cursor-pointer" :class="$wire.visibility === 'pesantren_secret' ? 'border-primary bg-light-primary' : 'border-gray-300'">
                            <input type="radio" wire:model.live="visibility" value="pesantren_secret" class="form-check-input mt-1" />
                            <div>
                                <div class="fw-bold text-gray-800">Pesantren Only (Rahasia dari Asesor)</div>
                                <div class="fs-8 text-muted">Hanya Pesantren + Admin yang bisa lihat. Cocok untuk Kartu Kendali (penilaian asesor di lapangan dari sudut pandang pesantren).</div>
                            </div>
                        </label>
                        <label class="d-flex align-items-start gap-3 p-3 rounded border cursor-pointer" :class="$wire.visibility === 'asesor_secret' ? 'border-info bg-light-info' : 'border-gray-300'">
                            <input type="radio" wire:model.live="visibility" value="asesor_secret" class="form-check-input mt-1" />
                            <div>
                                <div class="fw-bold text-gray-800">Asesor Only (Rahasia dari Pesantren)</div>
                                <div class="fs-8 text-muted">Hanya Asesor + Admin yang bisa lihat. Cocok untuk Laporan Visitasi (penilaian asesor terhadap pesantren).</div>
                            </div>
                        </label>
                    </div>
                </x-ui.form-field>

                <div class="row g-6 pt-2">
                    <div class="col-md-6">
                        <x-ui.form-field label="Hak Upload" class="mb-0">
                            <div class="d-flex flex-column gap-2">
                                <x-ui.checkbox model="pesantren_can_upload" label="Pesantren boleh upload" />
                                <x-ui.checkbox model="asesor_can_upload" label="Asesor boleh upload" />
                            </div>
                            <small class="text-muted fs-8 d-block mt-2">Admin selalu bisa upload. Centang ini hanya bila role lain perlu mengisi dokumen.</small>
                        </x-ui.form-field>
                    </div>

                    <div class="col-md-6">
                        <x-ui.form-field label="Status" class="mb-0">
                            <div class="d-flex align-items-center gap-6">
                                <x-ui.radio model="is_active" value="1" label="Aktif" />
                                <x-ui.radio model="is_active" value="0" label="Non-Aktif" />
                            </div>
                            <small class="text-muted fs-8 d-block mt-2">Kategori non-aktif tidak muncul di sidebar pesantren/asesor.</small>
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
