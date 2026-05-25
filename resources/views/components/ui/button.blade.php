@props([
    'href' => null,
    'type' => 'button',
    'variant' => 'primary',
    'size' => 'md',
    'unstyled' => false,
    'icon' => null,
    'iconPosition' => 'start',
    'iconClass' => null,
])

@php
    $variants = [
        'primary' => 'btn-primary',
        'secondary' => 'btn-light',
        'light' => 'btn-light',
        'success' => 'btn-success',
        'warning' => 'btn-warning',
        'danger' => 'btn-danger',
        'info' => 'btn-info',
        'link' => 'btn-link',
        'light-primary' => 'btn-light-primary',
        'light-success' => 'btn-light-success',
        'light-warning' => 'btn-light-warning',
        'light-danger' => 'btn-light-danger',
        'light-info' => 'btn-light-info',
    ];

    $sizes = [
        'sm' => 'btn-sm',
        'md' => '',
        'lg' => 'btn-lg',
    ];

    $iconClasses = trim(($iconClass ?: 'fs-4') . ($iconPosition === 'end' ? ' ms-1' : ' me-1'));
    $classes = $unstyled
        ? trim($sizes[$size] ?? '')
        : trim('btn spm-btn ' . ($variants[$variant] ?? $variants['primary']) . ' fw-semibold ' . ($sizes[$size] ?? ''));
@endphp

@if($href)
    <a href="{{ $href }}" data-ui-button="metronic" {{ $attributes->merge(['class' => $classes]) }}>
        @if($icon && $iconPosition === 'start')
            <x-ui.icon :name="$icon" :class="$iconClasses" />
        @endif
        {{ $slot }}
        @if($icon && $iconPosition === 'end')
            <x-ui.icon :name="$icon" :class="$iconClasses" />
        @endif
    </a>
@else
    <button type="{{ $type }}" data-ui-button="metronic" {{ $attributes->merge(['class' => $classes]) }}>
        @if($icon && $iconPosition === 'start')
            <x-ui.icon :name="$icon" :class="$iconClasses" />
        @endif
        {{ $slot }}
        @if($icon && $iconPosition === 'end')
            <x-ui.icon :name="$icon" :class="$iconClasses" />
        @endif
    </button>
@endif
