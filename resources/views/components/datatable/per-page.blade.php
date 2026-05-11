@props([
    'model' => 'perPage',
    'options' => [10, 25, 50, 100],
])

<x-ui.table-per-page :model="$model" :options="$options" {{ $attributes }} />
