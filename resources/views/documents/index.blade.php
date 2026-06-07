@extends('layouts.app')

@section('content')
<div data-module-page="dokumen">
    @php
        $activeDocLabel = $doc === 'all' || $doc === ''
            ? 'Semua Dokumen'
            : str($doc)->replace(['-', '_'], ' ')->upper()->toString();
    @endphp

    <x-slot name="header">
        {{ $pageTitle }}
    </x-slot>

    <x-ui.page
        :title="$pageTitle"
        subtitle="Daftar dokumen yang tersedia sesuai hak akses pengguna."
    >
        <x-datatable.layout
            :title="$pageTitle"
            subtitle="Buka berkas yang dibutuhkan untuk proses akreditasi."
            :records="$documents"
            class="spm-table-shell--document-category spm-table-shell--document-library"
        >
            <x-slot name="toolbar">
                <x-ui.badge variant="secondary">{{ $activeDocLabel }}</x-ui.badge>
            </x-slot>

            <x-slot name="filters">
                <form method="GET" action="{{ route('documents.index', ['doc' => $doc]) }}" class="d-flex align-items-center gap-2">
                    <input type="hidden" name="perPage" value="{{ $perPage }}">
                    <input type="text" name="search" value="{{ $search }}" placeholder="Cari dokumen..." class="form-control form-control-sm" onchange="this.form.submit()">
                </form>
            </x-slot>

            <x-slot name="thead">
                <x-ui.table-th class="spm-document-title-col">Dokumen</x-ui.table-th>
                <x-ui.table-th :min-width="false" align="center" class="spm-document-format-col">Format</x-ui.table-th>
                <x-ui.table-th :min-width="false" align="center" class="spm-document-date-col">Diunggah</x-ui.table-th>
                <x-ui.table-th align="end" class="spm-action-col">Aksi</x-ui.table-th>
            </x-slot>

            <x-slot name="tbody">
                @forelse ($documents as $document)
                    <tr>
                        <td class="spm-document-title-cell">
                            <div class="d-flex align-items-center gap-3">
                                <div class="symbol symbol-40px">
                                    <div class="symbol-label bg-light-primary text-primary">
                                        <x-ui.icon name="document" class="fs-2" />
                                    </div>
                                </div>

                                <div class="d-flex flex-column">
                                    <span class="text-gray-900 fw-semibold fs-6">{{ $document->title }}</span>
                                    <span class="text-muted fw-semibold fs-7 spm-document-file-name">{{ basename($document->file_path) }}</span>
                                </div>
                            </div>
                        </td>

                        <td class="text-center spm-document-format-cell">
                            <x-ui.badge variant="info">{{ strtoupper(pathinfo($document->file_path, PATHINFO_EXTENSION)) }}</x-ui.badge>
                        </td>

                        <td class="text-center spm-document-date-cell">
                            <span class="text-gray-700 fw-semibold">{{ $document->created_at->translatedFormat('d M Y') }}</span>
                        </td>

                        <td class="text-end spm-action-cell">
                            <x-ui.button
                                :href="Storage::url($document->file_path)"
                                target="_blank"
                                variant="light-primary"
                                size="sm"
                                icon="eye"
                                class="spm-table-compact-action"
                            >
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
@endsection
