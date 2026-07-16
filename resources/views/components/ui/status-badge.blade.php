@props([
    'variant' => 'primary',
    'light' => true,
])

@php
    $allowed = ['primary', 'success', 'warning', 'danger', 'info', 'secondary'];
    $variant = in_array($variant, $allowed, true) ? $variant : 'primary';
    $classes = $light ? "badge badge-light-{$variant} fw-semibold spm-status-badge spm-badge--soft" : "badge badge-{$variant} fw-semibold spm-status-badge";
@endphp

<span data-ui-status-badge="metronic" {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</span>
