@props([
    'icon',
    'label',
    'href' => null,
    'type' => 'button',
    'variant' => 'light',
    'size' => 'sm',
])

@php
    $variants = [
        'primary' => 'btn-light-primary',
        'secondary' => 'btn-light',
        'light' => 'btn-light',
        'success' => 'btn-light-success',
        'warning' => 'btn-light-warning',
        'danger' => 'btn-light-danger',
        'info' => 'btn-light-info',
    ];

    $sizes = [
        'sm' => 'btn-sm',
        'md' => '',
        'lg' => 'btn-lg',
    ];

    $classes = trim('btn btn-icon ' . ($variants[$variant] ?? $variants['light']) . ' ' . ($sizes[$size] ?? 'btn-sm'));
@endphp

@if($href)
    <a
        href="{{ $href }}"
        data-ui-icon-button="metronic"
        aria-label="{{ $label }}"
        title="{{ $label }}"
        {{ $attributes->merge(['class' => $classes]) }}
    >
        <x-ui.icon :name="$icon" class="fs-3" />
    </a>
@else
    <button
        type="{{ $type }}"
        data-ui-icon-button="metronic"
        aria-label="{{ $label }}"
        title="{{ $label }}"
        {{ $attributes->merge(['class' => $classes]) }}
    >
        <x-ui.icon :name="$icon" class="fs-3" />
    </button>
@endif
