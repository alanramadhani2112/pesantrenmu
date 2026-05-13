@props([
    'href' => null,
    'type' => 'button',
    'variant' => 'primary',
    'size' => 'md',
    'unstyled' => false,
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

    $classes = $unstyled
        ? trim($sizes[$size] ?? '')
        : trim('btn ' . ($variants[$variant] ?? $variants['primary']) . ' fw-semibold ' . ($sizes[$size] ?? ''));
@endphp

@if($href)
    <a href="{{ $href }}" data-ui-button="metronic" {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" data-ui-button="metronic" {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </button>
@endif
