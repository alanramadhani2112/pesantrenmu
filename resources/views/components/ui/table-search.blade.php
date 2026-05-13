@props([
    'placeholder' => 'Cari data...',
    'model' => 'search',
])

<div data-ui-table-search="metronic" class="position-relative">
    <x-ui.icon name="magnifier" class="fs-3 position-absolute top-50 translate-middle-y ms-4 text-gray-500" />

    <input
        type="text"
        wire:model.live.debounce.300ms="{{ $model }}"
        placeholder="{{ $placeholder }}"
        {{ $attributes->merge(['class' => 'form-control form-control-solid ps-12 w-275px']) }}
    >
</div>
