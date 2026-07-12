@extends('layouts.app')

@section('content')
<div data-module-page="dokumen">
    @php
        $activeDocLabel = $doc === 'all' || $doc === ''
            ? 'Semua Dokumen'
            : str($doc)->replace(['-', '_'], ' ')->upper()->toString();
        $activeCategory = $documentCategories->firstWhere('slug', $doc);
        $documentSubtitle = $activeCategory?->description
            ?: ($doc === 'all' || $doc === ''
                ? 'Semua dokumen yang tersedia sesuai hak akses Anda.'
                : 'Dokumen kategori '.$activeDocLabel.' yang tersedia untuk proses akreditasi.');
        $tabQueryParams = array_filter(request()->except(['page']));
        $categoryLinks = collect([[
            'slug' => 'all',
            'label' => 'Semua',
            'href' => route('documents.index', ['doc' => 'all']).($tabQueryParams ? '?'.http_build_query($tabQueryParams) : ''),
        ]])->merge($documentCategories->map(fn ($category) => [
            'slug' => $category->slug,
            'label' => $category->name,
            'href' => route('documents.index', ['doc' => $category->slug]).($tabQueryParams ? '?'.http_build_query($tabQueryParams) : ''),
        ]));
    @endphp

    <x-slot name="header">
        {{ $pageTitle }}
    </x-slot>

    <x-ui.page
        :title="$pageTitle"
        :subtitle="$documentSubtitle"
    >
        <div class="row g-5 mb-6 spm-document-summary">
            <div class="col-md-4">
                <x-ui.stat-card label="Kategori Aktif" value="{{ $activeDocLabel }}" variant="primary" icon="document" />
            </div>
            <div class="col-md-4">
                <x-ui.stat-card label="Dokumen Tampil" value="{{ $documents->total() }}" variant="info" icon="files-tablet" />
            </div>
            <div class="col-md-4">
                <x-ui.stat-card label="Hak Akses" value="{{ auth()->user()->role?->name ?? 'Pengguna' }}" variant="secondary" icon="shield-tick" />
            </div>
        </div>

        <x-ui.tabs class="mb-5 spm-document-category-tabs">
            @foreach($categoryLinks as $categoryLink)
                <li class="nav-item">
                    <a href="{{ $categoryLink['href'] }}"
                        data-ui-tab="metronic"
                        role="tab"
                        class="nav-link text-active-primary spm-tab-link {{ ($doc ?: 'all') === $categoryLink['slug'] ? 'active' : '' }}"
                        aria-selected="{{ ($doc ?: 'all') === $categoryLink['slug'] ? 'true' : 'false' }}">
                        {{ $categoryLink['label'] }}
                    </a>
                </li>
            @endforeach
        </x-ui.tabs>

        <x-ui.table
            :title="$pageTitle"
            :subtitle="$documentSubtitle"
            :records="$documents"
            class="spm-table-shell--document-category spm-table-shell--document-library"
        >
            <x-slot name="toolbar">
                <x-ui.badge variant="secondary">{{ $activeDocLabel }}</x-ui.badge>
            </x-slot>

            <x-slot name="filters">
                <form method="GET" action="{{ route('documents.index', ['doc' => $doc]) }}" id="documents-filter-form" class="d-flex align-items-center gap-3 flex-wrap">
                    <input type="hidden" name="perPage" value="{{ $perPage }}">
                    <x-datatable.search name="search" placeholder="Cari dokumen..." :value="$search" form="documents-filter-form" onchange="this.form.submit()" />
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
                                    @if($document->category)
                                        <span class="text-muted fs-8">{{ $document->category->name }}</span>
                                    @endif
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
                                :href="route('documents.download', $document)"
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
                                title="{{ ($doc ?: 'all') === 'all' ? 'Belum ada dokumen' : 'Dokumen '.$activeDocLabel.' belum tersedia' }}"
                                description="{{ ($doc ?: 'all') === 'all' ? 'Admin belum membagikan dokumen untuk Anda.' : 'Admin belum membagikan dokumen untuk kategori ini.' }}"
                                class="py-15"
                            />
                        </td>
                    </tr>
                @endforelse
            </x-slot>
        </x-ui.table>
    </x-ui.page>
</div>
@endsection
