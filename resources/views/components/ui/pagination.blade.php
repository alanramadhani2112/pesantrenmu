@props([
    'paginator',
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
            {{-- Previous --}}
            <li class="page-item previous {{ $paginator->onFirstPage() ? 'disabled' : '' }}">
                @if ($paginator->onFirstPage())
                    <span class="page-link" aria-hidden="true">
                        <x-ui.icon name="arrow-left" class="fs-7" />
                    </span>
                @else
                    <a class="page-link" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="{{ __('pagination.previous') }}">
                        <x-ui.icon name="arrow-left" class="fs-7" />
                    </a>
                @endif
            </li>

            {{-- Page Numbers --}}
            @foreach ($paginator->getUrlRange(1, $paginator->lastPage()) as $page => $url)
                @if ($page == $paginator->currentPage())
                    <li class="page-item active">
                        <span class="page-link" aria-current="page">{{ $page }}</span>
                    </li>
                @else
                    @php
                        // Show first, last, current ±2, and ellipsis
                        $show = $page == 1
                            || $page == $paginator->lastPage()
                            || abs($page - $paginator->currentPage()) <= 2;
                        $isEllipsis = !$show
                            && ($page == 2 || $page == $paginator->lastPage() - 1
                                || $page == $paginator->currentPage() - 3
                                || $page == $paginator->currentPage() + 3);
                    @endphp

                    @if($show)
                        <li class="page-item">
                            <a class="page-link" href="{{ $url }}" aria-label="{{ __('Go to page :page', ['page' => $page]) }}">
                                {{ $page }}
                            </a>
                        </li>
                    @elseif($isEllipsis)
                        <li class="page-item disabled" aria-disabled="true">
                            <span class="page-link">...</span>
                        </li>
                    @endif
                @endif
            @endforeach

            {{-- Next --}}
            <li class="page-item next {{ $paginator->hasMorePages() ? '' : 'disabled' }}">
                @if ($paginator->hasMorePages())
                    <a class="page-link" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="{{ __('pagination.next') }}">
                        <x-ui.icon name="arrow-right" class="fs-7" />
                    </a>
                @else
                    <span class="page-link" aria-hidden="true">
                        <x-ui.icon name="arrow-right" class="fs-7" />
                    </span>
                @endif
            </li>
        </ul>
    </nav>
@endif
