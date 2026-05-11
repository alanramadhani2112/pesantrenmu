@props([
    'compact' => false,
])

@php
    $classes = 'spm-sidebar-section' . ($compact ? ' spm-sidebar-section-compact' : '');
@endphp

<div data-ui-sidebar-section="metronic" {{ $attributes->merge(['class' => $classes]) }}>
    <span>{{ $slot }}</span>
</div>
