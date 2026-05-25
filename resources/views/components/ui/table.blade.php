@props([
    'title' => null,
    'subtitle' => null,
    'records' => null,
    'showPerPage' => true,
])

<div data-ui-table="metronic" {{ $attributes->merge(['class' => 'card spm-table-shell spm-table-shell--standard']) }}>
    @if($title || $subtitle || isset($filters) || isset($toolbar))
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
                    <div class="spm-table-filter-row">
                        {{ $filters }}
                    </div>
                </div>
            @endisset
        </div>
    @endif

    <div class="card-body pt-0 spm-table-body-wrap">
        @if($showPerPage)
            <div class="spm-table-utility-row">
                <x-ui.table-per-page />
            </div>
        @endif

        <div class="table-responsive spm-table-scroll">
            <table class="table table-row-dashed align-middle gs-0 gy-4 mb-0 spm-datatable spm-table spm-table--list">
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
            <div class="spm-table-footer">
                <div class="spm-table-result-meta">
                    Menampilkan {{ $records->firstItem() ?? 0 }} sampai {{ $records->lastItem() ?? 0 }} dari {{ $records->total() ?? 0 }} data
                </div>

                <div class="pagination-indonesia">
                    {{ $records->links('livewire.datatable-pagination') }}
                </div>
            </div>
        @endif
    </div>
</div>
