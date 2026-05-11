@props([
    'dense' => false,
    'tableClass' => null,
])

@php
    $classes = 'table table-row-dashed align-middle gs-0 mb-0 spm-simple-table';
    $classes .= $dense ? ' gy-2 spm-simple-table-dense' : ' gy-3';
    $classes = trim($classes . ' ' . $tableClass);
@endphp

<div data-ui-simple-table="metronic" {{ $attributes->merge(['class' => 'table-responsive spm-simple-table-wrap']) }}>
    <table class="{{ $classes }}">
        {{ $slot }}
    </table>
</div>
