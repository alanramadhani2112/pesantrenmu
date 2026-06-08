@props([
    'placeholder' => 'Cari data...',
    'name' => 'search',
    'value' => null,
    'form' => null,
])

@php
    $value ??= request($name, '');
@endphp

<div data-ui-table-search="metronic" class="position-relative">
    <x-ui.icon name="magnifier" class="fs-3 position-absolute top-50 translate-middle-y ms-4 text-gray-500" />

    <input
        type="text"
        name="{{ $name }}"
        value="{{ $value }}"
        placeholder="{{ $placeholder }}"
        @if($form) form="{{ $form }}" @endif
        {{ $attributes->merge(['class' => 'form-control form-control-solid ps-12 spm-table-search-input']) }}
    >
</div>
