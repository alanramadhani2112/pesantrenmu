@props([
    'placeholder' => 'Search...',
    'model' => 'search',
])

<x-ui.table-search :placeholder="$placeholder" :model="$model" {{ $attributes }} />
