<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use App\Models\DocumentCategory;

new #[Layout('layouts.app')] class extends Component {
    use \Livewire\WithPagination;

    public $doc = 'all';
    public $search = '';
    public $perPage = 10;

    public function mount($doc = 'all')
    {
        $this->doc = $doc;
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedPerPage()
    {
        $this->resetPage();
    }

    /**
     * Resolve the role-scope used for visibility filtering.
     * Admin sees every active category; asesor and pesantren are
     * scoped to their visibility lane; anyone else is denied.
     */
    public function getRoleScopeProperty(): ?string
    {
        /** @var \App\Models\User|null $user */
        $user = auth()->user();
        if (!$user) {
            return null;
        }

        return match (true) {
            $user->canAccessAdminArea() => 'admin',
            $user->isAsesor() => 'asesor',
            $user->isPesantren() => 'pesantren',
            default => null,
        };
    }

    public function getDocumentsProperty()
    {
        $documentService = app(\App\Services\DocumentService::class);
        return $documentService->getActiveDocuments(
            $this->roleScope,
            $this->doc,
            $this->search,
            $this->perPage
        );
    }

    /**
     * Resolve the human-friendly page title for the active doc filter.
     * Falls back to the DocumentCategory name if a slug is selected, so
     * newly-created categories show a meaningful heading without code edits.
     */
    public function getPageTitleProperty(): string
    {
        if ($this->doc === 'all' || $this->doc === '') {
            return 'Daftar Dokumen';
        }

        $category = DocumentCategory::query()
            ->where('slug', $this->doc)
            ->value('name');

        return $category ?: 'Daftar Dokumen';
    }
}; ?>

<div data-module-page="dokumen">
    <x-slot name="header">
        {{ $this->pageTitle }}
    </x-slot>

    <x-ui.page
        :title="$this->pageTitle"
        subtitle="Daftar dokumen yang tersedia sesuai hak akses pengguna."
    >
        <x-datatable.layout
            title="Dokumen Tersedia"
            subtitle="Buka berkas yang dibutuhkan untuk proses akreditasi."
        >
            <x-slot name="filters">
                <x-datatable.search placeholder="Cari dokumen..." />
            </x-slot>

            <x-slot name="thead">
                <x-ui.table-th>Dokumen</x-ui.table-th>
                <x-ui.table-th>Format</x-ui.table-th>
                <x-ui.table-th>Diunggah</x-ui.table-th>
                <x-ui.table-th align="end">Aksi</x-ui.table-th>
            </x-slot>

            <x-slot name="tbody">
                @forelse ($this->documents as $doc)
                    <tr wire:key="dokumen-{{ $doc->id }}">
                        <td>
                            <div class="d-flex align-items-center gap-3">
                                <div class="symbol symbol-40px">
                                    <div class="symbol-label bg-light-primary text-primary">
                                        <x-ui.icon name="document" class="fs-2" />
                                    </div>
                                </div>

                                <div class="d-flex flex-column">
                                    <span class="text-gray-900 fw-bold fs-6">{{ $doc->title }}</span>
                                    <span class="text-muted fw-semibold fs-7">{{ basename($doc->file_path) }}</span>
                                </div>
                            </div>
                        </td>

                        <td>
                            <x-ui.badge variant="info">{{ strtoupper(pathinfo($doc->file_path, PATHINFO_EXTENSION)) }}</x-ui.badge>
                        </td>

                        <td>
                            <span class="text-gray-700 fw-semibold">{{ $doc->created_at->translatedFormat('d M Y') }}</span>
                        </td>

                        <td class="text-end">
                            <x-ui.button
                                :href="Storage::url($doc->file_path)"
                                target="_blank"
                                variant="primary"
                                size="sm"
                            >
                                <x-ui.icon name="eye" class="fs-4 me-1" />
                                Buka Berkas
                            </x-ui.button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4">
                            <x-ui.empty-state
                                title="Belum ada dokumen"
                                description="Admin belum membagikan dokumen untuk kategori ini."
                                class="py-15"
                            />
                        </td>
                    </tr>
                @endforelse
            </x-slot>
        </x-datatable.layout>
    </x-ui.page>
</div>
