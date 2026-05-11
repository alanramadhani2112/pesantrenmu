@props([
    'title' => null,
    'subtitle' => null,
    'flush' => false,
])

<div data-ui-card="metronic" {{ $attributes->merge(['class' => 'card']) }}>
    @if($title || isset($toolbar))
        <div class="card-header border-0">
            <div class="card-title d-flex flex-column">
                @if($title)
                    <h3 class="spm-card-title mb-1">{{ $title }}</h3>
                @endif

                @if($subtitle)
                    <span class="spm-card-subtitle text-muted">{{ $subtitle }}</span>
                @endif
            </div>

            @isset($toolbar)
                <div class="card-toolbar">
                    {{ $toolbar }}
                </div>
            @endisset
        </div>
    @endif

    <div class="{{ $flush ? 'card-body p-0' : 'card-body' }}">
        {{ $slot }}
    </div>
</div>
