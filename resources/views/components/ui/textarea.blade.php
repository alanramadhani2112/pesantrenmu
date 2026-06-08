@props([
    'model' => null,
    'id' => null,
    'rows' => 4,
    'modifier' => null,
    'disabled' => false,
    'invalid' => false,
])

@php
    $id ??= $model;
    $hasError = $invalid || ($model && isset($errors) && $errors->has($model));
@endphp

<textarea
    data-ui-textarea="metronic"
    data-kt-autosize="true"
    rows="{{ $rows }}"
    @if($id) id="{{ $id }}" @endif
    @if($model) x-model="{{ $model }}" @endif
    @disabled($disabled)
    {{ $attributes->merge(['class' => 'form-control form-control-solid' . ($hasError ? ' is-invalid' : '')]) }}
>{{ $slot }}</textarea>
