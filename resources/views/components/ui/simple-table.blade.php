@props([
    'dense' => false,
    'tableClass' => null,
])

@php
    $classes = 'table table-row-dashed align-middle fs-6 gs-0 mb-0 spm-table spm-table--simple spm-simple-table';
    $classes .= $dense ? ' gy-2 spm-table--dense spm-simple-table-dense' : ' gy-3';
    $classes = trim($classes . ' ' . $tableClass);
@endphp

<div data-ui-simple-table="metronic" data-ui-table="metronic" {{ $attributes->merge(['class' => 'table-responsive spm-table-shell spm-table-shell--simple spm-simple-table-wrap']) }}>
    <table class="{{ $classes }}">
        {{ $slot }}
    </table>
</div>
