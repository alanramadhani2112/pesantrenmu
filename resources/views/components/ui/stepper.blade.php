@props([
    'variant' => 'pills',
    'direction' => 'row',
])

@php
    $allowedVariants = ['pills', 'links'];
    $variant = in_array($variant, $allowedVariants, true) ? $variant : 'pills';

    $classes = trim('stepper stepper-' . $variant . ($direction === 'column' ? ' stepper-column d-flex flex-column' : ''));
@endphp

<div data-ui-stepper="metronic" {{ $attributes->merge(['class' => $classes]) }}>
    <div class="stepper-nav">
        {{ $slot }}
    </div>
</div>
