@props([
    'name' => 'perPage',
    'value' => null,
    'options' => [10, 25, 50, 100],
    'variant' => 'labeled',
    'form' => null,
])

@php
    $value ??= request($name, $options[0] ?? 10);
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

    <select
        name="{{ $name }}"
        aria-label="Jumlah entri per halaman"
        @if($form) form="{{ $form }}" @endif
        onchange="this.form ? this.form.submit() : this.closest('form')?.submit()"
        {{ $attributes->merge(['class' => $selectClass]) }}
    >
        @foreach($options as $option)
            <option value="{{ $option }}" @selected((int) $value === (int) $option)>{{ $option }}</option>
        @endforeach
    </select>

    @unless($isCompact)
        <span class="spm-table-per-page-label">entri</span>
    @endunless
</div>
