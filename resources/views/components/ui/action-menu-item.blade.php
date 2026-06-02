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

    $classes = trim('menu-link spm-action-menu-item-link px-4 py-2 d-flex align-items-center gap-3 ' . $variantClass);
@endphp

<div class="menu-item">
    @if($href)
        <a href="{{ $href }}" role="menuitem" {{ $attributes->merge(['class' => $classes]) }}>
            {{ $slot }}
        </a>
    @else
        <x-ui.button :type="$type" variant="link" unstyled role="menuitem" {{ $attributes->merge(['class' => $classes . ' border-0 bg-transparent w-100 text-start']) }}>
            {{ $slot }}
        </x-ui.button>
    @endif
</div>
