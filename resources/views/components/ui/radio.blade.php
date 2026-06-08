@props([
    'model' => null,
    'id' => null,
    'label' => null,
    'value' => null,
    'modifier' => null,
])

@php
    $valueKey = ! is_null($value) ? \Illuminate\Support\Str::slug((string) $value) : null;
    $valueKey = $valueKey === '' ? md5((string) $value) : $valueKey;
    $id ??= $model ? $model . ($valueKey ? '-' . $valueKey : '') : null;
    $inputAttributes = $attributes->whereStartsWith(['x-', '@', ':']);
    $labelAttributes = $attributes->whereDoesntStartWith(['x-', '@', ':']);
@endphp

<label data-ui-radio="metronic" {{ $labelAttributes->merge(['class' => 'form-check form-check-custom form-check-solid']) }}>
    <input
        type="radio"
        @if($id) id="{{ $id }}" @endif
        @if($model) x-model="{{ $model }}" @endif
        @if(!is_null($value)) value="{{ $value }}" @endif
        {{ $inputAttributes->merge(['class' => 'form-check-input h-22px w-22px']) }}
    >

    @if($label)
        <span class="form-check-label fw-semibold text-gray-700">{{ $label }}</span>
    @endif
</label>
