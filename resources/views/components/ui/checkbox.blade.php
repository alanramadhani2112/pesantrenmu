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
    $inputAttributes = $attributes->whereStartsWith(['wire:', 'x-', '@', ':', 'aria-']);
    $labelAttributes = $attributes->whereDoesntStartWith(['wire:', 'x-', '@', ':', 'aria-']);
@endphp

<label data-ui-checkbox="metronic" {{ $labelAttributes->merge(['class' => 'form-check form-check-custom form-check-solid']) }}>
    <input
        type="checkbox"
        @if($id) id="{{ $id }}" @endif
        @if($model) wire:model{{ $modifier ? '.' . $modifier : '' }}="{{ $model }}" @endif
        @if(!is_null($value)) value="{{ $value }}" @endif
        {{ $inputAttributes->merge(['class' => 'form-check-input h-22px w-22px']) }}
    >

    @if($label)
        <span class="form-check-label fw-semibold text-gray-700">{{ $label }}</span>
    @elseif(trim((string) $slot) !== '')
        <span class="form-check-label fw-semibold text-gray-700">{{ $slot }}</span>
    @endif
</label>
