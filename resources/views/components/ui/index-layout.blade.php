@props([
    'title',
    'subtitle' => null,
    'tableHeader' => null,
])

<x-ui.page :title="$title" :subtitle="$subtitle" compact>
    @isset($toolbar)
        <div class="spm-index-toolbar mb-5">
            {{ $toolbar }}
        </div>
    @endisset

    @isset($tabs)
        <div class="spm-index-tabs-shell mb-5">
            {{ $tabs }}
        </div>
    @endisset

    @isset($content)
        {{ $content }}
    @else
        @if($tableHeader)
            <div class="card card-flush spm-table-shell">
                <div class="card-header pt-5 spm-table-header">
                    <h3 class="card-title align-items-start flex-column">
                        <span class="card-label spm-card-title text-gray-800">{{ $tableHeader }}</span>
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
