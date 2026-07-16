{{--
    SPM Table Component — Standard operational table with Metronic styling.

    SLOT USAGE (IMPORTANT):
    - thead: Pass <th> elements ONLY. Do NOT wrap in <tr>. Component renders <tr> automatically.
    - tbody: Pass <tr> elements directly.
    - filters: Search/filter form. Use x-datatable.search and x-ui.select. Per-page control renders in footer.
    - toolbar: Buttons, badges (optional).
    - Pagination automatic if :records receives paginator. No manual ->links().

    EXAMPLE thead:
        <x-slot name="thead">
            <x-ui.table-th field="name" :sortField="$sortField" :sortAsc="$sortAsc">Name</x-ui.table-th>
            <x-ui.table-th align="center">Status</x-ui.table-th>
            <x-ui.table-th align="end">Aksi</x-ui.table-th>
        </x-slot>
--}}
@props([
    'title' => null,
    'subtitle' => null,
    'records' => null,
    'showPerPage' => true,
    'perPagePosition' => 'footer',
    'perPageVariant' => 'compact',
    'perPageOptions' => [10, 25, 50],
    'tableClass' => null,
])

@php
    $perPagePosition = in_array($perPagePosition, ['toolbar', 'footer'], true) ? $perPagePosition : 'footer';
    $usesDatatableAdapter = $attributes->has('data-ui-table-adapter');
    $showToolbarPerPage = $showPerPage && $perPagePosition === 'toolbar';
    $showFooterPerPage = $showPerPage && $perPagePosition === 'footer';
    $defaultClasses = 'table align-middle table-row-dashed fs-6 gy-5 mb-0 spm-datatable spm-table spm-table--list spm-table--metronic-docs';
    $tableClasses = $tableClass ? trim($defaultClasses . ' ' . $tableClass) : $defaultClasses;
@endphp

<div data-ui-table="metronic" {{ $attributes->merge(['class' => 'card spm-card--clean spm-table-shell spm-table-shell--standard']) }}>
    @if($title || $subtitle || isset($filters) || isset($toolbar) || $showToolbarPerPage)
        <div class="card-header border-0 spm-table-header">
            <div class="spm-table-heading">
                @if($title)
                    <h3 class="spm-card-title mb-1">{{ $title }}</h3>
                @endif

                @if($subtitle)
                    <span class="spm-card-subtitle text-muted">{{ $subtitle }}</span>
                @endif
            </div>

            @isset($toolbar)
                <div class="spm-table-actions">
                    {{ $toolbar }}
                </div>
            @endisset

            @isset($filters)
                <div class="spm-table-controls">
                    <div class="spm-table-dom-row">
                        <div class="spm-table-dom-start">
                            <div class="spm-table-filter-row">
                                {{ $filters }}
                            </div>
                        </div>

                        @if($showToolbarPerPage || ! $usesDatatableAdapter)
                            <div class="spm-table-dom-end">
                                @if($showToolbarPerPage)
                                <x-ui.table-per-page :variant="$perPageVariant" :options="$perPageOptions" />
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            @elseif($showToolbarPerPage)
                <div class="spm-table-controls">
                    <div class="spm-table-dom-row">
                        <div class="spm-table-dom-start"></div>
                        <div class="spm-table-dom-end">
                            <x-ui.table-per-page :variant="$perPageVariant" :options="$perPageOptions" />
                        </div>
                    </div>
                </div>
            @endisset
        </div>
    @endif

    <div class="card-body pt-0 spm-table-body-wrap">
        <div class="table-responsive spm-table-scroll">
            <table class="{{ $tableClasses }}">
                {{-- SLOT thead: <th> elements only. Do NOT wrap in <tr> — component renders <tr> here. --}}
                <thead>
                    <tr class="text-start text-gray-500 fw-semibold gs-0 spm-table-head">
                        {{ $thead }}
                    </tr>
                </thead>
                <tbody class="fw-normal text-gray-700 spm-table-body">
                    {{ $tbody }}
                </tbody>
            </table>
        </div>

        @if($records && method_exists($records, 'links'))
            <div class="spm-table-footer {{ $showFooterPerPage ? 'spm-table-footer--datatable' : '' }}">
                    <div class="spm-table-footer-start">
                    @if($showFooterPerPage)
                        <x-ui.table-per-page :variant="$perPageVariant" :options="$perPageOptions" />
                    @endif

                    <div class="spm-table-result-meta">
                        Menampilkan {{ $records->firstItem() ?? 0 }}-{{ $records->lastItem() ?? 0 }} dari {{ $records->total() ?? 0 }} entri
                    </div>
                </div>

                <div class="spm-table-footer-end pagination-indonesia">
                    <x-ui.pagination :paginator="$records" :show-info="false" />
                </div>
            </div>
        @endif
    </div>
</div>
