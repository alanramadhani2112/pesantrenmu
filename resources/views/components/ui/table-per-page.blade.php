@props([
    'model' => 'perPage',
    'options' => [10, 25, 50, 100],
])

<div data-ui-table-per-page="metronic" class="d-flex align-items-center gap-3">
    <span class="spm-table-per-page-label">Tampilkan</span>

    <select wire:model.live="{{ $model }}" {{ $attributes->merge(['class' => 'form-select form-select-solid form-select-sm w-100px']) }}>
        @foreach($options as $option)
            <option value="{{ $option }}">{{ $option }}</option>
        @endforeach
    </select>

    <span class="spm-table-per-page-label">data</span>
</div>
