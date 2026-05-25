@props([
    'label',
    'value',
    'variant' => 'primary',
    'icon' => null,
])

@php
    $allowed = ['primary', 'success', 'warning', 'danger', 'info'];
    $variant = in_array($variant, $allowed, true) ? $variant : 'primary';
@endphp

<x-ui.card {{ $attributes->merge(['class' => 'h-100 spm-stat-card']) }}>
    <div class="d-flex align-items-center justify-content-between">
        <div>
            <span class="text-muted fw-semibold fs-7">{{ $label }}</span>
            <div class="fs-2 fw-bold text-gray-900 mt-2">{{ $value }}</div>
        </div>

        @if($icon)
            <div class="symbol symbol-45px">
                <div class="symbol-label bg-light-{{ $variant }} text-{{ $variant }}">
                    <x-ui.icon :name="$icon" class="fs-2x" />
                </div>
            </div>
        @endif
    </div>
</x-ui.card>
