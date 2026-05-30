@props([
    'title' => '',
    'subtitle' => null,
    'records' => null,
    'showPerPage' => true,
    'perPagePosition' => 'footer',
    'perPageVariant' => 'compact',
    'tableClass' => null,
])

<x-ui.table
    :title="$title"
    :subtitle="$subtitle"
    :records="$records"
    :show-per-page="$showPerPage"
    :per-page-position="$perPagePosition"
    :per-page-variant="$perPageVariant"
    :table-class="$tableClass"
    {{ $attributes->merge(['data-ui-table-adapter' => 'datatable']) }}
>
    @isset($filters)
        <x-slot name="filters">
            {{ $filters }}
        </x-slot>
    @endisset

    @isset($toolbar)
        <x-slot name="toolbar">
            {{ $toolbar }}
        </x-slot>
    @endisset

    <x-slot name="thead">
        {{ $thead }}
    </x-slot>

    <x-slot name="tbody">
        {{ $tbody }}
    </x-slot>
</x-ui.table>
