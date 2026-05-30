@props([
    'model' => 'perPage',
    'options' => [10, 25, 50, 100],
    'variant' => 'labeled',
])

@php
    $isCompact = $variant === 'compact';
    $containerClass = $isCompact
        ? 'd-flex align-items-center spm-table-per-page--compact'
        : 'd-flex align-items-center gap-3';
    $selectClass = $isCompact
        ? 'form-select form-select-solid form-select-sm w-90px'
        : 'form-select form-select-solid form-select-sm w-100px';
@endphp

<div data-ui-table-per-page="metronic" class="{{ $containerClass }}">
    @unless($isCompact)
        <span class="spm-table-per-page-label">Tampilkan</span>
    @endunless

    <select wire:model.live="{{ $model }}" aria-label="Jumlah entri per halaman" {{ $attributes->merge(['class' => $selectClass]) }}>
        @foreach($options as $option)
            <option value="{{ $option }}">{{ $option }}</option>
        @endforeach
    </select>

    @unless($isCompact)
        <span class="spm-table-per-page-label">entri</span>
    @endunless
</div>
