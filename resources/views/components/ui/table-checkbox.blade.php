@props([
    'model' => null,
    'id' => null,
    'value' => null,
    'label' => 'Pilih baris',
])

@php
    $inputAttributes = $attributes->whereStartsWith(['x-', '@', ':']);
    $wrapperAttributes = $attributes->whereDoesntStartWith(['x-', '@', ':']);
@endphp

<div data-ui-table-checkbox="metronic" {{ $wrapperAttributes->merge(['class' => 'form-check form-check-custom form-check-solid justify-content-center']) }}>
    <input
        type="checkbox"
        @if($id) id="{{ $id }}" @endif
        @if($model) x-model="{{ $model }}" @endif
        @if(!is_null($value)) value="{{ $value }}" @endif
        aria-label="{{ $label }}"
        {{ $inputAttributes->merge(['class' => 'form-check-input h-22px w-22px']) }}
    >
</div>
