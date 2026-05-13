@props([
    'model' => null,
    'id' => null,
    'type' => 'text',
    'modifier' => null,
    'disabled' => false,
])

@php
    $id ??= $model;
@endphp

<input
    data-ui-input="metronic"
    type="{{ $type }}"
    @if($id) id="{{ $id }}" @endif
    @if($model) wire:model{{ $modifier ? '.' . $modifier : '' }}="{{ $model }}" @endif
    @disabled($disabled)
    {{ $attributes->merge(['class' => 'form-control form-control-solid']) }}
>
