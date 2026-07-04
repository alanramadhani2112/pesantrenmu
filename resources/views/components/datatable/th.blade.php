@props([
    'field',
    'sortField',
    'sortAsc',
    'align' => 'start',
])

<!-- datatable-sort sortField="{{ $field }}" -->
<x-ui.table-th
    :field="$field"
    :sort-field="$sortField"
    :sort-asc="$sortAsc"
    :align="$align"
    {{ $attributes }}
>
    {{ $slot }}
</x-ui.table-th>
