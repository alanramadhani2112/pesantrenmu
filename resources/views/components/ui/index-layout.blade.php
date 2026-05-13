@props([
    'title',
    'subtitle' => null,
    'tableHeader' => null,
])

<x-ui.page :title="$title" :subtitle="$subtitle" compact>
    @isset($toolbar)
        <div class="mb-5">
            {{ $toolbar }}
        </div>
    @endisset

    @isset($tabs)
        <div class="card mb-5">
            <div class="card-header border-0 py-6 min-h-auto overflow-x-auto">
                {{ $tabs }}
            </div>
        </div>
    @endisset

    @isset($content)
        {{ $content }}
    @else
        @if($tableHeader)
            <div class="card card-flush">
                <div class="card-header pt-5">
                    <h3 class="card-title align-items-start flex-column">
                        <span class="card-label fw-bold text-gray-800">{{ $tableHeader }}</span>
                    </h3>
                </div>
                <div class="card-body pt-0">
                    {{ $slot }}
                </div>
            </div>
        @else
            {{ $slot }}
        @endif
    @endisset
</x-ui.page>
