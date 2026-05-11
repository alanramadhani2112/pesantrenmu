@props([
    'title' => null,
    'subtitle' => null,
    'records' => null,
    'showPerPage' => true,
])

<div data-ui-table="metronic" {{ $attributes->merge(['class' => 'card']) }}>
    @if($title || $subtitle || isset($filters))
        <div class="card-header border-0 pt-6">
            <div class="card-title d-flex flex-column">
                @if($title)
                    <h3 class="fw-bold text-gray-900 mb-1">{{ $title }}</h3>
                @endif

                @if($subtitle)
                    <span class="text-muted fw-semibold fs-7">{{ $subtitle }}</span>
                @endif
            </div>

            @isset($filters)
                <div class="card-toolbar">
                    <div class="d-flex flex-wrap align-items-center justify-content-end gap-3">
                        {{ $filters }}
                    </div>
                </div>
            @endisset
        </div>
    @endif

    <div class="card-body pt-0">
        @if($showPerPage || isset($toolbar))
            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-4 mb-5">
                <div>
                    @if($showPerPage)
                        <x-ui.table-per-page />
                    @endif
                </div>

                @isset($toolbar)
                    <div class="d-flex flex-wrap align-items-center justify-content-end gap-2">
                        {{ $toolbar }}
                    </div>
                @endisset
            </div>
        @endif

        <div class="table-responsive">
            <table class="table table-row-dashed align-middle gs-0 gy-4 mb-0">
                <thead>
                    <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                        {{ $thead }}
                    </tr>
                </thead>
                <tbody class="fw-semibold text-gray-700">
                    {{ $tbody }}
                </tbody>
            </table>
        </div>

        @if($records)
            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-4 pt-6">
                <div class="text-muted fw-semibold fs-7">
                    Menampilkan {{ $records->firstItem() ?? 0 }} sampai {{ $records->lastItem() ?? 0 }} dari {{ $records->total() ?? 0 }} data
                </div>

                <div class="pagination-indonesia">
                    {{ $records->links('livewire.datatable-pagination') }}
                </div>
            </div>
        @endif
    </div>
</div>
