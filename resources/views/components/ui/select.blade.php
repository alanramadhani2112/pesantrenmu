@props([
    'model' => null,
    'id' => null,
    'placeholder' => null,
    'options' => [],
    'size' => 'md',
    'modifier' => null,
    'disabled' => false,
])

@php
    $id ??= $model;

    $sizes = [
        'sm' => 'form-select-sm',
        'md' => '',
        'lg' => 'form-select-lg',
    ];

    $classes = trim('form-select form-select-solid ' . ($sizes[$size] ?? ''));
@endphp

<select
    data-ui-select="metronic"
    @if($id) id="{{ $id }}" @endif
    @if($model) wire:model{{ $modifier ? '.' . $modifier : '' }}="{{ $model }}" @endif
    @disabled($disabled)
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
