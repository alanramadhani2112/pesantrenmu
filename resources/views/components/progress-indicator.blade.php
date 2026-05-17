@props([
    'filled' => 0,
    'total' => 0,
    'percentage' => 0.0,
    'label' => '',
    'color' => 'green', // red | amber | green
])

@php
    $variantMap = [
        'red'   => 'danger',
        'amber' => 'warning',
        'green' => 'success',
    ];
    $variant = $variantMap[$color] ?? 'primary';
    $pct = min(100, max(0, (float) $percentage));
@endphp

<div {{ $attributes->merge(['class' => 'd-flex flex-column gap-1']) }}>
    <div class="d-flex align-items-center justify-content-between mb-1">
        <span class="fw-semibold fs-8 text-gray-700">{{ $label }}</span>
        <span class="fw-bold fs-8 text-{{ $variant }}">{{ $filled }}/{{ $total }} ({{ number_format($pct, 0) }}%)</span>
    </div>
    <div class="progress h-8px rounded-2">
        <div
            class="progress-bar bg-{{ $variant }}"
            role="progressbar"
            style="width: {{ $pct }}%"
            aria-valuenow="{{ $pct }}"
            aria-valuemin="0"
            aria-valuemax="100"
        ></div>
    </div>
</div>
