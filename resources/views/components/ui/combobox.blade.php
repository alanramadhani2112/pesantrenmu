@props([
    'label' => null,
    'placeholder' => null,
    'show' => null,
    'error' => [],
    'disabled' => false,
])

@php
    $messages = array_filter((array) $error);
    $inputAttributes = $attributes->whereStartsWith(['x-', '@', ':']);
    $wrapperAttributes = $attributes->whereDoesntStartWith(['x-', '@', ':']);
@endphp

<div data-ui-combobox="metronic" {{ $wrapperAttributes->merge(['class' => 'fv-row spm-form-field spm-combobox position-relative']) }}>
    @if($label)
        <label class="form-label fw-semibold text-gray-700 fs-7">{{ $label }}</label>
    @endif

    <input
        type="text"
        @if($placeholder) placeholder="{{ $placeholder }}" @endif
        @disabled($disabled)
        {{ $inputAttributes->merge(['class' => 'form-control form-control-solid' . (!empty($messages) ? ' is-invalid' : '')]) }}
    >

    @if($show)
        <div
            x-show="{{ $show }}"
            x-transition
            class="spm-combobox-menu"
        >
            {{ $slot }}
        </div>
    @endif

    @if(!empty($messages))
        <div class="invalid-feedback d-block fw-semibold">
            @foreach($messages as $message)
                <div>{{ $message }}</div>
            @endforeach
        </div>
    @endif
</div>
