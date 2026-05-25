@props([
    'class' => '',
])

<div data-ui-filter-bar="metronic" {{ $attributes->merge(['class' => trim('spm-filter-bar ' . $class)]) }}>
    {{ $slot }}
</div>
