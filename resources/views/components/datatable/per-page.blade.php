@props([
    'name' => 'perPage',
    'value' => null,
    'options' => [10, 25, 50, 100],
    'form' => null,
    'variant' => 'compact',
])

<x-ui.table-per-page
    :name="$name"
    :value="$value"
    :options="$options"
    :form="$form"
    :variant="$variant"
    {{ $attributes }}
/>
