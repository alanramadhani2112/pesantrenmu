@props([
    'label' => null,
    'for' => null,
    'error' => [],
    'hint' => null,
    'required' => false,
])

@php
    $messages = array_filter((array) $error);
@endphp

<div data-ui-form-field="metronic" {{ $attributes->merge(['class' => 'fv-row spm-form-field']) }}>
    @if($label)
        <label @if($for) for="{{ $for }}" @endif class="form-label fw-semibold text-gray-700 fs-7">
            {{ $label }}
            @if($required)
                <span class="text-danger ms-1">*</span>
            @endif
        </label>
    @endif

    {{ $slot }}

    @if(!empty($messages))
        <div class="invalid-feedback d-block fw-semibold">
            @foreach($messages as $message)
                <div>{{ $message }}</div>
            @endforeach
        </div>
    @elseif($hint)
        <div class="form-text">{{ $hint }}</div>
    @endif
</div>
