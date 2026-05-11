@props([
    'href' => null,
    'type' => 'button',
    'variant' => 'default',
])

@php
    $variantClass = [
        'primary' => 'text-primary',
        'danger' => 'text-danger',
        'warning' => 'text-warning',
        'success' => 'text-success',
    ][$variant] ?? '';

    $classes = trim('menu-link px-3 py-2 d-flex align-items-center gap-2 ' . $variantClass);
@endphp

<div class="menu-item">
    @if($href)
        <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
            {{ $slot }}
        </a>
    @else
        <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes . ' border-0 bg-transparent w-100 text-start']) }}>
            {{ $slot }}
        </button>
    @endif
</div>
