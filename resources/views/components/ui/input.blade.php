@props([
    'model' => null,
    'id' => null,
    'type' => 'text',
])

@php
    $id ??= $model;
@endphp

<input
    data-ui-input="metronic"
    type="{{ $type }}"
    @if($id) id="{{ $id }}" @endif
    @if($model) wire:model="{{ $model }}" @endif
    {{ $attributes->merge(['class' => 'form-control form-control-solid']) }}
>
