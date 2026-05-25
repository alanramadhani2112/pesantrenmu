@props([
    'value' => 0,
    'variant' => 'primary',
    'height' => '8px',
    'label' => null,
    'meta' => null,
    'dynamicValue' => null,
])

@php
    $allowed = ['primary', 'success', 'warning', 'danger', 'info', 'secondary'];
    $variant = in_array($variant, $allowed, true) ? $variant : 'primary';
    $pct = min(100, max(0, (float) $value));
@endphp

<div data-ui-progress="metronic" {{ $attributes->merge(['class' => 'spm-progress']) }}>
    @if($label || $meta)
        <div class="d-flex align-items-center justify-content-between mb-1">
            @if($label)
                <span class="spm-progress-label">{{ $label }}</span>
            @endif
            @if($meta)
                <span class="spm-progress-meta text-{{ $variant }}">{{ $meta }}</span>
            @endif
        </div>
    @endif

    <div class="progress rounded-2" style="height: {{ $height }};">
        <div
            class="progress-bar bg-{{ $variant }}"
            role="progressbar"
            @if($dynamicValue)
                x-bind:style="'width: ' + Math.min(100, Math.max(0, Number({{ $dynamicValue }}) || 0)) + '%'"
                x-bind:aria-valuenow="Math.min(100, Math.max(0, Number({{ $dynamicValue }}) || 0))"
            @else
                style="width: {{ $pct }}%"
                aria-valuenow="{{ $pct }}"
            @endif
            aria-valuemin="0"
            aria-valuemax="100"
        ></div>
    </div>
</div>
