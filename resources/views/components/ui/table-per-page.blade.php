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
        onchange="if (this.form) { let p=this.form.querySelector('[name=page]'); if(!p){ p=document.createElement('input'); p.type='hidden'; p.name='page'; this.form.appendChild(p); } p.value='1'; this.form.submit(); } else { const url = new URL(location.href); url.searchParams.set(this.name, this.value); url.searchParams.set('page', '1'); location.href = url; }"
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
