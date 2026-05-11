@props([
    'active' => false,
    'href' => null,
    'type' => 'button',
])

@php
    $classes = trim('nav-link text-active-primary py-4 px-0 me-8 ' . ($active ? 'active' : ''));
@endphp

<li class="nav-item">
    @if($href)
        <a
            href="{{ $href }}"
            data-ui-tab="metronic"
            @if($active) aria-current="page" @endif
            {{ $attributes->merge(['class' => $classes]) }}
        >
            {{ $slot }}
        </a>
    @else
        <button
            type="{{ $type }}"
            data-ui-tab="metronic"
            @if($active) aria-pressed="true" @else aria-pressed="false" @endif
            {{ $attributes->merge(['class' => $classes . ' bg-transparent border-0']) }}
        >
            {{ $slot }}
        </button>
    @endif
</li>
