@props([
    'title',
    'subtitle' => null,
    'compact' => false,
    'showHeading' => false,
])

@section('title', $title)

<div data-ui-page="metronic" {{ $attributes->merge(['class' => 'd-flex flex-column ' . ($compact ? 'gap-4' : 'gap-6') . ' spm-page']) }}>
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between {{ $compact ? 'gap-3' : 'gap-4' }}">
        <div>
            <h1 class="spm-page-title {{ $compact ? 'spm-page-title-compact mb-1' : 'mb-2' }} text-gray-900 {{ $showHeading ? '' : 'visually-hidden' }}">{{ $title }}</h1>

            @if($subtitle)
                <p class="spm-page-subtitle text-muted mb-0">{{ $subtitle }}</p>
            @endif
        </div>

        @isset($toolbar)
            <div class="d-flex align-items-center gap-2">
                {{ $toolbar }}
            </div>
        @endisset
    </div>

    {{ $slot }}
</div>
