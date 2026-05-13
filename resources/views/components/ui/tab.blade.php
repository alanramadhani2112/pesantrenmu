@props([
    'active' => false,
    'href' => null,
    'type' => 'button',
])

@php
    $classes = trim('nav-link text-active-primary py-4 px-0 me-8 ' . ($active ? 'active' : ''));
    $ariaPressed = $active ? 'true' : 'false';
@endphp

<li class="nav-item">
    @if($href)
        <a
            href="{{ $href }}"
            data-ui-tab="metronic"
            role="tab"
            aria-selected="{{ $active ? 'true' : 'false' }}"
            @if($active) aria-current="page" @endif
            {{ $attributes->merge(['class' => $classes]) }}
        >
            {{ $slot }}
        </a>
    @else
        <x-ui.button
            :type="$type"
            variant="link"
            unstyled
            data-ui-tab="metronic"
            role="tab"
            aria-selected="{{ $active ? 'true' : 'false' }}"
            aria-pressed="{{ $ariaPressed }}"
            {{ $attributes->merge(['class' => $classes . ' bg-transparent border-0']) }}
        >
            {{ $slot }}
        </x-ui.button>
    @endif
</li>
