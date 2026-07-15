@props([
    'label',
    'value',
    'variant' => 'primary',
    'description' => null,
    'actionLabel' => null,
    'actionHref' => null,
])

@php
    $allowed = ['primary', 'success', 'warning', 'danger', 'info'];
    $variant = in_array($variant, $allowed, true) ? $variant : 'primary';
@endphp

<div {{ $attributes->merge(['class' => 'spm-metric-box border border-dashed border-gray-300 rounded-3 p-5 h-100']) }}>
    <div class="d-flex align-items-center justify-content-between mb-4">
        <x-ui.badge :variant="$variant" class="px-3 py-2">{{ $label }}</x-ui.badge>
        <span class="fs-2hx fw-semibold text-gray-900 lh-1">{{ $value }}</span>
    </div>

    @if($description)
        <div class="text-muted fw-semibold fs-7 mb-4">{{ $description }}</div>
    @endif

    @if($actionLabel && $actionHref)
        <x-ui.button
            :href="$actionHref"
            variant="light-{{ $variant }}"
            size="sm"
            class="w-100"
        >
            {{ $actionLabel }}
        </x-ui.button>
    @endif
</div>
