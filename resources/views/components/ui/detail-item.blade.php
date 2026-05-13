@props([
    'label',
    'value' => null,
    'span' => 1,
])

@php
    $span = (int) $span;
    $columnClass = $span >= 2 ? 'col-md-12' : 'col-md-6';
    $hasSlot = trim((string) $slot) !== '';
@endphp

<div data-ui-detail-item="metronic" {{ $attributes->merge(['class' => "{$columnClass} spm-detail-item"]) }}>
    <div class="spm-detail-label">{{ $label }}</div>
    <div class="spm-detail-value">
        @if($hasSlot)
            {{ $slot }}
        @else
            {{ filled($value) ? $value : '-' }}
        @endif
    </div>
</div>
