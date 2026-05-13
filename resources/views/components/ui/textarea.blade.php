@props([
    'model' => null,
    'id' => null,
    'rows' => 4,
    'modifier' => null,
    'disabled' => false,
])

@php
    $id ??= $model;
@endphp

<textarea
    data-ui-textarea="metronic"
    rows="{{ $rows }}"
    @if($id) id="{{ $id }}" @endif
    @if($model) wire:model{{ $modifier ? '.' . $modifier : '' }}="{{ $model }}" @endif
    @disabled($disabled)
    {{ $attributes->merge(['class' => 'form-control form-control-solid']) }}
>{{ $slot }}</textarea>
