<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;

new #[Layout('layouts.app')] class extends Component {
    public $doc = 'all';

    public function mount($doc = 'all')
    {
        $this->doc = $doc;
    }

    public function getDocumentsProperty()
    {
        $documentService = app(\App\Services\DocumentService::class);
        /** @var \App\Models\User $user */
        $user = auth()->user();
        $role = $user->isAsesor() ? 'asesor' : ($user->isPesantren() ? 'pesantren' : null);
        return $documentService->getActiveDocuments($role, $this->doc);
    }
}; ?>

@php
    $pageTitle = match ($this->doc) {
        'iapm' => 'IAPM',
        'kartu_kendali' => 'Kartu Kendali',
        'visitasi' => 'Visitasi',
        default => 'Daftar Dokumen',
    };
@endphp

<div data-module-page="dokumen">
    <x-slot name="header">
        {{ $pageTitle }}
    </x-slot>

    <x-ui.page
        :title="$pageTitle"
        subtitle="Daftar dokumen yang tersedia sesuai hak akses pengguna."
    >
        <x-ui.table
            title="Dokumen Tersedia"
            subtitle="Buka berkas yang dibutuhkan untuk proses akreditasi."
            :show-per-page="false"
        >
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
        </x-ui.table>
    </x-ui.page>
</div>
