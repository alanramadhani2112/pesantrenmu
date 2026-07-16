@props([
    'active' => false,
    'href' => null,
    'type' => 'button',
])

@php
    $classes = trim('nav-link cursor-pointer spm-tab-link ' . ($active ? 'active' : ''));
    $tabHref = $href ?: '#';
@endphp

<li class="nav-item">
    <a
        href="{{ $tabHref }}"
        data-ui-tab="metronic"
        role="tab"
        aria-selected="{{ $active ? 'true' : 'false' }}"
        @if($active) aria-current="page" @endif
        {{ $attributes->merge(['class' => $classes]) }}
    >
        {{ $slot }}
    </a>
</li>
