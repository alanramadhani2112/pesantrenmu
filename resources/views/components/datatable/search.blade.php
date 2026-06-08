@props([
    'placeholder' => 'Cari data...',
    'name' => 'search',
    'value' => null,
    'form' => null,
])

<x-ui.table-search
    :name="$name"
    :value="$value"
    :placeholder="$placeholder"
    :form="$form"
    {{ $attributes }}
/>
