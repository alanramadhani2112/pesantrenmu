@props([
    'href' => null,
    'icon' => null,
    'title' => null,
    'description' => null,
    'variant' => 'primary',
    'iconSize' => '40px',
    'iconShape' => 'rounded',
    'iconClass' => 'fs-3',
    'align' => 'center',
])

@php
    $isCentered = $align === 'center';
    $baseClass = 'card border border-dashed border-gray-300 h-100 text-decoration-none spm-action-card';
    $bodyClass = $isCentered
        ? 'card-body d-flex flex-column align-items-center text-center p-4'
        : 'card-body d-flex align-items-center gap-4 p-4';
@endphp

@if($href)
    <a href="{{ $href }}" data-ui-action-card="metronic" {{ $attributes->merge(['class' => $baseClass]) }}>
        <div class="{{ $bodyClass }}">
            @if($icon)
                <x-ui.symbol-icon :icon="$icon" :variant="$variant" :size="$iconSize" :shape="$iconShape" :icon-class="$iconClass" class="{{ $isCentered ? 'mb-3' : '' }}" />
            @endif

            <div class="{{ $isCentered ? '' : 'flex-grow-1 min-w-0' }}">
                @if($title)
                    <span class="fw-semibold fs-8 fs-md-7 text-gray-800">{{ $title }}</span>
                @endif

                @if($description)
                    <span class="d-block text-muted fw-semibold fs-8 mt-1">{{ $description }}</span>
                @endif

                {{ $slot }}
            </div>

            @isset($actions)
                <div class="{{ $isCentered ? 'mt-3' : 'flex-shrink-0' }}">
                    {{ $actions }}
                </div>
            @endisset
        </div>
    </a>
@else
    <div data-ui-action-card="metronic" {{ $attributes->merge(['class' => $baseClass]) }}>
        <div class="{{ $bodyClass }}">
            @if($icon)
                <x-ui.symbol-icon :icon="$icon" :variant="$variant" :size="$iconSize" :shape="$iconShape" :icon-class="$iconClass" class="{{ $isCentered ? 'mb-3' : '' }}" />
            @endif

            <div class="{{ $isCentered ? '' : 'flex-grow-1 min-w-0' }}">
                @if($title)
                    <span class="fw-semibold fs-8 fs-md-7 text-gray-800">{{ $title }}</span>
                @endif

                @if($description)
                    <span class="d-block text-muted fw-semibold fs-8 mt-1">{{ $description }}</span>
                @endif

                {{ $slot }}
            </div>

            @isset($actions)
                <div class="{{ $isCentered ? 'mt-3' : 'flex-shrink-0' }}">
                    {{ $actions }}
                </div>
            @endisset
        </div>
    </div>
@endif
