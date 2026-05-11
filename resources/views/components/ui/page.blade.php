@props([
    'title',
    'subtitle' => null,
])

<div data-ui-page="metronic" {{ $attributes->merge(['class' => 'd-flex flex-column gap-6 spm-page']) }}>
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-4">
        <div>
            <h1 class="spm-page-title text-gray-900 mb-2">{{ $title }}</h1>

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
