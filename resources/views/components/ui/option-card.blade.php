@props([
    'name',
    'value',
    'title',
    'description' => null,
    'variant' => 'primary',
    'active' => false,
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
        name="{{ $name }}"
        value="{{ $value }}"
        @checked($active)
        class="form-check-input h-22px w-22px mt-1"
    >

    <span class="d-flex flex-column min-w-0">
        <span class="fw-semibold text-gray-800">{{ $title }}</span>
        @if($description)
            <span class="fs-8 text-muted fw-semibold lh-base">{{ $description }}</span>
        @endif
    </span>
</label>
