@props([
    'compact' => false,
])

@php
    $classes = 'spm-sidebar-section' . ($compact ? ' spm-sidebar-section-compact' : '');
@endphp

<div data-ui-sidebar-section="metronic" {{ $attributes->merge(['class' => $classes . ' menu-item']) }}>
    <div class="menu-content">
        <span class="menu-heading fw-semibold text-uppercase">{{ $slot }}</span>
    </div>
</div>
