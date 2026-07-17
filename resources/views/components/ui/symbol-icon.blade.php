@props([
    'icon' => null,
    'text' => null,
    'variant' => 'primary',
    'size' => '40px',
    'shape' => 'rounded',
    'iconClass' => 'fs-3',
    'labelClass' => null,
])

@php
    $shapeClass = $shape === 'circle' ? 'rounded-circle' : $shape;
    $labelClasses = trim('symbol-label bg-body border border-dashed border-gray-300 text-' . $variant . ' ' . $shapeClass . ' ' . ($labelClass ?? ''));
@endphp

<div data-ui-symbol-icon="metronic" {{ $attributes->merge(['class' => 'symbol symbol-' . $size . ' flex-shrink-0']) }}>
    <span class="{{ $labelClasses }}">
        @if($icon)
            <x-ui.icon :name="$icon" :class="$iconClass" />
        @elseif($text)
            {{ $text }}
        @else
            {{ $slot }}
        @endif
    </span>
</div>
