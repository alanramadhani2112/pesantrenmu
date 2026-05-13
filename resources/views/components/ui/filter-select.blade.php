@props([
    'model' => null,
    'placeholder' => null,
    'options' => [],
    'size' => 'md',
])

@php
    $sizes = [
        'sm' => 'form-select-sm',
        'md' => '',
        'lg' => 'form-select-lg',
    ];

    $classes = trim('form-select form-select-solid w-auto min-w-175px ' . ($sizes[$size] ?? ''));
@endphp

<select
    data-ui-filter-select="metronic"
    @if($model) wire:model.live="{{ $model }}" @endif
    {{ $attributes->merge(['class' => $classes]) }}
>
    @if($placeholder)
        <option value="">{{ $placeholder }}</option>
    @endif

    @foreach($options as $value => $label)
        <option value="{{ $value }}">{{ $label }}</option>
    @endforeach

    {{ $slot }}
</select>
