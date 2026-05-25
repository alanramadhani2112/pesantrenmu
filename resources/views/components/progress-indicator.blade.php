@props([
    'filled' => 0,
    'total' => 0,
    'percentage' => 0.0,
    'label' => '',
    'color' => 'green',
])

@php
    $variantMap = [
        'red' => 'danger',
        'amber' => 'warning',
        'green' => 'success',
    ];
    $variant = $variantMap[$color] ?? 'primary';
    $pct = min(100, max(0, (float) $percentage));
@endphp

<x-ui.progress
    :value="$pct"
    :variant="$variant"
    :label="$label"
    meta="{{ $filled }}/{{ $total }} ({{ number_format($pct, 0) }}%)"
    {{ $attributes->merge(['class' => 'd-flex flex-column gap-1']) }}
/>
