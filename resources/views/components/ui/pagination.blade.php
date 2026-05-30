@props([
    'paginator',
    'elements' => [],
    'showInfo' => true,
])

@if ($paginator->hasPages())
    <nav
        data-ui-pagination="metronic"
        class="spm-pagination d-flex flex-column flex-md-row align-items-center justify-content-between gap-4"
        role="navigation"
        aria-label="Pagination Navigation"
    >
        @if($showInfo)
            <div class="text-gray-500 fw-semibold fs-7">
                @if($paginator->total())
                    Menampilkan {{ $paginator->firstItem() }}-{{ $paginator->lastItem() }} dari {{ $paginator->total() }} entri
                @endif
            </div>
        @endif

        <ul class="pagination pagination-circle pagination-outline mb-0">
            <li class="page-item previous {{ $paginator->onFirstPage() ? 'disabled' : '' }}">
                @if ($paginator->onFirstPage())
                    <span class="page-link" aria-hidden="true">
                        <x-ui.icon name="arrow-left" class="fs-7" />
                    </span>
                @else
                    <button type="button" class="page-link" wire:click="previousPage" wire:loading.attr="disabled" rel="prev" aria-label="{{ __('pagination.previous') }}">
                        <x-ui.icon name="arrow-left" class="fs-7" />
                    </button>
                @endif
            </li>

            @foreach ($elements as $element)
                @if (is_string($element))
                    <li class="page-item disabled" aria-disabled="true">
                        <span class="page-link">{{ $element }}</span>
                    </li>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        <li class="page-item {{ $page == $paginator->currentPage() ? 'active' : '' }}">
                            @if ($page == $paginator->currentPage())
                                <span class="page-link" aria-current="page">{{ $page }}</span>
                            @else
                                <button type="button" class="page-link" wire:click="gotoPage({{ $page }})" aria-label="{{ __('Go to page :page', ['page' => $page]) }}">
                                    {{ $page }}
                                </button>
                            @endif
                        </li>
                    @endforeach
                @endif
            @endforeach

            <li class="page-item next {{ $paginator->hasMorePages() ? '' : 'disabled' }}">
                @if ($paginator->hasMorePages())
                    <button type="button" class="page-link" wire:click="nextPage" wire:loading.attr="disabled" rel="next" aria-label="{{ __('pagination.next') }}">
                        <x-ui.icon name="arrow-right" class="fs-7" />
                    </button>
                @else
                    <span class="page-link" aria-hidden="true">
                        <x-ui.icon name="arrow-right" class="fs-7" />
                    </span>
                @endif
            </li>
        </ul>
    </nav>
@endif
