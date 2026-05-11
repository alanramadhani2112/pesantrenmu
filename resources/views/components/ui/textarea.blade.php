@props([
    'model' => null,
    'id' => null,
    'rows' => 4,
])

@php
    $id ??= $model;
@endphp

<textarea
    data-ui-textarea="metronic"
    rows="{{ $rows }}"
    @if($id) id="{{ $id }}" @endif
    @if($model) wire:model="{{ $model }}" @endif
    {{ $attributes->merge(['class' => 'form-control form-control-solid']) }}
>{{ $slot }}</textarea>
