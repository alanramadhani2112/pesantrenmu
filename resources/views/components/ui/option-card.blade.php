@props([
    'model',
    'value',
    'title',
    'description' => null,
    'variant' => 'primary',
    'active' => false,
    'modifier' => null,
])

@php
    $variant = in_array($variant, ['primary', 'success', 'warning', 'danger', 'info'], true)
        ? $variant
        : 'primary';
@endphp

<label
    data-ui-option-card="metronic"
    {{ $attributes->merge(['class' => 'spm-option-card form-check form-check-custom form-check-solid align-items-start ' . ($active ? 'is-active border-' . $variant . ' bg-light-' . $variant : '')]) }}
>
    <input
        type="radio"
        wire:model{{ $modifier ? '.' . $modifier : '' }}="{{ $model }}"
        value="{{ $value }}"
        class="form-check-input h-22px w-22px mt-1"
    >

    <span class="d-flex flex-column min-w-0">
        <span class="fw-bold text-gray-800">{{ $title }}</span>
        @if($description)
            <span class="fs-8 text-muted fw-semibold lh-base">{{ $description }}</span>
        @endif
    </span>
</label>
