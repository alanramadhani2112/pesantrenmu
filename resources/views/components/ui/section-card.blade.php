@props([
    'title' => null,
    'subtitle' => null,
])

<div data-ui-section-card="metronic" {{ $attributes->merge(['class' => 'card spm-card--clean spm-section-card']) }}>
    @if($title || $subtitle || isset($toolbar))
        <div class="card-header border-0 py-4 spm-section-card-header">
            <div class="card-title d-flex align-items-center gap-3 m-0">
                <span class="spm-section-card-accent"></span>

                <div>
                    @if($title)
                        <h3 class="spm-card-title text-gray-900 mb-1">{{ $title }}</h3>
                    @endif

                    @if($subtitle)
                        <span class="spm-card-subtitle text-muted fw-semibold">{{ $subtitle }}</span>
                    @endif
                </div>
            </div>

            @isset($toolbar)
                <div class="card-toolbar d-flex align-items-center gap-2">
                    {{ $toolbar }}
                </div>
            @endisset
        </div>
    @endif

    <div class="card-body p-0">
        {{ $slot }}
    </div>
</div>
