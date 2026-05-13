@props([
    'model' => null,
    'id' => null,
    'value' => null,
    'label' => 'Pilih baris',
])

@php
    $inputAttributes = $attributes->whereStartsWith(['wire:', 'x-', '@', ':']);
    $wrapperAttributes = $attributes->whereDoesntStartWith(['wire:', 'x-', '@', ':']);
@endphp

<div data-ui-table-checkbox="metronic" {{ $wrapperAttributes->merge(['class' => 'form-check form-check-custom form-check-solid justify-content-center']) }}>
    <input
        type="checkbox"
        @if($id) id="{{ $id }}" @endif
        @if($model) wire:model.live="{{ $model }}" @endif
        @if(!is_null($value)) value="{{ $value }}" @endif
        aria-label="{{ $label }}"
        {{ $inputAttributes->merge(['class' => 'form-check-input h-22px w-22px']) }}
    >
</div>
