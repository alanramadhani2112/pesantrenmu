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

    $classes = trim('btn btn-icon fw-semibold ' . ($variants[$variant] ?? $variants['light']) . ' ' . ($sizes[$size] ?? 'btn-sm'));
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
    <x-ui.button
        :type="$type"
        variant="link"
        unstyled
        data-ui-icon-button="metronic"
        aria-label="{{ $label }}"
        title="{{ $label }}"
        {{ $attributes->merge(['class' => $classes]) }}
    >
        <x-ui.icon :name="$icon" class="fs-3" />
    </x-ui.button>
@endif
